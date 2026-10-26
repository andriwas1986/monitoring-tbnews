<?php
date_default_timezone_set('Asia/Jakarta');
$dataFile = __DIR__ . '/../data/sites.json';
if (!file_exists($dataFile)) {
    die("Data website tidak ditemukan");
}

$raw = file_get_contents($dataFile);
$sites = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $raw), true) ?: [];

// Ambil data hasil cek terakhir via POST jika ada, atau jalankan multi-curl
$postData = file_get_contents('php://input');
$results = [];

if (!empty($_POST['results_json'])) {
    $results = json_decode($_POST['results_json'], true);
}

if (empty($results)) {
    // Jalankan pengecekan cepat
    $mh = curl_multi_init();
    $handles = [];
    foreach ($sites as $i => $item) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $item['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$i] = ['handle' => $ch, 'item' => $item];
    }
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    foreach ($handles as $h) {
        $ch = $h['handle'];
        $item = $h['item'];
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $time = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
        $ip = curl_getinfo($ch, CURLINFO_PRIMARY_IP);
        $errno = curl_errno($ch);
        $status = ($code >= 200 && $code < 400) ? 'Normal' : ($code == 403 ? 'Cloudflare WAF' : ($errno == 6 ? 'DNS Belum Aktif' : 'Gangguan'));
        $results[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'url' => $item['url'],
            'region' => $item['region'] ?? '',
            'status_label' => $status,
            'http_code' => $code,
            'response_time_ms' => $time,
            'primary_ip' => $ip,
            'checked_at' => date('Y-m-d H:i:s'),
            'diagnostic' => ($errno == 6 ? 'Domain NXDOMAIN' : 'HTTP ' . $code)
        ];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
}

$filename = "Laporan_Status_Website_Polri_Jatim_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
// Output BOM untuk kompatibilitas Excel bahasa Indonesia / UTF-8
fputs($output, "\xEF\xBB\xBF");

// Header CSV
fputcsv($output, [
    'No',
    'Nama Website / Satker',
    'Wilayah',
    'URL Website',
    'Status Akses',
    'HTTP Code',
    'Waktu Respons (ms)',
    'IP Address Server',
    'Waktu Pengecekan',
    'Keterangan / Diagnosa'
]);

$no = 1;
foreach ($results as $row) {
    fputcsv($output, [
        $no++,
        $row['name'] ?? '',
        $row['region'] ?? '',
        $row['url'] ?? '',
        $row['status_label'] ?? '',
        $row['http_code'] ?? 0,
        $row['response_time_ms'] ?? 0,
        $row['primary_ip'] ?? '',
        $row['checked_at'] ?? '',
        $row['diagnostic'] ?? ''
    ]);
}

fclose($output);
exit;
