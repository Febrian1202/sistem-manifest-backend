<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgentRegisterController extends Controller
{
    /**
     * Register or Re-register a computer agent and issue a Sanctum token.
     */
    public function register(Request $request)
    {
        // 1. Security: Check AGENT_REGISTRATION_KEY from header FIRST
        $registrationKey = config('app.agent_registration_key');
        if ($request->header('X-Agent-Key') !== $registrationKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid Registration Key',
            ], 401);
        }

        // 2. Pre-normalize input payload
        $this->normalizeRegistrationPayload($request);

        // 3. Validation
        // Using a standard MAC address regex: 6 pairs of hex digits separated by : or -
        $validator = Validator::make($request->all(), [
            'mac_address' => ['required', 'string', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'hostname' => 'required|string',
            'serial_number' => 'nullable|string',
            'laboratory_id' => 'required|exists:laboratories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 2. Find or Create Computer record by mac_address
        // On re-registration, laboratory_id is locked to prevent unauthorized lab switching by client.
        $computer = Computer::where('mac_address', $request->mac_address)->first();

        if ($computer) {
            $computer->update([
                'hostname' => $request->hostname,
                'serial_number' => $request->serial_number,
                'last_seen_at' => now(),
            ]);
        } else {
            $computer = Computer::create([
                'mac_address' => $request->mac_address,
                'hostname' => $request->hostname,
                'serial_number' => $request->serial_number,
                'laboratory_id' => $request->laboratory_id,
                'last_seen_at' => now(),
            ]);
        }

        // 3. Revoke all existing tokens for this device
        // This ensures re-registration invalidates old compromised/replaced tokens.
        $computer->tokens()->delete();

        // 4. Create new token with 'scan:submit' ability
        $token = $computer->createToken('agent', ['scan:submit'])->plainTextToken;

        // 5. Return success response
        return response()->json([
            'status' => 'registered',
            'message' => 'Computer registered successfully',
            'computer_id' => $computer->id,
            'hostname' => $computer->hostname,
            'laboratory_id' => $computer->laboratory_id,
            'token' => $token,
        ], 201);
    }

    /**
     * Pre-normalize registration payload to handle diverse MAC formats and string quirks safely.
     */
    protected function normalizeRegistrationPayload(Request $request): void
    {
        $updates = [];

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
            // Normalize dashes to colons
            $cleanMac = str_replace('-', ':', $cleanMac);

            $updates['mac_address'] = $cleanMac;
        }

        $rawHostname = $request->input('hostname');
        if ($rawHostname !== null) {
            $hostname = trim((string) $rawHostname);
            if ($hostname !== '') {
                $updates['hostname'] = substr($hostname, 0, 255);
            }
        }

        $rawSerial = $request->input('serial_number');
        if ($rawSerial !== null) {
            $updates['serial_number'] = substr(trim((string) $rawSerial), 0, 255);
        }

        if (! empty($updates)) {
            $request->merge($updates);
        }
    }
}
