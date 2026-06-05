<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with(['causer', 'subject'])->latest();

        // 1. Filter Search (Deskripsi)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('description', 'like', "%{$search}%");
        }

        // 2. Filter User (Causer)
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id)
                ->where('causer_type', User::class);
        }

        // 3. Filter Tipe Entitas (Subject Type)
        if ($request->filled('entity_type')) {
            $entityType = $request->entity_type;
            if ($entityType === 'User') {
                $query->where('subject_type', User::class);
            } elseif ($entityType === 'Computer') {
                $query->where('subject_type', Computer::class);
            } elseif ($entityType === 'License') {
                $query->where('subject_type', LicenseInventory::class);
            } elseif ($entityType === 'SoftwareCatalog') {
                $query->where('subject_type', SoftwareCatalog::class);
            } elseif ($entityType === 'System') {
                $query->whereNull('subject_type');
            }
        }

        // 4. Filter Rentang Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', Carbon::parse($request->start_date));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', Carbon::parse($request->end_date));
        }

        $logs = $query->paginate(15)->withQueryString();
        $users = User::orderBy('name')->get();

        return view('pages.admin.activity-logs', compact('logs', 'users'));
    }
}
