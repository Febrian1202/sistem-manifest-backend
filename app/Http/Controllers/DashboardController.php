<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // --- 1. STATISTIK UTAMA (TTL: 10 Menit) ---
        $stats = Cache::remember('dashboard.stats', 600, function () {
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
                    $hasBlacklist = $computer->softwares->some(fn($s) => $s->catalog && $s->catalog->status === 'Blacklist');

                    $status = 'success';
                    $statusText = 'Clean';

                    if ($hasBlacklist) {
                        $status = 'destructive';
                        $statusText = 'Software Issue';
                    } elseif ($computer->os_license_status !== 'Licensed') {
                        $status = 'warning';
                        $statusText = 'OS Issue';
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
        $licenseLabelsChart = ['Licensed', 'Grace Period', 'Action Required'];
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