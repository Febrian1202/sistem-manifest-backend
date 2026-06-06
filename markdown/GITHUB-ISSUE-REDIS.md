# Issue: Implementasi Redis untuk Cache dan Queue serta Perbaikan Cache Invalidation

**Label**: `feature`, `performance`, `bugfix`, `redis`  
**Priority**: High  
**Estimasi**: ~2 jam  

---

## Konteks

Sistem saat ini dikonfigurasi untuk menggunakan driver `database` untuk cache dan queue. Untuk performa yang optimal di tingkat produksi, serta untuk sinkronisasi dengan Laravel Horizon (yang mewajibkan penggunaan Redis), kita perlu memindahkan konfigurasi cache dan queue ke Redis.

Selain itu, terdapat bug kritis di mana key cache yang disimpan tidak sesuai dengan key cache yang di-invalidate (dihapus). Hal ini menyebabkan data statistik di dashboard atau compliance reports tetap statis (stale) meskipun data di database sudah berubah.

---

## Instruksi Implementasi

### Step 1 — Konfigurasi Environment (`.env`)

Ubah driver cache dan queue agar menggunakan Redis pada file konfigurasi environment local (`.env`) dan contoh production (`.env.example` / `.env.production`).

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

Pastikan variabel Redis sudah terarah ke host dan port yang benar:
```env
REDIS_CLIENT=predis  # atau phpredis tergantung driver PHP yang dipakai
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

---

### Step 2 — Perbaikan Bug Cache Invalidation

Ditemukan ketidakcocokan (mismatch) nama key cache pada dashboard dan compliance report. Lakukan perbaikan pada file-file berikut:

#### A. Perbaiki Command `ClearDashboardCache`
* **File**: [ClearDashboardCache.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Console/Commands/ClearDashboardCache.php)
* **Masalah**: Command mencoba menghapus key `dashboard.stats`, padahal key asli yang disimpan oleh `DashboardController` memiliki suffix bulan: `dashboard.stats.{Y-m}`.
* **Solusi**: Ubah command agar menguji atau menghapus key berdasarkan pola tahun-bulan yang aktif, misal `dashboard.stats.` . `now()->format('Y-m')`.

#### B. Perbaiki Job `GenerateComplianceReportJob`
* **File**: [GenerateComplianceReportJob.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Jobs/GenerateComplianceReportJob.php)
* **Masalah**: Job mencoba menghapus key `dashboard_metrics` yang tidak ada dalam codebase sistem.
* **Solusi**: Ubah kode pembersihan cache di dalam job ini agar menghapus key yang benar-benar aktif:
  * `dashboard.stats.{Y-m}`
  * `dashboard.charts`
  * `compliance.global_stats`

#### C. Tambahkan Invalidation untuk `compliance.global_stats`
* **File**: 
  * [ComputerObserver.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Observers/ComputerObserver.php)
  * [SoftwareCatalogObserver.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Observers/SoftwareCatalogObserver.php)
  * [LicenseInventoryObserver.php](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/app/Observers/LicenseInventoryObserver.php)
* **Masalah**: Key cache `compliance.global_stats` tidak pernah di-invalidate saat ada perubahan data master.
* **Solusi**: Tambahkan `Cache::forget('compliance.global_stats')` pada method `created`, `updated`, `deleted` di observer-observer tersebut jika relevan.

---

### Step 3 — Konfigurasi Queue Worker & Dev Script

Agar queue `scans` dan `compliance` diproses secara bersamaan pada lokal development:

* **File**: [composer.json](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/composer.json)
* **Masalah**: Dev script saat ini hanya memantau queue `default` (`queue:listen --tries=1 --timeout=0`).
* **Solusi**: Update dev script di `composer.json` agar mendengarkan antrean spesifik:
  ```json
  "php artisan queue:listen --queue=scans,compliance,default --tries=3 --timeout=120"
  ```

---

## Checklist

- [ ] Driver `.env` lokal & production diubah ke `redis`
- [ ] Command `ClearDashboardCache` sukses menghapus pola key `dashboard.stats.{Y-m}` yang benar
- [ ] Job `GenerateComplianceReportJob` sukses menginvalidasi cache dashboard stats, charts, dan global compliance stats yang valid
- [ ] Observer menginvalidasi cache `compliance.global_stats` secara otomatis saat data komputer atau lisensi berubah
- [ ] Antrean `scans` dan `compliance` didefinisikan dengan benar pada script `dev` di `composer.json`
- [ ] `php artisan test` — semua unit & feature test berhasil dengan status PASS

---

## Referensi

- Laporan Audit Kesiapan Produksi: [PRODUCTION-READINESS.md](file:///home/ridaz/Development/Laravel/sistem-manifest-backend/markdown/PRODUCTION-READINESS.md)
- Konfigurasi Database & Redis: `config/database.php`
- Model & Observers terkait: `app/Models/` & `app/Observers/`
