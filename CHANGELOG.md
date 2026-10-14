# Changelog

Semua perubahan penting pada proyek **Sistem Manifest Lisensi Software USN Kolaka** akan dicatat dalam berkas ini.

Format penulisan changelog ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/), dan proyek ini mematuhi [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.3] - 2026-10-14

### Fixed
- **Peluncur Skrip Batch Agen Windows (`script/agent/*.bat`):**
  - Memperbaiki elevasi hak akses Administrator (UAC) pada `2-Pasang-Jadwal-Otomatis.bat` dan `3-Hapus-Jadwal-Otomatis.bat` dengan memanggil `cmd.exe` secara elevated menggunakan `[char]34`, memperbaiki kegagalan fatal PowerShell saat mencoba menjalankan berkas `.bat` secara langsung dengan `Start-Process -FilePath`.
  - Memperbaiki `1-Jalankan-Scan-Sekarang.bat` agar berjalan dalam mode `-Mode manual -ScanMode manual` dan menambahkan validasi keberadaan file `config.json` sebelum eksekusi.
  - Menghapus perintah `Clear-Host` pada `scanner.ps1` agar konsol Windows tidak terhapus tiba-tiba dan riwayat pesan error/status tetap terbaca jelas oleh operator.
- **Ketahanan Agen Pemindai PowerShell (`script/agent/scanner.ps1`):**
  - Mencegah error terminasi PowerShell `Method invocation failed because [System.Int32] does not contain a method named 'Trim'` saat membaca data DWORD / integer dari Windows Registry dengan melakukan casting `[string]` terlebih dahulu sebelum memanggil `.Trim()`.
  - Mencegah crash `You cannot call a method on a null-valued expression` saat properti WMI/CIM bernilai null (`Manufacturer`, `Model`, `SerialNumber`, `DNSHostName`).
  - Menambahkan fallback adapter jaringan dan pengambilan MAC address yang aman jika `IPEnabled = True` belum aktif.
  - Menstandarkan format tanggal UTC ISO 8601 menggunakan `InvariantCulture` agar kebal terhadap pengaturan regional / bahasa Windows.
- **Normalisasi Validasi Payload Scan & Registrasi (`ScanController` & `AgentRegisterController`):**
  - Menangani error 422 akibat format MAC address yang beragam (Cisco dot notation `0011.2233.4455`, dash separated `00-11-...`, bare 12 hex `001122334455`, atau lowercase) dan mengonversinya ke format standar colon uppercase.
  - Menangani hostname yang kosong dengan fallback aman ke hostname komputer yang telah terotentikasi.
  - Menangani anomali PowerShell 5.1 yang melakukan serialisasi array software 1 item menjadi JSON object tunggal, dengan membungkusnya kembali menjadi array sequential.
  - Memotong string hardware/software yang melebihi batas 255 karakter secara aman tanpa menggagalkan pemindaian unit.
  - Menormalkan nilai `scan_mode` secara case-insensitive.
  - Menangani nilai floating point atau string pada field numerik `ram_gb`, `disk_total_gb`, dan `disk_free_gb`.
- **Pengujian:**
  - Menambahkan feature test komprehensif pada `ScanPipelineTest` dan `AgentLabRegistrationTest` untuk memverifikasi ketahanan terhadap seluruh anomali format perangkat Windows.

## [1.1.2] - 2026-10-14

### Fixed
- **Pengelolaan Katalog Software Admin (`SoftwareDataController` & Views):**
  - Mengubah aturan validasi `UpdateSoftwareRequest` untuk field `category` dan `status` menjadi `sometimes|required`, sehingga update kategori saja atau status saja dapat diproses dengan sukses.
  - Memisahkan tombol **Edit Detail** (Sheet drawer dengan ikon pensil) keluar dari menu dropdown titik tiga untuk mencegah konflik event click pada Alpine.js.
  - Mengaktifkan kembali field pilihan `status` (`Unreviewed`, `Whitelist`, `Blacklist`) pada form Edit Detail Katalog Software.
  - Menambahkan dukungan opsi kategori lengkap (`Shareware`, `Other`) pada formulir edit detail dan filter kategori katalog software.
  - Menjadikan menu dropdown titik tiga (⋮) sebagai aksi cepat ubah status (`Set Whitelist`, `Set Blacklist`, `Set Unreviewed`).
