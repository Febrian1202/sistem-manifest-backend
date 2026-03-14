<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SoftwareCatalog;

class SoftwareDataController extends Controller
{
    //
    public function index(Request $request)
    {
        // 1. QUERY UTAMA
        $query = SoftwareCatalog::query();

        // Relasi & Hitung Jumlah Install
        // Asumsi: Anda sudah menambahkan relasi 'discoveries' di model SoftwareCatalog
        // Jika belum, tambahkan: public function discoveries() { return $this->hasMany(SoftwareDiscovery::class, 'catalog_id'); }
        $query->withCount('discoveries');
        $query->with([
            'discoveries' => function ($q) {
                $q->latest('install_date'); // Untuk ambil vendor/version terbaru
            }
        ]);

        // 2. SEARCH (Nama Software)
        if ($request->filled('search')) {
            $query->where('normalized_name', 'like', "%{$request->search}%");
        }

        // 3. FILTER KATEGORI
        if ($request->filled('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        // 4. FILTER STATUS
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        // 5. GET DATA (Pagination)
        $softwares = $query->orderBy('normalized_name')->paginate(10)->withQueryString();

        // 6. STATISTIK
        $stats = [
            'total' => SoftwareCatalog::count(),
            'unreviewed' => SoftwareCatalog::where('status', 'Unreviewed')->count(),
            'whitelist' => SoftwareCatalog::where('status', 'Whitelist')->count(),
            'blacklist' => SoftwareCatalog::where('status', 'Blacklist')->count(),
        ];

        return view('pages.admin.softwares', compact('softwares', 'stats'));
    }
}
