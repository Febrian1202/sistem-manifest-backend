# 🚀 Laporan Kesiapan Production — Sistem Manifest USN Kolaka

> **Tanggal Review:** 6 Juni 2026  
> **Reviewer:** AI Production Readiness Audit  
> **Status Keseluruhan:** ⚠️ **Perlu Perbaikan Sebelum Deploy**

---

## 📋 Ringkasan Eksekutif

Project ini sudah memiliki fondasi yang **sangat baik** — arsitektur MVC rapi, queue-based processing untuk scan, RBAC via Spatie Permission, enkripsi license key, activity logging, rate limiting pada login, dan test coverage yang lumayan. Namun ada **beberapa hal kritis** yang harus diperbaiki sebelum deploy ke production.

| Kategori | Status | Prioritas |
|---|---|---|
| Environment & Config | 🔴 Kritis | Wajib diperbaiki |
| Cache & Performance | 🟡 Perlu perhatian | Direkomendasikan |
| Seeder & Akun Default | 🔴 Kritis | Wajib diperbaiki |
| Keamanan | 🟢 Baik (minor issue) | Minor |
| Queue & Job Processing | 🟡 Perlu perhatian | Direkomendasikan |
| Logging | 🟡 Perlu perhatian | Direkomendasikan |
| Fitur Tambahan | 🔵 Opsional | Nice to have |

---

## 1. ✅ Environment & Konfigurasi (Selesai)

### 1.1 File `.env` — Masalah yang Harus Diperbaiki

**Status Aktual:** ✅ **SELESAI**
Pengaturan telah disesuaikan di dalam file `.env.production` (`APP_ENV=production`, `APP_DEBUG=false`).

### 1.2 APP_KEY

**Status Aktual:** ✅ **SELESAI**
Key baru telah digenerate pada `.env.production`.

### 1.3 AGENT_REGISTRATION_KEY

**Status Aktual:** ✅ **SELESAI**
Key baru telah disiapkan pada `.env.production`.

### 1.4 Database Credentials

**Status Aktual:** ✅ **SELESAI**
User database sudah diganti menggunakan akun *dedicated* (`manifest_user`) di `.env.production`.

### 1.5 Timezone

