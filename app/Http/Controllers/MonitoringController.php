<?php

namespace App\Http\Controllers;

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Services\SoftwareChangeDetectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class MonitoringController extends Controller
{
    /**
     * Display a listing of monitoring scan sessions.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = ScanSession::query()
            ->forUserLab($user)
            ->with(['computer.laboratory.faculty']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('computer', function ($q) use ($search) {
                $q->where('hostname', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('faculty_id') && $request->faculty_id !== 'All' && ! $user->hasRole('kepala_lab')) {
            $query->whereHas('computer.laboratory', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        if ($request->filled('laboratory_id') && $request->laboratory_id !== 'All' && ! $user->hasRole('kepala_lab')) {
            $query->whereHas('computer', function ($q) use ($request) {
                $q->where('laboratory_id', $request->laboratory_id);
            });
        }

        if ($request->filled('computer_id') && $request->computer_id !== 'All') {
            $query->where('computer_id', $request->computer_id);
        }

        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        if ($request->filled('trigger') && $request->trigger !== 'All') {
            $query->where('trigger', $request->trigger);
        }

        if ($request->filled('period_start')) {
            $query->whereDate('started_at', '>=', Carbon::parse($request->period_start));
        }

        if ($request->filled('period_end')) {
            $query->whereDate('started_at', '<=', Carbon::parse($request->period_end));
        }

        $scanSessions = $query->latest('started_at')->latest('id')->paginate(15)->withQueryString();

        $laboratories = Laboratory::with('faculty')->orderBy('name')->get();
        $faculties = Faculty::orderBy('name')->get();

        $computerQuery = Computer::query();
        if ($user->hasRole('kepala_lab') && $user->laboratory_id) {
            $computerQuery->where('laboratory_id', $user->laboratory_id);
        }
        $computers = $computerQuery->orderBy('hostname')->get();

        return view('monitoring.index', compact('scanSessions', 'laboratories', 'computers', 'faculties'));
    }

    /**
     * Display the specified scan session with software diff and compliance snapshots.
     */
    public function show(ScanSession $scanSession, SoftwareChangeDetectionService $changeService)
    {
        $user = auth()->user();

        $scanSession->loadMissing('computer.laboratory');

        if ($user->hasRole('kepala_lab')) {
            if (! $user->laboratory_id || (int) $scanSession->computer?->laboratory_id !== (int) $user->laboratory_id) {
                abort(403, 'Anda tidak memiliki akses ke data pemindaian laboratorium ini.');
            }
        }

        $scanSession->load([
            'softwareResults.catalog',
            'complianceSnapshots.licenseInventory',
            'complianceSnapshots.softwareCatalog',
        ]);

        $diff = $changeService->compareSessions($scanSession);
        $complianceChanges = $changeService->compareComplianceSnapshots($scanSession, $diff['previous_session'] ?? null);

        return view('monitoring.show', compact('scanSession', 'diff', 'complianceChanges'));
    }

    /**
     * Display global software change logs.
     */
    public function changes(Request $request, SoftwareChangeDetectionService $changeService)
    {
        $user = auth()->user();

        $filters = [
            'period_start' => $request->input('period_start'),
            'period_end' => $request->input('period_end'),
            'change_type' => $request->input('change_type'),
            'computer_id' => $request->input('computer_id'),
        ];

        if ($user->hasRole('kepala_lab')) {
            $filters['laboratory_id'] = $user->laboratory_id;
        } else {
            $filters['laboratory_id'] = $request->input('laboratory_id');
        }

        $allChanges = $changeService->getGlobalChanges($filters);

        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $changes = new LengthAwarePaginator(
            $allChanges->forPage($page, $perPage)->values(),
            $allChanges->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $laboratories = Laboratory::orderBy('name')->get();

        $computerQuery = Computer::query();
        if ($user->hasRole('kepala_lab') && $user->laboratory_id) {
            $computerQuery->where('laboratory_id', $user->laboratory_id);
        }
        $computers = $computerQuery->orderBy('hostname')->get();

        return view('monitoring.changes', compact('changes', 'laboratories', 'computers', 'filters'));
    }

    /**
     * Display compliance history across snapshots.
     */
    public function compliance(Request $request)
    {
        $user = auth()->user();

        $query = ComplianceSnapshot::query()
            ->forUserLab($user)
            ->with(['computer.laboratory', 'softwareCatalog', 'licenseInventory', 'scanSession']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('software_name', 'like', "%{$search}%")
                    ->orWhereHas('computer', function ($sq) use ($search) {
                        $sq->where('hostname', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('laboratory_id') && $request->laboratory_id !== 'All' && ! $user->hasRole('kepala_lab')) {
            $query->whereHas('computer', function ($q) use ($request) {
                $q->where('laboratory_id', $request->laboratory_id);
            });
        }

        if ($request->filled('computer_id') && $request->computer_id !== 'All') {
            $query->where('computer_id', $request->computer_id);
        }

        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        if ($request->filled('period_start')) {
            $query->whereDate('scanned_at', '>=', Carbon::parse($request->period_start));
        }

        if ($request->filled('period_end')) {
            $query->whereDate('scanned_at', '<=', Carbon::parse($request->period_end));
        }

        $snapshots = $query->latest('scanned_at')->latest('id')->paginate(15)->withQueryString();

        $laboratories = Laboratory::orderBy('name')->get();

        $computerQuery = Computer::query();
        if ($user->hasRole('kepala_lab') && $user->laboratory_id) {
            $computerQuery->where('laboratory_id', $user->laboratory_id);
        }
        $computers = $computerQuery->orderBy('hostname')->get();

        return view('monitoring.compliance', compact('snapshots', 'laboratories', 'computers'));
    }
}