- **Pengujian:**
  - Menambahkan feature test komprehensif pada `SoftwareCatalogManagementTest` untuk memastikan admin dapat memperbarui kategori secara terpisah, status secara terpisah, atau seluruh metadata sekaligus.


### Fixed
- **Validasi dan Penanganan Payload Scan Agen (`ScanController`):**
  - Menambahkan sanitasi otomatis (`normalizeScanPayload`) untuk mengonversi nilai numerik `version`, `vendor`, dan `install_date` (DWORD dari Windows Registry) menjadi string yang valid sebelum validasi.
  - Menangani objek `System.Version` dari Windows Store Apps (`Get-AppxPackage`) dan mengonversinya menjadi representasi string versi.
  - Memfilter secara aman entri software dengan nama kosong tanpa menggagalkan pemindaian unit komputer secara keseluruhan.
  - Menangani nilai prosesor bertipe array dari workstation dengan multi-socket CPU.
  - Menambahkan logging warning terperinci saat validasi payload scan gagal untuk kemudahan audit.
- **Ketahanan Skrip Scanner Agen (`script/agent/`):**
  - Mengonversi seluruh properti software secara eksplisit ke string (`[string]`) pada `scanner.ps1`.
  - Menambahkan pembacaan response stream error server pada blok `catch` agar detail pesan error validasi langsung tampil di konsol operator lab.
  - Menambahkan proteksi pengecekan file ekstraksi (`scanner.ps1`, `setup_tasks.ps1`) dan perbaikan elevasi hak akses Administrator (UAC) pada berkas batch launcher (`1-Jalankan-Scan-Sekarang.bat`, `2-Pasang-Jadwal-Otomatis.bat`, `3-Hapus-Jadwal-Otomatis.bat`).
- **Image Docker & Scheduled Command:**
  - Menambahkan paket `mariadb-client` pada `Dockerfile` untuk menyediakan binary `mysqldump` yang dibutuhkan oleh `spatie/laravel-backup`.
  - Menambahkan flag `--force` pada perintah `activitylog:clean` di `routes/console.php` agar berjalan otomatis di lingkungan production tanpa prompt konfirmasi interaktif.

## [1.1.0] - 2026-10-14

### Added
- **Tampilan Lisensi Fakultas & Pengambilan Product Key untuk Lab:**
  - Menambahkan menu dan halaman navigasi baru **"Lisensi Fakultas"** (`/lab/licenses`) khusus untuk role `kepala_lab` dan `staff_lab` via `LabLicenseController`.
  - Fitur penyingkapan Product Key (*reveal*) on-demand secara aman melalui endpoint terenkripsi `POST /lab/licenses/{license}/key` dengan pembatasan request (*rate limiting* 10 req/menit).
  - Komponen interaktif Alpine.js dengan penyamaran default (`masked_license_key`), timer auto-hide otomatis setelah 30 detik, serta tombol salin ke clipboard.
  - Audit trail komprehensif: setiap tindakan penyingkapan kunci lisensi dicatat ke `activity_log` beserta informasi pengguna dan fakultas terkait tanpa menyimpan teks mentah key.

### Fixed
- **Penanganan Enkripsi Ganda & Masking License Key:**
  - Memperbaiki `UserTestingSeeder` agar tidak memanggil `Crypt::encryptString()` secara manual pada `LicenseInventory` yang telah memiliki Eloquent cast `'license_key' => 'encrypted'`.
  - Menambahkan auto-unwrap pada accessor model `LicenseInventory` untuk kompatibilitas data warisan dan memastikan kunci tanpa format tanda hubung (`-`) tetap tersensor dengan aman.
- **Isolasi Service Docker Testing:**
  - Mengubah penamaan service backend testing menjadi `testing-app` dan `testing-web` dengan konfigurasi upstream Nginx khusus untuk mencegah tabrakan routing pada Traefik host.

## [1.0.8] - 2026-10-14

