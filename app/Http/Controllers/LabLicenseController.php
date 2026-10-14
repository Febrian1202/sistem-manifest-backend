<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabLicenseController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $facultyId = $user->faculty_id ?? $user->laboratory?->faculty_id;
        $faculty = $facultyId ? Faculty::find($facultyId) : null;

        if (! $faculty) {
            return view('lab-licenses.index', [
                'faculty' => null,
                'allocations' => collect(),
            ]);
        }

        $query = LicenseAllocation::query()
            ->with(['licenseInventory.catalog'])
            ->forFaculty($faculty->id)
            ->active()
            ->orderByDesc('allocation_date')
            ->orderBy('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('licenseInventory.catalog', function ($q) use ($search) {
                $q->where('normalized_name', 'like', "%{$search}%");
            });
        }

        $allocations = $query->paginate(12)->withQueryString();

        return view('lab-licenses.index', compact('faculty', 'allocations'));
    }

    public function getKey(Request $request, LicenseInventory $license): JsonResponse
    {
        $user = $request->user();
        $facultyId = $user->faculty_id ?? $user->laboratory?->faculty_id;

        if (! $facultyId) {
            abort(403, 'Akun Anda tidak terafiliasi dengan fakultas mana pun.');
        }

        $isAllocated = $license->allocations()
            ->forFaculty($facultyId)
            ->active()
            ->exists();

        if (! $isAllocated) {
            abort(403, 'Lisensi ini tidak dialokasikan untuk fakultas Anda.');
        }

        activity()
            ->performedOn($license)
            ->causedBy($user)
            ->withProperties([
                'software' => $license->catalog->normalized_name ?? 'N/A',
                'faculty' => $user->faculty?->name ?? $user->laboratory?->faculty?->name ?? 'N/A',
                'role' => $user->getRoleNames()->first(),
            ])
            ->log('Melihat license key alokasi fakultas');

        return response()->json([
            'key' => $license->license_key,
        ]);
    }
}
