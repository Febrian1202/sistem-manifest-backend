<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLicenseAllocationRequest;
use App\Http\Requests\UpdateLicenseAllocationRequest;
use App\Models\Faculty;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LicenseAllocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = LicenseAllocation::query()
            ->with(['licenseInventory.catalog', 'faculty', 'creator']);

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('catalog_id')) {
            $query->whereHas('licenseInventory', function ($q) use ($request) {
                $q->where('catalog_id', $request->catalog_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('licenseInventory.catalog', function ($sub) use ($search) {
                    $sub->where('normalized_name', 'like', "%{$search}%");
                })
                    ->orWhereHas('faculty', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('licenseInventory', function ($sub) use ($search) {
                        $sub->where('purchase_order_number', 'like', "%{$search}%");
                    });
            });
        }

        $allocations = $query->latest('allocation_date')->latest('id')->paginate(10)->withQueryString();

        // Statistik Ringkasan
        $totalOwned = (int) LicenseInventory::sum('quota_limit');
        $totalAllocatedSeats = (int) LicenseAllocation::active()->sum('allocated_quota');
        $totalUnallocatedSeats = max(0, $totalOwned - $totalAllocatedSeats);
        $totalRecipientsCount = LicenseAllocation::active()->distinct('faculty_id')->count('faculty_id');
        $totalActiveAllocations = LicenseAllocation::active()->count();

        $stats = [
            'total_owned' => $totalOwned,
            'total_allocated' => $totalAllocatedSeats,
            'total_unallocated' => $totalUnallocatedSeats,
            'total_recipients' => $totalRecipientsCount,
            'total_active_allocations' => $totalActiveAllocations,
        ];

        $faculties = Faculty::orderBy('name')->get();
        $catalogs = SoftwareCatalog::orderBy('normalized_name')->get();

        return view('licenses.allocations.index', compact('allocations', 'stats', 'faculties', 'catalogs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $licenses = LicenseInventory::with(['catalog', 'activeAllocations'])
            ->latest()
            ->get();

        $faculties = Faculty::orderBy('name')->get();

        return view('licenses.allocations.create', compact('licenses', 'faculties'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLicenseAllocationRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $validated['created_by'] = auth()->id();

            LicenseAllocation::create($validated);

            return redirect()->route('license-allocations.index')->with([
                'status' => 'success',
                'message' => 'Alokasi lisensi berhasil ditambahkan!',
            ]);
        } catch (Exception $e) {
            Log::error('License Allocation Store Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan saat menyimpan alokasi lisensi.',
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LicenseAllocation $licenseAllocation): RedirectResponse
    {
        return redirect()->route('license-allocations.edit', $licenseAllocation);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LicenseAllocation $licenseAllocation): View
    {
        $licenseAllocation->load(['licenseInventory.catalog', 'faculty']);

        $licenses = LicenseInventory::with(['catalog', 'activeAllocations'])
            ->latest()
            ->get();

        $faculties = Faculty::orderBy('name')->get();

        return view('licenses.allocations.edit', compact('licenseAllocation', 'licenses', 'faculties'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLicenseAllocationRequest $request, LicenseAllocation $licenseAllocation): RedirectResponse
    {
        try {
            $licenseAllocation->update($request->validated());

            return redirect()->route('license-allocations.index')->with([
                'status' => 'success',
                'message' => 'Alokasi lisensi berhasil diperbarui!',
            ]);
        } catch (Exception $e) {
            Log::error('License Allocation Update Error: '.$e->getMessage(), [
                'id' => $licenseAllocation->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan saat memperbarui alokasi lisensi.',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LicenseAllocation $licenseAllocation): RedirectResponse
    {
        try {
            $licenseAllocation->delete();

            return redirect()->route('license-allocations.index')->with([
                'status' => 'success',
                'message' => 'Alokasi lisensi berhasil dihapus!',
            ]);
        } catch (Exception $e) {
            Log::error('License Allocation Delete Error: '.$e->getMessage(), [
                'id' => $licenseAllocation->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan saat menghapus alokasi lisensi.',
            ]);
        }
    }
}
