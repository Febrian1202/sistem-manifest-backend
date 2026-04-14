# Project Overview: Sistem Manifest Backend

File ini berisi rangkuman high-level mengenai arsitektur, alur kerja, dan fungsionalitas dari project **Sistem Manifest Backend**.

## 1. Tentang Projek & Fungsinya
Sistem Manifest Backend adalah sebuah aplikasi **IT Asset Management (ITAM)** dan **Software License Compliance** berbasis **Laravel**. 
Aplikasi ini berfungsi untuk memantau, mendata, dan mengaudit aset komputer beserta perangkat lunak (software) yang terinstal di setiap perangkat dalam sebuah jaringan/organisasi. Tujuan utamanya adalah memastikan bahwa perusahaan mematuhi aturan lisensi software (compliance), mendeteksi penggunaan software ilegal/bajakan, serta mengidentifikasi instalasi software yang masuk daftar hitam (blacklist).

## 2. Bagaimana Alur Kerjanya (Workflow)
Sistem ini bekerja dengan skema **Agent-Server**:
1. **Penerimaan Data (Scanning):** Komputer client (melalui agent script/aplikasi) mengirimkan data spesifikasi hardware lengkap dengan daftar software yang terinstal ke endpoint API (`POST /api/scan-result`).
2. **Filtering & Pencatatan:** 
   - Backend memproses data yang masuk. Jika ada hardware baru, sistem akan merekamnya (atau mengupdate jika sudah ada).
   - Daftar software akan difilter menggunakan logic pengecekan keyword. Software sampah (seperti redistributable, runtime, driver) akan diabaikan. Keyword prioritas (Crack, Torrent, Steam) akan diberi perhatian khusus.
   - Software yang lolos filter akan didaftarkan secara otomatis ke dalam **Katalog Software**.
   - Sistem mencatat bukti instalasi (Discovery) yang mengaitkan komputer tersebut dengan software terkait.
3. **Manajemen oleh Admin:** Admin melalui dashboard web (UI) dapat:
   - Mereview daftar komputer.
   - Mendefinisikan status software di katalog (Whitelist, Blacklist, Unreviewed) dan Kategorinya (Freeware, Commercial, OpenSource).
   - Memasukkan data pembelian lisensi untuk software komersial.
4. **Audit Kepatuhan (Compliance Audit):** Sistem secara otomatis membandingkan jumlah instalasi dari sebuah software komersial berbanding dengan jumlah kuota lisensi yang dibeli. Jika jumlah instalasi melebihi batas lisensi, sistem akan menandainya sebagai defisit/ilegal.

## 3. Database & Relasinya
Konsep database dirancang dalam rancangan yang saling berelasi dengan 5 tabel utama:

- **`computers` (Aset Komputer):** Menyimpan data spesifikasi teknis (OS, RAM, Disk, Processor, IP, MAC Address).
- **`software_catalogs` (Katalog Software Master):** Menyimpan rincian standar software yang dikenal beserta kategorinya (`Freeware`, `Commercial`, `OpenSource`) dan statusnya (`Whitelist`, `Blacklist`, `Unreviewed`).
- **`software_discoveries` (Junction & History Instalasi):** Tabel yang mencatat bahwa sebuah komputer (dari `computers`) menginstal suatu software dari (katalog `software_catalogs`).
- **`license_inventories` (Inventori Lisensi):** Menyimpan catatan pembelian lisensi untuk software komersial yang terdaftar di `software_catalogs` beserta kuota limitnya.
- **`compliance_reports` (Laporan Kepatuhan Historis):** Menyimpan rekam jejak dan snapshot status kepatuhan dari masing-masing komputer (`Safe`, `Warning`, `Critical`) beserta total software bajakan/terlarang.

**Relasi:**
- `Computer` *has many* `SoftwareDiscoveries`
- `SoftwareCatalog` *has many* `SoftwareDiscoveries`
- `SoftwareCatalog` *has many* `LicenseInventories`
- `Computer` *has many* `ComplianceReports`

## 4. Routing
Routing dibagi menjadi dua area utama, yaitu **API** (untuk Agent) dan **Web** (untuk Admin Dashboard).

- **API (`routes/api.php`):**
  - `POST /api/scan-result` : Endpoint utama untuk menerima payload JSON berisi spesifikasi hardware dan daftar aplikasi yang diinstal dari agen.

- **Web Admin (`routes/web.php`):** Rute manajemen berdasar views:
  - `GET /dashboard` : Halaman ringkasan/dashboard utama.
  - `GET & PUT /computers` : List komputer dan update info komputer.
  - `GET & PUT /softwares` : List master katalog instalasi dan status kategori.
  - `GET, POST, PUT, DELETE /licenses` : Manajemen riwayat inventori lisensi produk (CRUD).
  - `GET /compliance` : Halaman data summary laporan kepatuhan (audit lisensi berbayar).
  - `GET & POST /reports` : Halaman filter laporan secara keseluruhan dan fitur Export data.

## 5. Controllers
Sistem ini memisahkan logika berdasarkan domain data ke dalam Controllers yang terstruktur:

- **`Api\ScanController` :** Otak utama dari proses ingest data. Menangani validasi hardware & software. Memisahkan otomatis antara program sampah (SDKs, runtimes, drivers) dari target esensial. Jika baru, langsung membuatkan basis katalog (Auto-discovery) lalu menyimpan log install di tabel `SoftwareDiscoveries`.
- **`ComplianceDataController` :** Menjalankan kalkulasi perhitungan "Deficit". Mencari semua software "Commercial" lalu menghitung jumlah diinstall (`discoveries`) dikurangi jumlah beli (`licenses.quota_limit`). Memunculkan data apakah instalasi "Legal" atau "Non-Compliant".
- **`ComputerDataController` :** Untuk melayani view daftar dan edit detail Komputer dari UI.
- **`SoftwareDataController` :** Untuk menangani update klasifikasi dari admin ke sebuah software (merubah status default Unreviewed menjadi Whitelist/Blacklist, atau Freeware menjadi Commercial).
- **`LicenseDataController` :** Menangani rekam tambah kuota bukti beli bagi software commercial.
- **`DashboardController` & `ReportController` :** Logika untuk merangkum statistik secara general dan fungsi eksport laporan untuk direktur/audit IT.
