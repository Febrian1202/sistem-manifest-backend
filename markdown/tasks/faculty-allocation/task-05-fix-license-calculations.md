# Task 05: Perbaikan Kalkulasi Lisensi & Sentralisasi Service (Fix License Calculations & LicenseComplianceService)

## 1. Ringkasan Task
Memperbaiki kelemahan mendasar dalam logika audit lisensi yang ada saat ini dan menyatukan seluruh kalkulasi kepatuhan ke dalam service sentral: `App\Services\LicenseComplianceService`.

### Masalah yang Harus Diperbaiki:
1. **Bug `$catalog->licenses->first()` pada `GenerateComplianceReportJob.php` (Line 134):**
   Jika suatu software dibeli dalam beberapa gelombang pengadaan (misal: Batch 1 = 20 seat, Batch 2 = 30 seat), sistem hanya memeriksa record lisensi pertama. Total entitlement yang sah seharusnya adalah **50 seat**, bukan 20 seat!
2. **Evaluasi Per-Baris vs Total pada `LicenseDataController.php` & `ReportController.php`:**
   Saat ini, kedua controller membandingkan total penemuan software di seluruh kampus secara langsung terhadap kuota masing-masing baris lisensi tunggal tanpa agregasi gabungan. Akibatnya, software dengan instalasi 50 pada dua lisensi @ 30 seat akan salah ditandai sebagai *Over Limit* pada kedua baris tersebut.
3. **Duplikasi & Inkonsistensi Logika:**
   Logika status kepatuhan terfragmentasi di `ComplianceDataController`, `GenerateComplianceReportJob`, `ReportController`, dan `LicenseDataController`.

- **Status Dependensi:** Bergantung pada [Task 04 — Sistem Alokasi Lisensi](./task-04-license-allocation.md).
- **Target File yang Dibuat / Diubah:**
  - `app/Services/LicenseComplianceService.php` (Baru)
  - `app/Jobs/GenerateComplianceReportJob.php` (Refactor)
  - `app/Http/Controllers/LicenseDataController.php` (Refactor)
  - `app/Http/Controllers/ReportController.php` (Refactor)
  - `tests/Feature/LicenseCalculationAccuracyTest.php` (Baru)

---

## 2. Rincian Spesifikasi Teknis

### 2.1 Service Sentral: `App\Services\LicenseComplianceService`
Buat service baru untuk membungkus seluruh aturan kalkulasi kepatuhan:
```php
namespace App\Services;

use App\Models\SoftwareCatalog;
use App\Models\LicenseInventory;
use App\Models\Computer;
use App\Models\SoftwareDiscovery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LicenseComplianceService
{
    /**
     * Menghitung total kapasitas lisensi aktif (owned) untuk sebuah software catalog.
     * Mengagregasikan SUM(quota_limit) dari semua lisensi aktif yang belum expired.
     */
    public function getActiveEntitlement(int $catalogId): int
    {
        return (int) LicenseInventory::where('catalog_id', $catalogId)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->sum('quota_limit');
    }

    /**
     * Menghitung total lisensi yang terdistribusi melalui alokasi ke seluruh fakultas.
     */
    public function getTotalAllocated(int $catalogId): int
    {
        return (int) DB::table('license_allocations')
            ->join('license_inventories', 'license_allocations.license_inventory_id', '=', 'license_inventories.id')
            ->where('license_inventories.catalog_id', $catalogId)
            ->where('license_allocations.status', 'active')
            ->sum('license_allocations.allocated_quota');
    }

    /**
     * Menghitung jumlah instalasi aktual (distinct computer_id) untuk suatu catalog software,
     * dengan opsi pembatasan scope fakultas atau laboratorium.
     */
    public function getInstalledCount(int $catalogId, ?int $facultyId = null, ?int $laboratoryId = null): int
    {
        $query = SoftwareDiscovery::where('catalog_id', $catalogId)
            ->whereHas('computer', fn ($q) => $q->where('status', 'active'));

        if ($laboratoryId) {
            $query->whereHas('computer', fn ($q) => $q->where('laboratory_id', $laboratoryId));
        } elseif ($facultyId) {
            $query->whereHas('computer.laboratory', fn ($q) => $q->where('faculty_id', $facultyId));
        }

        return $query->distinct('computer_id')->count('computer_id');
    }

    /**
     * Mengevaluasi status kepatuhan sebuah software komersial pada level institusi (Universitas).
     */
    public function evaluateUniversityCompliance(SoftwareCatalog $catalog): array
    {
        $owned = $this->getActiveEntitlement($catalog->id);
        $allocated = $this->getTotalAllocated($catalog->id);
        $installed = $this->getInstalledCount($catalog->id);

        $deficit = max(0, $installed - $owned);
        $surplus = max(0, $owned - $installed);
        $unallocated = max(0, $owned - $allocated);

        $status = match (true) {
            $owned === 0 => 'Tidak Berlisensi',
            $installed > $owned => 'Kelebihan Penggunaan (Defisit)',
            default => 'Berlisensi'
        };

        return [
            'catalog_id' => $catalog->id,
            'software_name' => $catalog->normalized_name,
            'owned' => $owned,
            'allocated' => $allocated,
            'installed' => $installed,
            'unallocated' => $unallocated,
            'deficit' => $deficit,
            'surplus' => $surplus,
            'status' => $status,
            'is_compliant' => $deficit === 0,
        ];
    }
}
```

---

