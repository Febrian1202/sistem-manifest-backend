<?php

namespace App\Http\Controllers;

use App\Models\SoftwareCatalog;
use Illuminate\Http\Request;

class ComplianceDataController extends Controller
{
    //
    public function index()
    {
        // 1. Ambil software berbayar (Commercial) dengan agregasi dalam SATU query
        $softwares = SoftwareCatalog::where('category', 'Commercial')
            ->withCount('discoveries')
            ->withSum('licenses as owned_count', 'quota_limit')
            // Urutkan berdasarkan selisih (deficit) secara langsung di level database
            // Deficit = (jumlah terinstall) - (jumlah lisensi dimiliki)
            ->orderByRaw('(discoveries_count - COALESCE(owned_count, 0)) DESC')
            ->paginate(20)
            ->through(function ($software) {
                // Tambahkan atribut virtual untuk kebutuhan tampilan view
                $installed = $software->discoveries_count ?? 0;
                $owned = $software->owned_count ?? 0;
                $deficit = $installed > $owned ? $installed - $owned : 0;

                $software->installed_count = $installed;
                $software->owned_count = $owned;
                $software->deficit = $deficit;
                $software->is_compliant = $deficit === 0;

                return $software;
            });

        // 2. Hitung Statistik Global (Ini bisa dicache nanti di Step 2, tapi sekarang kita hitung efisien)
        // Karena kita pakai pagination, kita butuh query terpisah untuk total deficit global
        $stats = [
            'total_commercial' => SoftwareCatalog::where('category', 'Commercial')->count(),
            'total_deficit' => SoftwareCatalog::where('category', 'Commercial')
                ->withCount('discoveries')
                ->withSum('licenses as owned_count', 'quota_limit')
                ->get()
                ->sum(fn($s) => max(0, $s->discoveries_count - ($s->owned_count ?? 0))),
        ];

        // Hitung compliant/non-compliant untuk stats dashboard page
        // (Bisa dioptimasi lebih lanjut jika data sangat besar)
        $complianceStats = SoftwareCatalog::where('category', 'Commercial')
            ->withCount('discoveries')
            ->withSum('licenses as owned_count', 'quota_limit')
            ->get();
            
        $stats['compliant'] = $complianceStats->filter(fn($s) => ($s->discoveries_count <= ($s->owned_count ?? 0)))->count();
        $stats['non_compliant'] = $stats['total_commercial'] - $stats['compliant'];

        return view('pages.admin.compliance', compact('softwares', 'stats'));
    }
}
