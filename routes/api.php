<?php

use App\Http\Controllers\Api\AgentCommandController;
use App\Http\Controllers\Api\AgentRegisterController;
use App\Http\Controllers\Api\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['status' => 'success']);
});

// 1. Public Registration Route (Entry point for agents)
Route::post('/agent/register', [AgentRegisterController::class, 'register'])->middleware('throttle:5,1');

// 2. Protected Agent Routes
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/scan-result', [ScanController::class, 'store']);
    Route::get('/agent/scan-command', [AgentCommandController::class, 'index']);
});
