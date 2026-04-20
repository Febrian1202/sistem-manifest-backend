<?php

namespace App\Http\Controllers;

use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use Illuminate\Http\Request;
use App\Http\Requests\StoreLicenseRequest;
use App\Http\Requests\UpdateLicenseRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LicenseDataController extends Controller
{
    //
    public function index(Request $request)
    {
        // Data inventaris beserta relasi antar Katalog dan hitung jumlah instalasi (Usage)
        $query = LicenseInventory::with([
            'catalog' => function ($q) {
                $q->withCount('discoveries'); // Hitung jumlah instalasi
            }
        ]);

        // Fitur Pencarian dan Filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_order_number', 'like', "%{$search}%")
                    ->orWhereHas('catalog', function ($subQ) use ($search) {
                        $subQ->where('normalized_name', 'like', "%{$search}%");
                    });
            });
        }

        $licenses = $query->orderBy('expiry_date', 'asc')->paginate(10)->withQueryString();

        // Menyiapkan data untuk dropdown Tambah Lisensi
        // (Hanya ambil software yang statusnya Whitelist atau Commercial)
        $catalogs = SoftwareCatalog::whereIn('status', ['Whitelist', 'Unreviewed'])
            ->orderBy('normalized_name')
            ->get();

        // Hitung statistik untuk Dashboard Card
        $stats = [
            'total_licenses' => LicenseInventory::sum('quota_limit'), // Jumlah total Lisensi
            'total_value' => LicenseInventory::sum(\DB::raw('quota_limit * price_per_unit')), // Total Aset
            'expiring_soon' => LicenseInventory::where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>', now())
                ->count(),
            'expired' => LicenseInventory::where('expiry_date', '<', now())->count()
        ];
        return view('pages.admin.licenses', compact('licenses', 'catalogs', 'stats'));
    }

    // Menyimpan data pembelian
    public function store(StoreLicenseRequest $request)
    {
        try {
            $validated = $request->validated();

            // 2. Handle Upload Gambar
            if ($request->hasFile('proof_image')) {
                $path = $request->file('proof_image')->store('license_proofs', 'public');
                $validated['proof_image'] = $path;
            }

            // 3. Simpan ke Database
            LicenseInventory::create($validated);

            return back()->with([
                'status' => 'success',
                'message' => 'Data inventaris lisensi berhasil ditambahkan!'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Jika validasi gagal, kirim pesan error spesifik
            return back()->withErrors($e->validator)->withInput()->with([
                'status' => 'destructive',
                'message' => 'Gagal menyimpan! Ada kesalahan pada isian form Anda.'
            ]);
        } catch (\Exception $e) {
            Log::error('License Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem saat menyimpan data.'
            ]);
        }
    }

    public function update(UpdateLicenseRequest $request, LicenseInventory $license)
    {
        try {
            $validated = $request->validated();

            if ($request->hasFile('proof_image')) {
                // Hapus gambar lama jika ada
                if ($license->proof_image) {
                    Storage::disk('public')->delete($license->proof_image);
                }
                $validated['proof_image'] = $request->file('proof_image')->store('license_proofs', 'public');
            }

            $license->update($validated);

            return back()->with([
                'status' => 'success',
                'message' => 'Data lisensi berhasil diperbarui.'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput()->with([
                'status' => 'destructive',
                'message' => 'Gagal! Harap periksa kembali isian form Anda.'
            ]);
        } catch (\Exception $e) {
            Log::error('License Update Error: ' . $e->getMessage(), [
                'id' => $license->id,
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Gagal memperbarui data lisensi.'
            ]);
        }
    }

    // Menghapus Data Lisensi
    public function destroy(LicenseInventory $license)
    {
        // UX-002: Hapus gambar bukti dari storage agar tidak jadi orphan file
        if ($license->proof_image) {
            Storage::disk('public')->delete($license->proof_image);
        }

        $license->delete();

        return back()->with([
            'status' => 'success',
            'message' => 'Data inventaris lisensi berhasil dihapus.'
        ]);
    }
}