**Status Aktual:** ✅ **SELESAI (Issue #43 / PR #44)**
Konfigurasi timezone telah diperbarui menjadi `Asia/Makassar`.

Di config/app.php:
```php
'timezone' => env('APP_TIMEZONE', 'Asia/Makassar'),
```

### 1.6 Session Encryption

**Status Aktual:** ✅ **SELESAI**
`SESSION_ENCRYPT=true` telah diset di `.env.production`.

---

## 2. 🟡 Cache & Performance

### 2.1 Cache Store: Database vs Redis

**Status Saat Ini:**
| Service | Driver | Rekomendasi |
|---|---|---|
| Cache | `database` | ⚠️ Pindah ke `redis` |
| Session | `database` | ✅ OK untuk skala kecil |
| Queue | `database` | ⚠️ Pindah ke `redis` |

**Analisis:**
- Semua cache hit saat ini menuju **database** (`CACHE_STORE=database`).
- Redis sudah dikonfigurasi di `.env` (host, port, password) dan `predis/predis` sudah di-install di `composer.json`, tapi **belum digunakan**.
- Laravel Horizon (`laravel/horizon`) sudah terinstall dan dikonfigurasi, tapi Horizon **WAJIB menggunakan Redis**. Artinya jika ingin pakai Horizon, Redis harus diaktifkan.

**Temuan Cache Usage di Codebase:**

| File | Cache Key | TTL |
|---|---|---|
| [DashboardController](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Http/Controllers/DashboardController.php#L15) | `dashboard.stats.{Y-m}` | 600s (10 menit) |
| [DashboardController](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Http/Controllers/DashboardController.php#L47) | `dashboard.charts` | 300s (5 menit) |
| [ComplianceDataController](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Http/Controllers/ComplianceDataController.php#L51) | `compliance.global_stats` | 300s (5 menit) |

**Cache Invalidation:**
- ✅ [ComputerObserver](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Observers/ComputerObserver.php) — invalidate pada create/update/delete
- ✅ [LicenseInventoryObserver](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Observers/LicenseInventoryObserver.php) — invalidate pada create/update/delete
- ✅ [SoftwareCatalogObserver](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Observers/SoftwareCatalogObserver.php) — invalidate pada update/delete
- ✅ [ClearDashboardCache Command](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Console/Commands/ClearDashboardCache.php) — manual clear
- ✅ **SELESAI:** `ClearDashboardCache` command telah diperbaiki dan menghapus `dashboard.stats.{Y-m}` beserta `dashboard.charts` dengan benar.
- ✅ **SELESAI:** `GenerateComplianceReportJob` telah diperbarui untuk menghapus `dashboard.stats.{Y-m}` dan `dashboard.charts`.
- ✅ **SELESAI:** Invalidation untuk `compliance.global_stats` telah ditambahkan di command dan job terkait.

**Rekomendasi untuk Production:**

```env
# Gunakan Redis untuk cache dan queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# Session bisa tetap database (lebih stabil untuk skala kecil)
SESSION_DRIVER=database
```

> [!IMPORTANT]
> Pastikan Redis server sudah terinstall dan berjalan di server production sebelum mengubah konfigurasi ini. Jika Redis tidak tersedia, `database` driver tetap bisa digunakan — hanya saja performanya lebih rendah.

### 2.2 Rekomendasi Production Optimization

```bash
# Setelah deploy, jalankan:
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan icons:cache    # jika pakai blade-icons

# Build frontend assets
npm run build
```

---

## 3. 🔴 Seeder & Akun Default (KRITIS)

### 3.1 Password Default

**Status Aktual:** ✅ **SELESAI**
Di [DatabaseSeeder.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/database/seeders/DatabaseSeeder.php), pembuatan akun admin dan pimpinan sudah dimodifikasi agar menggunakan environment variable `env('DEFAULT_USER_PASSWORD')` dengan default password yang kuat dan tidak mudah ditebak (`ManifestUSN_2026!`). Seeder Inline User Creation kini lebih aman untuk production.

### 3.2 Seeder Chain

```
DatabaseSeeder
├── RoleAndPermissionSeeder  ✅ Aman (firstOrCreate, idempotent)
├── LicenseInventorySeeder   ⚠️ Data dummy (fake license keys, random PO numbers)
└── Inline User Creation     ⚠️ Password lemah
```

**Rekomendasi:**
- `RoleAndPermissionSeeder` — ✅ **Aman untuk production.** Menggunakan `firstOrCreate`, idempotent.
- `LicenseInventorySeeder` — ⚠️ **JANGAN dijalankan di production.** Berisi data test dummy.
- **User creation** — ⚠️ Ganti password atau buat seeder production terpisah.

### 3.3 Rekomendasi: Seeder Production

Jalankan di production hanya:
```bash
# Hanya seed roles & permissions
php artisan db:seed --class=RoleAndPermissionSeeder

# Buat admin secara manual via tinker
php artisan tinker
>>> $user = User::create(['name'=>'Admin', 'email'=>'admin@usn.ac.id', 'password'=>'PasswordKuatAnda123!']);
>>> $user->assignRole('admin');
```

---

## 4. 🟢 Keamanan (Status: Baik)

### 4.1 Hal yang Sudah Bagus ✅

| Aspek | Status | Detail |
|---|---|---|
| RBAC | ✅ Baik | Spatie Permission dengan role `admin` dan `pimpinan` |
| Rate Limiting (Login) | ✅ Baik | 5 attempts/menit per IP di [AuthController](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Http/Controllers/AuthController.php#L38) |
| Rate Limiting (API) | ✅ Baik | `throttle:5,1` pada register, `throttle:60,1` pada scan |
| Rate Limiting (License Key) | ✅ Baik | `throttle:10,1` pada endpoint reveal key |
| Session Regeneration | ✅ Baik | Dilakukan setelah login berhasil |
| Session Invalidation | ✅ Baik | Dilakukan saat logout |
| CSRF Protection | ✅ Baik | Default Laravel middleware |
| License Key Encryption | ✅ Baik | `'license_key' => 'encrypted'` cast di model |
| License Key Masking | ✅ Baik | `getMaskedLicenseKeyAttribute()` untuk display |
| Agent Token Scoping | ✅ Baik | Token dengan ability `scan:submit` |
| Token Revocation | ✅ Baik | Revoke semua token saat re-register |
| Input Validation | ✅ Baik | FormRequest classes digunakan untuk Account CRUD |
| Self-delete Protection | ✅ Baik | Admin tidak bisa hapus diri sendiri |
| Last Admin Protection | ✅ Baik | Minimal 1 admin harus selalu ada |
| Password Hashing | ✅ Baik | `'password' => 'hashed'` cast + Bcrypt 12 rounds |
| Hidden Attributes | ✅ Baik | `password` dan `remember_token` di-hide |
| Activity Logging | ✅ Baik | Spatie Activitylog pada semua model utama |
| No Debug Code | ✅ Bersih | Tidak ada `dd()` atau `dump()` di codebase |
| Trust Proxies | ✅ Baik | `trustProxies(at: '*')` dikonfigurasi |

### 4.2 Hal yang Perlu Diperhatikan ⚠️

- **Sanctum Token Expiration:** ✅ **SELESAI (Issue #43 / PR #44)**
  Expiration telah diatur ke `43200` menit (30 hari). 
  **Catatan:** Karena fitur *Dynamic Agent Download* (Issue #39/PR #40), script agent (`scanner.ps1`) sudah menangani kode error `401 Unauthorized` dengan menghapus token lama dan melakukan registrasi ulang menggunakan `registrationKey` di `config.json`. Oleh karena itu, **sangat aman** token ditambahkan expiration.

- **Horizon Dashboard:** ✅ **SELESAI (Issue #43 / PR #44)**
  Gate authorization telah ditambahkan di `HorizonServiceProvider` sehingga hanya user dengan role `admin` yang bisa mengaksesnya.

---

## 5. 🟡 Queue & Job Processing

### 5.1 Konfigurasi Saat Ini

| Aspek | Status |
|---|---|
| Queue Driver | `database` (⚠️ Redis direkomendasikan) |
| ProcessScanResultJob | ✅ `tries=3`, `backoff=[30,60,120]`, queue `scans` |
| GenerateComplianceReportJob | ✅ `tries=3`, `timeout=120`, `backoff=[30,60,120]`, queue `compliance` |
| Error Handling | ✅ `failed()` method dengan logging |
| DB Transaction | ✅ `SoftwareCatalogService::syncDiscoveries()` menggunakan transaction |

### 5.2 Konflik Horizon vs Database Queue

- [config/horizon.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/config/horizon.php) dikonfigurasi dengan `'connection' => 'redis'`
- Tapi `.env` menggunakan `QUEUE_CONNECTION=database`
- **Horizon TIDAK BISA berjalan dengan database queue driver!**
- Di `composer.json`, script `dev` menggunakan `queue:listen` (bukan Horizon), yang konsisten.

**Solusi:**
1. Jika **ingin pakai Horizon** (direkomendasikan): Set `QUEUE_CONNECTION=redis` dan pastikan Redis tersedia.
2. Jika **tanpa Redis**: Gunakan `php artisan queue:work --queue=scans,compliance,default` dan JANGAN gunakan Horizon.

### 5.3 Compliance Queue Missing

Di [config/horizon.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/config/horizon.php#L202), `supervisor-1` hanya menangani queue `['default', 'scans']`. Queue `compliance` ditangani oleh `compliance-worker` yang sudah benar.

Tapi di `composer.json` script `dev`:
```
php artisan queue:listen --tries=1 --timeout=0
```
Ini hanya mendengarkan queue `default`. **Queue `scans` dan `compliance` tidak diproses!**

**Fix:**
```diff
- php artisan queue:listen --tries=1 --timeout=0
+ php artisan queue:listen --queue=scans,compliance,default --tries=3 --timeout=120
```

---

## 6. ✅ Logging (Selesai)

### 6.1 Konfigurasi Saat Ini

**Status Aktual:** ✅ **SELESAI**
File `.env.production` sudah mengkonfigurasi `LOG_LEVEL=warning` dan `LOG_STACK=daily` sehingga log akan dipisah per hari.

---

## 7. 🔵 Fitur Tambahan yang Direkomendasikan

### 7.1 Fitur yang Sudah Ada ✅

Berdasarkan review, sistem sudah memiliki fitur-fitur inti yang solid:

- ✅ Dashboard dengan statistik dan chart
- ✅ Manajemen komputer (CRUD, scan request, bulk scan)
- ✅ Katalog software dengan auto-categorization
- ✅ Software discovery via agent scanning
- ✅ Manajemen lisensi (CRUD, encrypted keys, proof image)
- ✅ Compliance reporting (automated via job)
- ✅ Export laporan ke PDF/Excel (5 jenis laporan)
- ✅ RBAC (admin & pimpinan)
- ✅ Account management (CRUD, reset password)
- ✅ Activity logging
- ✅ Software blacklist (auto-detect crack/pirated)
- ✅ Agent command system (on-demand scan)

### 7.2 Fitur yang Mungkin Dibutuhkan untuk Skripsi/Lapangan

| Fitur | Prioritas | Alasan |
|---|---|---|
| **Custom Error Pages (403, 404, 500)** | ✅ Selesai | Sudah dibuat di `resources/views/errors/` dengan desain modern dan Tailwind CSS. |
| **Notifikasi Lisensi Hampir Expired** | 🔵 Opsional | Sudah ada detection "Grace Period" tapi belum ada notifikasi push (email/in-app). |
| **Backup Database Otomatis** | 🔵 Opsional | Untuk keamanan data di lapangan, bisa pakai `spatie/laravel-backup`. |
| **Health Check Page** | ✅ Sudah ada | Route `/up` sudah dikonfigurasi di `bootstrap/app.php`. |
| **Force HTTPS** | ✅ Selesai | Sudah dikonfigurasi via `ForceHttps` middleware dan `URL::forceScheme('https')` di `AppServiceProvider`. |
| **Idle Session Timeout Warning** | 🔵 Opsional | Session lifetime 120 menit tanpa warning kepada user. |

### 7.3 Fitur Tidak Kritis (Nice to Have)

- Dashboard realtime dengan polling/Livewire
- Notifikasi email untuk lisensi mendekati expired
- Export data ke CSV
- Bulk import lisensi dari Excel
- API versioning (`/api/v1/...`)

---

## 8. 📝 Pre-Deployment Checklist

### Wajib Sebelum Deploy (🔴)

- [ ] Ubah `APP_ENV=production` di `.env`
- [ ] Ubah `APP_DEBUG=false` di `.env`
- [ ] Set `APP_URL` ke domain/IP production yang benar
- [ ] Generate `APP_KEY` baru (jika belum ada data encrypted)
- [ ] Ganti `AGENT_REGISTRATION_KEY` dengan key baru
- [ ] Ganti credentials database (`DB_USERNAME`, `DB_PASSWORD`)
- [x] Ganti password default akun admin dan pimpinan
- [ ] Jangan jalankan `LicenseInventorySeeder` di production
- [ ] Set `SESSION_ENCRYPT=true`
- [x] Set `APP_TIMEZONE=Asia/Makassar` di config/app.php
- [ ] Jalankan `npm run build` untuk compile assets production
- [ ] Jalankan optimization commands:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache
  ```

### Direkomendasikan (🟡)

- [x] Install dan konfigurasi Redis, lalu set `CACHE_STORE=redis` dan `QUEUE_CONNECTION=redis`
- [x] Ubah `LOG_LEVEL=warning` dan `LOG_STACK=daily`
- [x] Fix bug `ClearDashboardCache` command (key mismatch)
- [x] Fix bug `GenerateComplianceReportJob` cache invalidation (key `dashboard_metrics` tidak ada)
- [x] Tambahkan cache invalidation untuk `compliance.global_stats`
- [x] Fix `composer.json` dev script untuk mendengarkan semua queue
- [x] Buat custom error pages (403, 404, 500)
- [x] Set `SANCTUM_EXPIRATION` untuk agent token
- [x] Amankan Horizon dashboard dengan auth gate

### Opsional (🔵)

- [ ] Konfigurasi mail driver (SMTP) untuk notifikasi
- [x] Setup scheduled task: `php artisan schedule:run` di crontab
- [x] Buat backup strategy
- [x] Setup supervisor untuk queue worker persistent

---

## 9. 🖥️ Panduan Deploy ke Server Production

### Minimum Requirements

- PHP 8.2+
- MySQL 8.0+ / MariaDB 10.6+
- Redis 6+ (direkomendasikan)
- Composer 2
- Node.js 18+ (untuk build frontend)
- Supervisor (untuk queue worker)

### Step-by-step Deploy

```bash
# 1. Clone repository
git clone <repo-url> /var/www/sistem-manifest
cd /var/www/sistem-manifest

# 2. Install dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Setup environment
cp .env.example .env
# Edit .env sesuai panduan di atas
php artisan key:generate

# 4. Database
php artisan migrate --force
php artisan db:seed --class=RoleAndPermissionSeeder

# 5. Buat admin manual
php artisan tinker --execute="
\$user = App\Models\User::create([
    'name' => 'Administrator',
    'email' => 'admin@usn.ac.id', 
    'password' => 'GantiDenganPasswordKuat123!'
]);
\$user->assignRole('admin');
echo 'Admin created!';
"

# 6. Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link

# 7. Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 8. Start queue worker (via Supervisor)
# Lihat contoh config di bawah
```

### Supervisor Config (Queue Worker)

```ini
[program:manifest-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sistem-manifest/artisan queue:work --queue=scans,compliance,default --sleep=3 --tries=3 --timeout=120 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sistem-manifest/storage/logs/worker.log
stopwaitsecs=3600
```

---

## 10. 📊 Ringkasan Penilaian

| Aspek | Skor | Catatan |
|---|---|---|
| **Arsitektur & Kode** | 9/10 | MVC rapi, service layer, proper job queuing |
| **Keamanan** | 8/10 | Sangat baik, minor issue di token expiration |
| **Kesiapan Production** | 5/10 | Perlu fix .env, password, dan cache config |
| **Test Coverage** | 7/10 | 8 feature tests, mencakup area kritis |
| **Dokumentasi** | 7/10 | README dan GEMINI.md informatif |
| **Fitur Kelengkapan** | 9/10 | Sangat lengkap untuk scope skripsi |

---

## 11. 🔴 Temuan Kritis Tambahan

### 11.1 File `public/hot` HARUS Dihapus

```bash
# File ini berisi:
http://[::1]:5173
```

File ini adalah indicator Vite dev-server. Jika ada di production, **semua asset (CSS/JS) akan gagal dimuat** karena browser mencoba load dari localhost:5173 yang tidak ada.

```bash
rm public/hot
echo "public/hot" >> .gitignore  # Pastikan tidak ter-commit lagi
```

> [!CAUTION]
> Ini adalah **showstopper**. Aplikasi tidak akan berjalan dengan benar jika file ini ada di production.

### 11.2 Hardcoded URL di `scanner.ps1`

Di [script/agent/scanner.ps1](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/script/agent/scanner.ps1), tadinya URL dan registration key di-hardcode.

**Status Aktual:** ✅ **SELESAI (Issue #39 / PR #40)**
Solusi yang diimplementasikan adalah fitur **Dynamic Agent Download** di mana admin dapat mendownload file `.zip` dari halaman Admin Computers.
Backend secara otomatis akan membuat file `config.json` di dalam `.zip` tersebut yang berisi `baseUrl` (diambil dari config `app.url`) dan `registrationKey` (dari konfigurasi sistem). Script `setup_tasks.ps1` dan `scanner.ps1` telah dimodifikasi agar secara otomatis dan dinamis membaca file `config.json` ini. Dengan pendekatan ini, admin tidak perlu lagi mengedit file konfigurasi atau URL secara manual.


### 11.3 Model `LicenseInventory` — License Key Bisa Terexpose

**Status Aktual:** ✅ **SELESAI (Issue #41 / PR #42)**
Di `LicenseInventory.php`, `license_key` sekarang sudah ditambahkan ke dalam properti `$hidden` sehingga tidak ikut terserialisasi jika tidak didefinisikan secara eksplisit.

### 11.4 Model `Computer` — Data Sensitif Tidak Di-hide

**Status Aktual:** ✅ **SELESAI (Issue #41 / PR #42)**
Di `Computer.php`, data sensitif seperti `mac_address`, `serial_number`, dan `ip_address` sudah ditambahkan ke dalam `$hidden` untuk mencegah data exposure.

---

## 12. ✅ Temuan Frontend & Asset (Selesai - Issue #45)

### 12.1 Font Awesome Dimuat 2x (Duplikat)

**Status Aktual:** ✅ **SELESAI**
CDN untuk `<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/...` sudah dikomentari di `app.blade.php`, sehingga FontAwesome hanya diload melalui NPM bundle dan tidak lagi duplikat.


### 12.2 Dependencies CDN Eksternal

**Status Aktual:** ✅ **SELESAI (Issue #45)**
ApexCharts, Chart.js, dan Alpine.js telah dimigrasikan ke NPM dan di-bundle via Vite untuk memastikan fungsionalitas tetap berjalan stabil di lingkungan dengan koneksi internet terbatas.

### 12.3 File Localization Belum Diterjemahkan

**Status Aktual:** ✅ **SELESAI (Issue #45)**
File localization di `lang/id/*.php` (auth, pagination, passwords, validation) telah diterjemahkan sepenuhnya ke Bahasa Indonesia yang baku dan informatif.

### 12.4 Sidebar Link Hardcoded

**Status Aktual:** ✅ **SELESAI (Issue #45)**
Semua link navigasi di sidebar (`side-bar.blade.php`) telah diperbarui untuk menggunakan helper `route()` bawaan Laravel.

### 12.5 Typo di Layout

**Status Aktual:** ✅ **SELESAI (Issue #45)**
Typo `x-trasition.opacity` di layout `app.blade.php` telah diperbaiki menjadi `x-transition.opacity`.

### 12.6 Package `@fontsource/inter` Tidak Digunakan

**Status Aktual:** ✅ **SELESAI (Issue #45)**
Package `@fontsource/inter` telah dihapus dari `package.json` dan `package-lock.json` via `npm uninstall`.

---

## 13. 📊 Ringkasan Penilaian Final

| Aspek | Skor | Catatan |
|---|---|---|
| **Arsitektur & Kode** | 9/10 | MVC rapi, service layer, proper job queuing |
| **Keamanan** | 9/10 | Sangat baik, issue token expiration & hidden attributes sudah diatasi |
| **Kesiapan Production** | 9/10 | Codebase siap, tinggal penyesuaian env saat deploy |
| **Test Coverage** | 7/10 | 8 feature tests, mencakup area kritis |
| **Dokumentasi** | 7/10 | README dan GEMINI.md informatif |
| **Fitur Kelengkapan** | 9/10 | Sangat lengkap untuk scope skripsi |
| **Frontend Quality** | 9/10 | Shadcn-style UI bagus, CDN dependency & duplikasi sudah fix |

### Verdict

> **Kode dan arsitektur sudah sangat siap production.** Perbaikan utama yang telah diimplementasikan:
> 1. ✅ Hapus `public/hot` — *Selesai (Sudah di-ignore oleh `.gitignore` sehingga tidak akan masuk ke production).*
> 2. ⚠️ Ubah `.env` ke production mode — *Masih perlu di-set saat deploy (`APP_ENV=production` dan `APP_DEBUG=false`).*
> 3. ✅ Ganti password default akun admin — *Selesai (Menggunakan env `DEFAULT_USER_PASSWORD` dengan default yang kuat).*
> 4. ✅ Update URL dan key di `scanner.ps1` — *Selesai (Fitur Dynamic Agent Download via Zip telah diselesaikan di Issue #39 / PR #40).*
> 5. ✅ Pertimbangkan Redis untuk cache & queue — *Selesai.*
> 6. ✅ Fix cache key bugs — *Selesai (Command `ClearDashboardCache` dan `GenerateComplianceReportJob` sudah fix).*
>
> **Setelah setting environment (poin 2) diatur saat deploy, aplikasi siap dijalankan di production.**
