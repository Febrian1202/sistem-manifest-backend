<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ScanController;
use App\Http\Controllers\Api\AgentRegisterController;

Route::get('/ping', function () {
    return response()->json(['status' => 'success']);
});

// 1. Public Registration Route (Entry point for agents)
Route::post('/agent/register', [AgentRegisterController::class, 'register']);

// 2. Protected Agent Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/scan-result', [ScanController::class, 'store']);
});