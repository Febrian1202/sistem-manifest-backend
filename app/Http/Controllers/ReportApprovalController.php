<?php

namespace App\Http\Controllers;

use App\Models\ComplianceReport;
use App\Models\Computer;
use App\Models\ReportApproval;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportApprovalController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $labId = $user->laboratory_id;

        if (! $labId) {
            abort(403, 'Anda belum ditugaskan ke laboratorium manapun.');
        }

        $approvals = ReportApproval::where('laboratory_id', $labId)
            ->with(['laboratory', 'reviewer'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('report-approvals.index', compact('approvals'));
    }

    public function show(ReportApproval $reportApproval): View
    {
        $this->authorizeLabAccess($reportApproval);

        $lab = $reportApproval->laboratory;
        $period = $reportApproval->period;

        try {
            $periodDate = Carbon::createFromFormat('Y-m', $period);
        } catch (\Exception $e) {
            $periodDate = now();
        }

        $startOfMonth = $periodDate->copy()->startOfMonth();
        $endOfMonth = $periodDate->copy()->endOfMonth();

        $computers = Computer::where('laboratory_id', $lab->id)
            ->with(['softwares.catalog', 'complianceReports.softwareCatalog'])
            ->get();

        $totalComputers = $computers->count();
        $scanned = $computers->whereBetween('last_seen_at', [$startOfMonth, $endOfMonth])->count();
        $compliantOS = $computers->where('os_license_status', 'Licensed')->count();
        $complianceRate = $totalComputers > 0 ? round(($compliantOS / $totalComputers) * 100, 1) : 0;

        $stats = [
            'total_computers' => $totalComputers,
            'scanned' => $scanned,
            'compliant' => $compliantOS,
            'non_compliant' => $totalComputers - $compliantOS,
            'compliance_rate' => $complianceRate,
        ];

        $violations = ComplianceReport::whereHas('computer', fn ($q) => $q->where('laboratory_id', $lab->id))
            ->where('status', '!=', 'Berlisensi')
            ->with(['computer', 'softwareCatalog'])
            ->get();

        return view('report-approvals.show', compact(
            'reportApproval',
            'lab',
            'computers',
            'stats',
            'violations',
            'period'
        ));
    }

    public function approve(Request $request, ReportApproval $reportApproval): RedirectResponse
    {
        $this->authorizeLabAccess($reportApproval);

        if ($reportApproval->status !== 'pending') {
            return back()->with([
                'status' => 'destructive',
                'message' => 'Laporan ini sudah di-review sebelumnya.',
                'error' => 'Laporan ini sudah di-review sebelumnya.',
            ]);
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $reportApproval->update([
            'status' => 'approved',
            'notes' => $request->notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        activity()
            ->performedOn($reportApproval)
            ->causedBy(auth()->user())
            ->withProperties([
                'laboratory_id' => $reportApproval->laboratory_id,
                'period' => $reportApproval->period,
                'notes' => $request->notes,
            ])
            ->log("Menyetujui laporan kepatuhan lab {$reportApproval->laboratory->name} periode {$reportApproval->period}");

        return redirect()->route('lab.reports.index')->with([
            'status' => 'success',
            'message' => 'Laporan berhasil disetujui.',
            'success' => 'Laporan berhasil disetujui.',
        ]);
    }

    public function reject(Request $request, ReportApproval $reportApproval): RedirectResponse
    {
        $this->authorizeLabAccess($reportApproval);

        if ($reportApproval->status !== 'pending') {
            return back()->with([
                'status' => 'destructive',
                'message' => 'Laporan ini sudah di-review sebelumnya.',
                'error' => 'Laporan ini sudah di-review sebelumnya.',
            ]);
        }

        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $reportApproval->update([
            'status' => 'rejected',
            'notes' => $request->notes,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        activity()
            ->performedOn($reportApproval)
            ->causedBy(auth()->user())
            ->withProperties([
                'laboratory_id' => $reportApproval->laboratory_id,
                'period' => $reportApproval->period,
                'notes' => $request->notes,
            ])
            ->log("Menolak laporan kepatuhan lab {$reportApproval->laboratory->name} periode {$reportApproval->period}");

        return redirect()->route('lab.reports.index')->with([
            'status' => 'success',
            'message' => 'Laporan ditolak. Catatan telah disimpan.',
            'success' => 'Laporan ditolak. Catatan telah disimpan.',
        ]);
    }

    public function previewPdf(ReportApproval $reportApproval): Response
    {
        $this->authorizeLabAccess($reportApproval);

        $lab = $reportApproval->laboratory;

        $complianceData = ComplianceReport::whereHas('computer', fn ($q) => $q->where('laboratory_id', $lab->id))
            ->with(['computer', 'softwareCatalog', 'licenseInventory'])
            ->get();

        $computers = Computer::where('laboratory_id', $lab->id)
            ->with(['softwares.catalog', 'complianceReports'])
            ->get();

        $pdf = Pdf::loadView('report-approvals.preview-pdf', [
            'reportApproval' => $reportApproval,
            'lab' => $lab,
            'period' => $reportApproval->period,
            'complianceData' => $complianceData,
            'computers' => $computers,
            'printedBy' => auth()->user()->name,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream("preview-laporan-{$lab->code}-{$reportApproval->period}.pdf");
    }

    private function authorizeLabAccess(ReportApproval $reportApproval): void
    {
        if ($reportApproval->laboratory_id !== auth()->user()->laboratory_id) {
            abort(403, 'Anda tidak memiliki akses ke laporan laboratorium ini.');
        }
    }
}