### Added
- **Dokumentasi ERD Akademik & Chen Notation:**
  - Penyusunan dokumen bab metodologi dan spesifikasi ERD tesis secara menyeluruh di `docs/diagrams/erd/`.
  - Diagram ERD Chen notation format Draw.io dan Mermaid, serta suite audit konsistensi otomatis.

### Fixed
- **Isolasi Jaringan Docker Testing:**
  - Menambahkan berkas `docker-compose.testing.yml` dengan isolasi jaringan internal untuk mencegah tabrakan resolusi DNS pada container testing multi-instance.
  - Memperbarui filter noise software agar mengklasifikasikan .NET Toolset sebagai system junk.

## [1.0.7] - 2026-10-13

### Fixed
- **Kebijakan Penarikan Image Docker Production:**
  - Menambahkan `pull_policy: always` pada konfigurasi service di `docker-compose.prod.yml` untuk menjamin server deploy selalu menggunakan image kontainer terbaru dari GHCR.

## [1.0.6] - 2026-10-13

### Added
- **Klasifikasi Otomatis Katalog Software & Pembersihan Junk:**
  - Layanan `SoftwareCatalogService` dan perintah Artisan `software:reclassify` untuk kategorisasi otomatis (*Commercial*, *Freeware*, *Open Source*, *System Component*).
  - Filter noise pemindai OS untuk menyingkirkan paket pembaruan internal dan komponen sistem dari perhitungan kepatuhan lisensi.

## [1.0.5] - 2026-10-13

### Fixed
- **Validasi Integritas Paket ZIP Scanner Agent:**
  - Memperbarui `AgentDownloadController` agar memvalidasi kelengkapan seluruh berkas skrip pemindai dan one-click batch launcher (`1-Jalankan-Scan-Sekarang.bat`, `2-Pasang-Jadwal-Otomatis.bat`, `3-Hapus-Jadwal-Otomatis.bat`) secara ketat sebelum membuat arsip ZIP, mencegah pengemasan berkas yang tidak lengkap secara diam-diam.
  - Memperbarui konfigurasi `.gitattributes` dengan aturan `eol=crlf` khusus berkas batch (`*.bat` dan `*.cmd`) guna menjaga kompatibilitas eksekusi skrip command prompt di sistem operasi Windows.

### Changed
- **Penyempurnaan Panduan Halaman Unduhan Scanner:**
  - Memperbarui antarmuka pengguna `agent-download.blade.php` agar menampilkan daftar berkas batch launcher siap pakai secara transparan serta petunjuk eksekusi klik ganda tanpa harus menjalankan skrip PowerShell manual.

## [1.0.4] - 2026-10-13

### Added
- **Perencanaan & Instrumen User Testing Komprehensif:**
  - Menyusun dokumen rencana dan protokol pengujian pengguna (`docs/Testing/user-testing.md` dan format Word `perencanaan-user-testing.docx`) mengacu pada metodologi *Handbook of Usability Testing* (Rubin & Chisnell).
  - Menyediakan 16 skenario tugas berbasis peran (`admin`, `pimpinan`, `kepala_lab`, `staff_lab`) dengan kriteria keberhasilan yang terukur.
  - Lembar observasi evaluator (Completion Rate, Time-on-Task, Error Rate/Assistances).
  - Instrumen evaluasi dukungan keputusan manajerial dengan 4 skenario kasus (*case vignettes*) dan metrik *Decision Accuracy Rate* (DAR).
  - Kuesioner System Usability Scale (SUS 10 butir pertanyaan skala Likert) dan panduan analisis data statistik deskriptif untuk tesis.
  - Formulir persetujuan partisipan (*Informed Consent Form*) dan skrip fasilitator pengujian.
- **Diagram Arsitektur & Aliran Sistem:**
  - Diagram Konteks sistem manifest lisensi (`docs/diagrams/dfd/diagram-konteks.drawio` dan `docs/diagrams/dfd/diagram-konteks.md`).
  - Data Flow Diagram (DFD) Level 1 (`docs/diagrams/dfd/dfd-level-1.drawio` dan `docs/diagrams/dfd/dfd-level-1.md`).
  - Data Flow Diagram (DFD) Level 2 per modul proses (`docs/diagrams/dfd/dfd-level-2.drawio` dan `docs/diagrams/dfd/dfd-level-2.md`).
  - Flowmap proses bisnis sistem manifest lisensi software (`docs/diagrams/flowmap/flowmap.drawio` dan `docs/diagrams/flowmap/flowmap.md`).
