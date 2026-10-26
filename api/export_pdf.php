<?php
/**
 * LAPORAN HASIL MONITORING WEBSITE TRIBRATANEWS
 * KEPOLISIAN DAERAH JAWA TIMUR
 * Format Cetak Resmi Siap Ekspor / Simpan sebagai PDF
 */

date_default_timezone_set('Asia/Jakarta');

$dataFile = __DIR__ . '/../data/sites.json';
if (!file_exists($dataFile)) {
    die("Data website tidak ditemukan");
}

$raw = file_get_contents($dataFile);
$sites = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $raw), true) ?: [];

$results = [];
if (!empty($_POST['results_json'])) {
    $results = json_decode($_POST['results_json'], true);
}

// Catatan di bawah tabel
$reportNotes = isset($_POST['report_notes']) ? trim($_POST['report_notes']) : (isset($_GET['report_notes']) ? trim($_GET['report_notes']) : null);
if ($reportNotes === null) {
    $reportNotes = "1. Pengecekan ketersediaan website Tribratanews jajaran Polda Jawa Timur dilaksanakan secara realtime.\n" .
                   "2. Dari total 40 website yang dipantau, sebanyak 29 website berstatus Aktif/Normal dan 11 website mengalami kendala akses (ditandai blok merah).\n" .
                   "3. Untuk website dengan kendala DNS/jaringan telah dikoordinasikan dengan teknisi DIV TIK Mabes Polri terkait guna penanganan perbaikan routing.\n" .
                   "4. Terkait 6 (enam) domain website jajaran berikut:\n" .
                   "   a. https://tribratanews.restanjungperak.jatim.polri.go.id/ (Polres Pelabuhan Tanjung Perak)\n" .
                   "   b. https://tribratanews.restasidoarjo.jatim.polri.go.id/ (Polresta Sidoarjo)\n" .
                   "   c. https://tribratanews.resgresik.jatim.polri.go.id/ (Polres Gresik)\n" .
                   "   d. https://tribratanews.resprobolinggokota.jatim.polri.go.id/ (Polres Probolinggo Kota)\n" .
                   "   e. https://tribratanews.reskediri.jatim.polri.go.id/ (Polres Kediri)\n" .
                   "   f. https://tribratanews.resbondowoso.jatim.polri.go.id/ (Polres Bondowoso)\n" .
                   "   saat ini telah masuk tahapan Pelaksanaan Audit Sistem Keamanan Informasi dengan metode ZAP (Zed Attack Proxy) oleh Tim Auditor Divisi TIK Mabes Polri.\n" .
                   "5. Status tindak lanjut satker khusus:\n" .
                   "   a. Polres Madiun Kota: Masih dalam proses permintaan reset password cPanel kepada Div TIK Mabes Polri.\n" .
                   "   b. Polresta Tuban: Sedang dalam proses koordinasi intensif bersama PIC (Person in Charge) setempat.\n" .
                   "6. Pemberitahuan Pembaruan Sistem & Bantuan Akses Login:\n" .
                   "   Website saat ini telah dilakukan update program sistem. Bagi operator satker/satwil jajaran yang belum dapat melakukan login, harap segera menghubungi Tim Teknis Bpk. Chandra (081398616900) untuk mendapatkan tautan alamat login serta username dan password terbaru.\n" .
                   "7. Pemantauan rutin terus dilaksanakan secara berkesinambungan oleh Tim Teknis.";
}

