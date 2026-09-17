<?php

namespace App\Http\Controllers;

use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->get('period', now()->format('Y-m'));

        try {
            $periodDate = Carbon::createFromFormat('Y-m', $period);
        } catch (\Exception $e) {
            $period = now()->format('Y-m');
            $periodDate = Carbon::createFromFormat('Y-m', $period);
        }

        $startOfMonth = $periodDate->copy()->startOfMonth();
        $endOfMonth = $periodDate->copy()->endOfMonth();

        $laboratories = Laboratory::withCount([
            'computers',
            'computers as scanned_computers_count' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('last_seen_at', [$startOfMonth, $endOfMonth]);
            },
        ])
            ->with([
                'reportApprovals' => function ($q) use ($period) {
                    $q->where('period', $period)
                        ->where('report_type', 'kepatuhan')
                        ->latest();
                },
                'penanggungJawab',
            ])
            ->get();

        return view('report-submissions.index', compact('laboratories', 'period'));
    }

    public function submit(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'laboratory_id' => 'required|exists:laboratories,id',
            'period' => 'required|date_format:Y-m',
        ]);

        $labId = $validated['laboratory_id'];
        $period = $validated['period'];

        $hasPending = ReportApproval::where([
            'laboratory_id' => $labId,
            'report_type' => 'kepatuhan',
            'period' => $period,
            'status' => 'pending',
        ])->exists();

        if ($hasPending) {
            return back()->with([
                'status' => 'destructive',
                'message' => 'Lab ini masih memiliki laporan pending yang belum di-review.',
                'error' => 'Lab ini masih memiliki laporan pending yang belum di-review.',
            ]);
        }

        $pjLab = User::role('kepala_lab')
            ->where('laboratory_id', $labId)
            ->first();

        ReportApproval::create([
            'laboratory_id' => $labId,
            'report_type' => 'kepatuhan',
            'period' => $period,
            'status' => 'pending',
            'reviewed_by' => $pjLab?->id,
        ]);

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['laboratory_id' => $labId, 'period' => $period])
            ->log("Mengirim laporan kepatuhan ke PJ Lab untuk periode {$period}");

        return back()->with([
            'status' => 'success',
            'message' => 'Laporan berhasil dikirim ke PJ Lab.',
            'success' => 'Laporan berhasil dikirim ke PJ Lab.',
        ]);
    }
}
