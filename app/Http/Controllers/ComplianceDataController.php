<?php

namespace App\Http\Controllers;

use App\Models\SoftwareCatalog;
use Illuminate\Http\Request;

class ComplianceDataController extends Controller
{
    //
    public function index()
    {
        // 1. Ambil HANYA software berbayar (Commercial) untuk diaudit
        $softwares = SoftwareCatalog::where('category', 'Commercial')
            // Eager load relasi ke komputer agar bisa dilihat siapa pelakunya
            ->with(['discoveries.computer'])
            ->withCount('discoveries') // Hitung total instalasi
            ->withSum('licenses as total_owned', 'quota_limit') // Hitung total lisensi dibeli
            ->get()
            ->map(function ($software) {
                // Proses Audit (Kalkulasi Kepatuhan)
                $installed = $software->discoveries_count ?? 0;
                $owned = $software->total_owned ?? 0;

                // Jika yang install lebih banyak dari lisensi, berarti ada Defisit (Ilegal)
                $deficit = $installed > $owned ? $installed - $owned : 0;

                // Masukkan hasil kalkulasi ke dalam object
                $software->installed_count = $installed;
                $software->owned_count = $owned;
                $software->deficit = $deficit;
                $software->is_compliant = $deficit === 0;

                return $software;
            })
            // Urutkan dari yang pelanggarannya paling banyak ke paling sedikit
            ->sortByDesc('deficit')
            ->values();

        // 2. Hitung Statistik Global untuk Dashboard Kepatuhan
        $stats = [
            'total_commercial' => $softwares->count(),
            'compliant' => $softwares->where('is_compliant', true)->count(),
            'non_compliant' => $softwares->where('is_compliant', false)->count(),
            'total_deficit' => $softwares->sum('deficit'), // Total software bajakan
        ];

        return view('pages.admin.compliance', compact('softwares', 'stats'));
    }
}
