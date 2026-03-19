<?php

namespace App\Http\Controllers;

use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use Illuminate\Http\Request;

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
    public function store(Request $request, LicenseInventory $license)
    {
        try {
            // 1. Validasi Data
            $validated = $request->validate([
                'catalog_id' => 'required|exists:software_catalogs,id',
                'purchase_order_number' => 'nullable|string|max:255',
                'quota_limit' => 'required|integer|min:1',
                'purchase_date' => 'nullable|date',
                'expiry_date' => 'nullable|date|after_or_equal:purchase_date',
                'price_per_unit' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'proof_image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Wajib & Gambar
            ], [
                // Custom Message agar user paham (Opsional)
                'proof_image.required' => 'Bukti pembelian berupa gambar wajib diunggah.',
                'catalog_id.required' => 'Silakan pilih software terlebih dahulu.',
                'quota_limit.min' => 'Kuota lisensi minimal adalah 1.',
            ]);

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
            // Jika ada error sistem/database (misal: database mati atau kolom kurang)
            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, LicenseInventory $license)
    {
        try {
            $validated = $request->validate([
                'catalog_id' => 'required|exists:software_catalogs,id',
                'quota_limit' => 'required|integer|min:1',
                'expiry_date' => 'nullable|date',
                'proof_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                'notes' => 'nullable|string',
                'price_per_unit' => 'nullable|numeric|min:0',
                'purchase_order_number' => 'nullable|string|max:255',
                'purchase_date' => 'nullable|date',
            ]);

            if ($request->hasFile('proof_image')) {
                // Hapus gambar lama jika ada
                if ($license->proof_image) {
                    \Storage::disk('public')->delete($license->proof_image);
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
                'message' => 'Gagal! Harap periksa kembali isian form Anda: ' . $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Gagal update: ' . $e->getMessage()
            ]);
        }
    }

    // Menghapus Data Lisensi
    public function destroy(LicenseInventory $license)
    {
        $license->delete();

        return back()->with([
            'status' => 'success',
            'message' => 'Data inventaris lisensi berhasil dihapus.'
        ]);
    }
}
