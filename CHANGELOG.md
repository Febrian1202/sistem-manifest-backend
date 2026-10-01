# Changelog

Semua perubahan penting pada proyek **Sistem Manifest Lisensi Software USN Kolaka** akan dicatat dalam berkas ini.

Format penulisan changelog ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/), dan proyek ini mematuhi [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

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