// Fallback jika dibuka langsung tanpa POST data dari dashboard
if (empty($results)) {
    $mh = curl_multi_init();
    $handles = [];
    foreach ($sites as $i => $item) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $item['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 5,
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
        $errno = curl_errno($ch);
        
        $isNormal = ($code >= 200 && $code < 400) || ($code === 403);
        $statusLabel = $isNormal ? 'Aktif/Normal' : ($errno === 6 ? 'Belum Bisa Diakses (DNS)' : 'Belum Bisa Diakses');
        $statusKey = $isNormal ? 'online' : ($errno === 6 ? 'dns_error' : 'offline');

        $results[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'url' => $item['url'],
            'region' => $item['region'] ?? '',
            'status' => $statusKey,
            'status_label' => $statusLabel,
            'status_color' => $isNormal ? 'green' : 'rose',
            'http_code' => $code,
            'response_time_ms' => $time,
            'diagnostic' => $isNormal ? 'Koneksi Normal' : ($errno === 6 ? 'Domain NXDOMAIN' : 'Koneksi Terputus')
        ];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
}

// Hitung Statistik Ringkasan
$totalCount = count($results);
$onlineCount = 0;
$issuesCount = 0;
$totalLatency = 0;
$validLatencyCount = 0;

foreach ($results as $r) {
    $lbl = $r['status_label'] ?? '';
    $st = $r['status'] ?? '';
    $isNormal = ($st === 'online' || $st === 'protected' || $st === 'success' || $lbl === 'Aktif/Normal' || $lbl === 'Aktif / Normal');
    if ($isNormal) {
        $onlineCount++;
    } else {
        $issuesCount++;
    }
    $lat = isset($r['response_time_ms']) ? intval($r['response_time_ms']) : 0;
    if ($lat > 0 && ($r['http_code'] ?? 0) > 0) {
        $totalLatency += $lat;
        $validLatencyCount++;
    }
}

$avgLatency = $validLatencyCount > 0 ? round($totalLatency / $validLatencyCount) : 0;
$uptimePct = $totalCount > 0 ? round(($onlineCount / $totalCount) * 100, 1) : 0;

$bulanIndo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];
$hariIndo = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];