- **Dataset Pengujian Deterministik (User Testing Seeder):**
  - Membuat `UserTestingSeeder` untuk menginisialisasi skenario pengujian langsung secara deterministik (2 lab, 30 komputer, riwayat scan, variasi lisensi Office/Photoshop/AutoCAD, temuan pelanggaran KMSPico, dan alur persetujuan laporan pending/approved).

### Changed
- **Penyempurnaan Tampilan Navigasi Sidebar:**
  - Menambahkan custom scrollbar ramping `.sidebar-scrollbar` pada `resources/css/app.css` agar navigasi sidebar tampak lebih bersih dan modern.
  - Memperbarui `resources/views/components/layout/side-bar.blade.php` untuk integrasi scrollbar kustom dan ekspresi dinamis label menu laporan.

## [1.0.3] - 2026-10-02

### Changed
- **Arsitektur Shared Database & Redis (Dokploy Optimization):**
  - Menghapus container dedicated `db` (MySQL 8.0) dan `redis` (Redis 7) dari `docker-compose.prod.yml` guna menghemat alokasi RAM server VPS (~400MB+).
  - Mengintegrasikan stack aplikasi ke jaringan eksternal `dokploy-network` untuk terhubung ke shared instance MySQL dan Redis di Dokploy.
  - Menghapus hardcoded `DB_HOST=db` dan `REDIS_HOST=redis` pada service `app`, `worker`, dan `cron` agar sepenuhnya membaca konfigurasi dinamis dari berkas lingkungan `.env`.
  - Memperbarui template konfigurasi `.env.production.example` dan `.env.example` dengan panduan shared host serta penambahan variabel `REDIS_DB` dan `REDIS_PREFIX` untuk isolasi data multi-aplikasi.

## [1.0.2] - 2026-10-02

### Added
- **One-Click Batch Launcher untuk Windows Agent:**
  - Menambahkan `1-Jalankan-Scan-Sekarang.bat` untuk menjalankan pemindaian hardware & software langsung dengan klik ganda tanpa terhalang PowerShell ExecutionPolicy.
  - Menambahkan `2-Pasang-Jadwal-Otomatis.bat` dengan fitur auto-elevasi Administrator (UAC prompt) untuk mendaftarkan jadwal pemindaian harian & polling otomatis ke Windows Task Scheduler.
  - Menambahkan `3-Hapus-Jadwal-Otomatis.bat` untuk mempermudah pelepasan/uninstall tugas Task Scheduler secara bersih.
  - Memperbarui paket unduhan ZIP agent pada `AgentDownloadController` dan panduan `instruksi.txt` agar mudah digunakan oleh petugas laboratorium.

## [1.0.1] - 2026-10-02

### Fixed
- **Paket Scanner Agent:** Menghapus direktori `script` dari `.dockerignore` agar file script PowerShell pemindai client (`script/agent/scanner.ps1` dan `setup_tasks.ps1`) disertakan di dalam Docker image dan dapat diunduh tanpa error 404.

## [1.0.0] - 2026-10-01

### Added
- **Otomatisasi Docker & Deployment Production:**
  - Multi-stage `Dockerfile` berbasis `php:8.4-fpm-alpine`, Node 20, dan Composer 2.7.
  - Script startup otomatis (`.docker/entrypoint.sh`) untuk sinkronisasi build assets, migrasi database otomatis (`migrate --force`), dan optimasi cache Laravel (`config`, `route`, `view`, `event`).
  - Konfigurasi PHP production (`.docker/php/php-production.ini`) dengan OPcache aktif, batas upload 50MB, dan zona waktu WITA (`Asia/Makassar`).
  - Konfigurasi Nginx container (`.docker/nginx/conf.d/app.conf`) dilengkapi kompresi Gzip, security headers, tuning buffer FastCGI, dan caching 1 tahun untuk asset build Vite.
  - Berkas `docker-compose.prod.yml` untuk server VPS dengan isolasi jaringan internal, integrasi image GitHub Container Registry (GHCR), dan binding port aman ke `127.0.0.1:8080`.
  - Berkas konfigurasi lingkungan `.env.production.example` dan panduan Host Reverse Proxy Nginx dengan SSL Let's Encrypt (`.docker/nginx/host-reverse-proxy.example.conf`).
  - Pipeline GitHub Actions (`.github/workflows/deploy.yml`) untuk build dan push image Docker multi-platform secara otomatis ke GHCR setiap kali git tag release (`v*`) dipublikasikan.

