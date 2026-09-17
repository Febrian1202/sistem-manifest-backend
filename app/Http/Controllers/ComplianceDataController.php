<?php

namespace App\Http\Controllers;

use App\Models\ReportApproval;
use App\Models\SoftwareCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ComplianceDataController extends Controller
{
    public function index()
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
                        ->with('computer:id,hostname,ip_address');
                },
            ])
            // Urutkan berdasarkan selisih (deficit) secara langsung di level database
            ->orderByRaw('(discoveries_count - COALESCE(owned_count, 0)) DESC')
            ->paginate(20)
            ->through(function ($software) {
                // Tambahkan atribut virtual untuk kebutuhan tampilan view
                $installed = $software->discoveries_count ?? 0;
                $owned = $software->owned_count ?? 0;
                $deficit = $installed > $owned ? $installed - $owned : 0;

                $software->installed_count = $installed;
                $software->owned_count = $owned;
                $software->deficit = $deficit;
                $software->is_compliant = $deficit === 0;

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

        return view('pages.admin.compliance', compact(
            'softwares',
            'stats',
            'totalCount',
            'nonCompliantCount',
            'compliantCount',
            'isPimpinan',
            'hasApprovedLabs',
            'currentPeriod'
        ));
    }
}