### 2.2 Refactoring `GenerateComplianceReportJob.php`

Gantikan bagian pengecekan baris 120-160 pada `app/Jobs/GenerateComplianceReportJob.php`.

#### Sebelum (Bermasalah):
```php
// HANYA MENGAMBIL LISENSI PERTAMA!
$license = $catalog->licenses->first();
if ($license) {
    if ($license->expiry_date && $license->expiry_date->isPast()) { ... }
    $installedCount = ...;
    if ($installedCount > $license->quota_limit) { ... }
}
```

#### Sesudah (Menggunakan Agregasi Entitlement):
```php
// 1. Ambil seluruh lisensi aktif untuk catalog ini
$activeLicenses = $catalog->licenses()
    ->where(function ($q) {
        $q->whereNull('expiry_date')
          ->orWhere('expiry_date', '>=', now()->toDateString());
    })
    ->get();

$totalQuota = $activeLicenses->sum('quota_limit');

// 2. Hitung total instalasi komputer aktif untuk catalog ini di seluruh kampus
$totalInstalled = SoftwareDiscovery::where('catalog_id', $catalog->id)
    ->whereHas('computer', fn ($q) => $q->where('status', 'active'))
    ->distinct('computer_id')
    ->count('computer_id');

// 3. Evaluasi kepatuhan berdasarkan total quota kepemilikan
if ($totalQuota === 0) {
    $status = 'Tidak Berlisensi';
    $keterangan = 'Software komersial terpasang tanpa adanya catatan lisensi resmi di universitas.';
    $licenseId = null;
} elseif ($totalInstalled > $totalQuota) {
    $status = 'Kelebihan Penggunaan';
    $keterangan = "Penggunaan ({$totalInstalled}) melampaui total kuota lisensi universitas ({$totalQuota}).";
    $licenseId = $activeLicenses->first()?->id; // Mengaitkan ke salah satu inventory referensi
} else {
    $status = 'Berlisensi';
    $keterangan = "Terpenuhi dari total {$totalQuota} kuota lisensi universitas.";
    $licenseId = $activeLicenses->first()?->id;
}
```

---

### 2.3 Refactoring `LicenseDataController.php` & `ReportController.php`

Pada `LicenseDataController::index()`, saat menghitung status lisensi (Aman / Mendekati Habis / Over Limit):
- Jangan bandingkan instalasi global langsung ke `quota_limit` tiap baris jika ada beberapa lisensi untuk satu software.
- Tampilkan informasi:
  1. Kuota baris lisensi ini (`quota_limit`).
  2. Total kuota gabungan software bersangkutan (`total_catalog_quota`).
  3. Total kursi yang sudah dialokasikan ke fakultas dari lisensi ini (`allocated_seats`).
  4. Sisa kursi lisensi ini yang masih dapat dialokasikan (`remaining_unallocated`).

Pada `ReportController::showLisensi()`:
- Hitung persentase pemakaian lisensi berdasarkan akumulasi alokasi dan instalasi yang benar, hindari angka persentase palsu akibat perbandingan per-baris tanpa konteks total kuota.

---

## 3. Langkah-Langkah Pengerjaan (Step-by-Step)

1. Buat class service baru:
   ```bash
   php artisan make:class Services/LicenseComplianceService
   ```
2. Tulis method-method kalkulasi agregasi pada `LicenseComplianceService.php`.
3. Buka `app/Jobs/GenerateComplianceReportJob.php`, cari pemanggilan `$catalog->licenses->first()`, lalu ganti dengan logika agregasi multi-lisensi.
4. Perbarui method di `LicenseDataController.php` agar status kuota merefleksikan alokasi dan total kepemilikan.
5. Perbarui `ReportController.php` pada pembuatan report lisensi.
6. Buat unit/feature test `tests/Feature/LicenseCalculationAccuracyTest.php` untuk menguji skenario kepemilikan ganda (*multiple license inventory*).
7. Jalankan suite testing untuk memvalidasi tidak ada regresi pada job scan session.

---

## 4. Kriteria Keberhasilan (Acceptance Criteria)
- [ ] Tidak ada lagi pemanggilan kode `$catalog->licenses->first()` yang mengevaluasi kuota software secara parsial di `GenerateComplianceReportJob`.
- [ ] Software dengan multiple inventory lisensi (misal 20 seat + 30 seat) memiliki total entitlement yang benar dihitung sebesar 50 seat.
- [ ] Jika instalasi aktual sebanyak 45 komputer, sistem menetapkan status **Berlisensi** (karena 45 $\le$ 50), dan BUKAN over limit.
- [ ] Lisensi yang telah kedaluwarsa (*expired*) secara otomatis dikeluarkan dari perhitungan kuota aktif.
- [ ] Seluruh controller yang menampilkan agregasi lisensi menggunakan metode kalkulasi yang konsisten.

---

## 5. Rencana Pengujian Otomatis (Pest Test)
Buat file `tests/Feature/LicenseCalculationAccuracyTest.php`:
1. `it('aggregates multiple license inventories for the same software catalog correctly')`
2. `it('marks compliance as compliant when installations exceed first license but are within total licenses')`
   *(Skenario: Lic 1 = 10, Lic 2 = 15, Installed = 18. Expected: Berlisensi, bukan over limit)*
3. `it('excludes expired licenses from active entitlement calculation')`
4. `it('calculates unallocated remaining quota accurately')`
5. `it('handles zero license commercial software with unlicenced status')`
