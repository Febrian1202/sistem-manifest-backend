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
                    $q->where('status', 'Blacklisted');
                })->distinct('computer_id')->count(),
                'healthyComputers' => Computer::where('os_license_status', 'Licensed')
                    ->whereDoesntHave('softwares.catalog', function ($q) {
                        $q->where('status', 'Blacklisted');
                    })->count(),
            ];
        });

        $totalComputers = $stats['totalComputers'];
        $newComputersThisMonth = $stats['newComputersThisMonth'];
        $totalInstallations = $stats['totalInstallations'];
        $newInstallationThisMonth = $stats['newInstallationThisMonth'];
        $uniqueSoftwares = $stats['uniqueSoftwares'];
        $criticalAlerts = $stats['unlicensedOS'] + $stats['computersWithBlacklist'];
        $systemHealth = $totalComputers > 0 ? round(($stats['healthyComputers'] / $totalComputers) * 100) : 0;

        // --- 2. CHART & ACTIVITY (TTL: 5 Menit) ---
        $data = Cache::remember('dashboard.charts', 300, function () {
            // OS Distribution
            $osStats = Computer::select('os_name', DB::raw('count(*) as total'))
                ->groupBy('os_name')
                ->orderByDesc('total')
                ->get();

            $osLabels = [];
            $osSeries = [];
            $otherCount = 0;

            foreach ($osStats as $index => $stat) {
                if ($index < 3) {
                    $name = str_replace(['Microsoft ', ' edition', 'Pro', 'Home', 'Enterprise'], '', $stat->os_name);
                    $osLabels[] = trim($name) ?: 'Unknown';
                    $osSeries[] = $stat->total;
                } else {
                    $otherCount += $stat->total;
                }
            }
            if ($otherCount > 0) {
                $osLabels[] = 'Others';
                $osSeries[] = $otherCount;
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

            // Recent Activity
            $recentActivities = Computer::withCount('softwares')
                ->orderBy('last_seen_at', 'desc')
                ->take(5)
                ->get()
                ->map(function ($computer) {
                    $status = 'success';
                    $statusText = 'Clean';

                    if ($computer->os_license_status !== 'Licensed') {
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
            ];
        });

        $osLabels = $data['osLabels'];
        $osSeries = $data['osSeries'];
        $licenseLabelsChart = ['Licensed', 'Grace Period', 'Action Required'];
        $licenseSeries = $data['licenseSeries'];
        $recentActivities = $data['recentActivities'];

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
            'recentActivities'
        ));
    }
}