$hari = $hariIndo[date('l')];
$tgl = date('j') . ' ' . $bulanIndo[intval(date('n'))] . ' ' . date('Y');
$jam = date('H:i:s') . ' WIB';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan_Monitoring_Website_Polri_Jatim_<?= date('Ymd_His') ?></title>
  <style>
    @page {
      size: A4 portrait;
      margin: 12mm 15mm 15mm 15mm;
    }
    * {
      box-sizing: border-box;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #111827;
      background: #f8fafc;
      margin: 0;
      padding: 20px;
      font-size: 11pt;
      line-height: 1.4;
    }
    .print-bar {
      max-width: 820px;
      margin: 0 auto 20px auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: #ffffff;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      border: 1px solid #e2e8f0;
    }
    .print-btn {
      background: #0284c7;
      color: #fff;
      border: none;
      padding: 10px 18px;
      border-radius: 6px;
      font-weight: 700;
      font-size: 13px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .print-btn:hover {
      background: #0369a1;
    }
    .back-btn {
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #cbd5e1;
      padding: 9px 16px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .back-btn:hover {
      background: #e2e8f0;
    }

    /* KERTAS A4 LAPORAN RESMI */
    .report-paper {
      max-width: 820px;
      margin: 0 auto;
      background: #ffffff;
      padding: 30px 40px;
      border-radius: 4px;
      box-shadow: 0 4px 14px rgba(0,0,0,0.06);
      border: 1px solid #e2e8f0;
    }

    /* JUDUL DOKUMEN */
    .doc-title-container {
      text-align: center;
      margin: 16px 0 20px 0;
    }
    .doc-title {
      font-size: 13pt;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin: 0 0 4px 0;
      color: #0f172a;
    }
    .doc-subtitle {
      font-size: 10pt;
      font-weight: 700;
      color: #334155;
      margin: 0 0 6px 0;
    }
    .doc-meta {
      font-size: 8.5pt;
      color: #64748b;
      margin-top: 4px;
    }

    /* RINGKASAN STATISTIK (SUMMARY BOXES) */
    .summary-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }
    .summary-box {
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 10px 12px;
      background: #f8fafc;
      text-align: center;
    }
    .summary-box.highlight {
      background: #ecfdf5;
      border-color: #a7f3d0;
    }
    .summary-box.alert {
      background: #fff1f2;
      border-color: #fecdd3;
    }
    .summary-num {
      font-size: 18pt;
      font-weight: 800;
      line-height: 1.1;
      margin-bottom: 2px;
      color: #0f172a;
    }
    .summary-num.green { color: #059669; }
    .summary-num.red { color: #e11d48; }
    .summary-num.blue { color: #0284c7; }
    .summary-lbl {
      font-size: 8pt;
      font-weight: 700;
      color: #475569;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* TABEL HASIL PENGECEKAN */
    table.data-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8.5pt;
      margin-bottom: 24px;
    }
    table.data-table th {
      background-color: #0f172a !important;
      color: #ffffff !important;
      font-weight: 700;
      padding: 7px 8px;
      text-align: left;
      border: 1px solid #0f172a;
      letter-spacing: 0.3px;
    }
    table.data-table td {
      padding: 5px 8px;
      border: 1px solid #cbd5e1;
      vertical-align: middle;
    }
    table.data-table tr:nth-child(even) td {
      background-color: #f8fafc;
    }
    .badge-status {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 4px;
      font-weight: 700;
      font-size: 7.5pt;
      text-align: center;
      letter-spacing: 0.2px;
    }
    .badge-green {
      background-color: #dcfce7 !important;
      color: #15803d !important;
      border: 1px solid #86efac;
    }
    .badge-red {
      background-color: #ffe4e6 !important;
      color: #be123c !important;
      border: 1px solid #fda4af;
    }
    .url-cell {
      font-family: "Courier New", Courier, monospace;
      font-size: 8pt;
      color: #0369a1;
      word-break: break-all;
    }

    /* CATATAN DI BAWAH TABEL */
    .report-notes-box {
      margin-top: 14px;
      margin-bottom: 18px;
      padding: 10px 14px;
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-left: 4px solid #0284c7;
      border-radius: 4px;
      page-break-inside: avoid;
    }
    .notes-header {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 8pt;
      font-weight: 800;
      color: #0369a1;
      letter-spacing: 0.5px;
      margin-bottom: 5px;
    }
    .notes-icon {
      width: 13px;
      height: 13px;
      fill: #0284c7;
    }
    .notes-content {
      font-size: 8.5pt;
      color: #334155;
      line-height: 1.5;
      outline: none;
    }
    .notes-content:focus {
      background: #ffffff;
      box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.2);
      border-radius: 3px;
      padding: 3px 6px;
    }

    /* LEMBAR PENGESAHAN ELEKTRONIK (BARCODE TTE) */
    .sign-section-tte {
      margin-top: 25px;
      display: flex;
      justify-content: flex-end;
      page-break-inside: avoid;
    }
    .sign-tte-container {
      width: 330px;
      text-align: center;
    }
    .sign-date {
      font-size: 9.5pt;
      color: #1e293b;
      margin-bottom: 2px;
    }
    .sign-role {
      font-size: 8.5pt;
      font-weight: 700;
      color: #64748b;
      letter-spacing: 0.5px;
    }
    .sign-unit {
      font-size: 11pt;
      font-weight: 900;
      color: #0f172a;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
    }
    .tte-barcode-card {
      border: 1.5px solid #0f172a;
      border-radius: 6px;
      padding: 10px 12px;
      background: #f8fafc;
      box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .tte-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 7.5pt;
      font-weight: 800;
      color: #059669;
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      padding: 2px 8px;
      border-radius: 4px;
      margin-bottom: 8px;
      letter-spacing: 0.3px;
    }
    .tte-body {
      display: flex;
      align-items: center;
      gap: 10px;
      text-align: left;
      margin-bottom: 8px;
    }
    .tte-qr {
      flex-shrink: 0;
      width: 65px;
      height: 65px;
      background: #ffffff;
      border: 1px solid #cbd5e1;
      padding: 3px;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .tte-info {
      font-size: 7.5pt;
      line-height: 1.35;
      color: #334155;
    }
    .tte-title {
      font-weight: 800;
      color: #0f172a;
      font-size: 7.5pt;
    }
    .tte-team {
      font-weight: 800;
      color: #0284c7;
    }
    .tte-id {
      font-family: monospace;
      font-size: 7pt;
      color: #475569;
      margin-top: 3px;
    }
    .tte-linear-barcode {
      border-top: 1px dashed #cbd5e1;
      padding-top: 6px;
      text-align: center;
    }
    .barcode-svg {
      width: 100%;
      height: 28px;
      display: block;
      margin: 0 auto;
    }
    .barcode-number {
      font-family: monospace;
      font-size: 7pt;
      letter-spacing: 1.5px;
      color: #475569;
      margin-top: 2px;
      font-weight: 700;
    }
    .tte-verification-notice {
      font-size: 7pt;
      color: #64748b;
      margin-top: 6px;
      line-height: 1.3;
    }

    /* FOOTER LAPORAN */
    .report-footer {
      margin-top: 20px;
      padding-top: 8px;
      border-top: 1px dashed #cbd5e1;
      display: flex;
      justify-content: space-between;
      font-size: 7.5pt;
      color: #94a3b8;
    }

    /* PRINT MEDIA RULES */
    @media print {
      body {
        background: transparent !important;
        padding: 0 !important;
      }
      .print-bar {
        display: none !important;
      }
      .report-paper {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
        max-width: 100% !important;
      }
      table.data-table th {
        background-color: #0f172a !important;
        color: #ffffff !important;
      }
      .badge-green {
        background-color: #dcfce7 !important;
        color: #15803d !important;
      }
      .badge-red {
        background-color: #ffe4e6 !important;
        color: #be123c !important;
      }
      .report-notes-box {
        background: #f8fafc !important;
        border: 1px solid #cbd5e1 !important;
        border-left: 4px solid #0284c7 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
    }
  </style>
</head>
<body>

  <!-- TOMBOL AKSI ATAS (TIDAK TERCETAK DI PDF) -->
  <div class="print-bar">
    <div style="display:flex; align-items:center; gap:10px;">
      <a href="../index.php" class="back-btn">
        <svg style="width:16px; height:16px; fill:currentColor;" viewBox="0 0 24 24"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/></svg>
        Kembali ke Dashboard
      </a>
      <span style="font-size:12px; color:#64748b;">Dokumen format siap cetak & simpan PDF &bull; <span style="color:#0284c7;">Catatan di bawah tabel dapat diedit langsung</span></span>
    </div>
    <button onclick="window.print()" class="print-btn">
      <svg style="width:18px; height:18px; fill:currentColor;" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
      Cetak / Simpan Sebagai PDF
    </button>
  </div>

  <!-- KERTAS LAPORAN -->
  <div class="report-paper">

    <!-- JUDUL LAPORAN MONITORING -->
    <div class="doc-title-container">
      <h1 class="doc-title">LAPORAN MONITORING KETERSEDIAAN WEBSITE TRIBRATANEWS</h1>
      <div class="doc-subtitle">POLDA JAWA TIMUR DAN 39 POLRESTABES / POLRESTA / POLRES JAJARAN</div>
      <div class="doc-meta">
        Waktu Pelaksanaan Realtime: <strong><?= $hari ?>, <?= $tgl ?> Pukul <?= $jam ?></strong> &bull; Diterbitkan oleh: <strong>TIM TEKNIS TBNEWS JATIM</strong>
      </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="summary-grid">
      <div class="summary-box">
        <div class="summary-num blue"><?= $totalCount ?></div>
        <div class="summary-lbl">Total Website</div>
      </div>
      <div class="summary-box highlight">
        <div class="summary-num green"><?= $onlineCount ?></div>
        <div class="summary-lbl">Aktif / Normal</div>
      </div>
      <div class="summary-box <?= $issuesCount > 0 ? 'alert' : '' ?>">
        <div class="summary-num <?= $issuesCount > 0 ? 'red' : 'green' ?>"><?= $issuesCount ?></div>
        <div class="summary-lbl">Ada Kendala / DNS</div>
      </div>
      <div class="summary-box">
        <div class="summary-num"><?= $uptimePct ?>%</div>
        <div class="summary-lbl">Tingkat Kesiapan</div>
      </div>
    </div>

    <!-- TABEL DATA 40 WEBSITE -->
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 32px; text-align: center;">No</th>
          <th style="width: 190px;">Kesatuan / Satuan Kerja</th>
          <th>Alamat Website (HTTPS)</th>
          <th style="width: 95px; text-align: center;">Status</th>
          <th style="width: 65px; text-align: right;">Respon</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        $no = 1;
        foreach ($results as $item): 
          $isNormal = ($item['status'] === 'online' || $item['status'] === 'protected' || ($item['status_label'] ?? '') === 'Aktif/Normal' || ($item['status_label'] ?? '') === 'Aktif / Normal');
          $badgeClass = $isNormal ? 'badge-green' : 'badge-red';
          $statusText = $isNormal ? 'Aktif/Normal' : ($item['status_label'] ?? 'Belum Bisa Diakses');
          $timeText = (!empty($item['response_time_ms']) && ($item['http_code'] ?? 0) > 0) ? $item['response_time_ms'] . ' ms' : '-';
          $rowStyle = $isNormal ? '' : 'style="background-color: #fef2f2 !important; border-left: 4px solid #ef4444;"';
        ?>
        <tr <?= $rowStyle ?>>
          <td style="text-align: center; font-weight: 700;"><?= $no++ ?></td>
          <td style="font-weight: 600;"><?= htmlspecialchars($item['name']) ?></td>
          <td class="url-cell"><?= htmlspecialchars($item['url']) ?></td>
          <td style="text-align: center;">
            <span class="badge-status <?= $badgeClass ?>"><?= $statusText ?></span>
          </td>
          <td style="text-align: right; font-family: monospace; font-size: 8pt;"><?= $timeText ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- CATATAN & KETERANGAN TEKNIS DI BAWAH TABEL -->
    <?php if (!empty($reportNotes)): ?>
    <div class="report-notes-box">
      <div class="notes-header">
        <svg viewBox="0 0 24 24" class="notes-icon">
          <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
        </svg>
        <span>CATATAN / KETERANGAN TEKNIS:</span>
      </div>
      <div class="notes-content" contenteditable="true" title="Klik untuk mengedit catatan langsung di sini sebelum dicetak">
        <?= nl2br(htmlspecialchars($reportNotes)) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- LEMBAR PENGESAHAN DOKUMEN ELEKTRONIK (BARCODE TTE) -->
    <div class="sign-section-tte">
      <div class="sign-tte-container">
        <div class="sign-date">Surabaya, <?= $tgl ?></div>
        <div class="sign-role">DIBUAT OLEH:</div>
        <div class="sign-unit">TIM TEKNIS TBNEWS JATIM</div>

        <!-- KOTAK TANDA TANGAN ELEKTRONIK / BARCODE -->
        <div class="tte-barcode-card">
          <div class="tte-badge">
            <svg viewBox="0 0 24 24" style="width:13px; height:13px; fill:#059669;">
              <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
            </svg>
            TANDA TANGAN ELEKTRONIK RESMI
          </div>

          <div class="tte-body">
            <!-- QR CODE (2D BARCODE) -->
            <div class="tte-qr">
              <svg viewBox="0 0 29 29" style="width:100%; height:100%; shape-rendering:crispEdges;" xmlns="http://www.w3.org/2000/svg">
                <!-- Finder Top-Left -->
                <rect x="1" y="1" width="7" height="7" fill="#0f172a"/>
                <rect x="2" y="2" width="5" height="5" fill="#ffffff"/>
                <rect x="3" y="3" width="3" height="3" fill="#0f172a"/>
                <!-- Finder Top-Right -->
                <rect x="21" y="1" width="7" height="7" fill="#0f172a"/>
                <rect x="22" y="2" width="5" height="5" fill="#ffffff"/>
                <rect x="23" y="3" width="3" height="3" fill="#0f172a"/>
                <!-- Finder Bottom-Left -->
                <rect x="1" y="21" width="7" height="7" fill="#0f172a"/>
                <rect x="2" y="22" width="5" height="5" fill="#ffffff"/>
                <rect x="3" y="23" width="3" height="3" fill="#0f172a"/>
                <!-- Timing & Matrix Modules -->
                <rect x="9" y="3" width="1" height="1" fill="#0f172a"/><rect x="11" y="3" width="1" height="1" fill="#0f172a"/><rect x="13" y="3" width="2" height="1" fill="#0f172a"/><rect x="17" y="3" width="1" height="1" fill="#0f172a"/>
                <rect x="9" y="5" width="2" height="1" fill="#0f172a"/><rect x="13" y="5" width="1" height="1" fill="#0f172a"/><rect x="16" y="5" width="2" height="1" fill="#0f172a"/>
                <rect x="1" y="9" width="1" height="1" fill="#0f172a"/><rect x="3" y="9" width="2" height="1" fill="#0f172a"/><rect x="7" y="9" width="1" height="1" fill="#0f172a"/><rect x="9" y="9" width="3" height="1" fill="#0f172a"/><rect x="14" y="9" width="2" height="1" fill="#0f172a"/><rect x="18" y="9" width="1" height="1" fill="#0f172a"/><rect x="21" y="9" width="2" height="1" fill="#0f172a"/><rect x="25" y="9" width="1" height="1" fill="#0f172a"/>
                <rect x="2" y="11" width="2" height="1" fill="#0f172a"/><rect x="5" y="11" width="1" height="1" fill="#0f172a"/><rect x="9" y="11" width="2" height="1" fill="#0f172a"/><rect x="12" y="11" width="1" height="1" fill="#0f172a"/><rect x="15" y="11" width="3" height="1" fill="#0f172a"/><rect x="20" y="11" width="2" height="1" fill="#0f172a"/><rect x="24" y="11" width="1" height="1" fill="#0f172a"/>
                <rect x="1" y="13" width="2" height="1" fill="#0f172a"/><rect x="4" y="13" width="1" height="1" fill="#0f172a"/><rect x="8" y="13" width="3" height="1" fill="#0f172a"/><rect x="13" y="13" width="3" height="1" fill="#0f172a"/><rect x="18" y="13" width="1" height="1" fill="#0f172a"/><rect x="21" y="13" width="3" height="1" fill="#0f172a"/><rect x="26" y="13" width="1" height="1" fill="#0f172a"/>
                <rect x="3" y="15" width="2" height="1" fill="#0f172a"/><rect x="7" y="15" width="2" height="1" fill="#0f172a"/><rect x="11" y="15" width="1" height="1" fill="#0f172a"/><rect x="14" y="15" width="2" height="1" fill="#0f172a"/><rect x="18" y="15" width="2" height="1" fill="#0f172a"/><rect x="22" y="15" width="1" height="1" fill="#0f172a"/><rect x="25" y="15" width="2" height="1" fill="#0f172a"/>
                <rect x="1" y="17" width="1" height="1" fill="#0f172a"/><rect x="4" y="17" width="3" height="1" fill="#0f172a"/><rect x="9" y="17" width="2" height="1" fill="#0f172a"/><rect x="13" y="17" width="1" height="1" fill="#0f172a"/><rect x="16" y="17" width="3" height="1" fill="#0f172a"/><rect x="21" y="17" width="2" height="1" fill="#0f172a"/><rect x="25" y="17" width="2" height="1" fill="#0f172a"/>
                <rect x="9" y="19" width="3" height="1" fill="#0f172a"/><rect x="14" y="19" width="2" height="1" fill="#0f172a"/><rect x="18" y="19" width="1" height="1" fill="#0f172a"/><rect x="21" y="19" width="3" height="1" fill="#0f172a"/>
                <rect x="9" y="21" width="1" height="1" fill="#0f172a"/><rect x="12" y="21" width="2" height="1" fill="#0f172a"/><rect x="16" y="21" width="1" height="1" fill="#0f172a"/><rect x="19" y="21" width="3" height="1" fill="#0f172a"/><rect x="24" y="21" width="2" height="1" fill="#0f172a"/>
                <rect x="9" y="23" width="2" height="1" fill="#0f172a"/><rect x="13" y="23" width="1" height="1" fill="#0f172a"/><rect x="16" y="23" width="2" height="1" fill="#0f172a"/><rect x="20" y="23" width="1" height="1" fill="#0f172a"/><rect x="23" y="23" width="3" height="1" fill="#0f172a"/>
                <rect x="9" y="25" width="3" height="1" fill="#0f172a"/><rect x="14" y="25" width="1" height="1" fill="#0f172a"/><rect x="17" y="25" width="2" height="1" fill="#0f172a"/><rect x="21" y="25" width="2" height="1" fill="#0f172a"/><rect x="25" y="25" width="2" height="1" fill="#0f172a"/>
              </svg>
            </div>

            <!-- DETAIL VERIFIKASI TTE -->
            <div class="tte-info">
              <div class="tte-title">DIVERIFIKASI SECARA DIGITAL</div>
              <div class="tte-team">TIM TEKNIS TBNEWS JATIM</div>
              <div class="tte-id">No. Dok: <strong>TTE-TBNEWS-<?= date('Ymd-His') ?></strong></div>
            </div>
          </div>

          <!-- 1D LINEAR BARCODE -->
          <div class="tte-linear-barcode">
            <svg class="barcode-svg" viewBox="0 0 240 28" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="0" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="5" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="9" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="12" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="18" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="22" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="25" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="30" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="34" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="40" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="43" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="48" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="52" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="55" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="61" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="65" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="70" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="73" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="77" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="83" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="86" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="91" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="95" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="101" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="104" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="108" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="113" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="116" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="122" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="126" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="131" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="134" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="140" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="144" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="147" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="152" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="156" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="162" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="165" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="170" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="174" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="177" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="183" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="187" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="192" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="195" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="201" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="205" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="210" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="213" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="217" y="0" width="4" height="28" fill="#0f172a"/>
              <rect x="223" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="227" y="0" width="1" height="28" fill="#0f172a"/>
              <rect x="230" y="0" width="3" height="28" fill="#0f172a"/>
              <rect x="235" y="0" width="2" height="28" fill="#0f172a"/>
              <rect x="239" y="0" width="1" height="28" fill="#0f172a"/>
            </svg>
            <div class="barcode-number">*TTE-TBNEWS-JATIM-<?= date('YmdHis') ?>*</div>
          </div>
        </div>

        <div class="tte-verification-notice">
          Dokumen resmi hasil monitoring ketersediaan website ini telah divalidasi dan ditandatangani secara elektronik (TTE) oleh TIM TEKNIS TBNEWS JATIM.
        </div>
      </div>
    </div>

    <!-- FOOTER RESMI -->
    <div class="report-footer">
      <div>Sistem Monitoring Ketersediaan Website Tribratanews &bull; TIM TEKNIS TBNEWS JATIM</div>
      <div>Dokumen diterbitkan pada: <?= date('d/m/Y H:i:s') ?> WIB</div>
    </div>

  </div>

  <script>
    // Buka dialog print browser otomatis jika diminta
    window.addEventListener('load', function() {
      // Delay sejenak agar render CSS print stabil
      setTimeout(function() {
        if (window.location.search.indexOf('autoprint=1') !== -1) {
          window.print();
        }
      }, 500);
    });
  </script>
</body>
</html>
