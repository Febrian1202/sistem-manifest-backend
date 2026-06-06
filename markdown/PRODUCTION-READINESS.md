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

## 1. 🔴 Environment & Konfigurasi (KRITIS)

### 1.1 File `.env` — Masalah yang Harus Diperbaiki

File `.env` saat ini masih menggunakan konfigurasi development:

```diff
- APP_ENV=local
+ APP_ENV=production

- APP_DEBUG=true
+ APP_DEBUG=false

- APP_URL=127.0.0.1
+ APP_URL=https://domain-anda.com

- LOG_LEVEL=debug
+ LOG_LEVEL=warning
```

> [!CAUTION]
> `APP_DEBUG=true` di production akan **mengekspos stack trace, query SQL, dan environment variables** kepada pengguna. Ini adalah risiko keamanan BESAR.

### 1.2 APP_KEY

- `APP_KEY` saat ini sudah di-set (`base64:mveaT0AJ...`).
- ⚠️ **PENTING:** Key ini sudah ada di git history (terlihat di `.env`). Untuk production, **generate key baru**:
  ```bash
  php artisan key:generate
  ```
- ⚠️ Karena `LicenseInventory` menggunakan `'license_key' => 'encrypted'`, mengganti APP_KEY akan membuat data license key yang sudah ada **tidak bisa didecrypt**. Jika sudah ada data production, gunakan `APP_PREVIOUS_KEYS` di `.env`.

### 1.3 AGENT_REGISTRATION_KEY

- Saat ini: `4ea50b7ae96598e1671af1240c243fcd`
- ⚠️ Key ini hardcoded di `.env` dan mungkin sudah ada di git. **Ganti dengan key baru** untuk production:
  ```bash
  php artisan tinker --execute="echo bin2hex(random_bytes(32));"
  ```

### 1.4 Database Credentials

```diff
- DB_USERNAME=root
- DB_PASSWORD=root
+ DB_USERNAME=manifest_user
+ DB_PASSWORD=<password-kuat-random>
```

> [!WARNING]
> Jangan gunakan `root/root` di production. Buat user MySQL dedicated dengan privilege terbatas.

### 1.5 Timezone

