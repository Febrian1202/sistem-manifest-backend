<?php

namespace App\Http\Controllers\Api;

use App\Models\Computer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class AgentRegisterController extends Controller
{
    /**
     * Register or Re-register a computer agent and issue a Sanctum token.
     */
    public function register(Request $request)
    {
        // 1. Validation
        // Using a standard MAC address regex: 6 pairs of hex digits separated by : or -
        $validator = Validator::make($request->all(), [
            'mac_address' => ['required', 'string', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/'],
            'hostname' => 'required|string',
            'serial_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // 1.5 Security: Check AGENT_REGISTRATION_KEY from header
        $registrationKey = config('app.agent_registration_key');
        if ($request->header('X-Agent-Key') !== $registrationKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: Invalid Registration Key'
            ], 401);
        }

        // 2. Find or Create Computer record by mac_address
        // Per Fix 2 requirements elsewhere, mac_address is the primary identifier
        $computer = Computer::updateOrCreate(
            ['mac_address' => $request->mac_address],
            [
                'hostname' => $request->hostname,
                'serial_number' => $request->serial_number,
                'last_seen_at' => now(),
            ]
        );

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
            'token' => $token,
        ], 201);
    }
}
