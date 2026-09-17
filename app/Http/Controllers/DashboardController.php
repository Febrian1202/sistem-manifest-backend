<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole('kepala_lab')) {
            return $this->labDashboard($user);
        }

        if ($user->hasRole('pimpinan')) {
            return $this->pimpinanDashboard();
        }

        return $this->adminDashboard();
    }

    private function labDashboard(User $user)
    {
        $lab = $user->laboratory;

        if (! $lab) {
            return view('dashboard.no-lab');
        }

        $labId = $lab->id;
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

        return view('dashboard.kepala-lab', compact('lab', 'stats', 'topSoftware', 'recentComputers', 'recentApprovals'));
    }

    private function pimpinanDashboard()
    {
        $currentPeriod = now()->format('Y-m');

        $approvedLabIds = ReportApproval::where('status', 'approved')
            ->where('report_type', 'kepatuhan')
            ->where('period', $currentPeriod)
            ->pluck('laboratory_id');

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

        return view('dashboard.pimpinan', compact('stats', 'approvedLabIds', 'laboratoriesStatus', 'currentPeriod'));
    }

    private function adminDashboard()
    {
        // --- 1. STATISTIK UTAMA (TTL: 10 Menit) ---
        $stats = Cache::remember('dashboard.stats.'.now()->format('Y-m'), 600, function () {
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
                // IMPROVEMENT-001: Komputer tidak aktif (> 7 hari)
                'inactiveComputers' => Computer::where('last_seen_at', '<', now()->subDays(7))->count(),
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

        // --- 2. CHART & ACTIVITY (TTL: 5 Menit) ---
        $data = Cache::remember('dashboard.charts', 300, function () {
            // BUG-001: OS Distribution with Normalization
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

            // License Status
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

            // IMPROVEMENT-004: Top 10 Software
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

            // Recent Activity
            $recentActivities = Computer::withCount('softwares')
                ->with(['softwares.catalog' => function ($q) {
                    $q->where('status', 'Blacklist');
                }])
                ->orderBy('last_seen_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($computer) {
                    // Check if computer has any blacklisted software
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
            'topSoftware'
        ));
    }
}
