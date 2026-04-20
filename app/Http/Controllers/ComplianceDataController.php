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

        // 2. Hitung Statistik Global (Efisien)
        $allCommercial = SoftwareCatalog::where('category', 'Commercial')
            ->withCount('discoveries')
            ->withSum('licenses as owned_count', 'quota_limit')
            ->get();

        $stats = [
            'total_commercial' => $allCommercial->count(),
            'total_deficit' => $allCommercial->sum(fn($s) => max(0, $s->discoveries_count - ($s->owned_count ?? 0))),
            'compliant' => $allCommercial->where(fn($s) => $s->discoveries_count <= ($s->owned_count ?? 0))->count(),
        ];
        $stats['non_compliant'] = $stats['total_commercial'] - $stats['compliant'];

        return view('pages.admin.compliance', compact('softwares', 'stats'));
    }
}
