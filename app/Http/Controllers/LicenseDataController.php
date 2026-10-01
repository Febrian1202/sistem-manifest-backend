<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLicenseRequest;
use App\Http\Requests\UpdateLicenseRequest;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Services\LicenseComplianceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LicenseDataController extends Controller
{
    public function __construct(
        protected LicenseComplianceService $complianceService
    ) {}

    public function index(Request $request)
    {
        $today = now()->toDateString();
        $thirtyDaysLater = now()->addDays(30)->toDateString();

        $activeQuotaSub = '(SELECT COALESCE(SUM(li2.quota_limit), 0) FROM license_inventories li2 WHERE li2.catalog_id = license_inventories.catalog_id AND (li2.expiry_date IS NULL OR li2.expiry_date >= CURRENT_DATE))';
        $installedSub = "(SELECT COUNT(DISTINCT sd.computer_id) FROM software_discoveries sd WHERE sd.catalog_id = license_inventories.catalog_id AND EXISTS (SELECT 1 FROM computers c WHERE c.id = sd.computer_id AND c.status = 'active'))";

        // Data inventaris beserta relasi antar Katalog dan hitung jumlah instalasi (Usage)
        $query = LicenseInventory::with([
            'catalog' => function ($q) {
                $q->withCount([
                    'discoveries' => fn ($sub) => $sub->whereHas('computer', fn ($c) => $c->where('status', 'active')),
                ]);
            },
            'allocations',
        ]);

        // Fitur Pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('purchase_order_number', 'like', "%{$search}%")
                    ->orWhereHas('catalog', function ($subQ) use ($search) {
                        $subQ->where('normalized_name', 'like', "%{$search}%");
                    });
            });
        }

        // Fitur Filter Status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'Aman':
                    // Usage <= Quota DAN Belum Expired
                    $query->whereRaw("{$installedSub} <= {$activeQuotaSub}")
                        ->where(function ($sub) use ($today) {
                            $sub->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today);
                        });
                    break;
                case 'Segera Habis':
                    // Usage > 80% Quota ATAU Expiring Soon (30 days)
                    $query->where(function ($q) use ($installedSub, $activeQuotaSub, $today, $thirtyDaysLater) {
                        $q->whereRaw("({$activeQuotaSub} > 0 AND {$installedSub} > ({$activeQuotaSub} * 0.8))")
                            ->orWhereBetween('expiry_date', [$today, $thirtyDaysLater]);
                    });
                    break;
                case 'Kedaluwarsa':
                    $query->where('expiry_date', '<', $today);
                    break;
                case 'Over Limit':
                    $query->whereRaw("{$installedSub} > {$activeQuotaSub}");
                    break;
            }
        }

        $licenses = $query->orderByRaw('expiry_date IS NULL ASC')
            ->orderBy('expiry_date', 'asc')
            ->paginate(12)
            ->withQueryString();

        $catalogIds = $licenses->pluck('catalog_id')->unique()->filter()->all();
        $catalogEntitlements = LicenseInventory::whereIn('catalog_id', $catalogIds)
            ->where(function ($q) use ($today) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', $today);
            })
            ->groupBy('catalog_id')
            ->selectRaw('catalog_id, SUM(quota_limit) as total_quota')
            ->pluck('total_quota', 'catalog_id');

        $catalogInstallCounts = SoftwareDiscovery::whereIn('catalog_id', $catalogIds)
            ->whereHas('computer', fn ($c) => $c->where('status', 'active'))
            ->groupBy('catalog_id')
            ->selectRaw('catalog_id, COUNT(DISTINCT computer_id) as total_installed')
            ->pluck('total_installed', 'catalog_id');

        foreach ($licenses as $license) {
            $license->total_catalog_quota = (int) ($catalogEntitlements[$license->catalog_id] ?? $license->quota_limit);
            $license->allocated_seats = $license->total_allocated;
            $license->remaining_unallocated = $license->remaining_unallocated;
            $license->total_catalog_installed = (int) ($catalogInstallCounts[$license->catalog_id] ?? 0);
        }

        // Menyiapkan data untuk dropdown Tambah Lisensi
        $catalogs = SoftwareCatalog::whereIn('status', ['Whitelist', 'Unreviewed'])
            ->orderBy('normalized_name')
            ->get();

        // Hitung statistik untuk Dashboard Card
        $stats = [
            'total_licenses' => LicenseInventory::sum('quota_limit'),
            'total_value' => LicenseInventory::sum(DB::raw('quota_limit * COALESCE(price_per_unit, 0)')),
            'expiring_soon' => LicenseInventory::where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>', now())
                ->count(),
            'expired' => LicenseInventory::where('expiry_date', '<', now())->count(),
        ];

        return view('pages.admin.licenses', compact('licenses', 'catalogs', 'stats'));
    }

    /**
     * Tampilkan detail lisensi beserta daftar komputer yang menggunakannya.
     */
    public function show(LicenseInventory $license)
    {
        $license->load(['catalog', 'allocations.faculty']);

        // Ambil software catalog terkait
        $catalog = $license->catalog;

        $license->total_catalog_quota = $this->complianceService->getActiveEntitlement($license->catalog_id);
        $license->allocated_seats = $license->total_allocated;
        $license->remaining_unallocated = $license->remaining_unallocated;
        $license->total_catalog_installed = $this->complianceService->getInstalledCount($license->catalog_id);

        // Ambil daftar discovery (komputer) yang menggunakan software ini
        $discoveries = $catalog->discoveries()->with('computer')->paginate(10);

        return view('pages.admin.license.show', compact('license', 'catalog', 'discoveries'));
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
                'message' => 'Data inventaris lisensi berhasil ditambahkan!',
            ]);

        } catch (ValidationException $e) {
            // Jika validasi gagal, kirim pesan error spesifik
            return back()->withErrors($e->validator)->withInput()->with([
                'status' => 'destructive',
                'message' => 'Gagal menyimpan! Ada kesalahan pada isian form Anda.',
            ]);
        } catch (\Exception $e) {
            Log::error('License Store Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem saat menyimpan data.',
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
                'message' => 'Data lisensi berhasil diperbarui.',
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput()->with([
                'status' => 'destructive',
                'message' => 'Gagal! Harap periksa kembali isian form Anda.',
            ]);
        } catch (\Exception $e) {
            Log::error('License Update Error: '.$e->getMessage(), [
                'id' => $license->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Gagal memperbarui data lisensi.',
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
            'message' => 'Data inventaris lisensi berhasil dihapus.',
        ]);
    }

    /**
     * Get the decrypted license key for AJAX request.
     */
    public function getKey(LicenseInventory $license)
    {
        // Authorize is handled by middleware, but good to double check
        if (! auth()->user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        activity()
            ->performedOn($license)
            ->causedBy(auth()->user())
            ->withProperties(['software' => $license->catalog->normalized_name ?? 'N/A'])
            ->log('Melihat license key');

        return response()->json([
            'key' => $license->license_key,
        ]);
    }
}
