<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Asia/Jakarta');

set_time_limit(60);

$dataFile = __DIR__ . '/../data/sites.json';
if (!file_exists($dataFile)) {
    echo json_encode(['success' => false, 'message' => 'File data/sites.json tidak ditemukan']);
    exit;
}

$raw = file_get_contents($dataFile);
if ($raw !== false) {
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
}
$sites = json_decode($raw, true);

if (!is_array($sites) || empty($sites)) {
    echo json_encode(['success' => false, 'message' => 'Daftar website kosong']);
    exit;
}

$userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36 Edg/125.0.0.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:127.0) Gecko/20100101 Firefox/127.0',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
];

$mh = curl_multi_init();
$handles = [];

foreach ($sites as $i => $item) {
    $url = $item['url'];
    if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
        $url = 'http://' . $url;
    }

    $ua = $userAgents[$i % count($userAgents)];

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
        CURLOPT_ENCODING => '', // Menerima gzip/deflate/br
        CURLOPT_COOKIEFILE => '', // Simpan cookie sesi
        CURLOPT_USERAGENT => $ua,
        CURLOPT_HTTPHEADER => [
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
        ]
    ]);

    if (defined('CURL_HTTP_VERSION_2_0')) {
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
    }

    curl_multi_add_handle($mh, $ch);
    $handles[$i] = [
        'handle' => $ch,
        'item' => $item
    ];
}

$running = null;
$multiErrors = [];

do {
    $status = curl_multi_exec($mh, $running);
    if ($running) {
        curl_multi_select($mh, 0.1);
    }
    while ($mInfo = curl_multi_info_read($mh)) {
        $chHandle = $mInfo['handle'];
        $multiErrors[(int)$chHandle] = [
            'result' => $mInfo['result'],
            'msg' => curl_error($chHandle)
        ];
    }
} while ($running > 0 && $status === CURLM_OK);

$results = [];
$stats = [
    'total' => count($sites),
    'online' => 0,
    'protected' => 0,
    'redirect' => 0,
    'client_error' => 0,
    'server_error' => 0,
    'dns_error' => 0,
    'timeout' => 0,
    'offline' => 0,
    'avg_response_time_ms' => 0
];

$totalLatency = 0;
$latencyCount = 0;

foreach ($handles as $i => $data) {
    $ch = $data['handle'];
    $item = $data['item'];
    $rawResponse = curl_multi_getcontent($ch);
    $info = curl_getinfo($ch);
    $chId = (int)$ch;
    $errno = $multiErrors[$chId]['result'] ?? curl_errno($ch);
    $errorMsg = $multiErrors[$chId]['msg'] ?? curl_error($ch);
    $httpCode = (int)($info['http_code'] ?? 0);
    $effectiveUrl = $info['url'] ?? $item['url'];
    $primaryIp = $info['primary_ip'] ?? '';
    $durationMs = round(($info['total_time'] ?? 0) * 1000);
    $dnsTimeMs = round(($info['namelookup_time'] ?? 0) * 1000);
    $connectTimeMs = round(($info['connect_time'] ?? 0) * 1000);

    $headerSize = $info['header_size'] ?? 0;
    $headersRaw = substr($rawResponse ?: '', 0, $headerSize);
    $bodyRaw = substr($rawResponse ?: '', $headerSize);

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

    $status = 'offline';
    $statusLabel = 'Tidak Dapat Diakses';
    $statusColor = 'red';
    $diagnostic = '';
    $suggestion = '';

    if ($httpCode >= 200 && $httpCode < 300) {
        $status = 'online';
        $statusLabel = 'Aktif/Normal';
        $statusColor = 'green';
        $diagnostic = 'Website merespons normal (HTTP ' . $httpCode . ' OK).';
        $stats['online']++;
    } elseif ($httpCode >= 300 && $httpCode < 400) {
        $status = 'redirect';
        $statusLabel = 'Dialihkan (Redirect ' . $httpCode . ')';
        $statusColor = 'blue';
        $diagnostic = 'Website mengalihkan permintaan ke: ' . $effectiveUrl;
        $stats['redirect']++;
    } elseif ($httpCode === 403 && ($isCloudflareMitigated || $isCloudflare)) {
        $status = 'protected';
        $statusLabel = 'Aktif/Normal';
        $statusColor = 'green';
        $diagnostic = 'Website aktif dan beroperasi normal di balik WAF Cloudflare.';
        $stats['protected']++;
    } elseif ($httpCode === 429) {
        $status = 'rate_limited';
        $statusLabel = 'Rate Limited (429)';
        $statusColor = 'orange';
        $diagnostic = 'Terkena pembatasan frekuensi sementara (Anti-Flooding).';
        $stats['client_error']++;
    } elseif ($httpCode >= 400 && $httpCode < 500) {
        $status = 'client_error';
        $statusLabel = 'Error ' . $httpCode;
        $statusColor = 'orange';
        $diagnostic = 'Website mengembalikan respon HTTP ' . $httpCode . '.';
        $stats['client_error']++;
    } elseif ($httpCode >= 500) {
        $status = 'server_error';
        $statusLabel = 'Gangguan Server (' . $httpCode . ')';
        $statusColor = 'red';
        $diagnostic = 'Server website sedang mengalami kendala internal (HTTP ' . $httpCode . ').';
        $stats['server_error']++;
    } elseif ($errno === CURLE_COULDNT_RESOLVE_HOST || $errno === 6 || stripos($errorMsg, 'resolve') !== false || ($httpCode === 0 && empty($primaryIp))) {
        $status = 'dns_error';
        $statusLabel = 'Belum Bisa Diakses (DNS)';
        $statusColor = 'red';
        $diagnostic = 'Nama domain gagal di-resolve oleh DNS (NXDOMAIN). Host belum terdaftar atau salah penulisan.';
        $stats['dns_error']++;

        if (stripos($item['url'], 'polres.go.id') !== false) {
            $suggested = str_ireplace('polres.go.id', 'polri.go.id', $item['url']);
            $suggestion = 'Gunakan akhiran polri.go.id: ' . $suggested;
        }
    } elseif ($errno === CURLE_OPERATION_TIMEDOUT || $errno === 28) {
        $status = 'timeout';
        $statusLabel = 'RTO / Waktu Habis';
        $statusColor = 'red';
        $diagnostic = 'Koneksi ke server timeout setelah ' . round($durationMs / 1000, 1) . ' detik.';
        $stats['timeout']++;
    } else {
        $status = 'offline';
        $statusLabel = 'Koneksi Gagal';
        $statusColor = 'red';
        $diagnostic = $errorMsg ?: 'Koneksi ke host tujuan gagal.';
        $stats['offline']++;
    }

    if ($durationMs > 0 && $httpCode > 0) {
        $totalLatency += $durationMs;
        $latencyCount++;
    }

    $sslActive = (stripos($effectiveUrl, 'https://') === 0);

    $results[] = [
        'id' => $item['id'],
        'name' => $item['name'],
        'url' => $item['url'],
        'region' => $item['region'] ?? '',
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
    ];

    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}

curl_multi_close($mh);

$stats['avg_response_time_ms'] = $latencyCount > 0 ? round($totalLatency / $latencyCount) : 0;

echo json_encode([
    'success' => true,
    'checked_at' => date('Y-m-d H:i:s'),
    'stats' => $stats,
    'data' => $results
], JSON_UNESCAPED_UNICODE);
