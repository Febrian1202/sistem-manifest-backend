<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateComputerRequest;
use Illuminate\Support\Facades\Log;

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

    public function update(UpdateComputerRequest $request, Computer $computer)
    {
        try {
            // 1. Validasi & Update Data
            $computer->update($request->validated());

            // 3. Redirect kembali
            return back()->with([
                'message' => 'Data komputer berhasil diperbarui!',
                'status' => 'success',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput()->with([
                'status' => 'destructive',
                'message' => 'Gagal! Harap periksa kembali isian form Anda.',
            ]);
        } catch (\Exception $e) {
            Log::error('Computer Update Error: ' . $e->getMessage(), [
                'id' => $computer->id,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem saat memperbarui data.'
            ]);
        }
    }
    public function requestScan(Computer $computer)
    {
        $computer->update(['scan_requested' => true]);

        return back()->with([
            'message' => 'Permintaan scan dikirim. Agent akan memproses pada polling berikutnya.',
            'status' => 'success',
        ]);
    }

    public function requestScanAll()
    {
        $updated = Computer::where('scan_requested', false)
            ->update(['scan_requested' => true]);

        return back()->with([
            'message' => "Permintaan scan dikirim ke {$updated} komputer.",
            'status' => 'success',
        ]);
    }

    public function destroy(Computer $computer)
    {
        try {
            $hostname = $computer->hostname;
            $computer->delete();

            return back()->with([
                'status' => 'success',
                'message' => "Komputer {$hostname} berhasil dihapus dari sistem."
            ]);
        } catch (\Exception $e) {
            Log::error('Computer Delete Error: ' . $e->getMessage(), [
                'id' => $computer->id,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with([
                'status' => 'destructive',
                'message' => 'Gagal menghapus komputer.'
            ]);
        }
    }
}
