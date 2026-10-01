<?php

namespace App\Http\Controllers;

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->hasRole('staff_lab')) {
            return redirect()->route('lab.inventory.index');
        }

        if ($user->hasRole('kepala_lab')) {
            return $this->labDashboard($user, $request);
        }

        if ($user->hasRole('pimpinan')) {
            return $this->pimpinanDashboard($request);
        }

        return $this->adminDashboard($request);
    }

    /**
     * Endpoint to fetch JSON chart trend data for scans and compliance.
     */
    public function chartData(Request $request): JsonResponse
    {
        $user = auth()->user();
        $period = $request->query('period', '7d');
        $labId = $request->filled('laboratory_id') ? (int) $request->query('laboratory_id') : null;

        $chartData = $this->getChartDataArray($user, $period, $labId);

        return response()->json($chartData);
    }

    private function labDashboard(User $user, Request $request)
    {
        $lab = $user->laboratory;

        if (! $lab) {
            return view('dashboard.no-lab');
        }

        $labId = $lab->id;
        $period = $request->query('period', '7d');

        $monitoringStats = $this->getMonitoringStats($user, $labId);
        $unscannedComputers = $this->getUnscannedComputers($user, $labId);
        $chartData = $this->getChartDataArray($user, $period, $labId);

        $totalComputers = Computer::where('laboratory_id', $labId)->count();
        $scannedThisMonth = Computer::where('laboratory_id', $labId)
            ->whereMonth('last_seen_at', now()->month)
            ->whereYear('last_seen_at', now()->year)
            ->count();

        $licensedOS = Computer::where('laboratory_id', $labId)->where('os_license_status', 'Licensed')->count();
        $complianceRate = $totalComputers > 0 ? round(($licensedOS / $totalComputers) * 100, 1) : 0;
        $totalSoftware = SoftwareDiscovery::whereHas('computer', fn ($q) => $q->where('laboratory_id', $labId))->count();
        $pendingReports = ReportApproval::where('laboratory_id', $labId)->where('status', 'pending')->count();

        $stats = [
            'total_computers' => $totalComputers,
            'scanned_this_month' => $scannedThisMonth,
            'licensed_os' => $licensedOS,
            'compliance_rate' => $complianceRate,
            'total_software' => $totalSoftware,
            'pending_reports' => $pendingReports,
        ];

        $topSoftware = SoftwareDiscovery::whereHas('computer', fn ($q) => $q->where('laboratory_id', $labId))
            ->with('catalog')
            ->selectRaw('catalog_id, COUNT(*) as total')
            ->groupBy('catalog_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->catalog ? $item->catalog->normalized_name : 'Unknown Application',
                    'total' => $item->total,
                ];
            });

        $recentComputers = Computer::where('laboratory_id', $labId)
            ->orderByDesc('last_seen_at')
            ->take(5)
            ->get();

        $recentApprovals = ReportApproval::where('laboratory_id', $labId)
            ->latest()
            ->take(3)
            ->get();

        return view('dashboard.kepala-lab', compact(
            'lab',
            'stats',
            'monitoringStats',
            'unscannedComputers',
            'chartData',
            'period',
            'topSoftware',
            'recentComputers',
            'recentApprovals'
        ));
    }

    private function pimpinanDashboard(Request $request)
    {
        $user = auth()->user();
        $currentPeriod = now()->format('Y-m');
        $period = $request->query('period', '7d');
        $selectedLabId = $request->filled('laboratory_id') ? (int) $request->query('laboratory_id') : null;

        $approvedLabIds = ReportApproval::where('status', 'approved')
            ->where('report_type', 'kepatuhan')
            ->where('period', $currentPeriod)
            ->pluck('laboratory_id');

        $laboratories = Laboratory::whereIn('id', $approvedLabIds)->orderBy('name')->get();

        $monitoringStats = $this->getMonitoringStats($user, $selectedLabId);
        $unscannedComputers = $this->getUnscannedComputers($user, $selectedLabId);
        $chartData = $this->getChartDataArray($user, $period, $selectedLabId);

        $totalLabs = Laboratory::count();
        $approvedLabsCount = $approvedLabIds->count();
        $totalComputers = Computer::whereIn('laboratory_id', $approvedLabIds)->count();
        $licensedOS = Computer::whereIn('laboratory_id', $approvedLabIds)->where('os_license_status', 'Licensed')->count();
        $complianceRate = $totalComputers > 0 ? round(($licensedOS / $totalComputers) * 100, 1) : 0;

        $stats = [
            'total_computers' => $totalComputers,
            'approved_labs' => $approvedLabsCount,
            'total_labs' => $totalLabs,
            'compliance_rate' => $complianceRate,
            'licensed_os' => $licensedOS,
        ];

        $laboratoriesStatus = Laboratory::withCount('computers')
            ->with(['reportApprovals' => function ($q) use ($currentPeriod) {
                $q->where('period', $currentPeriod)->where('report_type', 'kepatuhan')->latest();
            }])
            ->get();

        return view('dashboard.pimpinan', compact(
            'stats',
            'approvedLabIds',
            'laboratoriesStatus',
            'currentPeriod',
            'period',
            'selectedLabId',
            'laboratories',
            'monitoringStats',
            'unscannedComputers',
            'chartData'
        ));
    }

    private function adminDashboard(Request $request)
    {
        $user = auth()->user();
        $period = $request->query('period', '7d');
        $selectedLabId = $request->filled('laboratory_id') ? (int) $request->query('laboratory_id') : null;

        $statsKey = 'dashboard.stats.'.now()->format('Y-m');
        if ($selectedLabId || $period !== '7d') {
            $statsKey .= '.'.($selectedLabId ?? 'all').'.'.$period;
        }

        // --- 1. STATISTIK UTAMA (TTL: 10 Menit) ---
        $stats = Cache::remember($statsKey, 600, function () use ($user, $selectedLabId, $period) {
            $totalComputers = Computer::count();

            return [
                'totalComputers' => $totalComputers,
                'newComputersThisMonth' => Computer::where('created_at', '>=', now()->startOfMonth())->count(),
                'totalInstallations' => SoftwareDiscovery::count(),
                'newInstallationThisMonth' => SoftwareDiscovery::where('created_at', '>=', now()->startOfMonth())->count(),
                'uniqueSoftwares' => SoftwareDiscovery::distinct('raw_name')->count(),
                'unlicensedOS' => Computer::where('os_license_status', '!=', 'Licensed')->count(),
                'computersWithBlacklist' => SoftwareDiscovery::whereHas('catalog', function ($q) {
                    $q->where('status', 'Blacklist');
                })->distinct('computer_id')->count(),
                'healthyComputers' => Computer::where('os_license_status', 'Licensed')
                    ->whereDoesntHave('softwares.catalog', function ($q) {
                        $q->where('status', 'Blacklist');
                    })->count(),
                'inactiveComputers' => Computer::where('last_seen_at', '<', now()->subDays(7))->count(),
                'monitoringStats' => $this->getMonitoringStats($user, $selectedLabId),
                'unscannedComputers' => $this->getUnscannedComputers($user, $selectedLabId),
                'chartData' => $this->getChartDataArray($user, $period, $selectedLabId),
                'laboratories' => Laboratory::orderBy('name')->get(),
            ];
        });

        $totalComputers = $stats['totalComputers'];
        $newComputersThisMonth = $stats['newComputersThisMonth'];
        $totalInstallations = $stats['totalInstallations'];
        $newInstallationThisMonth = $stats['newInstallationThisMonth'];
        $uniqueSoftwares = $stats['uniqueSoftwares'];
        $criticalAlerts = $stats['unlicensedOS'] + $stats['computersWithBlacklist'];
        $systemHealth = $totalComputers > 0 ? round(($stats['healthyComputers'] / $totalComputers) * 100) : 0;
        $inactiveComputers = $stats['inactiveComputers'];
        $monitoringStats = $stats['monitoringStats'];
        $unscannedComputers = $stats['unscannedComputers'];
        $chartData = $stats['chartData'];
        $laboratories = $stats['laboratories'];

        // --- 2. CHART & ACTIVITY (TTL: 5 Menit) ---
        $data = Cache::remember('dashboard.charts', 300, function () {
            $osStats = Computer::select(DB::raw("
                CASE 
                    WHEN os_name LIKE '%Windows 10%' THEN 'Windows 10'
                    WHEN os_name LIKE '%Windows 11%' THEN 'Windows 11'
                    WHEN os_name LIKE '%Ubuntu%' THEN os_name
                    ELSE 'Others'
                END as normalized_os
            "), DB::raw('count(*) as total'))
                ->groupBy('normalized_os')
                ->orderByDesc('total')
                ->get();

            $osLabels = [];
            $osSeries = [];

            foreach ($osStats as $stat) {
                $osLabels[] = $stat->normalized_os ?: 'Unknown';
                $osSeries[] = $stat->total;
            }

            $licenseStats = Computer::select('os_license_status', DB::raw('count(*) as total'))
                ->groupBy('os_license_status')
                ->orderByDesc('total')
                ->pluck('total', 'os_license_status')
                ->toArray();

            $licenseLabels = ['Licensed', 'Grace Period', 'Unlicensed', 'Notification', 'Unknown'];
            $licenseSeries = [];

            foreach ($licenseLabels as $label) {
                if ($label === 'Unlicensed') {
                    $count = ($licenseStats['Unlicensed'] ?? 0) + ($licenseStats['Notification'] ?? 0) + ($licenseStats['OOB Grace'] ?? 0);
                    $licenseSeries[] = $count;
                } elseif ($label === 'Notification' || $label === 'Unknown') {
                    continue;
                } else {
                    $licenseSeries[] = $licenseStats[$label] ?? 0;
                }
            }

            $topSoftware = SoftwareDiscovery::with('catalog')
                ->selectRaw('catalog_id, COUNT(*) as total')
                ->groupBy('catalog_id')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->catalog ? $item->catalog->normalized_name : 'Unknown Application',
                        'total' => $item->total,
                    ];
                });

            $recentActivities = Computer::withCount('softwares')
                ->with(['softwares.catalog' => function ($q) {
                    $q->where('status', 'Blacklist');
                }])
                ->orderBy('last_seen_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($computer) {
                    $hasBlacklist = $computer->softwares->some(fn ($s) => $s->catalog && $s->catalog->status === 'Blacklist');

                    $status = 'success';
                    $statusText = 'Aman';

                    if ($hasBlacklist) {
                        $status = 'destructive';
                        $statusText = 'Masalah Software';
                    } elseif ($computer->os_license_status !== 'Licensed') {
                        $status = 'warning';
                        $statusText = 'Masalah OS';
                    }

                    return [
                        'id' => $computer->id,
                        'computer' => $computer->hostname,
                        'time' => $computer->last_seen_at ? $computer->last_seen_at->diffForHumans() : '-',
                        'status' => $status,
                        'statusText' => $statusText,
                        'software' => $computer->softwares_count,
                    ];
                });

            return [
                'osLabels' => $osLabels,
                'osSeries' => $osSeries,
                'licenseSeries' => $licenseSeries,
                'recentActivities' => $recentActivities,
                'topSoftware' => $topSoftware,
            ];
        });

        $osLabels = $data['osLabels'];
        $osSeries = $data['osSeries'];
        $licenseLabelsChart = ['Berlisensi', 'Masa Tenggang', 'Perlu Tindakan'];
        $licenseSeries = $data['licenseSeries'];
        $recentActivities = $data['recentActivities'];
        $topSoftware = $data['topSoftware'];

        return view('pages.admin.dashboard', compact(
            'totalComputers',
            'newComputersThisMonth',
            'totalInstallations',
            'newInstallationThisMonth',
            'uniqueSoftwares',
            'systemHealth',
            'criticalAlerts',
            'osLabels',
            'osSeries',
            'licenseLabelsChart',
            'licenseSeries',
            'recentActivities',
            'inactiveComputers',
            'topSoftware',
            'period',
            'selectedLabId',
            'laboratories',
            'monitoringStats',
            'unscannedComputers',
            'chartData'
        ));
    }

    /**
     * Resolve date range from period query parameter.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(?string $period): array
    {
        return match ($period) {
            '30d' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfDay()],
            '3m' => [now()->subMonths(3)->startOfDay(), now()->endOfDay()],
            default => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
        };
    }

    /**
     * Compute periodic monitoring statistics.
     *
     * @return array<string, int>
     */
    private function getMonitoringStats(User $user, ?int $labId = null): array
    {
        $computerQuery = Computer::active()->forUserLab($user)->when($labId, fn ($q) => $q->where('laboratory_id', $labId));

        if ($user->hasRole('pimpinan') && ! $labId) {
            $currentPeriod = now()->format('Y-m');
            $approvedLabIds = ReportApproval::where('status', 'approved')
                ->where('report_type', 'kepatuhan')
                ->where('period', $currentPeriod)
                ->pluck('laboratory_id')
                ->toArray();

            $computerQuery->whereIn('laboratory_id', $approvedLabIds);
        }

        $totalComputers = (clone $computerQuery)->count();

        $scannedTodayComputerIds = ScanSession::forUserLab($user)
            ->when($labId, fn ($q) => $q->forLaboratory($labId))
            ->whereDate('started_at', today())
            ->where('status', 'completed')
            ->distinct()
            ->pluck('computer_id')
            ->toArray();

        $scannedToday = (clone $computerQuery)->whereIn('id', $scannedTodayComputerIds)->count();
        $unscannedToday = max(0, $totalComputers - $scannedToday);

        $scansCompletedToday = ScanSession::forUserLab($user)
            ->when($labId, fn ($q) => $q->forLaboratory($labId))
            ->whereDate('started_at', today())
            ->where('status', 'completed')
            ->count();

        $scansFailedToday = ScanSession::forUserLab($user)
            ->when($labId, fn ($q) => $q->forLaboratory($labId))
            ->whereDate('started_at', today())
            ->where('status', 'failed')
            ->count();

        if ($labId) {
            $totalLabs = 1;
        } elseif ($user->hasRole('kepala_lab')) {
            $totalLabs = $user->laboratory_id ? 1 : 0;
        } elseif ($user->hasRole('pimpinan')) {
            $currentPeriod = now()->format('Y-m');
            $totalLabs = ReportApproval::where('status', 'approved')
                ->where('report_type', 'kepatuhan')
                ->where('period', $currentPeriod)
                ->count();
        } else {
            $totalLabs = Laboratory::count();
        }

        $identifiedSoftwares = ScanSoftwareResult::forUserLab($user)
            ->when($labId, fn ($q) => $q->forLaboratory($labId))
            ->distinct('raw_name')
            ->count('raw_name');

        if ($identifiedSoftwares === 0) {
            $identifiedSoftwares = SoftwareDiscovery::forUserLab($user)
                ->when($labId, fn ($q) => $q->whereHas('computer', fn ($c) => $c->where('laboratory_id', $labId)))
                ->distinct('raw_name')
                ->count('raw_name');
        }

        $actionableFindings = ComplianceSnapshot::forUserLab($user)
            ->when($labId, fn ($q) => $q->forLaboratory($labId))
            ->whereIn('status', ['Perlu Ditinjau', 'Perlu Tindakan'])
            ->count();

        return [
            'total_labs' => $totalLabs,
            'total_computers' => $totalComputers,
            'scanned_today' => $scannedToday,
            'unscanned_today' => $unscannedToday,
            'scans_completed_today' => $scansCompletedToday,
            'scans_failed_today' => $scansFailedToday,
            'identified_softwares' => $identifiedSoftwares,
            'actionable_findings' => $actionableFindings,
        ];
    }

    /**
     * Retrieve active computers without a completed scan today.
     */
    private function getUnscannedComputers(User $user, ?int $labId = null)
    {
        $computerQuery = Computer::active()->forUserLab($user)->when($labId, fn ($q) => $q->where('laboratory_id', $labId));

        if ($user->hasRole('pimpinan') && ! $labId) {
            $currentPeriod = now()->format('Y-m');
            $approvedLabIds = ReportApproval::where('status', 'approved')
                ->where('report_type', 'kepatuhan')
                ->where('period', $currentPeriod)
                ->pluck('laboratory_id')
                ->toArray();

            $computerQuery->whereIn('laboratory_id', $approvedLabIds);
        }

        $scannedTodayComputerIds = ScanSession::forUserLab($user)
            ->when($labId, fn ($q) => $q->forLaboratory($labId))
            ->whereDate('started_at', today())
            ->where('status', 'completed')
            ->distinct()
            ->pluck('computer_id')
            ->toArray();

        return $computerQuery
            ->whereNotIn('id', $scannedTodayComputerIds)
            ->with('laboratory')
            ->orderBy('hostname')
            ->take(10)
            ->get();
    }

    /**
     * Generate chart trend data for scans and compliance.
     */
    private function getChartDataArray(User $user, string $period = '7d', ?int $labId = null): array
    {
        if ($user->hasRole('kepala_lab')) {
            $labId = $user->laboratory_id;
        }

        $approvedLabIds = [];
        if ($user->hasRole('pimpinan')) {
            $currentPeriod = now()->format('Y-m');
            $approvedLabIds = ReportApproval::where('status', 'approved')
                ->where('report_type', 'kepatuhan')
                ->where('period', $currentPeriod)
                ->pluck('laboratory_id')
                ->toArray();

            if ($labId && ! in_array($labId, $approvedLabIds)) {
                $labId = -1;
            }
        }

        [$startDate, $endDate] = $this->resolveDateRange($period);

        $labels = [];
        $completed = [];
        $failed = [];
        $berlisensi = [];
        $tidakBerlisensi = [];
        $gracePeriod = [];
        $perluDitinjau = [];

        // Sessions aggregate
        $sessionQuery = ScanSession::forUserLab($user)
            ->when($labId, fn ($q) => $q->forLaboratory($labId))
            ->whereBetween('started_at', [$startDate, $endDate]);

        if ($user->hasRole('pimpinan') && ! $labId) {
            $sessionQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $approvedLabIds));
        }

        $sessions = $sessionQuery
            ->selectRaw('DATE(started_at) as scan_date, status, COUNT(*) as aggregate')
            ->groupBy('scan_date', 'status')
            ->get();

        // Snapshots aggregate
        $snapshotQuery = ComplianceSnapshot::forUserLab($user)
            ->when($labId, fn ($q) => $q->forLaboratory($labId))
            ->whereBetween('scanned_at', [$startDate, $endDate]);

        if ($user->hasRole('pimpinan') && ! $labId) {
            $snapshotQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $approvedLabIds));
        }

        $snapshots = $snapshotQuery
            ->selectRaw('DATE(scanned_at) as snap_date, status, COUNT(*) as aggregate')
            ->groupBy('snap_date', 'status')
            ->get();

        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateKey = $current->format('Y-m-d');
            $labels[] = $current->translatedFormat('d M');

            $daySessions = $sessions->where('scan_date', $dateKey);
            $completed[] = (int) ($daySessions->where('status', 'completed')->first()?->aggregate ?? 0);
            $failed[] = (int) ($daySessions->where('status', 'failed')->first()?->aggregate ?? 0);

            $daySnaps = $snapshots->where('snap_date', $dateKey);
            $berlisensi[] = (int) ($daySnaps->where('status', 'Berlisensi')->first()?->aggregate ?? 0);
            $tidakBerlisensi[] = (int) ($daySnaps->where('status', 'Tidak Berlisensi')->first()?->aggregate ?? 0);
            $gracePeriod[] = (int) ($daySnaps->where('status', 'Grace Period')->first()?->aggregate ?? 0);
            $perluDitinjau[] = (int) ($daySnaps->whereIn('status', ['Perlu Ditinjau', 'Perlu Tindakan'])->sum('aggregate') ?? 0);

            $current->addDay();
        }

        return [
            'scan_trend' => [
                'labels' => $labels,
                'completed' => $completed,
                'failed' => $failed,
            ],
            'compliance_trend' => [
                'labels' => $labels,
                'berlisensi' => $berlisensi,
                'tidak_berlisensi' => $tidakBerlisensi,
                'grace_period' => $gracePeriod,
                'perlu_ditinjau' => $perluDitinjau,
            ],
        ];
    }
}
