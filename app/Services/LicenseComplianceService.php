<?php

namespace App\Services;

use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use Illuminate\Database\Eloquent\Collection;
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
     * Mengambil koleksi lisensi aktif untuk sebuah software catalog.
     *
     * @return Collection<int, LicenseInventory>
     */
    public function getActiveLicenses(int $catalogId): Collection
    {
        return LicenseInventory::where('catalog_id', $catalogId)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now()->toDateString());
            })
            ->get();
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
     *
     * @return array{catalog_id: int, software_name: string, owned: int, allocated: int, installed: int, unallocated: int, deficit: int, surplus: int, status: string, is_compliant: bool}
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
            default => 'Berlisensi',
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