Di [config/app.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/config/app.php#L70):
```php
'timezone' => 'UTC',
```

Karena ini untuk USN Kolaka (Sulawesi Tenggara), seharusnya:
```php
'timezone' => 'Asia/Makassar',  // WITA (UTC+8)
```

### 1.6 Session Encryption

```diff
- SESSION_ENCRYPT=false
+ SESSION_ENCRYPT=true
```

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
- ⚠️ **Bug:** `ClearDashboardCache` command menghapus `dashboard.stats` tapi cache key sebenarnya `dashboard.stats.{Y-m}` (dengan suffix bulan). **Cache tidak ter-clear!**
- ⚠️ **Bug:** `GenerateComplianceReportJob` menghapus `dashboard_metrics` tapi key ini **tidak ada** di codebase manapun. Seharusnya menghapus `dashboard.stats.{Y-m}` dan `dashboard.charts`.
- ⚠️ Tidak ada invalidation untuk `compliance.global_stats` di observer manapun.

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

### 3.1 Password Default TIDAK AMAN

Di [DatabaseSeeder.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/database/seeders/DatabaseSeeder.php#L29):

```php
// Akun Admin — PASSWORD LEMAH!
$admin = User::firstOrCreate([
    'email' => 'admin@usn.ac.id',
], [
    'name' => 'Administrator',
    'password' => 'password',     // ❌ SANGAT LEMAH
]);

// Akun Pimpinan — PASSWORD LEMAH!
$pimpinan = User::firstOrCreate([
    'email' => 'pimpinan@usn.ac.id',
], [
    'name' => 'Pimpinan',
    'password' => 'password',     // ❌ SANGAT LEMAH
]);
```

> [!CAUTION]
> Password `password` bisa ditebak oleh siapapun. Untuk production:
> 1. Gunakan password kuat saat seeding awal
> 2. **Segera ganti password** setelah login pertama kali
> 3. Pertimbangkan force password change pada login pertama

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

- **Sanctum Token Expiration:** Saat ini `null` di [config/sanctum.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/config/sanctum.php#L53). Agent token tidak pernah expire. Pertimbangkan menambahkan expiration (misalnya 30 hari):
  ```php
  'expiration' => 43200, // 30 hari dalam menit
  ```

- **Horizon Dashboard:** Saat ini accessible tanpa auth tambahan. Di production, tambahkan gate authorization di `HorizonServiceProvider`:
  ```php
  Horizon::auth(function ($request) {
      return $request->user()?->hasRole('admin');
  });
  ```

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

## 6. 🟡 Logging

### 6.1 Konfigurasi Saat Ini

- Channel: `stack` → `single` (file tunggal)
- Level: `debug` (terlalu verbose untuk production)
- Path: `storage/logs/laravel.log`

### 6.2 Rekomendasi Production

```env
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14
```

Menggunakan `daily` akan membuat file log terpisah per hari dan auto-prune setelah 14 hari.

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
| **Custom Error Pages (403, 404, 500)** | 🟡 Sedang | Saat ini tidak ada custom error page. User akan melihat halaman error default Laravel. |
| **Notifikasi Lisensi Hampir Expired** | 🔵 Opsional | Sudah ada detection "Grace Period" tapi belum ada notifikasi push (email/in-app). |
| **Backup Database Otomatis** | 🔵 Opsional | Untuk keamanan data di lapangan, bisa pakai `spatie/laravel-backup`. |
| **Health Check Page** | ✅ Sudah ada | Route `/up` sudah dikonfigurasi di `bootstrap/app.php`. |
| **Force HTTPS** | 🟡 Sedang | Belum ada middleware untuk force HTTPS. |
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
- [ ] Ganti password default akun admin dan pimpinan
- [ ] Jangan jalankan `LicenseInventorySeeder` di production
- [ ] Set `SESSION_ENCRYPT=true`
- [ ] Set `APP_TIMEZONE=Asia/Makassar` di config/app.php
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
- [ ] Ubah `LOG_LEVEL=warning` dan `LOG_STACK=daily`
- [x] Fix bug `ClearDashboardCache` command (key mismatch)
- [x] Fix bug `GenerateComplianceReportJob` cache invalidation (key `dashboard_metrics` tidak ada)
- [x] Tambahkan cache invalidation untuk `compliance.global_stats`
- [x] Fix `composer.json` dev script untuk mendengarkan semua queue
- [ ] Buat custom error pages (403, 404, 500)
- [ ] Set `SANCTUM_EXPIRATION` untuk agent token
- [ ] Amankan Horizon dashboard dengan auth gate

### Opsional (🔵)

- [ ] Konfigurasi mail driver (SMTP) untuk notifikasi
- [ ] Setup scheduled task: `php artisan schedule:run` di crontab
- [ ] Buat backup strategy
- [ ] Setup supervisor untuk queue worker persistent

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

Di [LicenseInventory.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Models/LicenseInventory.php), `license_key` diencrypt via cast tapi **TIDAK ada di `$hidden`**. Jika model di-serialize ke JSON, key akan muncul dalam bentuk terdekripsi.

```php
// Tambahkan $hidden:
protected $hidden = ['license_key'];
```

### 11.4 Model `Computer` — Data Sensitif Tidak Di-hide

Di [Computer.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Models/Computer.php), tidak ada `$hidden`. Field seperti `mac_address`, `serial_number`, `ip_address` bisa terexpose jika model di-serialize.

---

## 12. 🟡 Temuan Frontend & Asset

### 12.1 Font Awesome Dimuat 2x (Duplikat)

**Status Aktual:** ✅ **SELESAI**
CDN untuk `<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/...` sudah dikomentari di `app.blade.php`, sehingga FontAwesome hanya diload melalui NPM bundle dan tidak lagi duplikat.


### 12.2 Dependencies CDN Eksternal

Layout utama memuat library dari CDN:
- ApexCharts (`cdn.jsdelivr.net`)
- Chart.js (`cdn.jsdelivr.net`)
- Alpine.js (`cdn.jsdelivr.net`)

Ini berarti **jika internet mati, dashboard charts tidak akan bekerja**. Untuk lingkungan USN Kolaka yang mungkin koneksi tidak stabil, pertimbangkan bundling via NPM.

### 12.3 File Localization Belum Diterjemahkan

File `lang/id/*.php` masih berisi teks bahasa Inggris (copy-paste dari `lang/en/`). Contoh:
```php
// lang/id/auth.php — masih English:
'failed' => 'These credentials do not match our records.',
```

Seharusnya:
```php
'failed' => 'Kredensial ini tidak cocok dengan catatan kami.',
```

### 12.4 Sidebar Link Hardcoded

Di [side-bar.blade.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/resources/views/components/layout/side-bar.blade.php), link navigasi menggunakan path hardcoded (`href="/dashboard"`) alih-alih `{{ route('dashboard') }}`. Ini fragile jika route berubah.

### 12.5 Typo di Layout

Di `app.blade.php`: `x-trasition.opacity` seharusnya `x-transition.opacity` (huruf 'n' hilang).

### 12.6 Package `@fontsource/inter` Tidak Digunakan

Di `package.json`, `@fontsource/inter` terinstall tapi CSS hanya import `@fontsource/poppins`. Bisa dihapus untuk mengurangi ukuran bundle.

---

## 13. 📊 Ringkasan Penilaian Final

| Aspek | Skor | Catatan |
|---|---|---|
| **Arsitektur & Kode** | 9/10 | MVC rapi, service layer, proper job queuing |
| **Keamanan** | 8/10 | Sangat baik, minor issue di token expiration & hidden attributes |
| **Kesiapan Production** | 5/10 | Perlu fix .env, password, cache config, `public/hot` |
| **Test Coverage** | 7/10 | 8 feature tests, mencakup area kritis |
| **Dokumentasi** | 7/10 | README dan GEMINI.md informatif |
| **Fitur Kelengkapan** | 9/10 | Sangat lengkap untuk scope skripsi |
| **Frontend Quality** | 7/10 | Shadcn-style UI bagus, tapi ada CDN dependency & duplikasi |

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
