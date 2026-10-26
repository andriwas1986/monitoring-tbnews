<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
date_default_timezone_set('Asia/Jakarta');

$url = trim($_GET['url'] ?? $_POST['url'] ?? '');
$name = trim($_GET['name'] ?? $_POST['name'] ?? '');
$id = trim($_GET['id'] ?? $_POST['id'] ?? '');

if (empty($url)) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter URL wajib disertakan'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Pastikan scheme http:// atau https://
if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
    $url = 'http://' . $url;
}

// User-Agent Pool (Real Windows Desktop Browsers)
$userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
];
$selectedUa = $userAgents[crc32($url) % count($userAgents)];

// Header browser manusia asli
$headers = [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'Accept-Language: id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
    'Cache-Control: max-age=0',
    'Sec-Ch-Ua: "Not/A)Brand";v="8", "Chromium";v="126", "Google Chrome";v="126"',
    'Sec-Ch-Ua-Mobile: ?0',
    'Sec-Ch-Ua-Platform: "Windows"',
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-User: ?1',
    'Upgrade-Insecure-Requests: 1',
    'Priority: u=0, i'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_CONNECTTIMEOUT => 4,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_ENCODING => '', // Otomatis terima gzip, deflate, br agar seperti browser asli
    CURLOPT_COOKIEFILE => '', // Aktifkan cookie engine in-memory
    CURLOPT_USERAGENT => $selectedUa,
    CURLOPT_HTTPHEADER => $headers
]);

if (defined('CURL_HTTP_VERSION_2_0')) {
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
}

$startTime = microtime(true);
$rawResponse = curl_exec($ch);
$durationMs = round((microtime(true) - $startTime) * 1000);

$info = curl_getinfo($ch);
$errno = curl_errno($ch);
$errorMsg = curl_error($ch);
$headerSize = $info['header_size'] ?? 0;
$headersRaw = substr($rawResponse ?: '', 0, $headerSize);
$bodyRaw = substr($rawResponse ?: '', $headerSize);
curl_close($ch);

$httpCode = (int)($info['http_code'] ?? 0);
$effectiveUrl = $info['url'] ?? $url;
$primaryIp = $info['primary_ip'] ?? '';
$dnsTimeMs = round(($info['namelookup_time'] ?? 0) * 1000);
$connectTimeMs = round(($info['connect_time'] ?? 0) * 1000);

// Parse server header
$server = '';
if (preg_match('/^server:\s*([^\r\n]+)/mi', $headersRaw, $matches)) {
    $server = trim($matches[1]);
}

$isCloudflare = (
    stripos($headersRaw, 'cf-ray') !== false ||
    stripos($headersRaw, 'cf-cache-status') !== false ||
    stripos($server, 'cloudflare') !== false ||
    stripos($headersRaw, 'cf-mitigated') !== false
);

$isCloudflareMitigated = false;
if (
    stripos($headersRaw, 'cf-mitigated: challenge') !== false ||
    ($isCloudflare && ($httpCode === 403 || $httpCode === 503)) ||
    stripos($bodyRaw, 'challenges.cloudflare.com') !== false ||
    stripos($bodyRaw, 'Attention Required! | Cloudflare') !== false
) {
    $isCloudflareMitigated = true;
}

// Analisis Status
$status = 'offline';
$statusLabel = 'Tidak Dapat Diakses';
$statusColor = 'red';
$diagnostic = '';
$suggestion = '';

if ($httpCode >= 200 && $httpCode < 300) {
    $status = 'online';
    $statusLabel = 'Aktif/Normal';
    $statusColor = 'green';
    $diagnostic = 'Website merespons normal (HTTP ' . $httpCode . ' OK). Konten dapat diakses lancar.';
} elseif ($httpCode >= 300 && $httpCode < 400) {
    $status = 'redirect';
    $statusLabel = 'Dialihkan (Redirect ' . $httpCode . ')';
    $statusColor = 'blue';
    $diagnostic = 'Website mengalihkan permintaan ke: ' . $effectiveUrl;
} elseif ($httpCode === 403 && ($isCloudflareMitigated || $isCloudflare)) {
    $status = 'protected';
    $statusLabel = 'Aktif/Normal';
    $statusColor = 'green';
    $diagnostic = 'Website aktif dan beroperasi normal di balik WAF Cloudflare. Proteksi anti-DDoS/Bot berjalan normal untuk keamanan.';
} elseif ($httpCode === 429) {
    $status = 'rate_limited';
    $statusLabel = 'Rate Limited (429)';
    $statusColor = 'orange';
    $diagnostic = 'Server membatasi frekuensi pengecekan sementara (Anti-Flooding). Mohon tunggu sejenak.';
} elseif ($httpCode >= 400 && $httpCode < 500) {
    $status = 'client_error';
    $statusLabel = 'Error ' . $httpCode;
    $statusColor = 'orange';
    $diagnostic = 'Website mengembalikan respon HTTP ' . $httpCode . ' (Akses Dibatasi atau Tidak Ditemukan).';
} elseif ($httpCode >= 500) {
    $status = 'server_error';
    $statusLabel = 'Gangguan Server (' . $httpCode . ')';
    $statusColor = 'red';
    $diagnostic = 'Server website sedang mengalami kendala internal (HTTP ' . $httpCode . ').';
} elseif ($errno === CURLE_COULDNT_RESOLVE_HOST || $errno === 6 || ($httpCode === 0 && empty($primaryIp))) {
    $status = 'dns_error';
    $statusLabel = 'Belum Bisa Diakses (DNS)';
    $statusColor = 'red';
    $diagnostic = 'Nama domain gagal di-resolve oleh server DNS (NXDOMAIN). Host belum diarahkan ke IP server atau salah penulisan.';

    if (stripos($url, 'polres.go.id') !== false) {
        $suggested = str_ireplace('polres.go.id', 'polri.go.id', $url);
        $suggestion = 'Gunakan domain resmi polri.go.id: ' . $suggested;
    }
} elseif ($errno === CURLE_OPERATION_TIMEDOUT || $errno === 28) {
    $status = 'timeout';
    $statusLabel = 'RTO / Waktu Habis';
    $statusColor = 'red';
    $diagnostic = 'Koneksi ke server timeout setelah ' . round($durationMs / 1000, 1) . ' detik.';
} else {
    $status = 'offline';
    $statusLabel = 'Koneksi Gagal';
    $statusColor = 'red';
    $diagnostic = $errorMsg ?: 'Koneksi ke host tujuan gagal.';
}

$sslActive = (stripos($effectiveUrl, 'https://') === 0);

echo json_encode([
    'success' => true,
    'id' => $id,
    'name' => $name,
    'url' => $url,
    'http_code' => $httpCode,
    'status' => $status,
    'status_label' => $statusLabel,
    'status_color' => $statusColor,
    'response_time_ms' => $durationMs,
    'dns_time_ms' => $dnsTimeMs,
    'connect_time_ms' => $connectTimeMs,
    'primary_ip' => $primaryIp,
    'effective_url' => $effectiveUrl,
    'server' => $server ?: ($isCloudflare ? 'cloudflare' : '-'),
    'ssl_active' => $sslActive,
    'is_cloudflare' => $isCloudflare,
    'diagnostic' => $diagnostic,
    'suggestion' => $suggestion,
    'error_message' => $errorMsg,
    'checked_at' => date('Y-m-d H:i:s'),
    'checked_at_human' => date('H:i:s') . ' WIB'
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
