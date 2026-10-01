<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Laboratory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $accessibleLabIds = $user->getAccessibleLaboratoryIds();

        if (empty($accessibleLabIds)) {
            abort(403, 'Anda belum ditugaskan ke laboratorium manapun.');
        }

        $lab = $user->laboratory;
        if (! $lab) {
            if ($user->faculty) {
                $lab = (object) [
                    'name' => 'Fakultas '.$user->faculty->name,
                    'code' => $user->faculty->code,
                    'building' => 'Fakultas '.$user->faculty->name,
                    'floor' => '-',
                    'description' => 'Seluruh laboratorium di bawah '.$user->faculty->name,
                ];
            } else {
                $lab = Laboratory::whereIn('id', $accessibleLabIds)->first();
            }
        }

        $query = Computer::forUserLab($user)
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

        $baseCountQuery = Computer::whereIn('laboratory_id', $accessibleLabIds);
        $totalComputers = (clone $baseCountQuery)->count();
        $scannedThisMonth = (clone $baseCountQuery)
            ->whereMonth('last_seen_at', now()->month)
            ->whereYear('last_seen_at', now()->year)
            ->count();

        $licensedOS = (clone $baseCountQuery)->where('os_license_status', 'Licensed')->count();
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
        $accessibleLabIds = $user->getAccessibleLaboratoryIds();

        if (! in_array($computer->laboratory_id, $accessibleLabIds)) {
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
