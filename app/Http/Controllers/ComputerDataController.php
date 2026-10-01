<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateComputerRequest;
use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Services\SoftwareChangeDetectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ComputerDataController extends Controller
{
    //
    public function index(Request $request)
    {
        // 1. Mulai Query
        $query = Computer::with(['laboratory.faculty']);

        // 2. Logika Search (Hostname, IP, atau OS)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('hostname', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhere('os_name', 'like', "%{$search}%");
            });
        }

        // 3. Filter Berdasarkan Fakultas
        if ($request->filled('faculty_id') && $request->faculty_id !== 'All') {
            $query->whereHas('laboratory', function ($q) use ($request) {
                $q->where('faculty_id', $request->faculty_id);
            });
        }

        // 4. Filter Berdasarkan Laboratorium
        if ($request->filled('laboratory_id') && $request->laboratory_id !== 'All') {
            $query->where('laboratory_id', $request->laboratory_id);
        }

        // 5. Filter Berdasarkan Lokasi
        if ($request->filled('location') && $request->location !== 'All') {
            $query->where('location', $request->location);
        }

        // 6. Filter Berdasarkan Status Lisensi
        if ($request->filled('license_status') && $request->license_status !== 'All') {
            $query->where('os_license_status', $request->license_status);
        }

        // 7. Ambil data (Pagination)
        $computers = $query->latest('last_seen_at')->paginate(10)->withQueryString();

        // 8. Ambil daftar lokasi unik untuk opsi Filter
        $locations = Computer::select('location')
            ->whereNotNull('location')
            ->distinct()
            ->orderBy('location')
            ->pluck('location');

        // 9. Ambil daftar fakultas untuk opsi Filter
        $faculties = Faculty::orderBy('name')->get();

        // 10. Ambil daftar laboratorium untuk opsi Filter (dengan relasi fakultas)
        $laboratories = Laboratory::with('faculty')->orderBy('name')->get();

        return view('pages.admin.computers', compact('computers', 'locations', 'laboratories', 'faculties'));
    }

    public function show(Computer $computer)
    {
        $computer->load([
            'laboratory.faculty',
            'softwares' => function ($q) {
                $q->orderBy('raw_name');
            },
            'softwares.catalog',
        ]);

        return view('pages.admin.computers-show', compact('computer'));
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
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator)->withInput()->with([
                'status' => 'destructive',
                'message' => 'Gagal! Harap periksa kembali isian form Anda.',
            ]);
        } catch (\Exception $e) {
            Log::error('Computer Update Error: '.$e->getMessage(), [
                'id' => $computer->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->with([
                'status' => 'destructive',
                'message' => 'Terjadi kesalahan sistem saat memperbarui data.',
            ]);
        }
    }

    public function requestScan(Computer $computer)
    {
        $computer->update(['scan_requested' => true]);

        activity()
            ->performedOn($computer)
            ->causedBy(auth()->user())
            ->log("Meminta scan ulang komputer {$computer->hostname}");

        return back()->with([
            'message' => 'Permintaan scan dikirim. Scanner akan memproses pada polling berikutnya.',
            'status' => 'success',
        ]);
    }

    public function requestScanAll()
    {
        $updated = Computer::where('scan_requested', false)
            ->update(['scan_requested' => true]);

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['affected_count' => $updated])
            ->log("Meminta scan ulang ke {$updated} komputer");

        return back()->with([
            'message' => "Permintaan scan dikirim ke {$updated} komputer.",
            'status' => 'success',
        ]);
    }

    public function history(Request $request, Computer $computer, SoftwareChangeDetectionService $changeService)
    {
        $user = auth()->user();

        if ($user->hasRole('kepala_lab')) {
            if (! $user->laboratory_id || (int) $computer->laboratory_id !== (int) $user->laboratory_id) {
                abort(403, 'Anda tidak memiliki akses ke histori komputer laboratorium ini.');
            }
        }

        $computer->load('laboratory');

        $query = $computer->scanSessions()
            ->with(['softwareResults', 'complianceSnapshots']);

        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        if ($request->filled('period_start')) {
            $query->whereDate('started_at', '>=', Carbon::parse($request->period_start));
        }

        if ($request->filled('period_end')) {
            $query->whereDate('started_at', '<=', Carbon::parse($request->period_end));
        }

        $sessions = $query->latest('started_at')->latest('id')->paginate(10)->withQueryString();

        $sessionsWithDiff = $sessions->through(function ($session) use ($changeService) {
            $diff = $changeService->compareSessions($session);
            $complianceDiff = $changeService->compareComplianceSnapshots($session, $diff['previous_session'] ?? null);

            return [
                'session' => $session,
                'diff' => $diff,
                'compliance_diff' => $complianceDiff,
            ];
        });

        return view('monitoring.computer-history', compact('computer', 'sessions', 'sessionsWithDiff'));
    }

    public function destroy(Computer $computer)
    {
        try {
            $hostname = $computer->hostname;
            $computer->delete();

            return back()->with([
                'status' => 'success',
                'message' => "Komputer {$hostname} berhasil dihapus dari sistem.",
            ]);
        } catch (\Exception $e) {
            Log::error('Computer Delete Error: '.$e->getMessage(), [
                'id' => $computer->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with([
                'status' => 'destructive',
                'message' => 'Gagal menghapus komputer.',
            ]);
        }
    }
}
