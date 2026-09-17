<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaboratoryRequest;
use App\Http\Requests\UpdateLaboratoryRequest;
use App\Models\Laboratory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LaboratoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Laboratory::query()->withCount('computers')->with('penanggungJawab');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('building', 'like', "%{$search}%");
            });
        }

        $laboratories = $query->latest()->paginate(10)->withQueryString();

        return view('laboratories.index', compact('laboratories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('laboratories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLaboratoryRequest $request)
    {
        try {
            Laboratory::create($request->validated());

            return redirect()->route('laboratories.index')->with([
                'status' => 'success',
                'message' => 'Laboratorium berhasil ditambahkan!',
            ]);
        } catch (\Exception $e) {
            Log::error('Laboratory Store Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan saat menyimpan data laboratorium.',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Laboratory $laboratory)
    {
        $laboratory->load(['computers', 'penanggungJawab']);

        return view('laboratories.edit', compact('laboratory'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLaboratoryRequest $request, Laboratory $laboratory)
    {
        try {
            $laboratory->update($request->validated());

            return redirect()->route('laboratories.index')->with([
                'status' => 'success',
                'message' => 'Data laboratorium berhasil diperbarui!',
            ]);
        } catch (\Exception $e) {
            Log::error('Laboratory Update Error: '.$e->getMessage(), [
                'id' => $laboratory->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan saat memperbarui data laboratorium.',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Laboratory $laboratory)
    {
        try {
            if ($laboratory->computers()->exists()) {
                return back()->with([
                    'status' => 'destructive',
                    'message' => 'Gagal menghapus! Laboratorium ini masih memiliki komputer terkait.',
                ]);
            }

            // Unlink any assigned PJ Lab users
            $laboratory->penanggungJawab()->update(['laboratory_id' => null]);

            $name = $laboratory->name;
            $laboratory->delete();

            return redirect()->route('laboratories.index')->with([
                'status' => 'success',
                'message' => "Laboratorium {$name} berhasil dihapus!",
            ]);
        } catch (\Exception $e) {
            Log::error('Laboratory Destroy Error: '.$e->getMessage(), [
                'id' => $laboratory->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan saat menghapus laboratorium.',
            ]);
        }
    }
}
