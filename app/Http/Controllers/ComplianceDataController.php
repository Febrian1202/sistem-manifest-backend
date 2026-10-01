<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\ReportApproval;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Services\LicenseComplianceService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComplianceDataController extends Controller
{
    public function __construct(
        protected LicenseComplianceService $complianceService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $isKepalaLab = $user->hasRole('kepala_lab');
        $isPimpinan = $user->hasRole('pimpinan');
        $currentPeriod = now()->format('Y-m');

        $approvedLabIds = null;
        if ($isPimpinan) {
            $approvedLabIds = ReportApproval::where('status', 'approved')
                ->where('report_type', 'kepatuhan')
                ->where('period', $currentPeriod)
                ->pluck('laboratory_id');
        }

        $faculties = Faculty::orderBy('name')->get();
        $facultyId = $request->query('faculty_id');
        $selectedFaculty = ($facultyId && ! $isKepalaLab) ? Faculty::find($facultyId) : null;

        // MODE 1: Filter per Fakultas
        if ($selectedFaculty) {
            $breakdown = $this->complianceService->getFacultyComplianceBreakdown(
                $selectedFaculty->id,
                $isPimpinan ? $approvedLabIds : null
            );

            // Ambil data penemuan (discoveries) untuk modal Lacak PC / Rincian Lab
            $catalogIds = $breakdown->pluck('catalog_id')->all();
            $discoveriesByCatalog = SoftwareDiscovery::whereIn('catalog_id', $catalogIds)
                ->whereHas('computer', function ($q) use ($selectedFaculty, $isPimpinan, $approvedLabIds) {
                    $q->where('status', 'active')
                        ->whereHas('laboratory', fn ($l) => $l->where('faculty_id', $selectedFaculty->id));

                    if ($isPimpinan && $approvedLabIds) {
                        $q->whereIn('laboratory_id', $approvedLabIds);
                    }
                })
                ->with(['computer.laboratory:id,name,faculty_id'])
                ->get()
                ->groupBy('catalog_id');

            $breakdownObjects = $breakdown->map(function ($item) use ($discoveriesByCatalog) {
                $obj = (object) $item;
                $obj->owned_count = $this->complianceService->getActiveEntitlement($item['catalog_id']);
                $obj->owned = $obj->owned_count;
                $obj->allocated_count = $item['allocated'];
                $obj->discoveries = $discoveriesByCatalog->get($item['catalog_id'], collect());
                $obj->lab_distribution = $obj->discoveries
                    ->groupBy(fn ($d) => $d->computer?->laboratory?->name ?? 'Tidak Ditugaskan')
                    ->map(fn ($group) => $group->count());

                return $obj;
            });

            $totalCount = $breakdown->count();
            $compliantCount = $breakdown->where('is_compliant', true)->count();
            $nonCompliantCount = $breakdown->where('is_compliant', false)->count();

            $stats = [
                'total_commercial' => $totalCount,
                'total_allocated' => (int) $breakdown->sum('allocated'),
                'total_installed' => (int) $breakdown->sum('installed'),
                'total_deficit' => (int) $breakdown->sum('deficit'),
                'total_surplus' => (int) $breakdown->sum('surplus'),
                'compliant' => $compliantCount,
                'non_compliant' => $nonCompliantCount,
            ];

            // Paginasi manual untuk collection
            $page = (int) $request->query('page', 1);
            $perPage = 20;
            $pagedData = $breakdownObjects->slice(($page - 1) * $perPage, $perPage)->values();
            $softwares = new LengthAwarePaginator(
                $pagedData,
                $breakdownObjects->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            $hasApprovedLabs = ! $isPimpinan || ($approvedLabIds && $approvedLabIds->isNotEmpty());
            $crossFacultyMatrix = collect();

            return view('pages.admin.compliance', compact(
                'softwares',
                'stats',
                'totalCount',
                'nonCompliantCount',
                'compliantCount',
                'isPimpinan',
                'isKepalaLab',
                'hasApprovedLabs',
                'currentPeriod',
                'faculties',
                'selectedFaculty',
                'crossFacultyMatrix'
            ));
        }

        // MODE 2: Mode Global / Institusi (Semua Fakultas atau Kepala Lab)
        // Closure to scope discoveries by role
        $scopeDiscoveriesByRole = function ($query) use ($isKepalaLab, $isPimpinan, $user, $approvedLabIds) {
            if ($isKepalaLab) {
                $query->whereHas('computer', fn ($q) => $q->where('laboratory_id', $user->laboratory_id ?? 0));
            } elseif ($isPimpinan) {
                $query->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $approvedLabIds ?? collect()));
            }
        };

        // 1. Ambil software berbayar (Commercial) dengan agregasi dalam SATU query
        $softwares = SoftwareCatalog::where('category', 'Commercial')
            ->withCount([
                'discoveries' => function ($query) use ($scopeDiscoveriesByRole) {
                    $scopeDiscoveriesByRole($query);
                    $query->select(DB::raw('count(distinct(computer_id))'));
                },
            ])
            ->withSum('licenses as owned_count', 'quota_limit')
            ->with([
                'discoveries' => function ($query) use ($scopeDiscoveriesByRole, $isKepalaLab, $isPimpinan, $user, $approvedLabIds) {
                    $scopeDiscoveriesByRole($query);
                    $query->select('id', 'catalog_id', 'computer_id', 'version', 'created_at')
                        ->whereIn('id', function ($q) use ($isKepalaLab, $isPimpinan, $user, $approvedLabIds) {
                            $subQuery = $q->select(DB::raw('MAX(id)'))
                                ->from('software_discoveries');
                            if ($isKepalaLab) {
                                $subQuery->whereIn('computer_id', function ($cq) use ($user) {
                                    $cq->select('id')->from('computers')->where('laboratory_id', $user->laboratory_id ?? 0);
                                });
                            } elseif ($isPimpinan) {
                                $subQuery->whereIn('computer_id', function ($cq) use ($approvedLabIds) {
                                    $cq->select('id')->from('computers')->whereIn('laboratory_id', $approvedLabIds ?? collect());
                                });
                            }
                            $subQuery->groupBy('computer_id', 'catalog_id');
                        })
                        ->with(['computer' => function ($cq) {
                            $cq->select('id', 'hostname', 'ip_address', 'laboratory_id')
                                ->with('laboratory:id,name,faculty_id');
                        }]);
                },
            ])
            // Urutkan berdasarkan selisih (deficit) secara langsung di level database
            ->orderByRaw('(discoveries_count - COALESCE(owned_count, 0)) DESC')
            ->paginate(20)
            ->through(function ($software) {
                $installed = $software->discoveries_count ?? 0;
                $owned = $software->owned_count ?? 0;
                $allocated = $this->complianceService->getTotalAllocated($software->id);
                $deficit = $installed > $owned ? $installed - $owned : 0;
                $surplus = $owned > $installed ? $owned - $installed : 0;

                $software->installed_count = $installed;
                $software->owned_count = $owned;
                $software->allocated = $allocated;
                $software->allocated_count = $allocated;
                $software->unallocated_count = max(0, $owned - $allocated);
                $software->deficit = $deficit;
                $software->surplus = $surplus;
                $software->is_compliant = $deficit === 0;

                // Persebaran per lab untuk sheet
                $software->lab_distribution = $software->discoveries
                    ->groupBy(fn ($d) => $d->computer?->laboratory?->name ?? 'Tidak Ditugaskan')
                    ->map(fn ($group) => $group->count());

                return $software;
            });

        // 2. Hitung Statistik Global (Efisien dengan Cache per role)
        if ($isKepalaLab) {
            $cacheKey = "compliance.stats.lab_{$user->laboratory_id}";
        } elseif ($isPimpinan) {
            $hash = md5(($approvedLabIds ?? collect())->sort()->implode(','));
            $cacheKey = "compliance.stats.pimpinan_{$currentPeriod}_{$hash}";
        } else {
            $cacheKey = 'compliance.stats.admin';
        }

        $stats = Cache::remember($cacheKey, 300, function () use ($scopeDiscoveriesByRole) {
            $allCommercial = SoftwareCatalog::where('category', 'Commercial')
                ->withCount([
                    'discoveries' => function ($query) use ($scopeDiscoveriesByRole) {
                        $scopeDiscoveriesByRole($query);
                        $query->select(DB::raw('count(distinct(computer_id))'));
                    },
                ])
                ->withSum('licenses as owned_count', 'quota_limit')
                ->get();

            $total = $allCommercial->count();
            $compliant = $allCommercial->where(fn ($s) => $s->discoveries_count <= ($s->owned_count ?? 0))->count();

            return [
                'total_commercial' => $total,
                'total_deficit' => $allCommercial->sum(fn ($s) => max(0, $s->discoveries_count - ($s->owned_count ?? 0))),
                'compliant' => $compliant,
                'non_compliant' => $total - $compliant,
            ];
        });

        $totalCount = $stats['total_commercial'];
        $nonCompliantCount = $stats['non_compliant'];
        $compliantCount = $stats['compliant'];
        $hasApprovedLabs = ! $isPimpinan || ($approvedLabIds && $approvedLabIds->isNotEmpty());

        $crossFacultyMatrix = (! $isKepalaLab)
            ? $this->complianceService->getCrossFacultyMatrix()
            : collect();

        return view('pages.admin.compliance', compact(
            'softwares',
            'stats',
            'totalCount',
            'nonCompliantCount',
            'compliantCount',
            'isPimpinan',
            'isKepalaLab',
            'hasApprovedLabs',
            'currentPeriod',
            'faculties',
            'selectedFaculty',
            'crossFacultyMatrix'
        ));
    }
}
