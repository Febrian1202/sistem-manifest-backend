<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $lab = $user->laboratory;

        if (! $lab) {
            abort(403, 'Anda belum ditugaskan ke laboratorium manapun.');
        }

        $query = $lab->computers()
            ->withCount('softwares')
            ->with('latestComplianceReport');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('hostname', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('os_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('license_status') && $request->license_status !== 'All') {
            $query->where('os_license_status', $request->license_status);
        }

        $computers = $query->latest('last_seen_at')->paginate(15)->withQueryString();

        $totalComputers = $lab->computers()->count();
        $scannedThisMonth = $lab->computers()
            ->whereMonth('last_seen_at', now()->month)
            ->whereYear('last_seen_at', now()->year)
            ->count();

        $licensedOS = $lab->computers()->where('os_license_status', 'Licensed')->count();
        $complianceRate = $totalComputers > 0 ? round(($licensedOS / $totalComputers) * 100, 1) : 0;

        $stats = [
            'total_computers' => $totalComputers,
            'scanned_this_month' => $scannedThisMonth,
            'licensed_os' => $licensedOS,
            'compliance_rate' => $complianceRate,
        ];

        return view('lab-inventory.index', compact('computers', 'lab', 'stats'));
    }

    public function show(Computer $computer): View
    {
        $user = auth()->user();

        if ($computer->laboratory_id !== $user->laboratory_id) {
            abort(403, 'Anda tidak memiliki akses ke komputer di laboratorium ini.');
        }

        $computer->load([
            'softwares' => function ($q) {
                $q->orderBy('raw_name');
            },
            'softwares.catalog',
            'complianceReports.softwareCatalog',
            'laboratory',
        ]);

        return view('lab-inventory.show', compact('computer'));
    }
}
