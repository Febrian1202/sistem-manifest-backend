<?php

namespace App\Services;

use App\Models\Faculty;
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

    /**
     * Menghitung total alokasi lisensi aktif untuk sebuah software catalog di fakultas tertentu.
     */
    public function getFacultyAllocated(int $catalogId, int $facultyId): int
    {
        return (int) DB::table('license_allocations')
            ->join('license_inventories', 'license_allocations.license_inventory_id', '=', 'license_inventories.id')
            ->where('license_inventories.catalog_id', $catalogId)
            ->where('license_allocations.faculty_id', $facultyId)
            ->where('license_allocations.status', 'active')
            ->sum('license_allocations.allocated_quota');
    }

    /**
     * Menghitung rekapitulasi kepatuhan seluruh software komersial untuk sebuah fakultas tertentu.
     *
     * @param  \Illuminate\Support\Collection|array|null  $allowedLabIds
     */
    public function getFacultyComplianceBreakdown(int $facultyId, mixed $allowedLabIds = null): \Illuminate\Support\Collection
    {
        $commercialCatalogs = SoftwareCatalog::where('category', 'Commercial')->get();

        return $commercialCatalogs->map(function ($catalog) use ($facultyId, $allowedLabIds) {
            $allocated = $this->getFacultyAllocated($catalog->id, $facultyId);

            $discoveryQuery = SoftwareDiscovery::where('catalog_id', $catalog->id)
                ->whereHas('computer', function ($q) use ($facultyId, $allowedLabIds) {
                    $q->where('status', 'active')
                        ->whereHas('laboratory', fn ($l) => $l->where('faculty_id', $facultyId));

                    if ($allowedLabIds !== null) {
                        $q->whereIn('laboratory_id', $allowedLabIds);
                    }
                });

            $installed = (int) $discoveryQuery->distinct('computer_id')->count('computer_id');

            $deficit = max(0, $installed - $allocated);
            $surplus = max(0, $allocated - $installed);

            $utilizationRate = $allocated > 0
                ? round(($installed / $allocated) * 100, 1)
                : null;

            $status = match (true) {
                $allocated === 0 && $installed > 0 => 'Tanpa Alokasi (Defisit Penuh)',
                $installed > $allocated => 'Defisit',
                $installed < $allocated => 'Surplus',
                default => 'Cukup (Sesuai Alokasi)',
            };

            return [
                'catalog_id' => $catalog->id,
                'software_name' => $catalog->normalized_name,
                'normalized_name' => $catalog->normalized_name,
                'allocated' => $allocated,
                'installed' => $installed,
                'installed_count' => $installed,
                'deficit' => $deficit,
                'surplus' => $surplus,
                'utilization_rate' => $utilizationRate,
                'status' => $status,
                'is_compliant' => $deficit === 0,
            ];
        })->filter(fn ($item) => $item['allocated'] > 0 || $item['installed'] > 0)->values();
    }

    /**
     * Menghasilkan matriks komparasi kepatuhan seluruh fakultas untuk ringkasan eksekutif.
     *
     * @param  \Illuminate\Support\Collection|array|null  $allowedLabIds
     */
    public function getCrossFacultyMatrix(mixed $allowedLabIds = null): \Illuminate\Support\Collection
    {
        $faculties = Faculty::withCount(['laboratories', 'computers'])->get();

        return $faculties->map(function ($faculty) use ($allowedLabIds) {
            $breakdown = $this->getFacultyComplianceBreakdown($faculty->id, $allowedLabIds);

            return [
                'faculty_id' => $faculty->id,
                'faculty_code' => $faculty->code,
                'faculty_name' => $faculty->name,
                'total_labs' => $faculty->laboratories_count,
                'total_computers' => $faculty->computers_count,
                'total_allocated_seats' => (int) $breakdown->sum('allocated'),
                'total_installed_seats' => (int) $breakdown->sum('installed'),
                'total_deficit' => (int) $breakdown->sum('deficit'),
                'total_surplus' => (int) $breakdown->sum('surplus'),
                'non_compliant_software_count' => $breakdown->where('deficit', '>', 0)->count(),
            ];
        });
    }

    /**
     * Menghasilkan analisis kebutuhan dan alokasi lisensi software komprehensif.
     *
     * @param  \Illuminate\Support\Collection|array|null  $allowedLabIds
     * @return array{
     *     summary: array{
     *         total_commercial_software: int,
     *         total_owned: int,
     *         total_allocated: int,
     *         total_unallocated: int,
     *         total_installed: int,
     *         total_deficit: int,
     *         total_surplus: int
     *     },
     *     faculty_distributions: \Illuminate\Support\Collection,
     *     procurement_insights: \Illuminate\Support\Collection
     * }
     */
    public function getLicenseNeedsAnalysis(mixed $allowedLabIds = null): array
    {
        $commercialCatalogs = SoftwareCatalog::where('category', 'Commercial')
            ->orderBy('normalized_name')
            ->get();

        $faculties = Faculty::withCount(['laboratories', 'computers'])
            ->orderBy('name')
            ->get();

        $facultyDistributions = $faculties->map(function ($faculty) use ($allowedLabIds) {
            $breakdown = $this->getFacultyComplianceBreakdown($faculty->id, $allowedLabIds)
                ->map(function ($item) {
                    $recommendation = match (true) {
                        $item['deficit'] > 0 => "Perlu tambahan {$item['deficit']} lisensi",
                        $item['surplus'] > 0 => "Surplus {$item['surplus']} lisensi (potensi redistribusi)",
                        default => 'Alokasi optimal (seimbang)',
                    };

                    $item['recommendation'] = $recommendation;

                    return $item;
                });

            return [
                'faculty' => $faculty,
                'breakdown' => $breakdown,
                'total_allocated' => (int) $breakdown->sum('allocated'),
                'total_installed' => (int) $breakdown->sum('installed'),
                'total_deficit' => (int) $breakdown->sum('deficit'),
                'total_surplus' => (int) $breakdown->sum('surplus'),
            ];
        });

        $procurementInsights = $commercialCatalogs->map(function ($catalog) use ($facultyDistributions, $allowedLabIds) {
            $owned = $this->getActiveEntitlement($catalog->id);

            // Total instalasi universitas
            $discoveryQuery = SoftwareDiscovery::where('catalog_id', $catalog->id)
                ->whereHas('computer', function ($q) use ($allowedLabIds) {
                    $q->where('status', 'active');
                    if ($allowedLabIds !== null) {
                        $q->whereIn('laboratory_id', $allowedLabIds);
                    }
                });
            $installed = (int) $discoveryQuery->distinct('computer_id')->count('computer_id');

            $allocated = $this->getTotalAllocated($catalog->id);
            $universityDeficit = max(0, $installed - $owned);
            $universitySurplus = max(0, $owned - $installed);

            // Deteksi persebaran defisit & surplus di level fakultas
            $facultyDeficits = [];
            $facultySurpluses = [];

            foreach ($facultyDistributions as $dist) {
                $softwareItem = $dist['breakdown']->firstWhere('catalog_id', $catalog->id);
                if ($softwareItem) {
                    if ($softwareItem['deficit'] > 0) {
                        $facultyDeficits[] = [
                            'faculty_name' => $dist['faculty']->name,
                            'faculty_code' => $dist['faculty']->code,
                            'deficit' => $softwareItem['deficit'],
                        ];
                    } elseif ($softwareItem['surplus'] > 0) {
                        $facultySurpluses[] = [
                            'faculty_name' => $dist['faculty']->name,
                            'faculty_code' => $dist['faculty']->code,
                            'surplus' => $softwareItem['surplus'],
                        ];
                    }
                }
            }

            $recommendation = match (true) {
                $universityDeficit > 0 && ! empty($facultySurpluses) => "Pengadaan baru minimal {$universityDeficit} unit disarankan. Evaluasi redistribusi dari fakultas yang surplus.",
                $universityDeficit > 0 => "Pengadaan baru minimal {$universityDeficit} unit lisensi disarankan untuk memenuhi kebutuhan instalasi.",
                ! empty($facultyDeficits) => 'Kapasitas lisensi universitas mencukupi secara total. Disarankan redistribusi alokasi antarfakultas.',
                default => 'Kapasitas dan alokasi lisensi berada dalam kondisi seimbang.',
            };

            return [
                'catalog_id' => $catalog->id,
                'software_name' => $catalog->normalized_name,
                'owned' => $owned,
                'allocated' => $allocated,
                'installed' => $installed,
                'net_deficit' => $universityDeficit,
                'net_surplus' => $universitySurplus,
                'faculty_deficits' => $facultyDeficits,
                'faculty_surpluses' => $facultySurpluses,
                'has_issues' => $universityDeficit > 0 || ! empty($facultyDeficits),
                'recommendation' => $recommendation,
            ];
        })->filter(fn ($item) => $item['has_issues'] || $item['installed'] > 0 || $item['owned'] > 0)
            ->sortByDesc('net_deficit')
            ->values();

        $totalOwned = $procurementInsights->sum('owned');
        $totalAllocated = $procurementInsights->sum('allocated');
        $totalInstalled = $procurementInsights->sum('installed');
        $totalDeficit = $procurementInsights->sum('net_deficit');
        $totalSurplus = $procurementInsights->sum('net_surplus');

        return [
            'summary' => [
                'total_commercial_software' => $commercialCatalogs->count(),
                'total_owned' => (int) $totalOwned,
                'total_allocated' => (int) $totalAllocated,
                'total_unallocated' => (int) max(0, $totalOwned - $totalAllocated),
                'total_installed' => (int) $totalInstalled,
                'total_deficit' => (int) $totalDeficit,
                'total_surplus' => (int) $totalSurplus,
            ],
            'faculty_distributions' => $facultyDistributions,
            'procurement_insights' => $procurementInsights,
        ];
    }
}