- **Struktur Organisasi Bertingkat & Scoping Data:**
  - Hirarki entitas Fakultas (`Faculty`) -> Laboratorium (`Laboratory`) -> Komputer (`Computer`).
  - Scoping isolasi data otomatis berbasis laboratorium (`ScopedByLaboratory` trait dan `User::getAccessibleLaboratoryIds()`).
  - Manajemen master data Fakultas dan Laboratorium (CRUD lengkap dan filter pencarian).

- **Otentikasi & Role-Based Access Control (RBAC):**
  - Implementasi 4 peran pengguna via Spatie Permission: `admin`, `pimpinan`, `kepala_lab`, dan `staff_lab`.
  - Guard ganda: `web` (User berbasis sesi) dan `sanctum` (Computer model sebagai Authenticatable).
  - Pembatasan rute dan navigasi sidebar adaptif berdasarkan peran.

- **Integrasi Scanner Agent & Pipeline Scan:**
  - Endpoint REST API agen: registrasi komputer (`/api/agent/register`), kirim hasil scan (`/api/scan-result`), dan polling perintah scan (`/api/agent/scan-command`).
  - Script scanner PowerShell (`script/agent/`) untuk instalasi pada client workstation laboratorium.
  - Antrean asinkron Laravel Horizon dengan Redis: `ProcessScanResultJob` dan `GenerateComplianceReportJob`.
  - Pelacakan perubahan software antar-sesi scan (`SoftwareChangeDetectionService`: added, removed, changed, returned).
  - Normalisasi nama software dan pemfilteran noise update OS (`SoftwareFilterService`).

- **Manajemen Inventaris Lisensi & Kepatuhan:**
  - Manajemen katalog master software (`SoftwareCatalog`) dan inventaris lisensi (`LicenseInventory`).
  - Enkripsi kunci lisensi pada database (`encrypted` cast) dan audit trail akses kunci lisensi via `spatie/laravel-activitylog`.
  - Sistem alokasi lisensi dari tingkat universitas ke fakultas dengan validasi sisa kuota.
  - Kalkulasi status kepatuhan terpusat (`LicenseComplianceService`) mencakup status Compliant, Under-Licensed, Over-Licensed, Whitelist, dan Blocked/Piracy.

- **Pusat Laporan & Dashboard Eksekutif:**
  - Dashboard analitik untuk Admin dan Pimpinan dengan grafik tren (ApexCharts & Chart.js) serta filter periode.
  - Alur persetujuan laporan kepatuhan laboratorium (`ReportSubmission` & `ReportApproval`) antara Admin dan Kepala Lab.
  - 8 laporan komprehensif yang dapat diekspor ke format PDF (DomPDF) dan Excel (PhpSpreadsheet):
    - Laporan Kepatuhan Lisensi
    - Laporan Kebutuhan Lisensi (Executive Report)
    - Laporan Rekap Monitoring
    - Laporan Perubahan Software
    - Laporan Inventaris Lisensi
    - Laporan Inventaris Komputer
    - Laporan Alokasi Lisensi Fakultas
    - Laporan Katalog Software

- **Testing & Quality Assurance:**
  - 358 pengujian unit dan feature tests menggunakan Pest PHP dengan 1.743 asersi.
  - Pengujian Golden Path komprehensif untuk semua peran (`AdminGoldenPathTest`, `PimpinanGoldenPathTest`, `KepalaLabGoldenPathTest`, `StaffLabGoldenPathTest`).
  - Suite pengujian End-to-End (E2E) menggunakan Playwright (`npm run test:e2e`).
