<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // --- 1. STATISTIK UTAMA ---

        // A. Total Komputer
        $totalComputers = Computer::count();
        $newComputersThisMonth = Computer::where('created_at', '>=', now()->startOfMonth())->count();

        // B. Total Software
        $totalInstallations = SoftwareDiscovery::count();
        $newInstallationThisMonth = SoftwareDiscovery::where('created_at', '>=', now()->startOfMonth())->count();
        $uniqueSoftwares = SoftwareDiscovery::distinct('raw_name')->count();

        // C. Peringatan Kritis
        $unlicensedOS = Computer::where('os_license_status', '!=', 'Licensed')->count();

        $computersWithBlacklist = SoftwareDiscovery::whereHas('catalog', function ($q) {
            $q->where('status', 'Blacklisted');
        })->distinct('computer_id')->count();

        $criticalAlerts = $unlicensedOS + $computersWithBlacklist;

        // D. Kesehatan Sistem
        $healthyComputers = Computer::where('os_license_status', 'Licensed')
            ->whereDoesntHave('softwares.catalog', function ($q) {
                $q->where('status', 'Blacklisted');
            })->count();

        $systemHealth = $totalComputers > 0 ? round(($healthyComputers / $totalComputers) * 100) : 0;


        // --- 2. CHART: OS DISTRIBUTION (PERBAIKAN DI SINI) ---
        // Ganti 'os_info' menjadi 'os_name'
        $osStats = Computer::select('os_name', DB::raw('count(*) as total'))
            ->groupBy('os_name')
            ->orderByDesc('total')
            ->get();

        $osLabels = [];
        $osSeries = [];
        $otherCount = 0;

        foreach ($osStats as $index => $stat) {
            if ($index < 3) {
                // Bersihkan nama OS (Ganti $stat->os_info jadi $stat->os_name)
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


        // --- 3. CHART: LICENSE STATUS ---
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

        $licenseLabelsChart = ['Licensed', 'Grace Period', 'Action Required'];


        // --- 4. TABLE: RECENT ACTIVITY ---
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