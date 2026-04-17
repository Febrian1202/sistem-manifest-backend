<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AgentCommandController extends Controller
{
    /**
     * Check if a scan is requested for the authenticated computer.
     * GET /api/agent/scan-command
     */
    public function index(Request $request)
    {
        // Get the authenticated computer via Sanctum
        $computer = $request->user();

        return response()->json([
            'should_scan' => (bool) $computer->scan_requested,
        ]);
    }
}
