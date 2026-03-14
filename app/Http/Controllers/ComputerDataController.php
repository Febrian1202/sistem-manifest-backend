<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use Illuminate\Http\Request;

class ComputerDataController extends Controller
{
    //
    public function index(Request $request)
    {
        // 1. Mulai Query
        $query = Computer::query();

        // 2. Logika Search (Hostname, IP, atau OS)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('hostname', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('os_name', 'like', "%{$search}%");
            });
        }

        // 3. Filter Berdasarkan Lokasi
        if ($request->filled('location') && $request->location !== 'All') {
            $query->where('location', $request->location);
        }

        // 4. Filter Berdasarkan Status Lisensi
        if ($request->filled('license_status') && $request->license_status !== 'All') {
            $query->where('os_license_status', $request->license_status);
        }

        // 5. Ambil data (Pagination)
        $computers = $query->latest('last_seen_at')->paginate(10)->withQueryString();

        // 6. Ambil daftar lokasi unik untuk opsi Filter
        $locations = Computer::select('location')
            ->whereNotNull('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        return view('pages.admin.computers', compact('computers', 'locations'));
    }

    public function update(Request $request, Computer $computer)
    {
        // 1. Validasi
        $validated = $request->validate([
            'location' => 'nullable|string|max:255',
            // Tambahkan field lain jika ingin bisa diedit, misal: 'os_license_status'
        ]);

        // 2. Update Data
        $computer->update($validated);

        // 3. Redirect kembali
        return back()->with('status', 'Data komputer berhasil diperbarui!');
    }
}
