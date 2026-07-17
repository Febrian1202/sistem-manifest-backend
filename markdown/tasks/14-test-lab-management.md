# Task 14 — Test: `LaboratoryManagementTest` + `AccountKepalaLabTest`

## Sprint: 2 (CRUD Lab & Akun)
## Prioritas: Tinggi
## Dependensi: Task 11, Task 12
## Estimasi: 1-2 jam

---

## Deskripsi

Buat test Pest untuk memverifikasi CRUD laboratorium dan manajemen akun kepala_lab.

## Langkah-langkah

### 1. Buat `LaboratoryManagementTest`

File: `tests/Feature/LaboratoryManagementTest.php`

**Skenario yang harus ditest:**

```php
// RBAC
it('admin can view laboratory list');
it('admin can create a laboratory');
it('admin can update a laboratory');
it('admin can delete a laboratory without computers');
it('admin cannot delete a laboratory that has computers');
it('kepala_lab cannot access laboratory CRUD');
it('pimpinan cannot access laboratory CRUD');

// Validasi
it('laboratory code must be unique');
it('laboratory name is required');
it('laboratory code is required');

// Data
it('laboratory index shows computer count');
it('laboratory index shows assigned PJ Lab name');
```

### 2. Buat `AccountKepalaLabTest`

File: `tests/Feature/AccountKepalaLabTest.php`

**Skenario yang harus ditest:**

```php
// CRUD akun kepala_lab
it('admin can create account with kepala_lab role and laboratory');
it('kepala_lab role requires laboratory_id');
it('admin role does not require laboratory_id');
it('pimpinan role does not require laboratory_id');

// Edit role
it('changing role from kepala_lab to admin clears laboratory_id');
it('changing role to kepala_lab requires laboratory_id');

// Proteksi
it('kepala_lab cannot create other accounts');
it('kepala_lab cannot delete accounts');
it('minimum one admin enforcement still works');

// Display
it('account list shows role and laboratory columns');
```

## Referensi
- Lihat test yang sudah ada di `tests/Feature/` untuk memahami pola penulisan (Pest syntax)
- Proyek menggunakan SQLite `:memory:` untuk testing
- Lihat bagaimana test lain melakukan setup (user creation, role assignment, authentication)

## File yang Dibuat
- `tests/Feature/LaboratoryManagementTest.php`
- `tests/Feature/AccountKepalaLabTest.php`

## Verifikasi
- [ ] `php artisan test --filter=LaboratoryManagement` — semua pass
- [ ] `php artisan test --filter=AccountKepalaLab` — semua pass
- [ ] `composer test` — semua test (lama + baru) pass
