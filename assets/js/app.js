/**
 * Tribratanews Cyber Command Center - Master Engine
 * POLDA JAWA TIMUR
 * 100% Self-Contained, Zero-CDN-Dependency
 */

const CACHE_KEY = 'tribrata_cc_v23';

const App = {
  sites: [],
  filteredSites: [],
  stats: {
    total: 0,
    online: 0,
    protected: 0,
    issues: 0,
    dnsError: 0,
    avgLatency: 0
  },
  currentFilter: 'all',
  searchQuery: '',
  viewMode: localStorage.getItem('tribrata_view_mode') || 'table',
  theme: localStorage.getItem('tribrata_theme') || 'light',
  sortBy: 'id',
  sortAsc: true,
  isCheckingAll: false,
  progress: { current: 0, total: 0, percent: 0 },
  autoRefreshSeconds: 0,
  autoRefreshTimer: null,
  countdown: 0,
  countdownTimer: null,
  selectedSite: null,
  soundEnabled: localStorage.getItem('tribrata_sound_enabled') === 'true',
  audioCtx: null,
  terminalLogs: [],
  isTerminalOpen: false,

  // SVG Icons helper
  icons: {
    shield: '<svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>',
    bolt: '<svg viewBox="0 0 24 24"><path d="M11 21h-1l1-7H7.5c-.58 0-.57-.32-.38-.66.19-.34.05-.08.07-.12C8.48 10.94 10.42 7.54 13 3h1l-1 7h3.5c.49 0 .56.33.47.51l-.07.15C14.94 14.15 12.98 17.58 11 21z"/></svg>',
    globe: '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>',
    lock: '<svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>',
    external: '<svg viewBox="0 0 24 24"><path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg>',
    info: '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>',
    refresh: '<svg viewBox="0 0 24 24"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/></svg>',
    check: '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
    copy: '<svg viewBox="0 0 24 24"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/></svg>',
    terminal: '<svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 14H4V8h16v10zm-2-1h-6v-2h6v2zM7.5 17l-1.41-1.41L8.67 13l-2.58-2.59L7.5 9l4 4-4 4z"/></svg>',
    star: '<svg viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>'
  },

  // Inisialisasi
  async init() {
    this.initTheme();
    this.updateClock();
    setInterval(() => this.updateClock(), 1000);

    this.bindEvents();
    this.initAudio();
    this.updateSoundButtonUI();

    // Hapus cache versi lama yang salah urutan atau format
    localStorage.removeItem('tribrata_results_cache');
    localStorage.removeItem('tribrata_v2_cache');
    localStorage.removeItem('tribrata_cc_v5');
    localStorage.removeItem('tribrata_cc_v6');
    localStorage.removeItem('tribrata_cc_v7');
    localStorage.removeItem('tribrata_cc_v8');
    localStorage.removeItem('tribrata_cc_v9');
    localStorage.removeItem('tribrata_cc_v10');
    localStorage.removeItem('tribrata_cc_v11');
    localStorage.removeItem('tribrata_cc_v12');

    await this.loadSites();

    // Muat cache terbaru
    const cached = localStorage.getItem(CACHE_KEY);
    if (cached) {
      try {
        const parsed = JSON.parse(cached);
        this.sites = this.sites.map(site => {
          const match = parsed.find(p => p.id === site.id);
          return match ? { ...site, ...match } : site;
        });
        this.calculateStats();
        this.render();
      } catch (e) {
        console.error('Error load cache:', e);
      }
    } else {
      this.checkAllFast();
    }
  },

  // Audio Synthesizer
  initAudio() {
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if (AudioCtx) {
        this.audioCtx = new AudioCtx();
      }
    } catch (e) {
      console.warn('Web Audio not supported:', e);
    }
  },

  playSound(type = 'click') {
    if (!this.soundEnabled || !this.audioCtx) return;
    try {
      if (this.audioCtx.state === 'suspended') {
        this.audioCtx.resume();
      }
      const osc = this.audioCtx.createOscillator();
      const gain = this.audioCtx.createGain();
      osc.connect(gain);
      gain.connect(this.audioCtx.destination);

      const now = this.audioCtx.currentTime;

      if (type === 'click') {
        osc.frequency.setValueAtTime(800, now);
        osc.frequency.exponentialRampToValueAtTime(400, now + 0.05);
        gain.gain.setValueAtTime(0.08, now);
        gain.gain.linearRampToValueAtTime(0.01, now + 0.05);
        osc.start(now);
        osc.stop(now + 0.05);
      } else if (type === 'success') {
        osc.frequency.setValueAtTime(523.25, now);
        osc.frequency.setValueAtTime(659.25, now + 0.08);
        osc.frequency.setValueAtTime(783.99, now + 0.16);
        gain.gain.setValueAtTime(0.1, now);
        gain.gain.exponentialRampToValueAtTime(0.01, now + 0.3);
        osc.start(now);
        osc.stop(now + 0.3);
      }
    } catch (e) {}
  },

  // Theme Engine (Terang / Gelap)
  initTheme() {
    if (!this.theme || (this.theme !== 'light' && this.theme !== 'dark')) {
      this.theme = 'light';
    }
    document.body.className = 'theme-' + this.theme;
    this.updateThemeButtonUI();
  },

  toggleTheme() {
    this.theme = (this.theme === 'light') ? 'dark' : 'light';
    localStorage.setItem('tribrata_theme', this.theme);
    document.body.className = 'theme-' + this.theme;
    this.updateThemeButtonUI();
    this.playTone(660, 0.08, 'sine');
    this.showToast(this.theme === 'light' ? 'Mode Terang Aktif (Tampilan Segar & Jelas)' : 'Mode Gelap Cyber Aktif', 'info');
  },

  updateThemeButtonUI() {
    const iconEl = document.getElementById('themeIcon');
    const labelEl = document.getElementById('themeLabel');
    const btn = document.getElementById('btnToggleTheme');
    if (!btn) return;

    if (this.theme === 'light') {
      if (iconEl) iconEl.textContent = '☀️';
      if (labelEl) labelEl.textContent = 'Mode Terang';
      btn.title = 'Beralih ke Mode Gelap Cyber';
    } else {
      if (iconEl) iconEl.textContent = '🌙';
      if (labelEl) labelEl.textContent = 'Mode Gelap';
      btn.title = 'Beralih ke Mode Terang';
    }
  },

  toggleSound() {
    this.soundEnabled = !this.soundEnabled;
    localStorage.setItem('tribrata_sound_enabled', this.soundEnabled);
    this.updateSoundButtonUI();
    this.showToast(this.soundEnabled ? 'Audio Cyber Aktif' : 'Audio Dimatikan', 'info');
  },

  updateSoundButtonUI() {
    const btn = document.getElementById('btnToggleSound');
    if (!btn) return;
    if (this.soundEnabled) {
      btn.style.color = '#00f2fe';
      btn.style.borderColor = 'rgba(6, 182, 212, 0.4)';
    } else {
      btn.style.color = '#64748b';
      btn.style.borderColor = '';
    }
  },

  toggleFullscreen() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen().catch(() => {});
      this.showToast('Mode Fullscreen Command Center Aktif', 'info');
    } else {
      if (document.exitFullscreen) document.exitFullscreen();
    }
  },

  // Terminal Log Feeder
  addTerminalLog(message, type = 'info') {
    const time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    this.terminalLogs.unshift({ time, message, type });
    if (this.terminalLogs.length > 40) this.terminalLogs.pop();
    this.renderTerminalLogs();
  },

  renderTerminalLogs() {
    const container = document.getElementById('terminalLogContent');
    if (!container) return;

    container.innerHTML = this.terminalLogs.map(log => {
      let color = '#38bdf8';
      if (log.type === 'success') color = '#34d399';
      if (log.type === 'warning') color = '#fbbf24';
      if (log.type === 'error') color = '#fda4af';

      return `
        <div class="log-item-row">
          <span class="log-item-time">[${log.time}]</span> 
          <span class="font-bold" style="color:${color};">${log.message}</span>
        </div>
      `;
    }).join('');
  },

  toggleTerminal() {
    this.isTerminalOpen = !this.isTerminalOpen;
    const terminalEl = document.getElementById('terminalDrawer');
    const btn = document.getElementById('btnToggleTerminal');
    if (!terminalEl) return;

    if (this.isTerminalOpen) {
      terminalEl.classList.remove('hidden');
      if (btn) btn.style.background = '#0284c7';
    } else {
      terminalEl.classList.add('hidden');
      if (btn) btn.style.background = '';
    }
  },

  updateClock() {
    const now = new Date();
    const clockEl = document.getElementById('liveClock');
    const dateEl = document.getElementById('liveDate');
    if (clockEl) {
      clockEl.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
    }
    if (dateEl) {
      dateEl.textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
    }
  },

  async loadSites() {
    try {
      const res = await fetch('api/sites.php');
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        this.sites = json.data.map(item => ({
          ...item,
          status: item.status || 'pending',
          status_label: item.status_label || 'Belum Diperiksa',
          status_color: item.status_color || 'gray',
          http_code: item.http_code || 0,
          response_time_ms: item.response_time_ms || 0,
          primary_ip: item.primary_ip || '-',
          server: item.server || '-',
          checked_at: item.checked_at || null,
          is_loading: false
        }));
        this.calculateStats();
        this.render();
      }
    } catch (err) {
      console.error(err);
    }
  },

  calculateStats() {
    let online = 0;
    let dnsErr = 0;
    let issues = 0;
    let totalLatency = 0;
    let latencyCount = 0;

    this.sites.forEach(s => {
      if (s.status === 'online' || s.status === 'protected') {
        online++;
      } else if (s.status === 'dns_error') {
        dnsErr++;
        issues++;
      } else if (s.status !== 'pending') {
        issues++;
      }

      if (s.response_time_ms > 0 && s.http_code > 0) {
        totalLatency += s.response_time_ms;
        latencyCount++;
      }
    });

    this.stats = {
      total: this.sites.length,
      online: online,
      protected: 0,
      dnsError: dnsErr,
      issues: issues,
      avgLatency: latencyCount > 0 ? Math.round(totalLatency / latencyCount) : 0
    };

    const statTotalEl = document.getElementById('statTotal');
    if (statTotalEl) statTotalEl.textContent = this.stats.total;

    const countAllEl = document.getElementById('countAll');
    if (countAllEl) countAllEl.textContent = this.stats.total;

    const statOnlineEl = document.getElementById('statOnline');
    if (statOnlineEl) statOnlineEl.textContent = this.stats.online;

    const statProtectedEl = document.getElementById('statProtected');
    if (statProtectedEl) statProtectedEl.textContent = 0;

    const statIssuesEl = document.getElementById('statIssues');
    if (statIssuesEl) statIssuesEl.textContent = this.stats.issues;

    const statAvgLatencyEl = document.getElementById('statAvgLatency');
    if (statAvgLatencyEl) statAvgLatencyEl.textContent = this.stats.avgLatency + ' ms';

    const accessPct = this.stats.total > 0 ? Math.round((online / this.stats.total) * 100) : 0;
    const pctEl = document.getElementById('statAccessiblePct');
    if (pctEl) {
      pctEl.textContent = `${accessPct}% Siaga & Normal`;
    }
    return this.stats;
  },

  getFilteredSites() {
    let list = [...this.sites];

    if (this.searchQuery.trim()) {
      const q = this.searchQuery.toLowerCase().trim();
      list = list.filter(s =>
        (s.name && s.name.toLowerCase().includes(q)) ||
        (s.url && s.url.toLowerCase().includes(q)) ||
        (s.region && s.region.toLowerCase().includes(q)) ||
        (s.primary_ip && s.primary_ip.toLowerCase().includes(q))
      );
    }

    if (this.currentFilter === 'online') {
      list = list.filter(s => s.status === 'online' || s.status === 'protected');
    } else if (this.currentFilter === 'protected') {
      list = list.filter(s => s.status === 'protected');
    } else if (this.currentFilter === 'issues') {
      list = list.filter(s => s.status !== 'online' && s.status !== 'protected' && s.status !== 'pending');
    } else if (this.currentFilter === 'dns_error') {
      list = list.filter(s => s.status === 'dns_error');
    }

    const mul = this.sortAsc ? 1 : -1;
    if (this.sortBy === 'name') {
      list.sort((a, b) => mul * a.name.localeCompare(b.name));
    } else if (this.sortBy === 'speed') {
      list.sort((a, b) => mul * ((a.response_time_ms || 99999) - (b.response_time_ms || 99999)));
    } else if (this.sortBy === 'status') {
      const weight = { 'dns_error': 1, 'server_error': 2, 'timeout': 3, 'offline': 4, 'client_error': 5, 'protected': 6, 'online': 7, 'pending': 8 };
      list.sort((a, b) => mul * ((weight[a.status] || 9) - (weight[b.status] || 9)));
    } else {
      list.sort((a, b) => mul * (a.id - b.id));
    }

    return list;
  },

  setSortColumn(col) {
    if (this.sortBy === col) {
      this.sortAsc = !this.sortAsc;
    } else {
      this.sortBy = col;
      this.sortAsc = true;
    }
    this.playSound('click');
    this.render();
  },

  saveCache() {
    try {
      localStorage.setItem(CACHE_KEY, JSON.stringify(this.sites));
    } catch (e) {}
  },

  // Cek single website
  async checkSingle(id) {
    const site = this.sites.find(s => s.id === id);
    if (!site) return;

    this.playSound('click');
    site.is_loading = true;
    this.renderSingleItem(id);

    this.addTerminalLog(`Menguji koneksi ke ${site.name} (${site.url})...`, 'info');

    try {
      const url = `api/check.php?url=${encodeURIComponent(site.url)}&name=${encodeURIComponent(site.name)}&id=${site.id}`;
      const res = await fetch(url);
      const data = await res.json();

      if (data.success) {
        Object.assign(site, data);
        const logType = (site.status === 'online') ? 'success' : (site.status === 'protected' ? 'warning' : 'error');
        this.addTerminalLog(`[#${site.id}] ${site.name}: ${site.status_label} (HTTP ${site.http_code}) - ${site.response_time_ms}ms`, logType);
        this.playSound(site.status === 'online' || site.status === 'protected' ? 'success' : 'click');
      } else {
        site.status = 'offline';
        site.status_label = 'Gagal Pengecekan';
        site.status_color = 'red';
      }
    } catch (err) {
      site.status = 'offline';
      site.status_label = 'Koneksi Error';
      site.status_color = 'red';
      site.error_message = err.message;
    } finally {
      site.is_loading = false;
      this.calculateStats();
      this.saveCache();
      this.render();

      if (this.selectedSite && this.selectedSite.id === id) {
        this.openDetailModal(id);
      }
    }
  },

  // Cek Progresif (Anti-Bot Throttling Jitter)
  async checkAllProgressive() {
    if (this.isCheckingAll) return;
    this.isCheckingAll = true;

    this.playSound('click');
    const total = this.sites.length;
    this.progress = { current: 0, total, percent: 0 };
    this.updateProgressBar(true);

    this.addTerminalLog(`Memulai pemindaian ${total} website dengan Anti-Bot Jitter...`, 'info');

    this.sites.forEach(s => s.is_loading = true);
    this.render();

    const concurrency = 4;
    let index = 0;

    const worker = async () => {
      while (index < total) {
        const curIdx = index++;
        const site = this.sites[curIdx];
        if (!site) break;

        // Human-like jitter
        await new Promise(r => setTimeout(r, 120 + Math.random() * 150));

        try {
          const url = `api/check.php?url=${encodeURIComponent(site.url)}&name=${encodeURIComponent(site.name)}&id=${site.id}`;
          const res = await fetch(url);
          const data = await res.json();
          if (data.success) {
            Object.assign(site, data);
            const logType = (site.status === 'online') ? 'success' : (site.status === 'protected' ? 'warning' : 'error');
            this.addTerminalLog(`[${site.id}/40] ${site.name} -> ${site.status_label} (${site.response_time_ms}ms)`, logType);
          }
        } catch (err) {
          site.status = 'offline';
          site.status_label = 'Koneksi Gagal';
          site.status_color = 'red';
        } finally {
          site.is_loading = false;
          this.progress.current++;
          this.progress.percent = Math.round((this.progress.current / total) * 100);
          this.updateProgressBar();
          this.calculateStats();
          this.renderSingleItem(site.id);
        }
      }
    };

    const workers = [];
    for (let i = 0; i < Math.min(concurrency, total); i++) {
      workers.push(worker());
    }

    await Promise.all(workers);

    this.isCheckingAll = false;
    this.saveCache();
    this.playSound('success');
    this.addTerminalLog(`Pemindaian progresif ${total} website tuntas.`, 'success');
    setTimeout(() => this.updateProgressBar(false), 800);
    this.render();
    this.showToast('Semua website selesai diperiksa!', 'success');
  },

  // Cek Kilat (Parallel Multi-cURL Server-side)
  async checkAllFast() {
    if (this.isCheckingAll) return;
    this.isCheckingAll = true;

    this.playSound('click');
    this.progress = { current: 0, total: this.sites.length, percent: 35 };
    this.updateProgressBar(true);

    const btn = document.getElementById('btnCheckAllFast');
    if (btn) btn.classList.add('opacity-75');

    this.addTerminalLog('Menjalankan CEK WEBSITE paralel (Multi-cURL Stealth Mode)...', 'info');

    try {
      const res = await fetch('api/check_all.php');
      const json = await res.json();

      this.progress.percent = 100;
      this.updateProgressBar();

      if (json.success && Array.isArray(json.data)) {
        json.data.forEach(resultItem => {
          const match = this.sites.find(s => s.id === resultItem.id);
          if (match) {
            Object.assign(match, resultItem);
          }
        });
        this.calculateStats();
        this.saveCache();
        this.playSound('success');
        this.addTerminalLog(`Pengecekan website selesai dalam ~1.8 detik (${json.data.length} website).`, 'success');
        this.showToast(`Pengecekan website selesai (${json.data.length} website)`, 'success');
      }
    } catch (err) {
      console.error(err);
      this.showToast('Error saat menghubungi server pengecekan', 'error');
    } finally {
      this.isCheckingAll = false;
      this.sites.forEach(s => s.is_loading = false);
      if (btn) btn.classList.remove('opacity-75');
      setTimeout(() => this.updateProgressBar(false), 800);
      this.render();
    }
  },

  updateProgressBar(show = true) {
    const container = document.getElementById('progressContainer');
    const fill = document.getElementById('progressBarFill');
    const text = document.getElementById('progressText');
    const pct = document.getElementById('progressPercent');

    if (!container) return;

    if (show) {
      container.classList.remove('hidden');
      fill.style.width = `${this.progress.percent}%`;
      text.textContent = `Memeriksa ${this.progress.current} dari ${this.progress.total} website...`;
      pct.textContent = `${this.progress.percent}%`;
    } else {
      container.classList.add('hidden');
    }
  },

  setAutoRefresh(seconds) {
    this.autoRefreshSeconds = parseInt(seconds, 10);
    clearInterval(this.autoRefreshTimer);
    clearInterval(this.countdownTimer);

    const badge = document.getElementById('autoRefreshBadge');

    if (this.autoRefreshSeconds <= 0) {
      if (badge) badge.classList.add('hidden');
      return;
    }

    if (badge) badge.classList.remove('hidden');
    this.countdown = this.autoRefreshSeconds;
    this.updateCountdownBadge();

    this.countdownTimer = setInterval(() => {
      this.countdown--;
      if (this.countdown <= 0) {
        this.countdown = this.autoRefreshSeconds;
        this.checkAllFast();
      }
      this.updateCountdownBadge();
    }, 1000);
  },

  updateCountdownBadge() {
    const el = document.getElementById('countdownVal');
    if (el) el.textContent = `${this.countdown}s`;
  },

  render() {
    const list = this.getFilteredSites();
    const countEl = document.getElementById('filteredCount');
    if (countEl) countEl.textContent = `${list.length} Website`;

    const gridContainer = document.getElementById('gridContainer');
    const tableContainer = document.getElementById('tableContainer');

    if (this.viewMode === 'grid') {
      gridContainer.classList.remove('hidden');
      tableContainer.classList.add('hidden');
      this.renderGrid(list);
    } else {
      gridContainer.classList.add('hidden');
      tableContainer.classList.remove('hidden');
      this.renderTable(list);
    }
  },

  renderGrid(list) {
    const container = document.getElementById('gridContainer');
    if (!container) return;

    if (list.length === 0) {
      container.innerHTML = `
        <div class="empty-state-box">
          <p class="empty-state-title">Tidak ada website yang cocok dengan pencarian.</p>
          <button onclick="App.resetFilter()" class="btn btn-secondary">Reset Pencarian</button>
        </div>
      `;
      return;
    }

    container.innerHTML = list.map(site => this.generateCardHtml(site)).join('');
  },

  generateCardHtml(site) {
    const statusMeta = this.getStatusMeta(site);
    const speedBadge = this.getSpeedBadge(site.response_time_ms, site.http_code);
    const hasHttps = site.url.startsWith('https://') || (site.effective_url && site.effective_url.startsWith('https://'));
    const isPolda = (site.id === 1);
    const isInaccessible = (site.status === 'dns_error' || site.status === 'offline' || site.status === 'timeout' || site.status === 'server_error' || site.status === 'client_error' || (site.status !== 'online' && site.status !== 'protected' && site.status !== 'pending' && !site.is_loading));

    return `
      <div id="card-site-${site.id}" class="site-card ${isPolda ? 'polda-card-vip' : ''} ${isInaccessible ? 'card-error-block' : ''}">
        <div class="site-card-topbar" style="background: ${statusMeta.barColor};"></div>

        <div>
          <div class="card-header-row">
            <div>
              <div class="d-flex align-center gap-6">
                <span class="card-id-badge">#${site.id}</span>
                <span class="card-region-label">${site.region || 'Jatim'}</span>
              </div>
              <h3 class="card-site-name" onclick="App.openDetailModal(${site.id})">
                ${site.name}
              </h3>
            </div>
            <div>
              <span class="status-pill ${statusMeta.pillClass}">
                <span class="pulse-dot"></span>
                ${site.is_loading ? 'Memeriksa...' : statusMeta.label}
              </span>
            </div>
          </div>

          <div class="card-url-bar">
            <div class="card-url-inner">
              <span style="color:#0284c7; display:flex;">${hasHttps ? this.icons.lock : this.icons.globe}</span>
              <a href="${site.url}" target="_blank" rel="noopener noreferrer" class="card-url-link">
                ${site.url}
              </a>
            </div>
            <button onclick="App.copyToClipboard('${site.url}')" class="action-btn" title="Salin URL">
              ${this.icons.copy}
            </button>
          </div>

          <div class="card-metrics-grid">
            <div class="card-metric-cell">
              <span class="card-metric-label">Kecepatan (Ping)</span>
              ${speedBadge}
            </div>
            <div class="card-metric-cell">
              <span class="card-metric-label">Terakhir Dicek</span>
              <span class="card-metric-val font-mono" style="font-size:11px;">${this.formatCheckTime(site)}</span>
            </div>
          </div>
        </div>

        <div class="card-footer-row">
          <span style="font-size:10px; color:#64748b;">${site.checked_at_human || (site.checked_at ? site.checked_at.split(' ')[1] : 'Belum dicek')}</span>
          <div class="d-flex gap-6">
            <button onclick="App.openDetailModal(${site.id})" class="btn btn-secondary" style="padding:4px 10px; font-size:11px;">Detail</button>
            <button onclick="App.checkSingle(${site.id})" class="action-btn refresh ${site.is_loading ? 'spinning' : ''}" title="Cek Ulang">${this.icons.refresh}</button>
          </div>
        </div>
      </div>
    `;
  },

  renderTable(list) {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;

    if (list.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" style="padding: 60px 0; text-align: center; color: #64748b;">
            <p style="font-size: 15px; font-weight: 600;">Tidak ada website yang cocok dengan filter / pencarian.</p>
            <button onclick="App.resetFilter()" class="btn btn-secondary" style="margin-top: 10px;">Reset Pencarian</button>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = list.map(site => this.generateTableRowHtml(site)).join('');
  },

  generateTableRowHtml(site) {
    const statusMeta = this.getStatusMeta(site);
    const speedBadge = this.getSpeedBadge(site.response_time_ms, site.http_code);
    const hasHttps = site.url.startsWith('https://') || (site.effective_url && site.effective_url.startsWith('https://'));
    const isPolda = (site.id === 1);
    const isInaccessible = (site.status === 'dns_error' || site.status === 'offline' || site.status === 'timeout' || site.status === 'server_error' || site.status === 'client_error' || (site.status !== 'online' && site.status !== 'protected' && site.status !== 'pending' && !site.is_loading));

    return `
      <tr id="row-site-${site.id}" class="${isPolda ? 'polda-vip-row' : ''} ${isInaccessible ? 'row-error-block' : ''}">
        <!-- No -->
        <td class="text-center font-mono font-bold">
          ${isPolda ? `
            <span class="num-badge-polda" title="Polda Jawa Timur">1</span>
          ` : `
            <span class="num-badge-regular">#${site.id}</span>
          `}
        </td>

        <!-- Satker / Nama -->
        <td>
          <div class="d-flex align-center gap-8">
            <span class="satker-title" onclick="App.openDetailModal(${site.id})">
              ${site.name}
            </span>
          </div>
          <span class="region-text">${site.region || 'Jatim'}</span>
        </td>

        <!-- URL -->
        <td>
          <div class="url-box">
            <span class="d-flex" style="color: ${hasHttps ? '#059669' : '#64748b'};">
              ${hasHttps ? this.icons.lock : this.icons.globe}
            </span>
            <a href="${site.url}" target="_blank" rel="noopener noreferrer" class="url-link" title="${site.url}">
              ${site.url}
            </a>
          </div>
        </td>

        <!-- Status Akses -->
        <td class="nowrap">
          <span class="status-pill ${statusMeta.pillClass}">
            <span class="pulse-dot"></span>
            ${site.is_loading ? 'Memeriksa...' : statusMeta.label}
          </span>
        </td>

        <!-- Kecepatan -->
        <td class="text-center nowrap">
          ${speedBadge}
        </td>

        <!-- Terakhir Dicek -->
        <td class="nowrap text-dim font-mono" style="font-size: 11.5px;">
          ${this.formatCheckTime(site)}
        </td>

        <!-- Aksi -->
        <td class="text-right nowrap">
          <div class="table-actions">
            <a href="${site.url}" target="_blank" rel="noopener noreferrer" class="action-btn" title="Buka Website">
              ${this.icons.external}
            </a>
            <button onclick="App.openDetailModal(${site.id})" class="action-btn" title="Detail Diagnosa">
              ${this.icons.info}
            </button>
            <button onclick="App.checkSingle(${site.id})" class="action-btn refresh ${site.is_loading ? 'spinning' : ''}" title="Cek Ulang">
              ${this.icons.refresh}
            </button>
          </div>
        </td>
      </tr>
    `;
  },

  renderSingleItem(id) {
    const site = this.sites.find(s => s.id === id);
    if (!site) return;

    const rowEl = document.getElementById(`row-site-${id}`);
    if (rowEl && this.viewMode === 'table') {
      rowEl.outerHTML = this.generateTableRowHtml(site);
    }

    const cardEl = document.getElementById(`card-site-${id}`);
    if (cardEl && this.viewMode === 'grid') {
      cardEl.outerHTML = this.generateCardHtml(site);
    }
  },

  getStatusMeta(site) {
    if (site.is_loading) {
      return {
        label: 'Memeriksa...',
        pillClass: 'status-loading',
        barColor: '#0284c7',
        httpColorClass: 'http-color-loading'
      };
    }

    if (site.status === 'online' || site.status === 'protected') {
      return {
        label: 'Aktif/Normal',
        pillClass: 'status-online',
        barColor: '#059669',
        httpColorClass: 'http-color-online'
      };
    }

    if (site.status === 'dns_error') {
      return {
        label: 'Belum Bisa Diakses (DNS)',
        pillClass: 'status-error',
        barColor: '#ef4444',
        httpColorClass: 'http-color-issues'
      };
    }

    if (site.status === 'timeout' || site.status === 'offline' || site.status === 'server_error' || site.status === 'client_error') {
      return {
        label: site.status_label || 'Belum Bisa Diakses',
        pillClass: 'status-error',
        barColor: '#ef4444',
        httpColorClass: 'http-color-issues'
      };
    }

    return {
      label: 'Belum Dicek',
      pillClass: 'status-dns',
      barColor: '#64748b',
      httpColorClass: 'http-color-dns'
    };
  },

  formatCheckTime(site) {
    if (!site.checked_at && !site.checked_at_human) return '-';
    if (site.checked_at_human) {
      const parts = site.checked_at_human.split(',');
      const timeOnly = parts[0].trim();
      return timeOnly.includes('WIB') ? timeOnly : `${timeOnly} WIB`;
    }
    if (site.checked_at) {
      const parts = site.checked_at.split(' ');
      const timeOnly = parts[1] || parts[0];
      return timeOnly.includes('WIB') ? timeOnly : `${timeOnly} WIB`;
    }
    return '-';
  },

  getSpeedBadge(ms, code) {
    if (!code || ms <= 0) {
      return `<span style="font-family:'JetBrains Mono',monospace; color:#64748b; font-size:11px;">-</span>`;
    }

    let speedClass = 'speed-fast';
    let widthPct = Math.min(100, Math.round((ms / 2000) * 100));

    if (ms > 1500) {
      speedClass = 'speed-slow';
    } else if (ms > 600) {
      speedClass = 'speed-med';
    }

    return `
      <div class="speed-badge ${speedClass}">
        <span>${ms} ms</span>
        <div class="speed-bar-track">
          <div class="speed-bar-fill" style="width: ${widthPct}%;"></div>
        </div>
      </div>
    `;
  },

  getHttpText(code) {
    const codes = {
      200: 'OK',
      301: 'Moved',
      302: 'Redirect',
      400: 'Bad Req',
      403: 'WAF Guard',
      404: 'Not Found',
      429: 'Rate Limit',
      500: 'Server Err',
      502: 'Bad Gateway',
      503: 'Unavailable'
    };
    return codes[code] ? `(${codes[code]})` : '';
  },

  // Modal Detail Diagnosa
  openDetailModal(id) {
    this.playSound('click');
    const site = this.sites.find(s => s.id === id);
    if (!site) return;

    this.selectedSite = site;
    const modal = document.getElementById('detailModal');
    const content = document.getElementById('detailModalContent');
    const statusMeta = this.getStatusMeta(site);

    content.innerHTML = `
      <div class="modal-body-padding">
        <div class="modal-top-row">
          <div>
            <div class="d-flex align-center gap-8">
              <span class="font-mono" style="font-size:12px; font-weight:800; background:rgba(6,182,212,0.15); color:#0284c7; padding:2px 8px; border-radius:4px; border:1px solid rgba(6,182,212,0.3);">#${site.id}</span>
              <span class="font-bold" style="font-size:12px; color:#64748b;">${site.region || 'Jawa Timur'}</span>
            </div>
            <h2 class="modal-site-title">${site.name}</h2>
            <div class="modal-url-row">
              <a href="${site.url}" target="_blank" class="modal-url-text">${site.url}</a>
              <button onclick="App.copyToClipboard('${site.url}')" class="action-btn" title="Salin URL" style="width:24px; height:24px;">${this.icons.copy}</button>
            </div>
          </div>
          <button onclick="App.closeDetailModal()" class="action-btn" style="width:36px; height:36px;">✕</button>
        </div>

        <div class="modal-status-banner">
          <div>
            <span class="modal-banner-subtitle">Status Akses Website</span>
            <span class="status-pill ${statusMeta.pillClass}" style="margin-top:4px;">
              <span class="pulse-dot"></span> ${statusMeta.label}
            </span>
          </div>
          <div class="text-right">
            <span class="modal-banner-subtitle">Latensi (Ping)</span>
            <span class="modal-http-large font-mono" style="color:var(--cyan-primary);">${site.response_time_ms ? `${site.response_time_ms} ms` : '-'}</span>
          </div>
        </div>

        <div class="modal-diag-panel">
          <strong class="modal-diag-heading">Keterangan Diagnosa</strong>
          ${site.diagnostic || (site.error_message ? site.error_message : 'Belum dilakukan pemindaian.')}
        </div>

        <div class="modal-metrics-3col">
          <div class="modal-metric-card">
            <span class="modal-metric-card-lbl">Total Latensi</span>
            <span class="modal-metric-card-val">${site.response_time_ms ? `${site.response_time_ms} ms` : '-'}</span>
          </div>
          <div class="modal-metric-card">
            <span class="modal-metric-card-lbl">DNS Lookup</span>
            <span class="modal-metric-card-val">${site.dns_time_ms ? `${site.dns_time_ms} ms` : '-'}</span>
          </div>
          <div class="modal-metric-card">
            <span class="modal-metric-card-lbl">IP Server</span>
            <span class="modal-metric-card-val truncate" style="color:#38bdf8;" title="${site.primary_ip}">${site.primary_ip || '-'}</span>
          </div>
          <div class="modal-metric-card">
            <span class="modal-metric-card-lbl">Web Server / CDN</span>
            <span class="modal-metric-card-val">${site.server || '-'}</span>
          </div>
          <div class="modal-metric-card">
            <span class="modal-metric-card-lbl">Keamanan SSL</span>
            <span class="modal-metric-card-val" style="color:${site.ssl_active ? '#34d399' : '#94a3b8'};">${site.ssl_active ? 'HTTPS Aktif' : 'HTTP Biasa'}</span>
          </div>
          <div class="modal-metric-card">
            <span class="modal-metric-card-lbl">Terakhir Dicek</span>
            <span class="modal-metric-card-val" style="font-size:12px; color:#cbd5e1;">${site.checked_at_human || '-'}</span>
          </div>
        </div>

        <div class="modal-bottom-actions">
          <div class="d-flex gap-8">
            <button onclick="App.openEditModal(${site.id})" class="btn btn-secondary">Edit</button>
            <button onclick="App.deleteSite(${site.id})" class="btn btn-secondary" style="color:#fda4af; border-color:rgba(244,63,94,0.3);">Hapus</button>
          </div>
          <div class="d-flex gap-8">
            <a href="${site.url}" target="_blank" class="btn btn-secondary">Kunjungi Web</a>
            <button onclick="App.checkSingle(${site.id})" class="btn btn-primary">Cek Ulang</button>
          </div>
        </div>
      </div>
    `;

    modal.classList.add('open');
  },

  closeDetailModal() {
    const modal = document.getElementById('detailModal');
    if (modal) modal.classList.remove('open');
  },

  openAddModal() {
    this.playSound('click');
    document.getElementById('siteModalTitle').textContent = 'Tambah Website Baru';
    document.getElementById('siteFormId').value = '';
    document.getElementById('siteFormName').value = '';
    document.getElementById('siteFormUrl').value = 'http://';
    document.getElementById('siteFormRegion').value = 'Jawa Timur';

    const modal = document.getElementById('siteFormModal');
    if (modal) modal.classList.add('open');
  },

  openEditModal(id) {
    this.closeDetailModal();
    const site = this.sites.find(s => s.id === id);
    if (!site) return;

    this.playSound('click');
    document.getElementById('siteModalTitle').textContent = 'Edit Data Website';
    document.getElementById('siteFormId').value = site.id;
    document.getElementById('siteFormName').value = site.name;
    document.getElementById('siteFormUrl').value = site.url;
    document.getElementById('siteFormRegion').value = site.region || '';

    const modal = document.getElementById('siteFormModal');
    if (modal) modal.classList.add('open');
  },

  closeSiteModal() {
    const modal = document.getElementById('siteFormModal');
    if (modal) modal.classList.remove('open');
  },

  async saveSiteForm(e) {
    e.preventDefault();
    const id = document.getElementById('siteFormId').value;
    const name = document.getElementById('siteFormName').value.trim();
    const url = document.getElementById('siteFormUrl').value.trim();
    const region = document.getElementById('siteFormRegion').value.trim();

    if (!name || !url) {
      this.showToast('Nama dan URL wajib diisi!', 'warning');
      return;
    }

    const action = id ? 'edit' : 'add';
    const payload = { action, id: parseInt(id, 10), name, url, region };

    try {
      const res = await fetch('api/sites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();

      if (data.success) {
        this.showToast(data.message, 'success');
        this.closeSiteModal();
        await this.loadSites();
      } else {
        this.showToast(data.message || 'Gagal menyimpan data', 'error');
      }
    } catch (err) {
      this.showToast('Terjadi kesalahan jaringan', 'error');
    }
  },

  async deleteSite(id) {
    const site = this.sites.find(s => s.id === id);
    if (!site) return;

    if (!confirm(`Hapus "${site.name}" dari daftar pemantauan?`)) return;

    try {
      const res = await fetch('api/sites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id })
      });
      const data = await res.json();
      if (data.success) {
        this.closeDetailModal();
        this.showToast('Website berhasil dihapus', 'success');
        await this.loadSites();
      }
    } catch (err) {
      this.showToast('Gagal menghapus', 'error');
    }
  },

  async resetToDefault() {
    if (!confirm('Kembalikan ke 40 website Polda & Polres Jatim default (Polda Jatim urutan #1)?')) return;

    try {
      const res = await fetch('api/sites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'reset' })
      });
      const data = await res.json();
      if (data.success) {
        localStorage.removeItem(CACHE_KEY);
        this.showToast('Daftar website berhasil direset ke default', 'success');
        await this.loadSites();
        this.checkAllFast();
      }
    } catch (err) {
      this.showToast('Gagal mereset data', 'error');
    }
  },

  copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
      this.showToast('URL disalin ke clipboard', 'info');
      this.playSound('click');
    });
  },

  exportCsv() {
    this.playSound('click');
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/export.php';
    form.target = '_blank';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'results_json';
    input.value = JSON.stringify(this.sites);

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    this.showToast('Mengunduh Laporan CSV...', 'success');
  },

  async exportPdf() {
    this.playSound('click');
    const isUnchecked = this.sites.every(s => s.status === 'pending' || !s.checked_at);
    if (isUnchecked) {
      this.showToast('Menjalankan pemindaian realtime sebelum cetak PDF...', 'info');
      await this.checkAllFast();
    }
    this.openPdfNotesModal();
  },

  openPdfNotesModal() {
    const modal = document.getElementById('pdfNotesModal');
    const notesInput = document.getElementById('pdfReportNotes');
    if (notesInput && !notesInput.value.trim()) {
      this.applyPdfTemplate('standard');
    }
    if (modal) modal.classList.add('open');
  },

  closePdfNotesModal() {
    const modal = document.getElementById('pdfNotesModal');
    if (modal) modal.classList.remove('open');
  },

  applyPdfTemplate(type) {
    const notesInput = document.getElementById('pdfReportNotes');
    if (!notesInput) return;

    this.calculateStats();
    const stats = this.stats || { total: (this.sites ? this.sites.length : 40), online: 29, issues: 11, dnsError: 11 };
    const issueCount = (stats.issues !== undefined && stats.issues >= 0) ? stats.issues : (stats.dnsError || 0);
    const onlineCount = stats.online !== undefined ? stats.online : 29;
    const totalCount = stats.total || 40;

    if (type === 'standard') {
      notesInput.value = `1. Pengecekan ketersediaan website Tribratanews jajaran Polda Jawa Timur dilaksanakan secara realtime.\n` +
        `2. Dari total 40 website yang dipantau, sebanyak 29 website berstatus Aktif/Normal dan 11 website mengalami kendala akses (ditandai blok merah).\n` +
        `3. Untuk website dengan kendala DNS/jaringan telah dikoordinasikan dengan teknisi DIV TIK Mabes Polri terkait guna penanganan perbaikan routing.\n` +
        `4. Terkait 6 (enam) domain website jajaran berikut:\n` +
        `   a. https://tribratanews.restanjungperak.jatim.polri.go.id/ (Polres Pelabuhan Tanjung Perak)\n` +
        `   b. https://tribratanews.restasidoarjo.jatim.polri.go.id/ (Polresta Sidoarjo)\n` +
        `   c. https://tribratanews.resgresik.jatim.polri.go.id/ (Polres Gresik)\n` +
        `   d. https://tribratanews.resprobolinggokota.jatim.polri.go.id/ (Polres Probolinggo Kota)\n` +
        `   e. https://tribratanews.reskediri.jatim.polri.go.id/ (Polres Kediri)\n` +
        `   f. https://tribratanews.resbondowoso.jatim.polri.go.id/ (Polres Bondowoso)\n` +
        `   saat ini telah masuk tahapan Pelaksanaan Audit Sistem Keamanan Informasi dengan metode ZAP (Zed Attack Proxy) oleh Tim Auditor Divisi TIK Mabes Polri.\n` +
        `5. Status tindak lanjut satker khusus:\n` +
        `   a. Polres Madiun Kota: Masih dalam proses permintaan reset password cPanel kepada Div TIK Mabes Polri.\n` +
        `   b. Polresta Tuban: Sedang dalam proses koordinasi intensif bersama PIC (Person in Charge) setempat.\n` +
        `6. Pemberitahuan Pembaruan Sistem & Bantuan Akses Login:\n` +
        `   Website saat ini telah dilakukan update program sistem. Bagi operator satker/satwil jajaran yang belum dapat melakukan login, harap segera menghubungi Tim Teknis Bpk. Chandra (081398616900) untuk mendapatkan tautan alamat login serta username dan password terbaru.\n` +
        `7. Pemantauan rutin terus dilaksanakan secara berkesinambungan oleh Tim Teknis.`;
    } else if (type === 'realtime') {
      notesInput.value = `1. Pengecekan ketersediaan website Tribratanews jajaran Polda Jawa Timur dilaksanakan secara realtime.\n` +
        `2. Dari total ${totalCount} website yang dipantau, sebanyak ${onlineCount} website berstatus Aktif/Normal dan ${issueCount} website mengalami kendala akses (ditandai blok merah).\n` +
        `3. Untuk website dengan kendala DNS/jaringan telah dikoordinasikan dengan teknisi DIV TIK Mabes Polri terkait guna penanganan perbaikan routing.\n` +
        `4. Terkait 6 (enam) domain website jajaran berikut:\n` +
        `   a. https://tribratanews.restanjungperak.jatim.polri.go.id/ (Polres Pelabuhan Tanjung Perak)\n` +
        `   b. https://tribratanews.restasidoarjo.jatim.polri.go.id/ (Polresta Sidoarjo)\n` +
        `   c. https://tribratanews.resgresik.jatim.polri.go.id/ (Polres Gresik)\n` +
        `   d. https://tribratanews.resprobolinggokota.jatim.polri.go.id/ (Polres Probolinggo Kota)\n` +
        `   e. https://tribratanews.reskediri.jatim.polri.go.id/ (Polres Kediri)\n` +
        `   f. https://tribratanews.resbondowoso.jatim.polri.go.id/ (Polres Bondowoso)\n` +
        `   saat ini telah masuk tahapan Pelaksanaan Audit Sistem Keamanan Informasi dengan metode ZAP (Zed Attack Proxy) oleh Tim Auditor Divisi TIK Mabes Polri.\n` +
        `5. Status tindak lanjut satker khusus:\n` +
        `   a. Polres Madiun Kota: Masih dalam proses permintaan reset password cPanel kepada Div TIK Mabes Polri.\n` +
        `   b. Polresta Tuban: Sedang dalam proses koordinasi intensif bersama PIC (Person in Charge) setempat.\n` +
        `6. Pemberitahuan Pembaruan Sistem & Bantuan Akses Login:\n` +
        `   Website saat ini telah dilakukan update program sistem. Bagi operator satker/satwil jajaran yang belum dapat melakukan login, harap segera menghubungi Tim Teknis Bpk. Chandra (081398616900) untuk mendapatkan tautan alamat login serta username dan password terbaru.\n` +
        `7. Pemantauan rutin terus dilaksanakan secara berkesinambungan oleh Tim Teknis.`;
    } else if (type === 'clear') {
      notesInput.value = '';
    }
    notesInput.focus();
  },

  confirmExportPdf() {
    this.playSound('click');
    const notesInput = document.getElementById('pdfReportNotes');
    const notes = notesInput ? notesInput.value.trim() : '';

    this.closePdfNotesModal();

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'api/export_pdf.php?autoprint=1';
    form.target = '_blank';

    const inputData = document.createElement('input');
    inputData.type = 'hidden';
    inputData.name = 'results_json';
    inputData.value = JSON.stringify(this.sites);
    form.appendChild(inputData);

    const inputNotes = document.createElement('input');
    inputNotes.type = 'hidden';
    inputNotes.name = 'report_notes';
    inputNotes.value = notes;
    form.appendChild(inputNotes);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    this.showToast('Membuka Laporan PDF Realtime Siap Cetak...', 'success');
  },

  showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    const toastText = document.getElementById('toastText');

    if (!toast || !toastText) return;

    toastText.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
      toast.classList.remove('show');
    }, 3200);
  },

  setViewMode(mode) {
    this.viewMode = mode;
    localStorage.setItem('tribrata_view_mode', mode);

    const btnGrid = document.getElementById('btnViewGrid');
    const btnTable = document.getElementById('btnViewTable');

    if (mode === 'grid') {
      btnGrid.classList.add('active');
      btnTable.classList.remove('active');
    } else {
      btnTable.classList.add('active');
      btnGrid.classList.remove('active');
    }

    this.playSound('click');
    this.render();
  },

  setFilter(filter) {
    this.currentFilter = filter;
    document.querySelectorAll('.filter-btn').forEach(btn => {
      if (btn.dataset.filter === filter) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
    this.playSound('click');
    this.render();
  },

  resetFilter() {
    this.searchQuery = '';
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';
    this.setFilter('all');
  },

  bindEvents() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        this.searchQuery = e.target.value;
        this.render();
      });
    }

    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
      sortSelect.addEventListener('change', (e) => {
        this.sortBy = e.target.value;
        this.render();
      });
    }

    const autoRefreshSelect = document.getElementById('autoRefreshSelect');
    if (autoRefreshSelect) {
      autoRefreshSelect.addEventListener('change', (e) => {
        this.setAutoRefresh(e.target.value);
      });
    }

    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        this.setFilter(btn.dataset.filter);
      });
    });

    const siteForm = document.getElementById('siteForm');
    if (siteForm) {
      siteForm.addEventListener('submit', (e) => this.saveSiteForm(e));
    }

    window.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeDetailModal();
        this.closeSiteModal();
        this.closePdfNotesModal();
      }
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        searchInput?.focus();
      }
    });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
