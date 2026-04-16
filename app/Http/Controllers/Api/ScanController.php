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

        // 2. Validate Input
        $request->validate([
            'hostname'           => 'required|string|max:255',
            'processor'          => 'nullable|string|max:255',
            'ram_gb'             => 'nullable|integer|min:0',
            'disk_total_gb'      => 'nullable|integer|min:0',
            'disk_free_gb'       => 'nullable|integer|min:0',
            'manufacturer'       => 'nullable|string|max:255',
            'model'              => 'nullable|string|max:255',
            'serial_number'      => 'nullable|string|max:255',
            'ip_address'         => 'nullable|string|max:45',
            'mac_address'        => 'nullable|string|max:17',
            'os_name'            => 'nullable|string|max:255',
            'os_version'         => 'nullable|string|max:255',
            'os_architecture'    => 'nullable|string|max:50',
            'os_license_status'  => 'nullable|string|max:50',
            'os_partial_key'     => 'nullable|string|max:255',
            'installed_software' => 'nullable|array',
            'installed_software.*.name'    => 'required|string',
            'installed_software.*.version' => 'nullable|string',
            'installed_software.*.vendor'  => 'nullable|string',
            'installed_software.*.install_date' => 'nullable|string',
        ]);

        // 3. Update Authenticated Computer record (Identity comes from Token)
        $computer = $request->user();
        $computer->update([
            'hostname'          => $request->hostname,
            'processor'         => $request->processor,
            'ram_gb'            => $request->ram_gb,
            'disk_total_gb'     => $request->disk_total_gb,
            'disk_free_gb'      => $request->disk_free_gb,
            'manufacturer'      => $request->manufacturer,
            'model'             => $request->model,
            'serial_number'     => $request->serial_number,

            'ip_address'        => $request->ip_address ?? $request->ip(),
            'mac_address'       => $request->mac_address,

            'os_name'           => $request->os_name,
            'os_version'        => $request->os_version,
            'os_architecture'   => $request->os_architecture,
            'os_license_status' => $request->os_license_status,
            'os_partial_key'    => $request->os_partial_key,

            'last_seen_at'      => now(),
        ]);

        // 4. Dispatch heavy software processing to async job
        if ($request->installed_software) {
            ProcessScanResultJob::dispatch(
                $computer,
                $request->installed_software
            );
        }

        return response()->json([
            'status'   => 'received',
            'message'  => 'Data scan hardware & software sedang diproses',
            'computer' => $computer->hostname
        ], 202);
    }
}