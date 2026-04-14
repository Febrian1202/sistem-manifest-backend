<?php

namespace App\Http\Controllers\Api;

use App\Models\Computer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessScanResultJob;

class ScanController extends Controller
{
    /**
     * Store scan results from agent.
     * Identity is tied to the Sanctum token (auth()->user()).
     */
    public function store(Request $request)
    {
        // 1. Check Token Ability
        if (!$request->user()->tokenCan('scan:submit')) {
            return response()->json(['message' => 'Token does not have scan submission abilities.'], 403);
        }

        // 2. Validate Input (Keep existing rules)
        $request->validate([
            'computer_name' => 'required|string',
            // ... (keep rest of validaton)
            'installed_software' => 'required|array',
        ]);

        // 3. Update Authenticated Computer record (Identity comes from Token)
        $computer = $request->user();
        $computer->update([
            'hostname' => $request->computer_name, // Update hostname in case it changed
            'processor' => $request->processor,
            'ram_gb' => $request->ram_gb,
            'disk_total_gb' => $request->disk_total_gb,
            'disk_free_gb' => $request->disk_free_gb,
            'manufacturer' => $request->manufacturer,
            'model' => $request->model,
            'serial_number' => $request->serial_number,
            
            'ip_address' => $request->ip_address ?? $request->ip(),
            'mac_address' => $request->mac_address, // Sync MAC in case of updates

            'os_name' => $request->os_name ?? $request->os_info,
            'os_version' => $request->os_version,
            'os_architecture' => $request->os_architecture,
            'os_license_status' => $request->os_license_status,
            'os_partial_key' => $request->os_partial_key,

            'last_seen_at' => now(),
        ]);

        // 4. Dispatch heavy software processing to async job
        ProcessScanResultJob::dispatch(
            $computer,
            $request->installed_software
        );

        return response()->json([
            'status' => 'received',
            'message' => 'Data scan hardware & software sedang diproses',
            'computer' => $computer->hostname
        ], 202);
    }
}