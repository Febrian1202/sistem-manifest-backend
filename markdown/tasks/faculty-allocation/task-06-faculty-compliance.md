# Task 06: Analisis Compliance Bertingkat per Fakultas (Faculty-Level Compliance Analysis)

## 1. Ringkasan Task
Membangun kemampuan analisis kepatuhan lisensi bertingkat (*hierarchical compliance analysis*). Jika sebelumnya audit lisensi hanya membandingkan kepemilikan global vs total instalasi (`Owned vs Installed`), kini sistem mampu menyajikan audit komparatif per fakultas:
```text
INSTALLED vs ALLOCATED (per Fakultas)
```
Sehingga sistem dapat secara otomatis mendeteksi:
- Fakultas mana yang mengalami **Defisit** (instalasi melebihi alokasi kursi).
- Fakultas mana yang mengalami **Surplus** (alokasi kursi belum termanfaatkan).
- Software komersial yang terpasang tanpa memiliki alokasi lisensi sama sekali.

- **Status Dependensi:** Bergantung pada [Task 04 — Sistem Alokasi Lisensi](./task-04-license-allocation.md) dan [Task 05 — Perbaikan Kalkulasi Lisensi](./task-05-fix-license-calculations.md).
- **Target File yang Dibuat / Diubah:**
  - `app/Services/LicenseComplianceService.php` (Menambahkan method analisis fakultas)
  - `app/Http/Controllers/ComplianceDataController.php` (Update untuk mendukung filter dan view per fakultas)
  - `resources/views/pages/admin/compliance.blade.php` (Update UI dengan filter fakultas & tabel alokasi vs terpasang)
  - `resources/views/components/compliance/faculty-summary-card.blade.php` (Komponen baru)
  - `tests/Feature/FacultyComplianceTest.php` (Baru)

---

## 2. Rincian Spesifikasi Teknis

### 2.1 Pengembangan `LicenseComplianceService.php`
Tambahkan fungsi-fungsi berikut untuk komputasi kepatuhan tingkat fakultas:

```php
/**
 * Menghitung rekapitulasi kepatuhan seluruh software komersial untuk sebuah fakultas tertentu.
 *
 * @param int $facultyId
 * @return \Illuminate\Support\Collection
 */
public function getFacultyComplianceBreakdown(int $facultyId): Collection
{
    $commercialCatalogs = SoftwareCatalog::where('category', 'Commercial')->get();
    
    return $commercialCatalogs->map(function ($catalog) use ($facultyId) {
        // 1. Alokasi aktif untuk fakultas ini
        $allocated = (int) DB::table('license_allocations')
            ->join('license_inventories', 'license_allocations.license_inventory_id', '=', 'license_inventories.id')
            ->where('license_inventories.catalog_id', $catalog->id)
            ->where('license_allocations.faculty_id', $facultyId)
            ->where('license_allocations.status', 'active')
            ->sum('license_allocations.allocated_quota');

        // 2. Instalasi aktual pada komputer aktif di seluruh lab fakultas ini
        $installed = (int) SoftwareDiscovery::where('catalog_id', $catalog->id)
            ->whereHas('computer', function ($q) use ($facultyId) {
                $q->where('status', 'active')
                  ->whereHas('laboratory', fn ($l) => $l->where('faculty_id', $facultyId));
            })
            ->distinct('computer_id')
            ->count('computer_id');

        $deficit = max(0, $installed - $allocated);
        $surplus = max(0, $allocated - $installed);
        
        $utilizationRate = $allocated > 0 
            ? round(($installed / $allocated) * 100, 1) 
            : null;

        $status = match (true) {
            $allocated === 0 && $installed > 0 => 'Tanpa Alokasi (Defisit Penuh)',
            $installed > $allocated => 'Defisit',
            $installed < $allocated => 'Surplus',
            default => 'Cukup (Sesuai Alokasi)'
        };

        return [
            'catalog_id' => $catalog->id,
            'software_name' => $catalog->normalized_name,
            'allocated' => $allocated,
            'installed' => $installed,
            'deficit' => $deficit,
            'surplus' => $surplus,
            'utilization_rate' => $utilizationRate,
            'status' => $status,
            'is_compliant' => $deficit === 0,
        ];
    })->filter(fn ($item) => $item['allocated'] > 0 || $item['installed'] > 0);
}

/**
 * Menghasilkan matriks komparasi kepatuhan seluruh fakultas untuk ringkasan eksekutif.
 */
public function getCrossFacultyMatrix(): Collection
{
    $faculties = Faculty::withCount(['laboratories', 'computers'])->get();

    return $faculties->map(function ($faculty) {
        $breakdown = $this->getFacultyComplianceBreakdown($faculty->id);

        return [
            'faculty_id' => $faculty->id,
            'faculty_code' => $faculty->code,
            'faculty_name' => $faculty->name,
            'total_labs' => $faculty->laboratories_count,
            'total_computers' => $faculty->computers_count,
            'total_allocated_seats' => $breakdown->sum('allocated'),
            'total_installed_seats' => $breakdown->sum('installed'),
            'total_deficit' => $breakdown->sum('deficit'),
            'total_surplus' => $breakdown->sum('surplus'),
            'non_compliant_software_count' => $breakdown->where('deficit', '>', 0)->count(),
        ];
    });
}
```

