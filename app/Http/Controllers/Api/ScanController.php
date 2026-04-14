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
     * Computer record is synced synchronously, software list is processed asynchronously.
     */
    public function store(Request $request)
    {
        // 1. Validate Input (Keep existing rules)
        $request->validate([
            'computer_name' => 'required|string',

            // Hardware Info
            'processor' => 'nullable|string',
            'ram_gb' => 'nullable|integer',
            'disk_total_gb' => 'nullable|integer',
            'disk_free_gb' => 'nullable|integer',
            'manufacturer' => 'nullable|string',
            'model' => 'nullable|string',
            'serial_number' => 'nullable|string',

            // Network & OS
            'ip_address' => 'nullable|string',
            'mac_address' => 'nullable|string',
            'os_name' => 'nullable|string',
            'os_version' => 'nullable|string',
            'os_architecture' => 'nullable|string',
            'os_license_status' => 'nullable|string',
            'os_partial_key' => 'nullable|string',

            // Software List
            'installed_software' => 'required|array',
        ]);

        // 2. Find or Create Computer record (Synchronous)
        $computer = Computer::updateOrCreate(
            ['hostname' => $request->computer_name],
            [
                'processor' => $request->processor,
                'ram_gb' => $request->ram_gb,
                'disk_total_gb' => $request->disk_total_gb,
                'disk_free_gb' => $request->disk_free_gb,
                'manufacturer' => $request->manufacturer,
                'model' => $request->model,
                'serial_number' => $request->serial_number,
                
                'ip_address' => $request->ip_address ?? $request->ip(),
                'mac_address' => $request->mac_address,

                'os_name' => $request->os_name ?? $request->os_info,
                'os_version' => $request->os_version,
                'os_architecture' => $request->os_architecture,
                'os_license_status' => $request->os_license_status,
                'os_partial_key' => $request->os_partial_key,

                'last_seen_at' => now(),
            ]
        );

        // 3. Dispatch heavy software processing to async job
        // We pass the hardware data and software list separately
        ProcessScanResultJob::dispatch(
            $request->only([
                'computer_name', 'processor', 'ram_gb', 'disk_total_gb', 'disk_free_gb',
                'manufacturer', 'model', 'serial_number', 'ip_address', 'mac_address',
                'os_name', 'os_version', 'os_architecture', 'os_license_status', 'os_partial_key'
            ]),
            $request->installed_software
        );

        // 4. Return 202 Accepted (Agent no longer waits for database sync)
        return response()->json([
            'status' => 'received',
            'message' => 'Data scan hardware & software sedang diproses',
            'computer' => $computer->hostname
        ], 202);
    }
}