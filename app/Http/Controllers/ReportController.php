<?php

namespace App\Http\Controllers;

use App\Exports\KepatuhanExport;
use App\Exports\KomputerExport;
use App\Exports\LisensiExport;
use App\Exports\SoftwareExport;
use App\Jobs\GenerateComplianceReportJob;
use App\Models\ComplianceReport;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\LicenseInventory;
use App\Models\ReportApproval;
use App\Models\SoftwareDiscovery;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // Menampilkan halaman Pusat Laporan
    public function index()
    {
        return view('pages.admin.reports');
    }

    /**
     * Helper to get date range from request or default to current month.
     */
    private function getDateRange(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : now()->startOfMonth();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : now()->endOfMonth();

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

    private function getEksekutifData($startDate, $endDate, ?array $labIds = null)
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

        return compact('totalComputers', 'totalInstallations', 'complianceRate', 'criticalAlerts', 'breakdown', 'topUnlicensed');
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

        $query = Computer::withCount('softwares')->whereBetween('created_at', [$startDate, $endDate])->orderBy('hostname');
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

        $query = Computer::withCount('softwares')->whereBetween('created_at', [$startDate, $endDate])->orderBy('hostname');
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

        $query = ComplianceReport::with(['computer', 'softwareCatalog'])
            ->whereBetween('scanned_at', [$startDate, $endDate])
            ->orderByDesc('scanned_at');

        if ($labIds !== null) {
            $query->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $labIds));
        }

        $reports = $query->paginate(15)->withQueryString();

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

        $query = ComplianceReport::with(['computer', 'softwareCatalog'])
            ->whereBetween('scanned_at', [$startDate, $endDate])
            ->orderByDesc('scanned_at');

        if ($labIds !== null) {
            $query->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $labIds));
        }

        $reports = $query->get();

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

    // --- 5. STATUS LISENSI [PDF + EXCEL] ---

    public function showLisensi(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $labIds = $this->resolveLabScope($request, $period);
        $approvalData = $this->getApprovalData('kepatuhan', $period, $labIds);
        $laboratories = $this->getAccessibleLaboratories($period);

        $usedCountSubQuery = 'SELECT COUNT(*) FROM software_discoveries WHERE software_discoveries.catalog_id = license_inventories.catalog_id';
        if ($labIds !== null) {
            $labIdList = implode(',', array_map('intval', $labIds));
            $usedCountSubQuery .= " AND software_discoveries.computer_id IN (SELECT id FROM computers WHERE laboratory_id IN ({$labIdList}))";
        }

        $licenses = LicenseInventory::with('catalog')
            ->select('*')
            ->selectRaw("({$usedCountSubQuery}) as used_count")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        // Enrich data then sort by usage_pct DESC
        $licenses = $licenses->map(function ($license) {
            $usage = $license->used_count;
            $license->remaining = max(0, $license->quota_limit - $usage);
            $license->usage_pct = $license->quota_limit > 0 ? round(($usage / $license->quota_limit) * 100, 1) : 0;

            return $license;
        })->sortByDesc('usage_pct')->values();

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

        $usedCountSubQuery = 'SELECT COUNT(*) FROM software_discoveries WHERE software_discoveries.catalog_id = license_inventories.catalog_id';
        if ($labIds !== null) {
            $labIdList = implode(',', array_map('intval', $labIds));
            $usedCountSubQuery .= " AND software_discoveries.computer_id IN (SELECT id FROM computers WHERE laboratory_id IN ({$labIdList}))";
        }

        $licenses = LicenseInventory::with('catalog')
            ->select('*')
            ->selectRaw("({$usedCountSubQuery}) as used_count")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function ($license) {
                $usage = $license->used_count;
                $license->remaining = max(0, $license->quota_limit - $usage);
                $license->usage_pct = $license->quota_limit > 0 ? round(($usage / $license->quota_limit) * 100, 1) : 0;

                return $license;
            });

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
