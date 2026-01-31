<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use Illuminate\Http\Request;

class ComputerDataController extends Controller
{
    //
    public function index()
    {
        $computers = Computer::latest('last_seen_at')->paginate(10);
        return view('pages.admin.computers', compact('computers'));
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
