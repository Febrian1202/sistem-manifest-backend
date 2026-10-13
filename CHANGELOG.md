# Changelog

Semua perubahan penting pada proyek **Sistem Manifest Lisensi Software USN Kolaka** akan dicatat dalam berkas ini.

Format penulisan changelog ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/), dan proyek ini mematuhi [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

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
  - Diagram Konteks sistem manifest lisensi (`docs/diagram-konteks.drawio` dan `docs/diagram-konteks.md`).
  - Data Flow Diagram (DFD) Level 1 (`docs/dfd-level-1.drawio` dan `docs/dfd-level-1.md`).
  - Data Flow Diagram (DFD) Level 2 per modul proses (`docs/dfd-level-2.drawio` dan `docs/dfd-level-2.md`).
  - Flowmap proses bisnis sistem manifest lisensi software (`docs/flowmap.drawio` dan `docs/flowmap.md`).
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
