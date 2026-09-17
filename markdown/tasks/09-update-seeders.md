# Task 09 — Update Seeders: Role, Permission, Default Users, Sample Labs

## Sprint: 1 (Fondasi)
## Prioritas: Tinggi
## Dependensi: Task 01-08 (semua migration dan model harus selesai)
## Estimasi: 30-45 menit

---

## Deskripsi

Update seeder yang sudah ada untuk menambah role `kepala_lab`, permission baru, default user PJ Lab, dan sample data laboratorium.

## Langkah-langkah

### 1. Update `RoleAndPermissionSeeder`

Buka `database/seeders/RoleAndPermissionSeeder.php` (atau nama file seeder yang menangani roles/permissions). Tambahkan:

**Permission baru (3 buah):**
```php
Permission::create(['name' => 'manage laboratories', 'guard_name' => 'web']);
Permission::create(['name' => 'review reports', 'guard_name' => 'web']);
Permission::create(['name' => 'view lab inventory', 'guard_name' => 'web']);
```

**Role baru:**
```php
$kepalaLab = Role::create(['name' => 'kepala_lab', 'guard_name' => 'web']);
$kepalaLab->givePermissionTo([
    'access admin panel',
    'view reports',
    'review reports',
    'view lab inventory',
]);
```

**Update role admin — tambah permission baru:**
```php
$admin->givePermissionTo('manage laboratories');
// Pastikan admin juga punya semua permission yang sudah ada
```

### 2. Update `DatabaseSeeder`

Tambahkan sample data laboratorium dan default user PJ Lab:

**Sample laboratorium:**
```php
use App\Models\Laboratory;

$lab1 = Laboratory::create([
    'name' => 'Laboratorium Komputer 1',
    'code' => 'LAB-KOM1',
    'building' => 'Gedung A',
    'floor' => '2',
    'description' => 'Lab komputer umum lantai 2',
]);

$lab2 = Laboratory::create([
    'name' => 'Laboratorium Komputer 2',
    'code' => 'LAB-KOM2',
    'building' => 'Gedung A',
    'floor' => '3',
    'description' => 'Lab komputer umum lantai 3',
]);

$labJaringan = Laboratory::create([
    'name' => 'Laboratorium Jaringan',
    'code' => 'LAB-JRG',
    'building' => 'Gedung B',
    'floor' => '1',
    'description' => 'Lab praktikum jaringan komputer',
]);
```

**Default user PJ Lab:**
```php
$kepalaLab1 = User::create([
    'name' => 'Kepala Lab Komputer',
    'email' => 'kepalalab@usn.ac.id',
    'password' => Hash::make(env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!')),
    'laboratory_id' => $lab1->id,
]);
$kepalaLab1->assignRole('kepala_lab');
```

### 3. Pertahankan User yang Sudah Ada

User default yang sudah ada **jangan dihapus**:
- `admin@usn.ac.id` → role `admin`, `laboratory_id` = NULL
- `pimpinan@usn.ac.id` → role `pimpinan`, `laboratory_id` = NULL

## Referensi
- Lihat file seeder yang sudah ada untuk mengetahui pola penulisan yang digunakan proyek ini
- Permission yang sudah ada: `access admin panel`, `manage computers`, `manage licenses`, `view reports`, `manage users`

## File yang Dimodifikasi
- `database/seeders/RoleAndPermissionSeeder.php` (atau nama file yang sesuai)
- `database/seeders/DatabaseSeeder.php`

## Verifikasi
 - [x] `php artisan migrate:fresh --seed` berjalan tanpa error
 - [x] Tabel `roles` berisi 3 role: `admin`, `kepala_lab`, `pimpinan`
 - [x] Tabel `permissions` berisi 8 permission (5 lama + 3 baru)
 - [x] Role `kepala_lab` punya 4 permission yang benar
 - [x] Tabel `laboratories` berisi sample data lab
 - [x] Tabel `users` berisi 3 user default (admin, pimpinan, kepala_lab)
 - [x] User kepala_lab punya `laboratory_id` yang terisi
 - [x] `composer test` — semua test lama masih pass