---

### 2.2 Pembaruan `ComplianceDataController.php`

Update method `index(Request $request)`:
1. Mendukung parameter filter `faculty_id`:
   ```php
   $facultyId = $request->query('faculty_id');
   $faculties = Faculty::orderBy('name')->get();
   ```
2. Jika `$facultyId` diisi:
   - Hitung statistik khusus fakultas bersangkutan melalui `$complianceService->getFacultyComplianceBreakdown($facultyId)`.
   - Siapkan kartu statistik: Total Alokasi Fakultas, Total Terpasang, Total Defisit Fakultas, Total Surplus Fakultas.
3. Jika `$facultyId` kosong (Mode Universitas Global):
   - Tetap menampilkan agregasi tingkat institusi (`Owned vs Installed`).
   - Sertakan tab/tabel ringkasan perbandingan antar-fakultas (`$complianceService->getCrossFacultyMatrix()`).

---

### 2.3 Pembaruan Antarmuka (`resources/views/pages/admin/compliance.blade.php`)

1. **Header & Filter Bar:**
   - Tambahkan dropdown pilih **Fakultas** (`<select name="faculty_id">`) dengan opsi "Semua Fakultas (Tingkat Universitas)" dan masing-masing fakultas.
   - Pilihan filter mengubah URL secara otomatis via JavaScript atau form GET submit (`?faculty_id=X`).
2. **Kartu Statistik Interaktif:**
   - **Tingkat Universitas:** Total Komersial, Total Lisensi Universitas (Owned), Total Terpasang (Installed), Total Defisit Global.
   - **Tingkat Fakultas:** Total Alokasi (Allocated), Total Terpasang (Installed), Defisit Fakultas, Surplus (Alokasi Belum Terpakai).
3. **Tabel Data Kepatuhan:**
   - Tambahkan kolom:
     - **Software** (Nama software komersial)
     - **Hak Universitas (Owned)** *(hanya di mode universitas)*
     - **Alokasi Fakultas (Allocated)**
     - **Terpasang (Installed)**
     - **Selisih (Defisit / Surplus)**
     - **Rasio Utilisasi (%)** (e.g. `83.3%`, warna hijau jika $\le 100\%$, merah jika $> 100\%$)
     - **Status Kepatuhan** (Badge warna: Hijau = Cukup, Merah = Defisit, Kuning = Surplus)
4. **Drill-Down Modal / Expandable Row:**
   - Menyediakan tombol "Rincian Lab" untuk melihat persebaran instalasi software tersebut di laboratorium-laboratorium fakultas bersangkutan.

---

## 3. Langkah-Langkah Pengerjaan (Step-by-Step)

1. Buka `LicenseComplianceService.php` dan tambahkan method `getFacultyComplianceBreakdown()` serta `getCrossFacultyMatrix()`.
2. Modifikasi `ComplianceDataController.php` untuk menerima injection `LicenseComplianceService`, menangani parameter `faculty_id`, dan menyiapkan data view.
3. Update template Blade `resources/views/pages/admin/compliance.blade.php`:
   - Tambahkan form filter fakultas.
   - Perbarui header tabel dengan kolom `Allocated`, `Installed`, `Deficit`, `Surplus`, dan `Utilization`.
   - Tambahkan tab/tabel rekap perbandingan fakultas jika di mode global.
4. Buat file feature test `tests/Feature/FacultyComplianceTest.php`.
5. Jalankan pengujian dan pastikan seluruh skenario lolos tanpa pembagian dengan nol (*zero division safe*).

---

## 4. Kriteria Keberhasilan (Acceptance Criteria)
- [ ] Pengguna dapat melihat status kepatuhan secara global (Universitas) maupun terfilter per Fakultas.
- [ ] Kolom `Allocated`, `Installed`, `Deficit`, dan `Surplus` dihitung dengan benar sesuai rumus bisnis.
- [ ] Kasus di mana software terinstall tetapi belum pernah diberi alokasi (`Allocated = 0`) ditangani secara aman dengan status "Tanpa Alokasi" dan utilisasi tidak menyebabkan error division by zero.
- [ ] Badge status menampilkan warna yang intuitif (Merah untuk defisit, Kuning/Biru untuk surplus, Hijau untuk patuh/cukup).
- [ ] Ringkasan perbandingan antar-fakultas menampilkan fakultas mana yang paling banyak mengalami defisit.

---

## 5. Rencana Pengujian Otomatis (Pest Test)
Buat file `tests/Feature/FacultyComplianceTest.php`:
1. `it('calculates faculty compliance with zero deficit when installed equals allocated')`
2. `it('detects deficit when installed software exceeds allocated quota in a faculty')`
   *(Alokasi = 10, Terpasang di Lab FTI = 15 -> Defisit = 5, Status = Defisit)*
3. `it('detects surplus when installed software is less than allocated quota')`
   *(Alokasi = 20, Terpasang di Lab FTI = 12 -> Surplus = 8, Status = Surplus)*
4. `it('handles unallocated software installation gracefully without division by zero')`
   *(Alokasi = 0, Terpasang = 5 -> Defisit = 5, Utilization = N/A)*
5. `it('filters compliance view by faculty_id correctly')`
6. `it('generates cross-faculty comparison matrix accurately')`
