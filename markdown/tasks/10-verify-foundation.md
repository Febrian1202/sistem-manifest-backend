# Task 10 — Verifikasi: migrate:fresh --seed + Test Regresi

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Task 09 (semua task Sprint 1 selesai)
## Estimasi: 15-30 menit

---

## Deskripsi

Task verifikasi akhir Sprint 1. Pastikan seluruh migration, model, dan seeder berjalan dengan benar, serta semua test yang sudah ada tetap pass.

## Langkah-langkah

### 1. Fresh Migrate + Seed

```bash
php artisan migrate:fresh --seed
```

Pastikan output tidak ada error.

### 2. Verifikasi Data di Database

Gunakan `php artisan tinker` untuk memverifikasi:

```php
// Cek tabel laboratories
App\Models\Laboratory::count(); // harus >= 3
App\Models\Laboratory::pluck('code'); // ['LAB-KOM1', 'LAB-KOM2', 'LAB-JRG']

// Cek role baru
Spatie\Permission\Models\Role::pluck('name'); // ['admin', 'kepala_lab', 'pimpinan']

// Cek permission baru
Spatie\Permission\Models\Permission::pluck('name');
// harus ada: 'manage laboratories', 'review reports', 'view lab inventory'

// Cek user kepala_lab
$kl = App\Models\User::role('kepala_lab')->first();
$kl->laboratory_id; // harus terisi
$kl->laboratory->name; // harus menampilkan nama lab

// Cek relasi Computer → Laboratory
App\Models\Computer::first()?->laboratory; // null (belum ada data, OK)

// Cek relasi Laboratory → computers
App\Models\Laboratory::first()->computers->count(); // 0 (belum ada komputer, OK)
```

### 3. Jalankan Test Suite

```bash
composer test
```

SEMUA test yang sudah ada harus tetap pass. Jika ada yang gagal, perbaiki sebelum lanjut ke Sprint 2.

### 4. Cek Migrasi Rollback

```bash
php artisan migrate:rollback --step=4
php artisan migrate
```

Pastikan 4 migration baru (laboratories, FK computers, FK users, report_approvals) bisa di-rollback dan di-migrate ulang tanpa error.

## Kriteria Kelulusan Sprint 1

 - [x] `php artisan migrate:fresh --seed` — sukses tanpa error
 - [x] 3 role ada di database (admin, kepala_lab, pimpinan)
 - [x] 8 permission ada di database
 - [x] 3 default user ada (admin, pimpinan, kepala_lab)
 - [x] Sample laboratorium tersimpan
 - [x] User kepala_lab terasosiasi ke laboratorium
 - [x] Semua relasi model berfungsi (Laboratory ↔ Computer, Laboratory ↔ User, Laboratory ↔ ReportApproval)
 - [x] `composer test` — SEMUA test pass
 - [x] Migration rollback + re-migrate berjalan lancar
