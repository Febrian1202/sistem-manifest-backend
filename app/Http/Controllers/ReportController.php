<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportExport;
use App\Models\SoftwareCatalog;
use App\Models\Computer;
use App\Models\LicenseInventory;

class ReportController extends Controller
{
    // Menampilkan halaman Pusat Laporan
    public function index()
    {
        return view('pages.admin.reports');
    }

    // Memproses ekspor berdasarkan pilihan user
    public function export(Request $request)
    {
        $request->validate([
            'report_type' => 'required|string',
            'format' => 'required|in:pdf,excel',
        ]);

        $type = $request->report_type;
        $format = $request->format;
        $data = [];

        // 1. Kumpulkan Data berdasarkan Tipe Laporan
        switch ($type) {
            case 'compliance_summary':
                $data['title'] = 'Ringkasan Kepatuhan Lisensi Perangkat Lunak';
                $data['softwares'] = $this->getComplianceData();
                break;

            case 'violation_report':
                $data['title'] = 'Laporan Detail Pelanggaran Hak Cipta (Non-Compliant)';
                // Ambil data yang defisitnya lebih dari 0
                $data['softwares'] = $this->getComplianceData()->filter(fn($s) => $s->deficit > 0);
                break;

            // Nanti Abang bisa tambah case 'asset_inventory', 'license_expiration', dll di sini

            default:
                return back()->with(['status' => 'destructive', 'message' => 'Tipe laporan tidak valid.']);
        }

        $data['print_date'] = now()->format('d F Y H:i');

        // 2. Eksekusi Export (PDF atau Excel)
        $viewPath = 'exports.' . $type;
        $fileName = 'USN_' . strtoupper($type) . '_' . date('Ymd_Hi');

        if ($format === 'pdf') {
            // DomPDF tidak mendukung CSS Flexbox/Grid, harus pakai table konvensional
            $pdf = Pdf::loadView($viewPath, $data)->setPaper('a4', 'landscape');
            return $pdf->download($fileName . '.pdf');
        } else {
            return Excel::download(new ReportExport($type, $data), $fileName . '.xlsx');
        }
    }

    // Private method pembantu untuk menghitung compliance (Biar rapi)
    private function getComplianceData()
    {
        return SoftwareCatalog::where('category', 'Commercial')
            ->with(['discoveries.computer'])
            ->withCount('discoveries')
            ->withSum('licenses as total_owned', 'quota_limit')
            ->get()
            ->map(function ($software) {
                $installed = $software->discoveries_count ?? 0;
                $owned = $software->total_owned ?? 0;
                $software->installed_count = $installed;
                $software->owned_count = $owned;
                $software->deficit = $installed > $owned ? $installed - $owned : 0;
                return $software;
            })->sortByDesc('deficit');
    }
}