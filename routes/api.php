<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ScanController;

Route::get('/ping', function () {
    return response()->json(['status' => 'success']);
});

Route::post('/scan-result', [ScanController::class, 'store']);