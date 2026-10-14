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
     * Pre-normalize scan payload to handle Windows registry, WMI, and driver quirks safely.
     */
    protected function normalizeScanPayload(Request $request): void
    {
        $updates = [];

        // 1. Hostname fallback & max length
        $rawHostname = $request->input('hostname');
        if (empty($rawHostname) || ! is_string($rawHostname)) {
            $updates['hostname'] = substr((string) ($request->user()?->hostname ?: 'UNKNOWN-PC'), 0, 255);
        } else {
            $updates['hostname'] = substr(trim($rawHostname), 0, 255);
        }

        // 2. MAC Address normalization & sanitization
        $rawMac = $request->input('mac_address');
        if ($rawMac !== null) {
            if (is_array($rawMac)) {
                $rawMac = reset($rawMac);
            }
            $cleanMac = strtoupper(trim((string) $rawMac));

            // Format: 001122334455 (12 hex chars) -> 00:11:22:33:44:55
            if (preg_match('/^[0-9A-F]{12}$/', $cleanMac)) {
                $cleanMac = implode(':', str_split($cleanMac, 2));
            }
            // Format: 0011.2233.4455 (Cisco format) -> 00:11:22:33:44:55
            elseif (preg_match('/^([0-9A-F]{4})\.([0-9A-F]{4})\.([0-9A-F]{4})$/', $cleanMac, $m)) {
                $hex = $m[1].$m[2].$m[3];
                $cleanMac = implode(':', str_split($hex, 2));
            }
            // Format: replace dashes with colons
            $cleanMac = str_replace('-', ':', $cleanMac);

            // If it still doesn't match standard 6-octet MAC, fall back safely to user's mac_address or null
            if (! preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $cleanMac)) {
                $cleanMac = $request->user()?->mac_address ?: null;
            }

            $updates['mac_address'] = $cleanMac;
        }

        // 3. Scan UUID validation & fallback
        $rawUuid = $request->input('scan_uuid');
        if (! empty($rawUuid) && ! Str::isUuid($rawUuid)) {
            $updates['scan_uuid'] = (string) Str::uuid();
        }

        // 4. Scan Mode normalization
        $rawScanMode = $request->input('scan_mode');
        if ($rawScanMode !== null) {
            $normalizedMode = strtolower(trim((string) $rawScanMode));
            $allowedModes = ['scheduled', 'manual', 'on_demand'];
            $updates['scan_mode'] = in_array($normalizedMode, $allowedModes, true) ? $normalizedMode : 'scheduled';
        }

        // 5. Timestamps (client_started_at, client_completed_at)
        foreach (['client_started_at', 'client_completed_at'] as $dateField) {
            $rawDate = $request->input($dateField);
            if (! empty($rawDate)) {
                try {
                    $updates[$dateField] = Carbon::parse($rawDate)->toIso8601String();
                } catch (\Throwable) {
                    $updates[$dateField] = now()->toIso8601String();
                }
            }
        }

        // 6. Processor (if CIM returned array from multi-socket CPU or long string)
        $rawProcessor = $request->input('processor');
        if (is_array($rawProcessor)) {
            $processors = array_filter(array_map('strval', $rawProcessor));
            $updates['processor'] = ! empty($processors) ? substr(trim(reset($processors)), 0, 255) : null;
        } elseif ($rawProcessor !== null) {
            $updates['processor'] = substr(trim((string) $rawProcessor), 0, 255);
        }

        // 7. IP address (if array or long string)
        $rawIp = $request->input('ip_address');
        if (is_array($rawIp)) {
            $ips = array_filter(array_map('strval', $rawIp));
            $updates['ip_address'] = ! empty($ips) ? substr(trim(reset($ips)), 0, 45) : null;
        } elseif ($rawIp !== null) {
            $updates['ip_address'] = substr(trim((string) $rawIp), 0, 45);
        }

        // 8. String fields max length truncations
        $stringLimits = [
            'manufacturer' => 255,
            'model' => 255,
            'serial_number' => 255,
            'os_name' => 255,
            'os_version' => 255,
            'os_architecture' => 50,
            'os_license_status' => 50,
            'os_partial_key' => 255,
            'agent_version' => 50,
        ];
        foreach ($stringLimits as $field => $limit) {
            $val = $request->input($field);
            if ($val !== null && is_string($val)) {
                $updates[$field] = substr(trim($val), 0, $limit);
            }
        }

        // 9. Numeric integers (ram_gb, disk_total_gb, disk_free_gb)
        foreach (['ram_gb', 'disk_total_gb', 'disk_free_gb'] as $numField) {
            $val = $request->input($numField);
            if ($val !== null) {
                $updates[$numField] = is_numeric($val) ? max(0, (int) round((float) $val)) : 0;
            }
        }

        // 10. Installed Software sanitization
        $rawSoftware = $request->input('installed_software');
        if ($rawSoftware !== null) {
            // If sent as a single dictionary/object instead of a list (e.g. from single-element PowerShell array)
            if (is_array($rawSoftware) && isset($rawSoftware['name'])) {
                $rawSoftware = [$rawSoftware];
            }

            if (is_array($rawSoftware)) {
                $sanitized = [];
                foreach ($rawSoftware as $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    // Extract name safely
                    $rawItemName = $item['name'] ?? null;
                    if (is_array($rawItemName)) {
                        $rawItemName = reset($rawItemName);
                    }
                    $name = $rawItemName !== null ? trim((string) $rawItemName) : '';
                    if ($name === '') {
                        continue; // Prune blank/empty entries safely
                    }

                    // Extract version safely
                    $version = $item['version'] ?? null;
                    if (is_array($version)) {
                        // System.Version object serialization: {"Major": 1, "Minor": 2, ...}
                        $versionParts = array_filter([
                            $version['Major'] ?? null,
                            $version['Minor'] ?? null,
                            $version['Build'] ?? null,
                            $version['Revision'] ?? null,
                        ], fn ($v) => $v !== null && (int) $v >= 0);
                        $version = ! empty($versionParts) ? implode('.', $versionParts) : (reset($version) ? (string) reset($version) : null);
                    } elseif ($version !== null) {
                        $version = trim((string) $version);
                    }

                    // Extract vendor safely
                    $vendor = $item['vendor'] ?? null;
                    if (is_array($vendor)) {
                        $vendor = reset($vendor);
                    }
                    $vendor = $vendor !== null ? trim((string) $vendor) : null;

                    // Extract install_date safely
                    $installDate = $item['install_date'] ?? null;
                    if (is_array($installDate)) {
                        $installDate = reset($installDate);
                    }
                    $installDate = $installDate !== null ? trim((string) $installDate) : null;

                    $entry = [
                        'name' => substr($name, 0, 255),
                    ];

                    if ($version !== null && $version !== '') {
                        $entry['version'] = substr($version, 0, 255);
                    } elseif (array_key_exists('version', $item)) {
                        $entry['version'] = null;
                    }

                    if ($vendor !== null && $vendor !== '') {
                        $entry['vendor'] = substr($vendor, 0, 255);
                    } elseif (array_key_exists('vendor', $item)) {
                        $entry['vendor'] = null;
                    }

                    if ($installDate !== null && $installDate !== '') {
                        $entry['install_date'] = substr($installDate, 0, 255);
                    } elseif (array_key_exists('install_date', $item)) {
                        $entry['install_date'] = null;
                    }

                    $sanitized[] = $entry;
                }
                $updates['installed_software'] = array_values($sanitized);
            }
        }

        if (! empty($updates)) {
            $request->merge($updates);
        }
    }
}
