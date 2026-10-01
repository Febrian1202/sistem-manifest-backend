# Sistem Manifest (USN Kolaka)

Sistem Informasi Manifest Lisensi Software & Manajemen Aset IT Laboratorium Universitas Sembilanbelas November (USN) Kolaka. Sistem ini dirancang untuk mendata aset komputer, memetakan lisensi perangkat lunak resmi, memantau perubahan instalasi software secara periodik, dan mengaudit kepatuhan legalitas software di seluruh fakultas dan laboratorium.

![Build Status](https://img.shields.io/github/actions/workflow/status/Febrian1202/sistem-manifest-backend/deploy.yml?branch=main)
![License](https://img.shields.io/badge/license-MIT-blue)
![PHP](https://img.shields.io/badge/PHP-8.2%2B%20%2F%208.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?logo=tailwindcss&logoColor=white)

---

## 📸 Demo & Tangkapan Layar

> _(Placeholder: Tambahkan screenshot atau GIF dashboard aplikasi di sini)_
> `![Dashboard Preview](/docs/images/dashboard-preview.png)`

---

## ✨ Fitur Utama

- **Role-Based Access Control (4 Peran Pengguna):**
  - **Administrator:** Akses penuh konfigurasi sistem, manajemen akun pengguna, master fakultas & laboratorium, alokasi lisensi, audit kepatuhan, activity log, dan dashboard queue Laravel Horizon.
  - **Pimpinan (Rektorat/Dekanat):** Akses *read-only* tingkat universitas/fakultas untuk dashboard eksekutif, laporan kebutuhan lisensi (*procurement planning*), inventaris komputer, dan rekap kepatuhan.
  - **Kepala Laboratorium (PJ Lab):** Akses terisolasi (*scoped*) sesuai laboratorium yang ditugaskan untuk mereview, menyetujui (*approve*), atau menolak (*reject*) laporan audit berkala, memantau riwayat perubahan software lab, serta mengunduh agent scanner.
  - **Staff Laboratorium (Operator):** Akses terisolasi (*scoped*) untuk memantau inventaris komputer laboratorium dan mengunduh paket agent scanner terkonfigurasi.
- **Hierarki Multi-tier & Data Scoping:** Struktur bertingkat **Fakultas &rarr; Laboratorium &rarr; Komputer &rarr; Sesi Pemindaian**, dengan isolasi data otomatis berbasis trait `ScopedByLaboratory`.
- **Manajemen Lisensi & Alokasi Kuota:** Pencatatan inventaris lisensi software resmi (OEM, Retail, Volume, Subscription), enkripsi aman *license key* dengan auditing reveal log, dan alokasi kuota lisensi bertingkat per fakultas/laboratorium.
- **Audit Kepatuhan & Engine Deteksi:**
  - Sentralisasi kepatuhan melalui `LicenseComplianceService` untuk menghitung kapasitas *entitlement* vs *installed*.
  - Deteksi otomatis software berlisensi resmi, *freeware/open-source* terverifikasi (`config/software_whitelist.php`), dan software ilegal/terlarang (`config/compliance.php`).
- **Deteksi Perubahan Software (Software Change Detection):** Pelacakan komparatif otomatis status software antar sesi pemindaian: *added* (baru diinstal), *removed* (dihapus), *changed* (perubahan versi), dan *returned* (terinstal kembali).
- **Alur Kerja Persetujuan Laporan (Approval Workflow):** Admin dapat membuat dan mendistribusikan laporan kepatuhan ke Kepala Lab. Kepala Lab dapat memeriksa rincian, melihat pratinjau PDF, dan memberikan persetujuan atau catatan penolakan.
- **Dashboard Visual & Grafik Interaktif:** Visualisasi data real-time menggunakan ApexCharts dan Chart.js (status kepatuhan, tren audit, distribusi sistem operasi, dan top software terinstal) serta *cascading filter* (Fakultas &rarr; Laboratorium).
- **Agent Scanner Windows Otomatis:** Script scanner PowerShell (`scanner.ps1`) ringan yang dapat diunduh langsung dari aplikasi dalam bentuk ZIP terkonfigurasi per lab, terintegrasi Windows Scheduled Task (`setup_tasks.ps1`).
- **Background Queue & Orkestrasi Horizon:** Pemrosesan asinkron untuk penerimaan data scan dan kalkulasi audit beban tinggi menggunakan Redis dan Laravel Horizon dashboard (`/horizon`).
- **Keamanan, Audit Trail & Backup:**
  - Enkripsi native untuk kunci lisensi (`encrypted` Eloquent cast) dengan throttling ketat.
  - Pencatatan seluruh aktivitas mutasi sistem via `spatie/laravel-activitylog`.
  - Cadangan otomatis database dan berkas media via `spatie/laravel-backup`.

---

## 🛠️ Prerequisites (Prasyarat)

Sebelum menjalankan proyek ini, pastikan sistem Anda memenuhi persyaratan berikut:

- **PHP** >= 8.2 (Direkomendasikan PHP 8.4)
- **Composer** >= 2.7
- **Node.js** >= 20.x & **NPM**
- **Database:** MySQL 8.0 (Production) atau SQLite (Testing/Development)
- **Redis Server** >= 7.0 (untuk Queue Worker & Caching)
- **Git**
- **Docker & Docker Compose** (Opsional, untuk deployment berbasis kontainer)

---

## 🚀 Panduan Instalasi Docker (Direkomendasikan)

Proyek ini telah dilengkapi arsitektur kontainer multi-stage (PHP 8.4-FPM Alpine, Nginx, MySQL 8, Redis 7, Horizon Worker, dan Cron Scheduler):

1. **Clone repositori:**

   ```bash
   git clone https://github.com/Febrian1202/sistem-manifest-backend.git
   cd sistem-manifest-backend
   ```

2. **Salin file konfigurasi environment:**

   ```bash
   cp .env.example .env
   ```

3. **Build dan jalankan seluruh container:**

   ```bash
   docker compose up -d --build
   ```

4. **Generate Application Key:**

   ```bash
   docker compose exec app php artisan key:generate --show
   ```

   *Salin teks output `base64:...` dan pastikan tersimpan pada variabel `APP_KEY` di file `.env`.*

5. **Jalankan Migrasi dan Seeder Awal:**

   ```bash
   docker compose exec app php artisan migrate --seed
   ```

6. **Akses Aplikasi:**
   Buka peramban di `http://localhost:8080`.
   - Web App: `http://localhost:8080`
   - Horizon Dashboard (Admin only): `http://localhost:8080/horizon`

---

## 💻 Panduan Instalasi Manual (Lokal / Development)

Jika ingin menjalankan secara langsung pada mesin lokal tanpa Docker:

1. **Clone repositori dan masuk ke direktori:**

   ```bash
   git clone https://github.com/Febrian1202/sistem-manifest-backend.git
   cd sistem-manifest-backend
   ```

2. **Instal dependensi PHP:**

   ```bash
   composer install
   ```

3. **Salin konfigurasi environment:**

   ```bash
   cp .env.example .env
   ```

4. **Generate Application Key:**

   ```bash
   php artisan key:generate
   ```

5. **Konfigurasi Database & Redis di `.env`:**

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_manifest
   DB_USERNAME=root
   DB_PASSWORD=

   QUEUE_CONNECTION=redis
   CACHE_STORE=redis
   REDIS_CLIENT=predis
   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   ```

6. **Jalankan Migrasi Database dan Seeder:**

   ```bash
   php artisan migrate --seed
   ```

7. **Instal dependensi Node.js & bangun aset frontend:**

   ```bash
   npm install
   npm run build
   ```

8. **Jalankan Lingkungan Development:**
   Gunakan perintah all-in-one yang menjalankan server, queue worker, tail log, dan Vite secara bersamaan:

   ```bash
   composer dev
   ```

   *Atau jika dijalankan di terminal terpisah:*
   ```bash
   php artisan serve                       # Terminal 1: Web server (http://localhost:8000)
   php artisan horizon                     # Terminal 2: Queue worker
   npm run dev                             # Terminal 3: Vite hot-reload
   ```

---

## 🔑 Akun Default Seeder

Setelah menjalankan `php artisan migrate --seed`, akun berikut siap digunakan. Password default adalah `ManifestUSN_2026!` (dapat dikonfigurasi melalui `DEFAULT_USER_PASSWORD` di `.env`):

| Peran (Role) | Email | Password | Cakupan Akses |
|---|---|---|---|
| **Administrator** | `admin@usn.ac.id` | `ManifestUSN_2026!` | Penuh ke seluruh modul & Horizon |
| **Pimpinan** | `pimpinan@usn.ac.id` | `ManifestUSN_2026!` | Read-only eksekutif seluruh fakultas |
| **Kepala Laboratorium** | `kepalalab@usn.ac.id` | `ManifestUSN_2026!` | Scoped ke Laboratorium Komputer 1 |
| **Staff Laboratorium** | `staff.lab@usn.ac.id` | `ManifestUSN_2026!` | Scoped ke Laboratorium Komputer 1 |

---

## 📡 API Reference (Agent Scanner)

REST API dilindungi oleh Laravel Sanctum dengan model `Computer` sebagai entitas terautentikasi:

### 1. Health Check
- **Endpoint:** `GET /api/ping`
- **Auth:** Public
- **Response:** `{"status": "success"}`

### 2. Registrasi Komputer Klien (Agent Register)
- **Endpoint:** `POST /api/agent/register`
- **Auth:** Public (Rate limit: 5 req/menit)
- **Header:** `Content-Type: application/json`
- **Payload:**
  ```json
  {
    "registration_key": "SECRET_REGISTRATION_KEY",
    "laboratory_id": 1,
    "hostname": "LABKOM1-PC01",
    "mac_address": "00:1A:2B:3C:4D:5E",
    "ip_address": "192.168.10.15",
    "os_name": "Windows 11 Pro 64-bit"
  }
  ```
- **Response:** Mengembalikan token akses Sanctum (`token`) untuk request berikutnya.

### 3. Pengiriman Hasil Pemindaian Software (Scan Result)
- **Endpoint:** `POST /api/scan-result`
- **Auth:** Bearer Token (Sanctum, Rate limit: 60 req/menit)
- **Payload:**
  ```json
  {
    "scan_session_id": "SCAN-20261001-0001",
    "os_name": "Windows 11 Pro",
    "os_version": "23H2",
    "scan_duration_seconds": 18,
    "softwares": [
      {
        "name": "Visual Studio Code",
        "version": "1.92.0",
        "publisher": "Microsoft Corporation",
        "install_date": "2026-02-15"
      }
    ]
  }
  ```

### 4. Polling Perintah Pemindaian Ulang (Scan Command)
- **Endpoint:** `GET /api/agent/scan-command`
- **Auth:** Bearer Token (Sanctum, Rate limit: 60 req/menit)
- **Deskripsi:** Komputer klien melakukan polling untuk mendeteksi apakah administrator meminta pemindaian manual.

---

## 🧪 Pengujian (Automated Testing)

Proyek ini menggunakan **Pest** (PHPUnit 11) dan **Playwright** untuk pengujian menyeluruh:

```bash
# Jalankan seluruh unit & feature test suite (SQLite in-memory, 350+ tests)
composer test

# Jalankan pengujian spesifik
php artisan test --filter=AdminGoldenPathTest
php artisan test --filter=LicenseCalculationAccuracyTest

# Jalankan End-to-End (E2E) testing dengan Playwright (semua 4 peran)
npm run test:e2e
```

---

## 📂 Struktur Proyek

```text
sistem-manifest-backend/
├── app/
│   ├── Console/Commands/        # Custom Artisan Commands (ClearDashboardCache, dll.)
│   ├── Exports/                 # Export Excel & Sheets (LicenseNeeds, SoftwareChanges, Kepatuhan)
│   ├── Http/
│   │   ├── Controllers/         # Web & API Controllers (Admin, Lab, Pimpinan, Scan)
│   │   ├── Middleware/          # Role check, Force HTTPS, Sanitization
│   │   └── Requests/            # Form Request Validation
│   ├── Jobs/                    # Background Jobs (ProcessScanResult, GenerateComplianceReport)
│   ├── Models/                  # Eloquent Models (Computer, LicenseInventory, Faculty, Lab)
│   │   └── Traits/              # ScopedByLaboratory scoping trait
│   ├── Observers/               # Model Observers (Computer, LicenseInventory, SoftwareCatalog)
│   ├── Providers/               # Service Providers (HorizonServiceProvider, AppServiceProvider)
│   ├── Services/                # Business Services (LicenseCompliance, SoftwareChangeDetection, dll.)
│   └── View/Components/         # Reusable Blade UI & Chart Components
├── config/                      # Konfigurasi aplikasi (compliance, software_whitelist, horizon, backup)
├── database/
│   ├── factories/               # Model factories untuk testing
│   ├── migrations/              # Database schema migrations
│   └── seeders/                 # Role, permission, master data, dan sample user seeders
├── e2e/                         # Playwright End-to-End Test Suite (Golden paths per role)
├── lang/id/                     # Lokalisasi Bahasa Indonesia
├── resources/
│   ├── css/ & js/               # Tailwind CSS v4, Alpine.js, ApexCharts, Chart.js
│   └── views/                   # Blade templates (Dashboard, Lab, Reports, Monitoring)
├── routes/
│   ├── api.php                  # Endpoint REST API agent scanner
│   ├── console.php              # Scheduled tasks & console routes
│   └── web.php                  # Web portal routes & role middleware
├── script/agent/                # Script PowerShell Agent Scanner (scanner.ps1, setup_tasks.ps1)
├── .docker/                     # Nginx configurations, PHP INI, dan entrypoint container
├── docker-compose.yml           # Definisi service Docker untuk development/staging
├── docker-compose.prod.yml      # Definisi service Docker production (reverse proxy bound)
└── tests/                       # Pest & PHPUnit Feature & Unit tests
```

---

## ⚙️ Deployment & Continuous Integration

- **CI/CD Workflow (`.github/workflows/deploy.yml`):**
  1. Menjalankan linter code style (`pint`).
  2. Menjalankan test suite otomatis (`composer test`).
  3. Membangun Docker image multi-stage dan mem-push ke GitHub Container Registry (GHCR).
  4. Melakukan deployment otomatis ke server VPS via SSH dengan `docker compose -f docker-compose.prod.yml up -d`.
- **Produksi Queue & Scheduler:** Dikelola otomatis di dalam kontainer `worker` (`php artisan horizon`) dan `cron` (`php artisan schedule:run`).

---

## 🤝 Kontribusi

1. Fork repositori ini.
2. Buat branch fitur baru (`git checkout -b feature/NamaFitur`).
3. Pastikan format kode rapi: `./vendor/bin/pint`.
4. Pastikan semua pengujian lolos: `composer test`.
5. Commit perubahan (`git commit -m 'feat: deskripsi perubahan'`).
6. Push ke branch Anda (`git push origin feature/NamaFitur`).
7. Buat Pull Request ke branch `main`.

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE). Hak Cipta &copy; 2026 Universitas Sembilanbelas November Kolaka.

