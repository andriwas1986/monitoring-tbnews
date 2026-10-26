# Sistem Monitoring Website Tribratanews - Jajaran Polda Jawa Timur

Website dashboard interaktif untuk memantau status ketersediaan, kesehatan, kecepatan akses (latensi/ping), dan status HTTP dari seluruh website Tribratanews jajaran Kepolisian Daerah Jawa Timur (Polda Jatim dan 39 Polres/Polresta/Polrestabes).

---

## 🚀 Cara Mengakses

Pastikan **Apache** di XAMPP dalam keadaan aktif (Running). Buka browser (Google Chrome, Microsoft Edge, Mozilla Firefox) dan kunjungi:

```text
http://localhost/cek website/
```
*(atau `http://localhost/cek%20website/`)*

---

## ✨ Fitur Utama

1. **Pengecekan Kilat (High-Speed Multi-cURL)**:
   - Tombol **"Cek Kilat"** memeriksa seluruh 40 website secara simultan (paralel) hanya dalam waktu **~1 - 2 detik** melalui backend PHP `curl_multi`.
2. **Pengecekan Progresif (Concurrent Worker Pool)**:
   - Menampilkan status pengecekan secara berurutan dengan animasi real-time dan bilah kemajuan (*progress bar*).
3. **Pengecekan Individu**:
   - Setiap kartu website memiliki tombol cek ulang tersendiri (ikon 🔄) untuk menguji satu website saja tanpa perlu memeriksa ulang seluruh daftar.
4. **Analisis Status Pintar (Smart Diagnostics)**:
   - 🟢 **Normal / Aktif (HTTP 200 OK)**: Website dapat diakses langsung oleh masyarakat/publik dengan lancar.
   - 🟡 **Aktif Terproteksi (Cloudflare / WAF - HTTP 403 / Turnstile)**: Website aktif dan responsif, namun dilindungi oleh CDN Cloudflare WAF untuk menangkal serangan siber/DDoS. Pengunjung melalui browser normal dapat mengaksesnya setelah verifikasi bot otomatis.
   - 🔵 **Dialihkan (Redirect HTTP 301 / 302)**: Website mengalihkan ke URL tujuan lain (misal dari HTTP ke HTTPS).
   - ⚪ **DNS / Domain Belum Terdaftar**: Hostname tidak dapat ditemukan oleh server DNS (NXDOMAIN) atau salah ketik domain.
   - 🔴 **Gangguan Server / Timeout**: Server tujuan down (HTTP 500/502/503) atau koneksi RTO (Request Time Out).
5. **Rekomendasi Diagnosa Otomatis**:
   - Sistem secara otomatis mendeteksi kesalahan penulisan (*typo*) pada URL dan memberikan saran perbaikan langsung pada kartu website.
6. **Auto Refresh Otomatis**:
   - Pilihan pemantauan berkala otomatis setiap **30 Detik**, **1 Menit**, atau **5 Menit** dengan penghitung waktu mundur (*countdown*).
7. **Pencarian & Filter Cepat**:
   - Filter berdasarkan status: *Semua*, *Normal*, *Terproteksi WAF*, *Ada Kendala*, dan *DNS Belum Aktif*.
   - Kotak pencarian responsif (bisa mencari nama polres, kota, atau URL). Pintasan keyboard: **`Ctrl + K`**.
8. **Dua Mode Tampilan**:
   - **Tampilan Grid (Kartu Interaktif)**: Visual modern dengan indikator lampu status dan metrik lengkap.
   - **Tampilan Tabel Kompak**: Tampilan ringkas untuk pemantauan data jumlah banyak dalam satu layar.
9. **Manajemen Data (CRUD)**:
   - Tambah website baru ke daftar pemantauan.
   - Edit nama atau perbaiki alamat URL yang salah.
   - Hapus website dari daftar.
   - Tombol **Reset ke Default** jika ingin mengembalikan ke daftar awal 40 website.
10. **Export Laporan ke CSV / Excel**:
    - Unduh laporan hasil pemantauan terkini lengkap dengan timestamp, kode HTTP, latensi, dan IP server.

---

## 📁 Struktur Berkas

```
c:\xampp\htdocs\cek website\
├── index.php                # Antarmuka dashboard utama
├── README.md                # Dokumentasi petunjuk penggunaan
├── api\
│   ├── check.php            # Endpoint pengecekan tunggal (detail JSON)
│   ├── check_all.php        # Endpoint pengecekan massal paralel (cepat ~1.5s)
│   ├── sites.php            # Endpoint manajemen data website (CRUD)
│   └── export.php           # Generator unduhan laporan CSV
├── data\
│   ├── sites.json           # Data aktif daftar website
│   └── default_sites.json   # Cadangan daftar 40 website awal
└── assets\
    ├── css\
    │   └── style.css        # Desain custom tema dark cyber police
    └── js\
        └── app.js           # Mesin logika aplikasi, filter, dan async checking
```

---

## 📌 Catatan Diagnosa untuk 40 Website Awal

Berdasarkan hasil pemindaian langsung:
- **Sebagian besar website (26 website)** aktif di balik perlindungan **Cloudflare CDN / WAF**. Website tersebut aman dan berfungsi normal ketika diakses dari peramban (browser).
- **Beberapa website** merespons langsung **HTTP 200 OK** (misalnya Polres Jombang, Polres Madiun, Polres Magetan).
- **Beberapa website** mengalami kendala DNS karena penulisan domain:
  - **Trenggalek**: Pada daftar awal tertulis `tribatanews` (kurang huruf 'r'). Domain aktifnya adalah `http://tribratanews.restrenggalek.jatim.polri.go.id/`.
  - **Sumenep**: Pada daftar awal berakhiran `.polres.go.id`. Domain aktifnya adalah `http://tribratanews.ressumenep.jatim.polri.go.id/`.
  - Anda dapat mengklik tombol **"Detail" -> "Edit Data"** pada kartu website terkait untuk memperbarui URL kapan saja!
