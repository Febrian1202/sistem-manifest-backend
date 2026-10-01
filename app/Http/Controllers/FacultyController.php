<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFacultyRequest;
use App\Http\Requests\UpdateFacultyRequest;
use App\Models\Faculty;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FacultyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Faculty::query();

        if (Schema::hasColumn('laboratories', 'faculty_id')) {
            $query->withCount('laboratories');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $faculties = $query->latest()->paginate(10)->withQueryString();

        return view('faculties.index', compact('faculties'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('faculties.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFacultyRequest $request)
    {
        try {
            Faculty::create($request->validated());

            return redirect()->route('faculties.index')->with([
                'status' => 'success',
                'message' => 'Fakultas berhasil ditambahkan!',
            ]);
        } catch (Exception $e) {
            Log::error('Faculty Store Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan saat menyimpan data fakultas.',
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Faculty $faculty)
    {
        if (Schema::hasColumn('laboratories', 'faculty_id')) {
            $faculty->load(['laboratories']);
        }

        return view('faculties.edit', compact('faculty'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFacultyRequest $request, Faculty $faculty)
    {
        try {
            $faculty->update($request->validated());

            return redirect()->route('faculties.index')->with([
                'status' => 'success',
                'message' => 'Data fakultas berhasil diperbarui!',
            ]);
        } catch (Exception $e) {
            Log::error('Faculty Update Error: '.$e->getMessage(), [
                'id' => $faculty->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan saat memperbarui data fakultas.',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Faculty $faculty)
    {
        try {
            if (Schema::hasColumn('laboratories', 'faculty_id') && $faculty->laboratories()->exists()) {
                return back()->with([
                    'status' => 'destructive',
                    'message' => 'Fakultas tidak dapat dihapus karena masih memiliki laboratorium.',
                ]);
            }

            $name = $faculty->name;
            $faculty->delete();

            return redirect()->route('faculties.index')->with([
                'status' => 'success',
                'message' => "Fakultas {$name} berhasil dihapus!",
            ]);
        } catch (Exception $e) {
            Log::error('Faculty Destroy Error: '.$e->getMessage(), [
                'id' => $faculty->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan saat menghapus fakultas.',
            ]);
        }
    }
}
