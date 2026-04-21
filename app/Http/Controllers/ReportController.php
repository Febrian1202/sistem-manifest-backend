<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Computer;
use App\Models\LicenseInventory;
use App\Models\SoftwareDiscovery;
use App\Models\ComplianceReport;
use App\Exports\KomputerExport;
use App\Exports\SoftwareExport;
use App\Exports\KepatuhanExport;
use App\Exports\LisensiExport;
use Illuminate\Support\Carbon;

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

    // --- 1. RINGKASAN EKSEKUTIF [PDF ONLY] ---

    public function showEksekutif(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $data = $this->getEksekutifData($startDate, $endDate);

        return view('reports.eksekutif', array_merge($data, [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
        ]));
    }

    public function exportEksekutif(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $data = $this->getEksekutifData($startDate, $endDate);
        $data['print_date'] = now()->format('d/m/Y H:i');
        $data['printed_by'] = auth()->user()->name . ' (' . auth()->user()->getRoleNames()->first() . ')';
        $data['startDateStr'] = $startDate->format('d/m/Y');
        $data['endDateStr'] = $endDate->format('d/m/Y');

        $pdf = Pdf::loadView('reports.pdf.eksekutif-pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->stream('laporan-eksekutif_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.pdf');
    }

    private function getEksekutifData($startDate, $endDate)
    {
        $totalComputers = Computer::count();
        $totalInstallations = SoftwareDiscovery::whereBetween('created_at', [$startDate, $endDate])->count();

        // Compliance stats
        $licensed = Computer::where('os_license_status', 'Licensed')->count();
        $complianceRate = $totalComputers > 0 ? round(($licensed / $totalComputers) * 100, 2) : 0;

        $criticalAlerts = SoftwareDiscovery::whereHas('catalog', function ($q) {
            $q->where('category', 'Commercial')->whereDoesntHave('licenses');
        })->whereBetween('created_at', [$startDate, $endDate])->count();

        $breakdown = [
            ['status' => 'Licensed', 'count' => $licensed, 'pct' => $totalComputers > 0 ? round(($licensed / $totalComputers) * 100, 1) : 0],
            ['status' => 'Grace Period', 'count' => Computer::where('os_license_status', 'Grace Period')->count(), 'pct' => $totalComputers > 0 ? round((Computer::where('os_license_status', 'Grace Period')->count() / $totalComputers) * 100, 1) : 0],
            ['status' => 'Action Required', 'count' => Computer::whereNotIn('os_license_status', ['Licensed', 'Grace Period'])->count(), 'pct' => $totalComputers > 0 ? round((Computer::whereNotIn('os_license_status', ['Licensed', 'Grace Period'])->count() / $totalComputers) * 100, 1) : 0],
        ];

        $topUnlicensed = SoftwareDiscovery::whereHas('catalog', function ($q) {
            $q->where('category', 'Commercial')->whereDoesntHave('licenses');
        })
            ->whereBetween('created_at', [$startDate, $endDate])
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
        $query = Computer::withCount('softwares')->whereBetween('created_at', [$startDate, $endDate])->orderBy('hostname');
        $computers = $query->paginate(15)->withQueryString();

        return view('reports.komputer', compact('computers', 'startDate', 'endDate'));
    }

    public function exportKomputer(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $format = $request->query('format', 'pdf');
        $computers = Computer::withCount('softwares')->whereBetween('created_at', [$startDate, $endDate])->orderBy('hostname')->get();

        if ($format === 'excel') {
            return Excel::download(new KomputerExport($computers, $startDate, $endDate), 'inventaris-komputer_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.xlsx');
        }

        $data = [
            'computers' => $computers,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name . ' (' . auth()->user()->getRoleNames()->first() . ')',
        ];

        return Pdf::loadView('reports.pdf.komputer-pdf', $data)->setPaper('a4', 'landscape')->stream();
    }

    // --- 3. INVENTARIS SOFTWARE [PDF + EXCEL] ---

    public function showSoftware(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $softwares = $this->getSoftwareData($startDate, $endDate)->paginate(15)->withQueryString();

        return view('reports.software', compact('softwares', 'startDate', 'endDate'));
    }

    public function exportSoftware(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $format = $request->query('format', 'pdf');
        $softwares = $this->getSoftwareData($startDate, $endDate)->get();

        if ($format === 'excel') {
            return Excel::download(new SoftwareExport($softwares, $startDate, $endDate), 'inventaris-software_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.xlsx');
        }

        $data = [
            'softwares' => $softwares,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name . ' (' . auth()->user()->getRoleNames()->first() . ')',
        ];

        return Pdf::loadView('reports.pdf.software-pdf', $data)->setPaper('a4', 'portrait')->stream();
    }

    private function getSoftwareData($startDate, $endDate)
    {
        return SoftwareDiscovery::whereBetween('software_discoveries.created_at', [$startDate, $endDate])
            ->join('software_catalogs', 'software_discoveries.catalog_id', '=', 'software_catalogs.id')
            ->select('software_catalogs.normalized_name', 'software_discoveries.version', 'software_catalogs.category', 'software_catalogs.id as catalog_id')
            ->selectRaw('count(distinct computer_id) as computer_count')
            ->with(['catalog.licenses'])
            ->groupBy('software_catalogs.normalized_name', 'software_discoveries.version', 'software_catalogs.category', 'software_catalogs.id')
            ->orderByDesc('computer_count');
    }

    // --- 4. KEPATUHAN LISENSI [PDF + EXCEL] ---

    public function showKepatuhan(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);

        $reports = ComplianceReport::with(['computer', 'softwareCatalog'])
            ->whereBetween('scanned_at', [$startDate, $endDate])
            ->orderByDesc('scanned_at')
            ->paginate(15)->withQueryString();

        return view('reports.kepatuhan', compact('reports', 'startDate', 'endDate'));
    }

    public function exportKepatuhan(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $format = $request->query('format', 'pdf');

        $reports = ComplianceReport::with(['computer', 'softwareCatalog'])
            ->whereBetween('scanned_at', [$startDate, $endDate])
            ->orderByDesc('scanned_at')
            ->get();

        if ($format === 'excel') {
            return Excel::download(new KepatuhanExport($reports, $startDate, $endDate), 'kepatuhan-lisensi_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.xlsx');
        }

        $data = [
            'reports' => $reports,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name . ' (' . (auth()->user()->roles->first()->name ?? 'User') . ')',
        ];

        return Pdf::loadView('reports.pdf.kepatuhan-pdf', $data)->setPaper('a4', 'portrait')->stream();
    }

    // --- 5. STATUS LISENSI [PDF + EXCEL] ---

    public function showLisensi(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $licenses = LicenseInventory::with('catalog')->whereBetween('created_at', [$startDate, $endDate])->get();

        // Enrich data with usage then sort by usage_pct DESC
        $licenses = $licenses->map(function ($license) {
            $usage = SoftwareDiscovery::where('catalog_id', $license->catalog_id)->count();
            $license->used_count = $usage;
            $license->remaining = max(0, $license->quota_limit - $usage);
            $license->usage_pct = $license->quota_limit > 0 ? round(($usage / $license->quota_limit) * 100, 1) : 0;
            return $license;
        })->sortByDesc('usage_pct')->values();

        // Manual pagination
        $page = request()->get('page', 1);
        $perPage = 15;
        $paginatedLicenses = new \Illuminate\Pagination\LengthAwarePaginator(
            $licenses->forPage($page, $perPage),
            $licenses->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('reports.lisensi', ['licenses' => $paginatedLicenses, 'startDate' => $startDate, 'endDate' => $endDate]);
    }

    public function exportLisensi(Request $request)
    {
        [$startDate, $endDate] = $this->getDateRange($request);
        $format = $request->query('format', 'pdf');
        $licenses = LicenseInventory::with('catalog')->whereBetween('created_at', [$startDate, $endDate])->get()->map(function ($license) {
            $usage = SoftwareDiscovery::where('catalog_id', $license->catalog_id)->count();
            $license->used_count = $usage;
            $license->remaining = max(0, $license->quota_limit - $usage);
            $license->usage_pct = $license->quota_limit > 0 ? round(($usage / $license->quota_limit) * 100, 1) : 0;
            return $license;
        });

        if ($format === 'excel') {
            return Excel::download(new LisensiExport($licenses, $startDate, $endDate), 'status-lisensi_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.xlsx');
        }

        $data = [
            'licenses' => $licenses,
            'startDateStr' => $startDate->format('d/m/Y'),
            'endDateStr' => $endDate->format('d/m/Y'),
            'print_date' => now()->format('d/m/Y H:i'),
            'printed_by' => auth()->user()->name . ' (' . auth()->user()->getRoleNames()->first() . ')',
        ];

        return Pdf::loadView('reports.pdf.lisensi-pdf', $data)->setPaper('a4', 'portrait')->stream();
    }

    public function runComplianceScan()
    {
        $computers = Computer::all();

        foreach ($computers as $computer) {
            \App\Jobs\GenerateComplianceReportJob::dispatch($computer)
                ->onQueue('compliance');
        }

        return back()->with([
            'status' => 'success',
            'message' => "Pemeriksaan kepatuhan untuk " . $computers->count() . " komputer telah dijadwalkan di background."
        ]);
    }
}
