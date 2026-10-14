<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessScanResultJob;
use App\Models\Computer;
use App\Models\ScanSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ScanController extends Controller
{
    /**
     * Store scan results from agent.
     * Identity is tied to the Sanctum token (auth()->user()).
     */
    public function store(Request $request)
    {
        // 1. Check Token Ability
        if (! $request->user()->tokenCan('scan:submit')) {
            return response()->json(['message' => 'Token does not have scan submission abilities.'], 403);
        }

        // 2. Reject retired computers
        if ($request->user()->status === 'retired') {
            return response()->json([
                'status' => 'error',
                'message' => 'Komputer dalam status retired tidak dapat mengirimkan hasil scan.',
            ], 403);
        }

        // 3. Pre-normalize input payload (robust against Windows registry quirks)
        $this->normalizeScanPayload($request);

        // 4. Validate Input
        $validator = Validator::make($request->all(), [
            'scan_uuid' => 'nullable|uuid',
            'client_started_at' => 'nullable|date',
            'client_completed_at' => 'nullable|date',
            'agent_version' => 'nullable|string|max:50',
            'scan_mode' => 'nullable|string|in:scheduled,manual,on_demand',
            'hostname' => 'required|string|max:255',
            'processor' => 'nullable|string|max:255',
            'ram_gb' => 'nullable|integer|min:0',
            'disk_total_gb' => 'nullable|integer|min:0',
            'disk_free_gb' => 'nullable|integer|min:0',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'ip_address' => 'nullable|string|max:45',
            'mac_address' => 'nullable|string|max:17|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'os_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:255',
            'os_architecture' => 'nullable|string|max:50',
            'os_license_status' => 'nullable|string|max:50',
            'os_partial_key' => 'nullable|string|max:255',
            'installed_software' => 'nullable|array',
            'installed_software.*.name' => 'required|string',
            'installed_software.*.version' => 'nullable|string',
            'installed_software.*.vendor' => 'nullable|string',
            'installed_software.*.install_date' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Scan submission validation failed for computer ['.$request->user()->hostname.']: ', [
                'computer_id' => $request->user()->id,
                'ip' => $request->ip(),
                'errors' => $validator->errors()->toArray(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Data scan tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 3. Check Idempotency via scan_uuid
        $scanUuid = $request->input('scan_uuid') ?: (string) Str::uuid();

        $existingSession = ScanSession::where('scan_uuid', $scanUuid)->first();
        if ($existingSession) {
            return response()->json([
                'status' => 'already_processed',
                'message' => 'Scan already processed',
                'scan_session_id' => $existingSession->id,
            ], 200);
        }

        // 4. Update Authenticated Computer record (Identity comes from Token)
        $computer = $request->user();
        $trigger = $request->input('scan_mode') ?? ($computer->scan_requested ? 'on_demand' : 'scheduled');

        $computer->update([
            'hostname' => $request->hostname,
            'processor' => $request->processor,
            'ram_gb' => $request->ram_gb,
            'disk_total_gb' => $request->disk_total_gb,
            'disk_free_gb' => $request->disk_free_gb,
            'manufacturer' => $request->manufacturer,
            'model' => $request->model,
            'serial_number' => $request->serial_number,

            'ip_address' => $request->ip_address ?? $request->ip(),
            'mac_address' => $request->mac_address,

            'os_name' => $request->os_name,
            'os_version' => $request->os_version,
            'os_architecture' => $request->os_architecture,
            'os_license_status' => $request->os_license_status,
            'os_partial_key' => $request->os_partial_key,

            'last_seen_at' => now(),
            'scan_requested' => false,
        ]);

        // 5. Create ScanSession
        $startedAt = $request->input('client_started_at')
            ? Carbon::parse($request->input('client_started_at'))
            : now();

        $scanSession = ScanSession::create([
            'computer_id' => $computer->id,
            'scan_uuid' => $scanUuid,
            'started_at' => $startedAt,
            'status' => 'pending',
            'trigger' => $trigger,
            'agent_version' => $request->input('agent_version'),
        ]);

        // 6. Dispatch software processing job
        $installedSoftware = $request->input('installed_software', []);

        ProcessScanResultJob::dispatch(
            $scanSession,
            $installedSoftware
        );

        return response()->json([
            'status' => 'received',
            'message' => 'Data scan hardware & software sedang diproses',
            'computer' => $computer->hostname,
            'scan_session_id' => $scanSession->id,
        ], 202);
    }

    /**
     * Pre-normalize scan payload to handle Windows registry and WMI quirks safely.
     */
    protected function normalizeScanPayload(Request $request): void
    {
        $updates = [];

        // 1. Processor (if CIM returned array from multi-socket CPU)
        if (is_array($request->input('processor'))) {
            $processors = array_filter(array_map('strval', $request->input('processor')));
            $updates['processor'] = ! empty($processors) ? substr(reset($processors), 0, 255) : null;
        }

        // 2. IP address (if array)
        if (is_array($request->input('ip_address'))) {
            $ips = array_filter(array_map('strval', $request->input('ip_address')));
            $updates['ip_address'] = ! empty($ips) ? substr(reset($ips), 0, 45) : null;
        }

        // 3. Installed Software sanitization
        $rawSoftware = $request->input('installed_software');
        if (is_array($rawSoftware)) {
            $sanitized = [];
            foreach ($rawSoftware as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $name = isset($item['name']) ? trim((string) $item['name']) : '';
                if ($name === '') {
                    continue; // Prune blank/empty entries safely
                }

                $version = $item['version'] ?? null;
                if (is_array($version)) {
                    // System.Version object serialization: {"Major": 1, "Minor": 2, ...}
                    $versionParts = array_filter([
                        $version['Major'] ?? null,
                        $version['Minor'] ?? null,
                        $version['Build'] ?? null,
                        $version['Revision'] ?? null,
                    ], fn ($v) => $v !== null && (int) $v >= 0);
                    $version = ! empty($versionParts) ? implode('.', $versionParts) : null;
                } elseif ($version !== null) {
                    $version = trim((string) $version);
                }

                $vendor = isset($item['vendor']) && $item['vendor'] !== null ? trim((string) $item['vendor']) : null;
                $installDate = isset($item['install_date']) && $item['install_date'] !== null ? trim((string) $item['install_date']) : null;

                $entry = [
                    'name' => $name,
                ];

                if ($version !== null && $version !== '') {
                    $entry['version'] = $version;
                } elseif (array_key_exists('version', $item)) {
                    $entry['version'] = null;
                }

                if ($vendor !== null && $vendor !== '') {
                    $entry['vendor'] = $vendor;
                } elseif (array_key_exists('vendor', $item)) {
                    $entry['vendor'] = null;
                }

                if ($installDate !== null && $installDate !== '') {
                    $entry['install_date'] = $installDate;
                } elseif (array_key_exists('install_date', $item)) {
                    $entry['install_date'] = null;
                }

                $sanitized[] = $entry;
            }
            $updates['installed_software'] = $sanitized;
        }

        if (! empty($updates)) {
            $request->merge($updates);
        }
    }
}
