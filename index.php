<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistem Monitoring Website Tribratanews - Polda Jawa Timur</title>
  
  <!-- Favicon -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2300f2fe'><path d='M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z'/></svg>">

  <!-- Master Executive Stylesheet (100% Self-Contained) -->
  <link rel="stylesheet" href="assets/css/style.css?v=25.0">
</head>
<body class="theme-light">

  <!-- TOP HEADER / COMMAND CENTER NAVBAR -->
  <header class="app-header">
    <div class="app-container">
      <div class="header-content">
        
        <!-- Brand & Police Badge -->
        <div class="brand-section">
          <div class="police-shield-icon">
            <svg viewBox="0 0 24 24">
              <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/>
            </svg>
          </div>
          <div>
            <div style="display:flex; align-items:center; gap:8px;">
              <span class="brand-tag">
                <span style="width:6px; height:6px; border-radius:50%; background:#00f2fe; display:inline-block;"></span>
                POLDA JAWA TIMUR
              </span>
              <span style="font-size:11px; font-weight:700; color:#fbbf24; display:none; align-items:center; gap:4px;" class="hide-mobile">
                🛡️ Anti-WAF Stealth Active
              </span>
            </div>
            <h1 class="brand-title">Monitoring Website Tribratanews</h1>
          </div>
        </div>

        <!-- Right Side: Clock & Control Tools -->
        <div class="header-actions">
          
          <!-- Live Date & Clock -->
          <div class="clock-box hide-mobile">
            <span id="liveClock" class="clock-time">--:--:-- WIB</span>
            <span id="liveDate" class="clock-date">Memuat tanggal...</span>
          </div>

          <!-- Auto Refresh Selector -->
          <div class="select-wrapper">
            <svg style="width:14px; height:14px; fill:#00f2fe;" viewBox="0 0 24 24">
              <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm4.2 14.2L11 13V7h1.5v5.2l4.5 2.7-.8 1.3z"/>
            </svg>
            <span class="hide-mobile">Auto:</span>
            <select id="autoRefreshSelect">
              <option value="0">Mati</option>
              <option value="30">30 Detik</option>
              <option value="60">1 Menit</option>
              <option value="300">5 Menit</option>
            </select>
            <span id="autoRefreshBadge" class="hidden font-mono" style="background:rgba(6,182,212,0.2); color:#38bdf8; padding:1px 6px; border-radius:4px; font-size:10px; font-weight:800;">
              <span id="countdownVal">0s</span>
            </span>
          </div>

          <!-- Theme Toggle (Terang / Gelap) -->
          <button id="btnToggleTheme" onclick="App.toggleTheme()" class="btn-theme-toggle" title="Ubah Tema Tampilan (Terang / Gelap)">
            <span id="themeIcon">☀️</span>
            <span id="themeLabel" class="hide-mobile">Mode Terang</span>
          </button>

          <!-- Cek Kilat (Parallel Multi-cURL Server) -->
          <button id="btnCheckAllFast" onclick="App.checkAllFast()" class="btn btn-primary" title="Pengecekan simultan seluruh 40 website dalam ~1.8 detik">
            <svg style="width:14px; height:14px; fill:#030a16;" viewBox="0 0 24 24">
              <path d="M11 21h-1l1-7H7.5c-.58 0-.57-.32-.38-.66.19-.34.05-.08.07-.12C8.48 10.94 10.42 7.54 13 3h1l-1 7h3.5c.49 0 .56.33.47.51l-.07.15C14.94 14.15 12.98 17.58 11 21z"/>
            </svg>
            <span>CEK WEBSITE</span>
          </button>

          <!-- Action Buttons Bar -->
          <div style="display:flex; align-items:center; gap:6px;">
            <!-- Cek Progresif (Anti-Bot Throttled Jitter) -->
            <button onclick="App.checkAllProgressive()" class="btn-icon" title="Cek Progresif (Anti-Bot Jitter)">
              <svg viewBox="0 0 24 24">
                <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
              </svg>
            </button>

            <!-- Toggle Terminal Drawer -->
            <button id="btnToggleTerminal" onclick="App.toggleTerminal()" class="btn-icon" title="Buka Terminal Log">
              <svg viewBox="0 0 24 24">
                <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8h16v10zm-2-1h-6v-2h6v2zM7.5 17l-1.41-1.41L8.67 13l-2.58-2.59L7.5 9l4 4-4 4z"/>
              </svg>
            </button>

            <!-- Sound FX Toggle -->
            <button id="btnToggleSound" onclick="App.toggleSound()" class="btn-icon" title="Toggle Suara Cyber">
              <svg viewBox="0 0 24 24">
                <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
              </svg>
            </button>

            <!-- Fullscreen Toggle -->
            <button onclick="App.toggleFullscreen()" class="btn-icon hide-mobile" title="Mode Fullscreen Kiosk">
              <svg viewBox="0 0 24 24">
                <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
              </svg>
            </button>

            <!-- Export CSV -->
            <button onclick="App.exportCsv()" class="btn-icon" title="Download Laporan Excel / CSV" style="color:#34d399;">
              <svg viewBox="0 0 24 24">
                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
              </svg>
            </button>

            <!-- Export PDF -->
            <button onclick="App.exportPdf()" class="btn-icon" title="Cetak / Download Laporan PDF Resmi" style="color:#ef4444;">
              <svg viewBox="0 0 24 24">
                <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
              </svg>
            </button>

            <!-- Tambah Website -->
            <button onclick="App.openAddModal()" class="btn-icon" title="Tambah Website Baru">
              <svg viewBox="0 0 24 24">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
              </svg>
            </button>

            <!-- Reset Default -->
            <button onclick="App.resetToDefault()" class="btn-icon" title="Reset ke 40 Website Awal (Polda Jatim #1)" style="color:#fda4af;">
              <svg viewBox="0 0 24 24">
                <path d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C23.08 11.03 18.27 8 12.5 8z"/>
              </svg>
            </button>
          </div>

        </div>

      </div>
    </div>
  </header>

  <!-- LIVE PROGRESS BAR CONTAINER -->
  <div id="progressContainer" class="progress-container hidden">
    <div class="app-container">
      <div class="progress-header">
        <span id="progressText">Memeriksa website...</span>
        <span id="progressPercent" class="font-mono">0%</span>
      </div>
      <div class="progress-track">
        <div id="progressBarFill" class="progress-fill" style="width: 0%;"></div>
      </div>
    </div>
  </div>

  <!-- REAL-TIME CYBER TERMINAL DRAWER -->
  <div id="terminalDrawer" class="hidden terminal-drawer">
    <div class="app-container">
      <div class="terminal-drawer-header">
        <span class="terminal-drawer-title">
          <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8h16v10zm-2-1h-6v-2h6v2zM7.5 17l-1.41-1.41L8.67 13l-2.58-2.59L7.5 9l4 4-4 4z"/></svg>
          TERMINAL MONITORING AKTIVITAS JARINGAN (STEALTH ENGINE ACTIVE)
        </span>
        <button onclick="App.toggleTerminal()" class="btn btn-secondary" style="padding:2px 8px; font-size:11px;">Tutup</button>
      </div>
      <div id="terminalLogContent" class="terminal-drawer-body">
        <div class="terminal-init-log">[SISTEM] Monitoring siap. Klik "CEK WEBSITE" untuk pemindaian.</div>
      </div>
    </div>
  </div>

  <!-- MAIN DASHBOARD CONTENT -->
  <main class="app-container" style="flex-grow:1; padding-top:24px; padding-bottom:40px;">

    <!-- STATS OVERVIEW CARDS (WARNA KEREN & ELEGAN) -->
    <div class="stats-grid">
      
      <!-- Total Website -->
      <div class="stat-card stat-card-total">
        <div class="stat-card-header">
          <span class="stat-title">Total Website</span>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M20 18c1.1 0 1.99-.9 1.99-2L22 6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2H0v2h24v-2h-4zM4 6h16v10H4V6z"/></svg>
          </div>
        </div>
        <div class="stat-value" id="statTotal">40</div>
        <div class="stat-desc">
          <span>Polda Jatim & 39 Polres</span>
        </div>
      </div>

      <!-- Normal / Online -->
      <div class="stat-card stat-card-online">
        <div class="stat-card-header">
          <span class="stat-title">Aktif / Normal</span>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
          </div>
        </div>
        <div class="stat-value" id="statOnline">0</div>
        <div class="stat-desc">
          <span>Siaga & Dapat Diakses</span>
        </div>
      </div>

      <!-- Gangguan / DNS -->
      <div class="stat-card stat-card-issues">
        <div class="stat-card-header">
          <span class="stat-title">Gangguan / DNS</span>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>
          </div>
        </div>
        <div class="stat-value" id="statIssues">0</div>
        <div class="stat-desc">
          <span>Perlu Pengecekan DNS</span>
        </div>
      </div>

      <!-- Latensi & Kesiapan -->
      <div class="stat-card stat-card-latency">
        <div class="stat-card-header">
          <span class="stat-title">Rata-rata Ping</span>
          <div class="stat-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10 10-4.49 10-10S17.51 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>
          </div>
        </div>
        <div class="stat-value" id="statAvgLatency">0 ms</div>
        <div class="stat-desc">
          <span id="statAccessiblePct">0% Siaga & Terproteksi</span>
        </div>
      </div>

    </div>

    <!-- CONTROL TOOLBAR -->
    <div class="toolbar">
      
      <!-- Tombol Cetak Laporan PDF & Indikator Data -->
      <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <button onclick="App.exportPdf()" class="btn btn-secondary" style="display:inline-flex; align-items:center; gap:8px; padding:7px 16px; font-weight:700; font-size:12px; color:#ef4444; border-color:rgba(239,68,68,0.35); background:rgba(239,68,68,0.06);" title="Cetak atau Simpan Laporan Hasil Pengecekan sebagai PDF">
          <svg style="width:15px; height:15px; fill:currentColor;" viewBox="0 0 24 24">
            <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
          </svg>
          <span>CETAK LAPORAN PDF</span>
        </button>

        <span style="font-size:13px; font-weight:700; color:var(--text-secondary); display:flex; align-items:center; gap:6px;">
          <span style="width:8px; height:8px; border-radius:50%; background:#10b981; display:inline-block; box-shadow:0 0 6px #10b981;"></span>
          Monitoring <span id="countAll">40</span> Website
        </span>
      </div>

      <!-- Search, Sort & View Mode -->
      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        
        <!-- Search Input -->
        <div class="search-box">
          <svg class="search-icon-left" style="width:16px; height:16px; position:absolute; left:12px; top:50%; transform:translateY(-50%); pointer-events:none; fill:#64748b;" viewBox="0 0 24 24">
            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
          </svg>
          <input type="text" id="searchInput" placeholder="Cari Polres, Kota, atau URL...">
          <span class="search-badge-right">Ctrl+K</span>
        </div>

        <!-- Sort Selector -->
        <div class="select-wrapper" style="padding:5px 10px;">
          <select id="sortSelect">
            <option value="id">Urutkan: No ID</option>
            <option value="name">Nama (A - Z)</option>
            <option value="status">Kendala Dulu</option>
            <option value="speed">Ping Tercepat</option>
          </select>
        </div>

        <!-- View Switcher -->
        <div class="view-toggle">
          <button id="btnViewTable" onclick="App.setViewMode('table')" class="filter-btn active" style="border:none; padding:5px 12px; margin:0;" title="Tampilan Tabel">
            Tabel
          </button>
          <button id="btnViewGrid" onclick="App.setViewMode('grid')" class="filter-btn" style="border:none; padding:5px 12px; margin:0;" title="Tampilan Grid">
            Grid
          </button>
        </div>

      </div>

    </div>

    <!-- SUMMARY TEXT & QUICK LEGEND -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:12px; color:#64748b; padding:0 4px;">
      <div style="display:flex; align-items:center; gap:8px;">
        <span style="font-weight:700;">Menampilkan:</span>
        <span id="filteredCount" class="font-mono" style="background:rgba(2,132,199,0.1); color:#0284c7; border:1px solid rgba(2,132,199,0.25); padding:2px 8px; border-radius:6px; font-weight:800;">40 Website</span>
      </div>
      <div style="display:flex; gap:16px; font-size:11px;" class="hide-mobile">
        <span style="display:flex; align-items:center; gap:5px;"><span style="width:8px; height:8px; border-radius:50%; background:#10b981;"></span> Aktif / Normal</span>
        <span style="display:flex; align-items:center; gap:5px;"><span style="width:8px; height:8px; border-radius:50%; background:#ef4444;"></span> Belum Bisa Diakses (Blok Merah)</span>
      </div>
    </div>

    <!-- MASTER TABLE VIEW (DEFAULT) -->
    <div id="tableContainer" class="table-panel">
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th onclick="App.setSortColumn('id')" class="sortable" style="text-align:center; width:60px;">No ↕</th>
              <th onclick="App.setSortColumn('name')" class="sortable">Website / Satker ↕</th>
              <th>Alamat URL</th>
              <th onclick="App.setSortColumn('status')" class="sortable">Status Akses ↕</th>
              <th onclick="App.setSortColumn('speed')" class="sortable" style="text-align:center;">Kecepatan ↕</th>
              <th>Terakhir Dicek</th>
              <th style="text-align:right;">Aksi</th>
            </tr>
          </thead>
          <tbody id="tableBody">
            <!-- Table rows injected by app.js -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- GRID VIEW (ALTERNATIVE) -->
    <div id="gridContainer" class="grid-container hidden">
      <!-- Grid cards injected by app.js -->
    </div>

  </main>

  <!-- FOOTER -->
  <footer class="app-footer">
    <div class="app-container footer-flex">
      <div class="footer-left">
        <span class="footer-shield-icon">
          <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
        </span>
        <span class="footer-title">Sistem Monitoring Website Tribratanews Jajaran Polda Jawa Timur</span>
      </div>
      <div class="footer-right">
        <span>🛡️ Anti-WAF Stealth Signature & Human Jitter Active</span>
      </div>
    </div>
  </footer>

  <!-- DETAIL DIAGNOSTIC MODAL -->
  <div id="detailModal" class="modal-overlay">
    <div class="modal-dialog">
      <div id="detailModalContent"></div>
    </div>
  </div>

  <!-- ADD / EDIT SITE MODAL -->
  <div id="siteFormModal" class="modal-overlay">
    <div class="modal-dialog modal-dialog-sm">
      <div class="modal-form-header">
        <h3 id="siteModalTitle" class="modal-form-title">Tambah Website Baru</h3>
        <button onclick="App.closeSiteModal()" class="action-btn">✕</button>
      </div>
      
      <form id="siteForm" class="site-form">
        <input type="hidden" id="siteFormId">
        
        <div>
          <label class="form-label">Nama Satker / Polres</label>
          <input type="text" id="siteFormName" class="form-input" required placeholder="Contoh: Polresta Tuban">
        </div>

        <div>
          <label class="form-label">URL Website</label>
          <input type="url" id="siteFormUrl" class="form-input font-mono" required placeholder="https://tribratanews...">
        </div>

        <div>
          <label class="form-label">Wilayah / Kategori</label>
          <input type="text" id="siteFormRegion" class="form-input" placeholder="Contoh: Tuban / Surabaya / Madura">
        </div>

        <div class="modal-form-actions">
          <button type="button" onclick="App.closeSiteModal()" class="btn btn-secondary">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Website</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL CATATAN SEBELUM CETAK PDF -->
  <div id="pdfNotesModal" class="modal-overlay">
    <div class="modal-dialog modal-dialog-sm" style="max-width: 660px;">
      <div class="modal-form-header">
        <div style="display:flex; align-items:center; gap:8px;">
          <svg style="width:20px; height:20px; fill:#ef4444;" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
          <h3 class="modal-form-title">Catatan Laporan Sebelum Cetak PDF</h3>
        </div>
        <button onclick="App.closePdfNotesModal()" class="action-btn">✕</button>
      </div>

      <div style="padding: 18px 20px;">
        <p style="font-size:12px; color:var(--text-muted); margin-bottom:14px; line-height:1.5;">
          Tuliskan catatan, hasil analisa, atau rekomendasi teknis yang akan dicantumkan <strong>di bawah tabel</strong> pada lembar laporan PDF resmi.
        </p>

        <div style="margin-bottom:12px;">
          <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
            <span>Isi Catatan / Rekomendasi Teknis:</span>
            <span style="font-size:11px; color:var(--text-muted); font-weight:normal;">(Dapat diedit bebas)</span>
          </label>
          <textarea id="pdfReportNotes" class="form-input" rows="14" style="resize:vertical; font-size:12px; line-height:1.5; font-family:inherit; min-height:230px;" placeholder="Tuliskan catatan teknis atau keterangan di sini..."></textarea>
        </div>

        <!-- Quick Template Buttons -->
        <div style="margin-bottom:18px;">
          <div style="font-size:11px; font-weight:700; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.5px;">Pilihan Template Catatan:</div>
          <div style="display:flex; flex-wrap:wrap; gap:8px;">
            <button type="button" onclick="App.applyPdfTemplate('standard')" class="btn btn-secondary" style="font-size:11px; padding:4px 10px;" title="Template Standar 29 Aktif, 11 Kendala">
              📋 Standar (29 Aktif / 11 Kendala)
            </button>
            <button type="button" onclick="App.applyPdfTemplate('realtime')" class="btn btn-secondary" style="font-size:11px; padding:4px 10px;" title="Sesuaikan otomatis dengan angka pemindaian saat ini">
              🔄 Sinkron Angka Realtime
            </button>
            <button type="button" onclick="App.applyPdfTemplate('clear')" class="btn btn-secondary" style="font-size:11px; padding:4px 10px; color:#ef4444;">
              🗑️ Kosongkan
            </button>
          </div>
        </div>

        <div class="modal-form-actions">
          <button type="button" onclick="App.closePdfNotesModal()" class="btn btn-secondary">Batal</button>
          <button type="button" onclick="App.confirmExportPdf()" class="btn btn-primary" style="background:#ef4444; border-color:#ef4444; display:inline-flex; align-items:center; gap:6px;">
            <svg style="width:16px; height:16px; fill:currentColor;" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
            Cetak / Simpan PDF
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- TOAST NOTIFICATION -->
  <div id="toast" class="toast-notice">
    <span style="color:var(--cyan-primary); display:flex;">
      <svg style="width:18px; height:18px; fill:currentColor;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
    </span>
    <span id="toastText">Notifikasi Sistem</span>
  </div>

  <!-- MASTER JAVASCRIPT ENGINE -->
  <script src="assets/js/app.js?v=25.0"></script>
</body>
</html>
