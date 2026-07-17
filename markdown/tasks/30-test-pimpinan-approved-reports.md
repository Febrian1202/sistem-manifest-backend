# Task 30 — Test: `PimpinanApprovedReportsTest` + Full Regression

## Sprint: 5 (Integrasi Pimpinan & Polish)
## Prioritas: Tinggi
## Dependensi: Task 25-29
## Estimasi: 1-2 jam

---

## Deskripsi

Buat test untuk memverifikasi bahwa Pimpinan hanya melihat laporan dari lab yang sudah approved, lalu jalankan full regression test.

## Langkah-langkah

### 1. Buat `PimpinanApprovedReportsTest`

File: `tests/Feature/PimpinanApprovedReportsTest.php`

```php
// Dashboard
it('pimpinan dashboard shows only approved lab data');
it('pimpinan dashboard shows message when no labs approved');

// Laporan preview
it('pimpinan report preview includes only approved labs');
it('pimpinan report preview excludes pending labs');
it('pimpinan report preview excludes rejected labs');

// Laporan export
it('pimpinan report export PDF includes only approved labs');
it('pimpinan report export Excel includes only approved labs');

// Compliance
it('pimpinan compliance page shows only approved lab data');

// Metadata
it('exported report includes approval metadata');

// Edge cases
it('pimpinan sees empty state when no reports approved');
it('admin still sees all data regardless of approval status');
it('newly approved lab appears in pimpinan view');
```

### 2. Setup Test Data

```php
beforeEach(function () {
    // Buat 3 lab
    $this->lab1 = Laboratory::factory()->create();
    $this->lab2 = Laboratory::factory()->create();
    $this->lab3 = Laboratory::factory()->create();

    // Buat komputer di masing-masing lab
    Computer::factory(5)->create(['laboratory_id' => $this->lab1->id]);
    Computer::factory(5)->create(['laboratory_id' => $this->lab2->id]);
    Computer::factory(5)->create(['laboratory_id' => $this->lab3->id]);

    // Lab1: approved, Lab2: pending, Lab3: rejected
    ReportApproval::factory()->approved()->create(['laboratory_id' => $this->lab1->id]);
    ReportApproval::factory()->create(['laboratory_id' => $this->lab2->id]); // pending
    ReportApproval::factory()->rejected()->create(['laboratory_id' => $this->lab3->id]);

    // Buat users
    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole('pimpinan');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});
```

### 3. Full Regression Test

Setelah semua test baru ditulis, jalankan full test suite:

```bash
composer test
```

Semua test (lama + baru dari Sprint 1-5) harus pass.

### 4. Checklist Akhir Sprint 5

- [ ] Semua fitur PJ Lab berfungsi (inventaris, review, approve/reject)
- [ ] Admin bisa kirim laporan ke PJ Lab
- [ ] Pimpinan hanya lihat data approved
- [ ] PDF/Excel menyertakan metadata approval
- [ ] Narasi "Agen Scanner" sudah diganti di UI
- [ ] Dashboard per role berfungsi
- [ ] Compliance page di-scope per role
- [ ] Semua test pass

## File yang Dibuat
- `tests/Feature/PimpinanApprovedReportsTest.php`

## Verifikasi
- [ ] `php artisan test --filter=PimpinanApprovedReports` — semua pass
- [ ] `composer test` — SEMUA test pass (0 failures)
