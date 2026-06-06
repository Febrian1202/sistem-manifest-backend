# 📝 Instruksi Implementasi Redis (Cache & Queue)

Dokumen ini berisi panduan dan instruksi detail bagi AI Agent atau Developer untuk mengimplementasikan **Redis sebagai Cache dan Queue driver** pada project Sistem Manifest USN Kolaka, serta memperbaiki beberapa bug terkait cache dan queue berdasarkan laporan kesiapan produksi.

---

## 🎯 Tujuan Utama
1. Memindahkan driver Cache dan Queue dari `database` ke `redis` untuk performa production.
2. Memperbaiki bug pada cache invalidation (Dashboard stats & Compliance stats).
3. Memperbaiki konfigurasi Queue dan sinkronisasi dengan Laravel Horizon.

---

## 🛠️ Langkah-Langkah Pengerjaan

### 1. Update Konfigurasi Environment (`.env`)
Saat ini sistem masih menggunakan `database` untuk cache dan queue.

- [ ] Buka file `.env` dan `.env.example`.
- [ ] Ubah konfigurasi driver menjadi redis:
  ```env
  CACHE_STORE=redis
  QUEUE_CONNECTION=redis
  ```
- [ ] Pastikan block konfigurasi Redis sudah benar (biasanya default sudah sesuai):
  ```env
  REDIS_CLIENT=phpredis
  REDIS_HOST=127.0.0.1
  REDIS_PASSWORD=null
  REDIS_PORT=6379
  ```
> **Catatan:** Untuk Queue, saat kita menggunakan Redis, aplikasi secara otomatis siap menggunakan Laravel Horizon (`laravel/horizon`).

---

### 2. Perbaikan Bug Cache Invalidation (KRITIS)
Ditemukan beberapa ketidakcocokan nama key cache antara saat data disimpan (set) dengan saat dihapus (forget/invalidate).

**A. Perbaiki Command `ClearDashboardCache.php`**
- File: `app/Console/Commands/ClearDashboardCache.php`
- Masalah: Command ini mencoba menghapus key `dashboard.stats`, padahal key yang disimpan oleh `DashboardController` memiliki suffix bulan: `dashboard.stats.{Y-m}`.
- Solusi: Ubah logika pada command agar melakukan looping atau menggunakan Redis wildcards untuk menghapus key berdasarkan pola bulan saat ini (dan bulan sebelumnya jika perlu), atau simpan list keys yang perlu dihapus.

**B. Perbaiki `GenerateComplianceReportJob.php`**
- File: `app/Jobs/GenerateComplianceReportJob.php`
- Masalah: Job ini mencoba menghapus cache dengan key `dashboard_metrics` (yang sebenarnya tidak ada di sistem).
- Solusi: Ganti kode cache invalidation tersebut menjadi menghapus key yang relevan, yaitu:
  - `dashboard.stats.{Y-m}` (bulan berjalan)
  - `dashboard.charts`
  - `compliance.global_stats`

**C. Tambahkan Invalidation untuk `compliance.global_stats`**
- File: Berbagai file Observer (`ComputerObserver`, `SoftwareCatalogObserver`, `LicenseInventoryObserver`) atau service terkait.
- Masalah: Saat ini key `compliance.global_stats` tidak di-invalidate ketika ada perubahan data (seperti saat computer baru diregister atau lisensi ditambah).
- Solusi: Pastikan `Cache::forget('compliance.global_stats')` dipanggil pada observer yang relevan saat ada perubahan data master.

---

### 3. Perbaikan Konfigurasi Queue Worker & Dev Script
Antrian (Queue) untuk proses compliance dan scan saat ini terlewat pada beberapa environment.

**A. Perbaiki `composer.json`**
- File: `composer.json` (bagian `scripts.dev`)
- Masalah: Perintah worker di local development hanya mendengarkan queue `default`. Antrian `scans` dan `compliance` tidak terproses.
- Solusi: Ubah script artisan queue:listen menjadi:
  ```json
  "php artisan queue:listen --queue=scans,compliance,default --tries=3 --timeout=120"
  ```

**B. Horizon Configuration (Opsional / Checklist)**
- File: `config/horizon.php`
- Pastikan konfigurasi queue `scans` dan `compliance` sudah dicakup oleh environment yang dituju (seperti blok konfigurasi `local` dan `production`).

---

## ✅ Kriteria Selesai (Acceptance Criteria)

Agent atau Developer dapat menganggap tugas ini **selesai** apabila:
1. Menjalankan `php artisan test` sukses tanpa ada error.
2. Saat submit hasil scan dari agent (dummy agent), job masuk ke Redis queue (`scans`) dan sukses diproses oleh worker.
3. Membuka halaman Dashboard setelah data berubah akan menampilkan data terbaru (Cache berhasil di-invalidate).
4. Laravel Horizon (jika diakses) menampilkan metric antrian yang sukses diproses, bukan database fallback.

Silakan jalankan perbaikan sesuai instruksi di atas secara sistematis.
