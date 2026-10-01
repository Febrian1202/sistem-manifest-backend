<?php

namespace App\Http\Controllers;

use App\Exports\KepatuhanExport;
use App\Exports\KomputerExport;
use App\Exports\LisensiExport;
use App\Exports\MonitoringRecapExport;
use App\Exports\SoftwareChangesExport;
use App\Exports\SoftwareExport;
use App\Jobs\GenerateComplianceReportJob;
use App\Models\ComplianceReport;
use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\LicenseInventory;
use App\Models\ReportApproval;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareDiscovery;
use App\Services\LicenseComplianceService;
use App\Services\SoftwareChangeDetectionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        protected SoftwareChangeDetectionService $changeDetectionService,
        protected LicenseComplianceService $complianceService
    ) {}

    // Menampilkan halaman Pusat Laporan
    public function index()
    {
        return view('pages.admin.reports');
    }

    /**
     * Helper to get date range from request or default to current month.
     * Supports both start_date/end_date and period_start/period_end.
     */
    private function getDateRange(Request $request): array
    {
        $startInput = $request->input('period_start') ?? $request->input('start_date');
        $endInput = $request->input('period_end') ?? $request->input('end_date');

        $startDate = $startInput ? Carbon::parse($startInput) : now()->startOfMonth();
        $endDate = $endInput ? Carbon::parse($endInput) : now()->endOfMonth();

        if ($startDate->greaterThan($endDate)) {
            $endDate = $startDate->copy()->endOfMonth();
        }

        return [$startDate, $endDate];
    }

    /**
     * Get approved laboratory IDs for a specific report type and period.
     */
    private function getApprovedLabIds(string $reportType, string $period): Collection
    {
        return ReportApproval::where('status', 'approved')
            ->where('report_type', $reportType)
            ->where('period', $period)
            ->pluck('laboratory_id');
    }

    /**
     * Resolve effective laboratory IDs to scope data by role and filters.
     * Returns:
     * - array of int (if scoped to specific lab IDs)
     * - null (if admin with all labs selected)
     */
    private function resolveLabScope(Request $request, string $period): ?array
    {
        $user = auth()->user();

        if ($user->hasRole('kepala_lab')) {
            return [$user->laboratory_id ?? 0];
        }

        if ($user->hasRole('pimpinan')) {
            $approvedLabIds = $this->getApprovedLabIds('kepatuhan', $period);

            if ($request->filled('laboratory_id') && $request->laboratory_id !== 'All') {
                $requestedId = (int) $request->laboratory_id;

                return $approvedLabIds->contains($requestedId) ? [$requestedId] : [0];
            }

            return $approvedLabIds->all();
        }

        // Admin
        if ($request->filled('laboratory_id') && $request->laboratory_id !== 'All') {
            return [(int) $request->laboratory_id];
        }

        return null;
    }

    /**
     * Get accessible laboratories for dropdown filtering in reports.
     */
    private function getAccessibleLaboratories(string $period): Collection
    {
        $user = auth()->user();

        if ($user->hasRole('kepala_lab')) {
            return $user->laboratory ? collect([$user->laboratory]) : collect();
        }

        if ($user->hasRole('pimpinan')) {
            $approvedLabIds = $this->getApprovedLabIds('kepatuhan', $period);

            return Laboratory::whereIn('id', $approvedLabIds)->orderBy('name')->get();
        }

        return Laboratory::orderBy('name')->get();
    }

    /**
     * Get approved report approval records with relations for metadata.
     */
    private function getApprovalData(string $reportType, string $period, ?array $labIds = null): Collection
    {
        $query = ReportApproval::where('status', 'approved')
            ->where('report_type', $reportType)
            ->where('period', $period)
            ->with(['laboratory', 'reviewer']);

        if ($labIds !== null) {
            $query->whereIn('laboratory_id', $labIds);
        }

        return $query->get();
    }

    /**
     * Get display name for selected laboratory.
     */
    private function getSelectedLabName($labId): string
    {
        if (empty($labId) || $labId === 'All') {
            return 'Semua Laboratorium';
        }
        $lab = Laboratory::find($labId);

        return $lab ? $lab->name : 'Semua Laboratorium';
    }

    // --- 1. RINGKASAN EKSEKUTIF [PDF ONLY] ---

    public function showEksekutif(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $isPimpinan = auth()->user()?->hasRole('pimpinan');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);
        $laboratories = $this->getAccessibleLaboratories($period);

        $data = $this->getEksekutifData($startDate, $endDate, $labIds);

        return view('reports.eksekutif', array_merge($data, [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'period' => $period,
            'isPimpinan' => $isPimpinan,
            'hasApprovedLabs' => ! $isPimpinan || ! empty($labIds),
            'approvalData' => $approvalData,
            'laboratories' => $laboratories,
            'selectedLabId' => $request->input('laboratory_id'),
        ]));
    }

    public function exportEksekutif(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);

        $data = $this->getEksekutifData($startDate, $endDate, $labIds);
        $data['print_date'] = now()->format('d/m/Y H:i');
        $data['printed_by'] = auth()->user()->name.' ('.(auth()->user()->getRoleNames()->first() ?? 'User').')';
        $data['startDateStr'] = $startDate->format('d/m/Y');
        $data['endDateStr'] = $endDate->format('d/m/Y');
        $data['period'] = $period;
        $data['approvalData'] = $approvalData;

        $pdf = Pdf::loadView('reports.pdf.eksekutif-pdf', $data)->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-eksekutif_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.pdf');
    }

    private function getEksekutifData($startDate, $endDate, ?array $labIds = null): array
    {
        $computersQuery = Computer::query();
        if ($labIds !== null) {
            $computersQuery->whereIn('laboratory_id', $labIds);
        }
        $totalComputers = $computersQuery->count();

        $installationsQuery = SoftwareDiscovery::whereBetween('created_at', [$startDate, $endDate]);
        if ($labIds !== null) {
            $installationsQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $labIds));
        }
        $totalInstallations = $installationsQuery->count();

        // Compliance stats
        $licensedQuery = Computer::where('os_license_status', 'Licensed');
        if ($labIds !== null) {
            $licensedQuery->whereIn('laboratory_id', $labIds);
        }
        $licensed = $licensedQuery->count();
        $complianceRate = $totalComputers > 0 ? round(($licensed / $totalComputers) * 100, 2) : 0;

        $criticalAlertsQuery = SoftwareDiscovery::whereHas('catalog', function ($q) {
            $q->where('category', 'Commercial')->whereDoesntHave('licenses');
        })->whereBetween('created_at', [$startDate, $endDate]);
        if ($labIds !== null) {
            $criticalAlertsQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $labIds));
        }
        $criticalAlerts = $criticalAlertsQuery->count();

        $graceQuery = Computer::where('os_license_status', 'Grace Period');
        $actionNeededQuery = Computer::whereNotIn('os_license_status', ['Licensed', 'Grace Period']);
        if ($labIds !== null) {
            $graceQuery->whereIn('laboratory_id', $labIds);
            $actionNeededQuery->whereIn('laboratory_id', $labIds);
        }
        $graceCount = $graceQuery->count();
        $actionNeededCount = $actionNeededQuery->count();

        $breakdown = [
            ['status' => 'Berlisensi', 'count' => $licensed, 'pct' => $totalComputers > 0 ? round(($licensed / $totalComputers) * 100, 1) : 0],
            ['status' => 'Masa Tenggang', 'count' => $graceCount, 'pct' => $totalComputers > 0 ? round(($graceCount / $totalComputers) * 100, 1) : 0],
            ['status' => 'Perlu Tindakan', 'count' => $actionNeededCount, 'pct' => $totalComputers > 0 ? round(($actionNeededCount / $totalComputers) * 100, 1) : 0],
        ];

        $topUnlicensedQuery = SoftwareDiscovery::whereHas('catalog', function ($q) {
            $q->where('category', 'Commercial')->whereDoesntHave('licenses');
        })->whereBetween('created_at', [$startDate, $endDate]);
        if ($labIds !== null) {
            $topUnlicensedQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $labIds));
        }

        $topUnlicensed = $topUnlicensedQuery
            ->select('raw_name', \DB::raw('count(*) as total'))
            ->groupBy('raw_name')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // Monitoring Summary
        $scanQuery = ScanSession::whereBetween('started_at', [$startDate, $endDate]);
        if ($labIds !== null) {
            $scanQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $labIds));
        }
        $totalScans = (clone $scanQuery)->count();
        $successfulScans = (clone $scanQuery)->where('status', 'completed')->count();
        $failedScans = (clone $scanQuery)->where('status', 'failed')->count();
        $scanSuccessRate = $totalScans > 0 ? round(($successfulScans / $totalScans) * 100, 1) : 0;

        $monitoringSummary = [
            'total_scans' => $totalScans,
            'successful_scans' => $successfulScans,
            'failed_scans' => $failedScans,
            'scan_success_rate' => $scanSuccessRate,
        ];

        return compact('totalComputers', 'totalInstallations', 'complianceRate', 'criticalAlerts', 'breakdown', 'topUnlicensed', 'monitoringSummary');
    }

    // --- 2. INVENTARIS KOMPUTER [PDF + EXCEL] ---

    public function showKomputer(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $isPimpinan = auth()->user()?->hasRole('pimpinan');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);
        $laboratories = $this->getAccessibleLaboratories($period);

        $query = Computer::withCount([
            'softwares',
            'scanSessions as total_scans' => fn ($q) => $q->whereBetween('scan_sessions.started_at', [$startDate, $endDate]),
        ])
            ->with(['scanSessions' => fn ($q) => $q->latest('started_at')->limit(1)])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('hostname');

        if ($labIds !== null) {
            $query->whereIn('laboratory_id', $labIds);
        }

        $computers = $query->paginate(15)->withQueryString();

        return view('reports.komputer', [
            'computers' => $computers,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'isPimpinan' => $isPimpinan,
            'hasApprovedLabs' => ! $isPimpinan || ! empty($labIds),
            'approvalData' => $approvalData,
            'laboratories' => $laboratories,
            'selectedLabId' => $request->input('laboratory_id'),
        ]);
    }

    public function exportKomputer(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);

        $query = Computer::withCount([
            'softwares',
            'scanSessions as total_scans' => fn ($q) => $q->whereBetween('scan_sessions.started_at', [$startDate, $endDate]),
        ])
            ->with(['scanSessions' => fn ($q) => $q->latest('started_at')->limit(1)])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('hostname');

        if ($labIds !== null) {
            $query->whereIn('laboratory_id', $labIds);
        }

        $computers = $query->get();

        if ($format === 'excel') {
            return Excel::download(new KomputerExport($computers, $startDate, $endDate, $approvalData), 'inventaris-komputer_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.xlsx');
        }

        $data = [
            'computers' => $computers,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name.' ('.(auth()->user()->getRoleNames()->first() ?? 'User').')',
            'period' => $period,
            'approvalData' => $approvalData,
        ];

        return Pdf::loadView('reports.pdf.komputer-pdf', $data)->setPaper('a4', 'landscape')->stream();
    }

    // --- 3. INVENTARIS SOFTWARE [PDF + EXCEL] ---

    public function showSoftware(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $isPimpinan = auth()->user()?->hasRole('pimpinan');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);
        $laboratories = $this->getAccessibleLaboratories($period);

        $softwares = $this->getSoftwareData($startDate, $endDate, $labIds)->paginate(15)->withQueryString();

        return view('reports.software', [
            'softwares' => $softwares,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'isPimpinan' => $isPimpinan,
            'hasApprovedLabs' => ! $isPimpinan || ! empty($labIds),
            'approvalData' => $approvalData,
            'laboratories' => $laboratories,
            'selectedLabId' => $request->input('laboratory_id'),
        ]);
    }

    public function exportSoftware(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);

        $softwares = $this->getSoftwareData($startDate, $endDate, $labIds)->get();

        if ($format === 'excel') {
            return Excel::download(new SoftwareExport($softwares, $startDate, $endDate, $approvalData), 'inventaris-software_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.xlsx');
        }

        $data = [
            'softwares' => $softwares,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name.' ('.(auth()->user()->getRoleNames()->first() ?? 'User').')',
            'period' => $period,
            'approvalData' => $approvalData,
        ];

        return Pdf::loadView('reports.pdf.software-pdf', $data)->setPaper('a4', 'portrait')->stream();
    }

    private function getSoftwareData($startDate, $endDate, ?array $labIds = null)
    {
        $hasHistorical = ScanSoftwareResult::whereHas('scanSession', function ($q) use ($startDate, $endDate, $labIds) {
            $q->whereBetween('started_at', [$startDate, $endDate]);
            if ($labIds !== null) {
                $q->whereHas('computer', fn ($c) => $c->whereIn('laboratory_id', $labIds));
            }
        })->exists();

        if ($hasHistorical) {
            $query = ScanSoftwareResult::whereHas('scanSession', function ($q) use ($startDate, $endDate, $labIds) {
                $q->whereBetween('started_at', [$startDate, $endDate]);
                if ($labIds !== null) {
                    $q->whereHas('computer', fn ($c) => $c->whereIn('laboratory_id', $labIds));
                }
            })
                ->join('scan_sessions', 'scan_software_results.scan_session_id', '=', 'scan_sessions.id')
                ->leftJoin('software_catalogs', 'scan_software_results.catalog_id', '=', 'software_catalogs.id');

            if ($labIds !== null) {
                $query->join('computers', 'scan_sessions.computer_id', '=', 'computers.id')
                    ->whereIn('computers.laboratory_id', $labIds);
            }

            return $query
                ->select(
                    'scan_software_results.catalog_id',
                    'scan_software_results.version',
                    \DB::raw('COALESCE(software_catalogs.normalized_name, scan_software_results.raw_name) as normalized_name'),
                    \DB::raw('COALESCE(software_catalogs.category, "Unknown") as category'),
                    \DB::raw('COUNT(DISTINCT scan_sessions.computer_id) as computer_count')
                )
                ->with(['catalog.licenses'])
                ->groupBy(
                    'scan_software_results.catalog_id',
                    'scan_software_results.version',
                    'normalized_name',
                    'category'
                )
                ->orderByDesc('computer_count');
        }

        $query = SoftwareDiscovery::whereBetween('software_discoveries.created_at', [$startDate, $endDate])
            ->join('software_catalogs', 'software_discoveries.catalog_id', '=', 'software_catalogs.id');

        if ($labIds !== null) {
            $query->join('computers', 'software_discoveries.computer_id', '=', 'computers.id')
                ->whereIn('computers.laboratory_id', $labIds);
        }

        return $query
            ->select(
                'software_discoveries.catalog_id',
                'software_discoveries.version',
                'software_catalogs.normalized_name',
                'software_catalogs.category'
            )
            ->selectRaw('COUNT(DISTINCT software_discoveries.computer_id) as computer_count')
            ->with(['catalog.licenses'])
            ->groupBy(
                'software_discoveries.catalog_id',
                'software_discoveries.version',
                'software_catalogs.normalized_name',
                'software_catalogs.category'
            )
            ->orderByDesc('computer_count');
    }

    // --- 4. KEPATUHAN LISENSI [PDF + EXCEL] ---

    public function showKepatuhan(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $isPimpinan = auth()->user()?->hasRole('pimpinan');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);
        $laboratories = $this->getAccessibleLaboratories($period);

        $reports = $this->getKepatuhanQuery($startDate, $endDate, $labIds)->paginate(15)->withQueryString();

        return view('reports.kepatuhan', [
            'reports' => $reports,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'isPimpinan' => $isPimpinan,
            'hasApprovedLabs' => ! $isPimpinan || ! empty($labIds),
            'approvalData' => $approvalData,
            'laboratories' => $laboratories,
            'selectedLabId' => $request->input('laboratory_id'),
        ]);
    }

    public function exportKepatuhan(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);

        $reports = $this->getKepatuhanQuery($startDate, $endDate, $labIds)->get();

        if ($format === 'excel') {
            return Excel::download(new KepatuhanExport($reports, $startDate, $endDate, $approvalData), 'kepatuhan-lisensi_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.xlsx');
        }

        $data = [
            'reports' => $reports,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name.' ('.(auth()->user()->getRoleNames()->first() ?? 'User').')',
            'period' => $period,
            'approvalData' => $approvalData,
        ];

        return Pdf::loadView('reports.pdf.kepatuhan-pdf', $data)->setPaper('a4', 'portrait')->stream();
    }

    private function getKepatuhanQuery($startDate, $endDate, ?array $labIds = null)
    {
        $hasHistorical = ComplianceSnapshot::whereBetween('scanned_at', [$startDate, $endDate])
            ->when($labIds !== null, function ($q) use ($labIds) {
                $q->whereHas('computer', fn ($c) => $c->whereIn('laboratory_id', $labIds));
            })
            ->exists();

        if ($hasHistorical) {
            $query = ComplianceSnapshot::with(['computer.laboratory', 'softwareCatalog'])
                ->whereBetween('scanned_at', [$startDate, $endDate])
                ->orderByDesc('scanned_at');

            if ($labIds !== null) {
                $query->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $labIds));
            }

            return $query;
        }

        $query = ComplianceReport::with(['computer.laboratory', 'softwareCatalog'])
            ->whereBetween('scanned_at', [$startDate, $endDate])
            ->orderByDesc('scanned_at');

        if ($labIds !== null) {
            $query->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $labIds));
        }

        return $query;
    }

    // --- 5. STATUS LISENSI [PDF + EXCEL] ---

    /**
     * Get license inventories with aggregated catalog entitlement and proportional usage.
     */
    private function getEnrichedLicenses(Carbon $startDate, Carbon $endDate, ?array $labIds): Collection
    {
        $today = now()->toDateString();

        $licenses = LicenseInventory::with(['catalog', 'allocations'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $catalogIds = $licenses->pluck('catalog_id')->unique()->filter()->all();

        // 1. Ambil total entitlement aktif untuk setiap catalog
        $catalogEntitlements = LicenseInventory::whereIn('catalog_id', $catalogIds)
            ->where(function ($q) use ($today) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today);
            })
            ->groupBy('catalog_id')
            ->selectRaw('catalog_id, SUM(quota_limit) as total_quota')
            ->pluck('total_quota', 'catalog_id');

        // 2. Ambil total instalasi unik pada komputer aktif untuk setiap catalog
        $installedQuery = SoftwareDiscovery::whereIn('catalog_id', $catalogIds)
            ->whereHas('computer', function ($q) use ($labIds) {
                $q->where('status', 'active');
                if ($labIds !== null) {
                    $q->whereIn('laboratory_id', $labIds);
                }
            });

        $catalogInstallCounts = $installedQuery
            ->groupBy('catalog_id')
            ->selectRaw('catalog_id, COUNT(DISTINCT computer_id) as total_installed')
            ->pluck('total_installed', 'catalog_id');

        return $licenses->map(function ($license) use ($catalogEntitlements, $catalogInstallCounts) {
            $catalogQuota = (int) ($catalogEntitlements[$license->catalog_id] ?? 0);
            $totalInstalled = (int) ($catalogInstallCounts[$license->catalog_id] ?? 0);
            $quotaLimit = (int) $license->quota_limit;

            if ($catalogQuota > 0) {
                $used = (int) round($quotaLimit * ($totalInstalled / $catalogQuota));
                $remaining = max(0, $quotaLimit - $used);
                $usagePct = $quotaLimit > 0 ? round(($used / $quotaLimit) * 100, 1) : 0;
            } else {
                $used = $totalInstalled;
                $remaining = 0;
                $usagePct = 0;
            }

            $license->used_count = $used;
            $license->remaining = $remaining;
            $license->usage_pct = $usagePct;
            $license->allocated_seats = $license->total_allocated;
            $license->unallocated_seats = $license->remaining_unallocated;
            $license->catalog_total_quota = $catalogQuota;
            $license->catalog_total_installed = $totalInstalled;

            return $license;
        })->sortByDesc('usage_pct')->values();
    }

    public function showLisensi(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);
        $laboratories = $this->getAccessibleLaboratories($period);

        $licenses = $this->getEnrichedLicenses($startDate, $endDate, $labIds);

        // Manual pagination
        $page = request()->get('page', 1);
        $perPage = 15;
        $paginatedLicenses = new LengthAwarePaginator(
            $licenses->forPage($page, $perPage),
            $licenses->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('reports.lisensi', [
            'licenses' => $paginatedLicenses,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'approvalData' => $approvalData,
            'laboratories' => $laboratories,
            'selectedLabId' => $request->input('laboratory_id'),
        ]);
    }

    public function exportLisensi(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);

        $licenses = $this->getEnrichedLicenses($startDate, $endDate, $labIds);

        if ($format === 'excel') {
            return Excel::download(new LisensiExport($licenses, $startDate, $endDate, $approvalData), 'status-lisensi_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.xlsx');
        }

        $data = [
            'licenses' => $licenses,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name.' ('.(auth()->user()->getRoleNames()->first() ?? 'User').')',
            'period' => $period,
            'approvalData' => $approvalData,
        ];

        return Pdf::loadView('reports.pdf.lisensi-pdf', $data)->setPaper('a4', 'portrait')->stream();
    }

    // --- 6. REKAP MONITORING BERKALA [PDF + EXCEL] ---

    public function showMonitoring(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);
        $laboratories = $this->getAccessibleLaboratories($period);

        $monitoringData = $this->getMonitoringData($startDate, $endDate, $labIds);
        $computers = $monitoringData['compQuery']->paginate(15)->withQueryString();

        return view('reports.monitoring', [
            'computers' => $computers,
            'summary' => $monitoringData['summary'],
            'labStats' => $monitoringData['labStats'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'approvalData' => $approvalData,
            'laboratories' => $laboratories,
            'selectedLabId' => $request->input('laboratory_id'),
            'selectedLabName' => $this->getSelectedLabName($request->input('laboratory_id')),
        ]);
    }

    public function exportMonitoring(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);

        $monitoringData = $this->getMonitoringData($startDate, $endDate, $labIds);
        $computers = $monitoringData['compQuery']->get();
        $summary = $monitoringData['summary'];
        $labStats = $monitoringData['labStats'];

        if ($format === 'excel') {
            return Excel::download(
                new MonitoringRecapExport($computers, $startDate, $endDate, $summary, $approvalData),
                'rekap-monitoring_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.xlsx'
            );
        }

        $data = [
            'computers' => $computers,
            'summary' => $summary,
            'labStats' => $labStats,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name.' ('.(auth()->user()->getRoleNames()->first() ?? 'User').')',
            'period' => $period,
            'selectedLabName' => $this->getSelectedLabName($request->input('laboratory_id')),
            'approvalData' => $approvalData,
        ];

        return Pdf::loadView('reports.pdf.monitoring-recap-pdf', $data)->setPaper('a4', 'portrait')->stream();
    }

    private function getMonitoringData($startDate, $endDate, ?array $labIds = null): array
    {
        $scanQuery = ScanSession::whereBetween('started_at', [$startDate, $endDate]);
        if ($labIds !== null) {
            $scanQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $labIds));
        }

        $totalScans = (clone $scanQuery)->count();
        $successfulScans = (clone $scanQuery)->where('status', 'completed')->count();
        $failedScans = (clone $scanQuery)->where('status', 'failed')->count();
        $successRate = $totalScans > 0 ? round(($successfulScans / $totalScans) * 100, 1) : 0;

        $computersQuery = Computer::query();
        if ($labIds !== null) {
            $computersQuery->whereIn('laboratory_id', $labIds);
        }
        $totalComputers = $computersQuery->count();

        $summary = [
            'total_computers' => $totalComputers,
            'total_scans' => $totalScans,
            'successful_scans' => $successfulScans,
            'failed_scans' => $failedScans,
            'success_rate' => $successRate,
        ];

        $labQuery = Laboratory::query();
        if ($labIds !== null) {
            $labQuery->whereIn('id', $labIds);
        }

        $labStats = $labQuery->withCount([
            'computers',
            'scanSessions as total_scans' => fn ($q) => $q->whereBetween('scan_sessions.started_at', [$startDate, $endDate]),
            'scanSessions as successful_scans' => fn ($q) => $q->whereBetween('scan_sessions.started_at', [$startDate, $endDate])->where('scan_sessions.status', 'completed'),
            'scanSessions as failed_scans' => fn ($q) => $q->whereBetween('scan_sessions.started_at', [$startDate, $endDate])->where('scan_sessions.status', 'failed'),
        ])->orderBy('name')->get()->map(function ($lab) {
            $lab->success_rate = $lab->total_scans > 0 ? round(($lab->successful_scans / $lab->total_scans) * 100, 1) : 0;

            return $lab;
        });

        $compQuery = Computer::with(['laboratory', 'scanSessions' => fn ($q) => $q->latest('started_at')->limit(1)])
            ->when($labIds !== null, fn ($q) => $q->whereIn('laboratory_id', $labIds))
            ->withCount([
                'scanSessions as total_scans' => fn ($q) => $q->whereBetween('scan_sessions.started_at', [$startDate, $endDate]),
                'scanSessions as successful_scans' => fn ($q) => $q->whereBetween('scan_sessions.started_at', [$startDate, $endDate])->where('scan_sessions.status', 'completed'),
                'scanSessions as failed_scans' => fn ($q) => $q->whereBetween('scan_sessions.started_at', [$startDate, $endDate])->where('scan_sessions.status', 'failed'),
            ])
            ->orderBy('hostname');

        return compact('summary', 'labStats', 'compQuery');
    }

    // --- 7. REKAP PERUBAHAN SOFTWARE [PDF + EXCEL] ---

    public function showPerubahan(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);
        $laboratories = $this->getAccessibleLaboratories($period);

        $perubahanData = $this->getPerubahanData($startDate, $endDate, $labIds, $request->input('change_type'));

        $page = (int) $request->get('page', 1);
        $perPage = 15;
        $paginatedChanges = new LengthAwarePaginator(
            $perubahanData['changes']->forPage($page, $perPage),
            $perubahanData['changes']->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('reports.perubahan', [
            'paginatedChanges' => $paginatedChanges,
            'summary' => $perubahanData['summary'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'approvalData' => $approvalData,
            'laboratories' => $laboratories,
            'selectedLabId' => $request->input('laboratory_id'),
            'selectedLabName' => $this->getSelectedLabName($request->input('laboratory_id')),
        ]);
    }

    public function exportPerubahan(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);

        $perubahanData = $this->getPerubahanData($startDate, $endDate, $labIds, $request->input('change_type'));
        $changes = $perubahanData['changes'];
        $summary = $perubahanData['summary'];

        if ($format === 'excel') {
            return Excel::download(
                new SoftwareChangesExport($changes, $startDate, $endDate, $summary, $approvalData),
                'rekap-perubahan-software_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.xlsx'
            );
        }

        $data = [
            'changes' => $changes,
            'summary' => $summary,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name.' ('.(auth()->user()->getRoleNames()->first() ?? 'User').')',
            'period' => $period,
            'selectedLabName' => $this->getSelectedLabName($request->input('laboratory_id')),
            'approvalData' => $approvalData,
        ];

        return Pdf::loadView('reports.pdf.software-changes-pdf', $data)->setPaper('a4', 'landscape')->stream();
    }

    private function getPerubahanData($startDate, $endDate, ?array $labIds = null, ?string $changeType = null): array
    {
        $filters = [
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
        ];

        if ($changeType && $changeType !== 'All') {
            $filters['change_type'] = $changeType;
        }

        if ($labIds !== null && count($labIds) === 1) {
            $filters['laboratory_id'] = $labIds[0];
        }

        $allChanges = $this->changeDetectionService->getGlobalChanges($filters);

        if ($labIds !== null && count($labIds) > 1) {
            $allChanges = $allChanges->whereIn('laboratory_id', $labIds)->values();
        }

        $summary = [
            'total' => $allChanges->count(),
            'added' => $allChanges->where('type', 'added')->count(),
            'removed' => $allChanges->where('type', 'removed')->count(),
            'version_changed' => $allChanges->where('type', 'version_changed')->count(),
            'returned' => $allChanges->where('type', 'returned')->count(),
        ];

        return [
            'changes' => $allChanges,
            'summary' => $summary,
        ];
    }

    public function runComplianceScan()
    {
        // Optimization for large datasets
        Computer::select('id', 'hostname')->chunk(100, function ($computers) {
            foreach ($computers as $computer) {
                GenerateComplianceReportJob::dispatch($computer)
                    ->onQueue('compliance');
            }
        });

        return back()->with([
            'status' => 'success',
            'message' => 'Pemeriksaan kepatuhan untuk semua komputer telah dijadwalkan di background.',
        ]);
    }
}
