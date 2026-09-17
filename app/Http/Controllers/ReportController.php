<?php

namespace App\Http\Controllers;

use App\Exports\KepatuhanExport;
use App\Exports\KomputerExport;
use App\Exports\LisensiExport;
use App\Exports\SoftwareExport;
use App\Jobs\GenerateComplianceReportJob;
use App\Models\ComplianceReport;
use App\Models\Computer;
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
     * Get approved report approval records with relations for metadata.
     */
    private function getApprovalData(string $reportType, string $period): Collection
    {
        return ReportApproval::where('status', 'approved')
            ->where('report_type', $reportType)
            ->where('period', $period)
            ->with(['laboratory', 'reviewer'])
            ->get();
    }

    // --- 1. RINGKASAN EKSEKUTIF [PDF ONLY] ---

    public function showEksekutif(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $isPimpinan = auth()->user()?->hasRole('pimpinan');
        $approvedLabIds = $isPimpinan ? $this->getApprovedLabIds('kepatuhan', $period) : null;
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $data = $this->getEksekutifData($startDate, $endDate, $approvedLabIds);

        return view('reports.eksekutif', array_merge($data, [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'period' => $period,
            'isPimpinan' => $isPimpinan,
            'hasApprovedLabs' => ! $isPimpinan || ($approvedLabIds && $approvedLabIds->isNotEmpty()),
            'approvalData' => $approvalData,
        ]));
    }

    public function exportEksekutif(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $isPimpinan = auth()->user()?->hasRole('pimpinan');
        $approvedLabIds = $isPimpinan ? $this->getApprovedLabIds('kepatuhan', $period) : null;
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $data = $this->getEksekutifData($startDate, $endDate, $approvedLabIds);
        $data['print_date'] = now()->format('d/m/Y H:i');
        $data['printed_by'] = auth()->user()->name.' ('.(auth()->user()->getRoleNames()->first() ?? 'User').')';
        $data['startDateStr'] = $startDate->format('d/m/Y');
        $data['endDateStr'] = $endDate->format('d/m/Y');
        $data['period'] = $period;
        $data['approvalData'] = $approvalData;

        $pdf = Pdf::loadView('reports.pdf.eksekutif-pdf', $data)->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-eksekutif_'.$startDate->format('Y-m-d').'_'.$endDate->format('Y-m-d').'.pdf');
    }

    private function getEksekutifData($startDate, $endDate, ?Collection $approvedLabIds = null)
    {
        $isPimpinan = auth()->user()?->hasRole('pimpinan');

        $computersQuery = Computer::query();
        if ($isPimpinan) {
            $computersQuery->whereIn('laboratory_id', $approvedLabIds ?? collect());
        }
        $totalComputers = $computersQuery->count();

        $installationsQuery = SoftwareDiscovery::whereBetween('created_at', [$startDate, $endDate]);
        if ($isPimpinan) {
            $installationsQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $approvedLabIds ?? collect()));
        }
        $totalInstallations = $installationsQuery->count();

        // Compliance stats
        $licensedQuery = Computer::where('os_license_status', 'Licensed');
        if ($isPimpinan) {
            $licensedQuery->whereIn('laboratory_id', $approvedLabIds ?? collect());
        }
        $licensed = $licensedQuery->count();
        $complianceRate = $totalComputers > 0 ? round(($licensed / $totalComputers) * 100, 2) : 0;

        $criticalAlertsQuery = SoftwareDiscovery::whereHas('catalog', function ($q) {
            $q->where('category', 'Commercial')->whereDoesntHave('licenses');
        })->whereBetween('created_at', [$startDate, $endDate]);
        if ($isPimpinan) {
            $criticalAlertsQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $approvedLabIds ?? collect()));
        }
        $criticalAlerts = $criticalAlertsQuery->count();

        $graceQuery = Computer::where('os_license_status', 'Grace Period');
        $actionNeededQuery = Computer::whereNotIn('os_license_status', ['Licensed', 'Grace Period']);
        if ($isPimpinan) {
            $graceQuery->whereIn('laboratory_id', $approvedLabIds ?? collect());
            $actionNeededQuery->whereIn('laboratory_id', $approvedLabIds ?? collect());
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
        if ($isPimpinan) {
            $topUnlicensedQuery->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $approvedLabIds ?? collect()));
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
        $approvedLabIds = $isPimpinan ? $this->getApprovedLabIds('kepatuhan', $period) : null;
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $query = Computer::withCount('softwares')->whereBetween('created_at', [$startDate, $endDate])->orderBy('hostname');
        if ($isPimpinan) {
            $query->whereIn('laboratory_id', $approvedLabIds ?? collect());
        }

        $computers = $query->paginate(15)->withQueryString();

        return view('reports.komputer', [
            'computers' => $computers,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'isPimpinan' => $isPimpinan,
            'hasApprovedLabs' => ! $isPimpinan || ($approvedLabIds && $approvedLabIds->isNotEmpty()),
            'approvalData' => $approvalData,
        ]);
    }

    public function exportKomputer(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $isPimpinan = auth()->user()?->hasRole('pimpinan');
        $approvedLabIds = $isPimpinan ? $this->getApprovedLabIds('kepatuhan', $period) : null;
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $query = Computer::withCount('softwares')->whereBetween('created_at', [$startDate, $endDate])->orderBy('hostname');
        if ($isPimpinan) {
            $query->whereIn('laboratory_id', $approvedLabIds ?? collect());
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
        $approvedLabIds = $isPimpinan ? $this->getApprovedLabIds('kepatuhan', $period) : null;
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $softwares = $this->getSoftwareData($startDate, $endDate, $approvedLabIds)->paginate(15)->withQueryString();

        return view('reports.software', [
            'softwares' => $softwares,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'isPimpinan' => $isPimpinan,
            'hasApprovedLabs' => ! $isPimpinan || ($approvedLabIds && $approvedLabIds->isNotEmpty()),
            'approvalData' => $approvalData,
        ]);
    }

    public function exportSoftware(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $isPimpinan = auth()->user()?->hasRole('pimpinan');
        $approvedLabIds = $isPimpinan ? $this->getApprovedLabIds('kepatuhan', $period) : null;
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $softwares = $this->getSoftwareData($startDate, $endDate, $approvedLabIds)->get();

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

    private function getSoftwareData($startDate, $endDate, ?Collection $approvedLabIds = null)
    {
        $query = SoftwareDiscovery::whereBetween('software_discoveries.created_at', [$startDate, $endDate])
            ->join('software_catalogs', 'software_discoveries.catalog_id', '=', 'software_catalogs.id');

        if ($approvedLabIds !== null) {
            $query->join('computers', 'software_discoveries.computer_id', '=', 'computers.id')
                ->whereIn('computers.laboratory_id', $approvedLabIds);
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
        $approvedLabIds = $isPimpinan ? $this->getApprovedLabIds('kepatuhan', $period) : null;
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $query = ComplianceReport::with(['computer', 'softwareCatalog'])
            ->whereBetween('scanned_at', [$startDate, $endDate])
            ->orderByDesc('scanned_at');

        if ($isPimpinan) {
            $query->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $approvedLabIds ?? collect()));
        }

        $reports = $query->paginate(15)->withQueryString();

        return view('reports.kepatuhan', [
            'reports' => $reports,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'period' => $period,
            'isPimpinan' => $isPimpinan,
            'hasApprovedLabs' => ! $isPimpinan || ($approvedLabIds && $approvedLabIds->isNotEmpty()),
            'approvalData' => $approvalData,
        ]);
    }

    public function exportKepatuhan(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $isPimpinan = auth()->user()?->hasRole('pimpinan');
        $approvedLabIds = $isPimpinan ? $this->getApprovedLabIds('kepatuhan', $period) : null;
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $query = ComplianceReport::with(['computer', 'softwareCatalog'])
            ->whereBetween('scanned_at', [$startDate, $endDate])
            ->orderByDesc('scanned_at');

        if ($isPimpinan) {
            $query->whereHas('computer', fn ($q) => $q->whereIn('laboratory_id', $approvedLabIds ?? collect()));
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
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $licenses = LicenseInventory::with('catalog')
            ->select('*')
            ->selectRaw('(SELECT COUNT(*) FROM software_discoveries WHERE software_discoveries.catalog_id = license_inventories.catalog_id) as used_count')
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
        ]);
    }

    public function exportLisensi(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $period = $request->input('period', $startDate->format('Y-m'));
        $format = $request->query('format', 'pdf');
        $approvalData = $this->getApprovalData('kepatuhan', $period);

        $licenses = LicenseInventory::with('catalog')
            ->select('*')
            ->selectRaw('(SELECT COUNT(*) FROM software_discoveries WHERE software_discoveries.catalog_id = license_inventories.catalog_id) as used_count')
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
