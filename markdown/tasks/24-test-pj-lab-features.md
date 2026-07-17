# Task 24 — Test Sprint 4: PJ Lab Features

## Sprint: 4 (Fitur PJ Lab + Kirim Laporan)
## Prioritas: Tinggi
## Dependensi: Task 19-23
## Estimasi: 2-3 jam

---

## Deskripsi

Buat test Pest untuk memverifikasi semua fitur PJ Lab dan pengiriman laporan oleh Admin.

## File Test yang Dibuat

### 1. `tests/Feature/ReportSubmissionTest.php`

```php
// Admin bisa melihat halaman kirim laporan
it('admin can view report submission page');

// Admin bisa kirim laporan ke PJ Lab
it('admin can submit report to lab');

// Admin tidak bisa kirim ulang jika masih pending
it('admin cannot submit report if pending exists');

// Admin bisa kirim ulang setelah approved/rejected
it('admin can resubmit report after approval');
it('admin can resubmit report after rejection');

// RBAC
it('kepala_lab cannot access report submission');
it('pimpinan cannot access report submission');

// Validasi
it('requires valid laboratory_id');
it('requires valid period format');

// Activity log
it('submission is logged in activity log');
```

### 2. `tests/Feature/KepalaLabRoleTest.php`

```php
// Scope akses
it('kepala_lab can only see computers in own lab');
it('kepala_lab cannot see computers in other labs');
it('kepala_lab cannot access computer CRUD routes');
it('kepala_lab cannot access license routes');
it('kepala_lab cannot access software catalog routes');
it('kepala_lab cannot access account management');

// Inventaris
it('kepala_lab can view lab inventory');
it('kepala_lab can view computer detail in own lab');
it('kepala_lab cannot view computer detail in other lab');

// Unassigned
it('kepala_lab without lab gets 403 on inventory');
```

### 3. `tests/Feature/ReportApprovalTest.php`

```php
// View
it('kepala_lab can view report approval list');
it('kepala_lab only sees own lab reports');

// Approve
it('kepala_lab can approve pending report');
it('approval sets status and reviewed_at');
it('notes are optional when approving');

// Reject
it('kepala_lab can reject pending report');
it('rejection requires notes');
it('rejection without notes fails validation');

// Final
it('cannot approve already approved report');
it('cannot reject already rejected report');
it('cannot approve report from other lab');

// Activity log
it('approval is logged in activity log');
it('rejection is logged in activity log');
```

### 4. `tests/Feature/LabScopedDashboardTest.php`

```php
it('admin sees full dashboard');
it('kepala_lab sees lab-scoped dashboard');
it('pimpinan sees approved-only dashboard');
it('kepala_lab without lab sees informative message');
```

## Referensi
- Lihat test Pest yang sudah ada di `tests/Feature/` untuk pola penulisan
- Gunakan factory yang sudah dibuat (Laboratory, ReportApproval)
- Setup user dengan role menggunakan Spatie: `$user->assignRole('kepala_lab')`

## File yang Dibuat
- `tests/Feature/ReportSubmissionTest.php`
- `tests/Feature/KepalaLabRoleTest.php`
- `tests/Feature/ReportApprovalTest.php`
- `tests/Feature/LabScopedDashboardTest.php`

## Verifikasi
- [ ] `php artisan test --filter=ReportSubmission` — semua pass
- [ ] `php artisan test --filter=KepalaLabRole` — semua pass
- [ ] `php artisan test --filter=ReportApproval` — semua pass
- [ ] `php artisan test --filter=LabScopedDashboard` — semua pass
- [ ] `composer test` — SEMUA test (lama + baru) pass
