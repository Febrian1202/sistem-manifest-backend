<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComplianceDataController;
use App\Http\Controllers\ComputerDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenseDataController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SoftwareDataController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware(['auth', 'role:admin|pimpinan'])->group(function () {
    // Shared Read-only access
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/computers', [ComputerDataController::class, 'index'])->name('computers');
    Route::get('/softwares', [SoftwareDataController::class, 'index'])->name('softwares');
    Route::get('/licenses', [LicenseDataController::class, 'index'])->name('licenses');
    Route::get('/compliance', [ComplianceDataController::class, 'index'])->name('compliance');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::post('/reports/export', [ReportController::class, 'export'])->name('reports.export');

    // Admin-only Mutations
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/computers/request-scan-all', [ComputerDataController::class, 'requestScanAll'])->name('computers.request-scan-all');
        Route::put('/computers/{computer}', [ComputerDataController::class, 'update'])->name('computers.update');
        Route::post('/computers/{computer}/request-scan', [ComputerDataController::class, 'requestScan'])->name('computers.request-scan');
        
        Route::put('softwares/{software}', [SoftwareDataController::class, 'update'])->name('softwares.update');
        
        Route::post('/licenses', [LicenseDataController::class, 'store'])->name('licenses.store');
        Route::put('/licenses/{license}', [LicenseDataController::class, 'update'])->name('licenses.update');
        Route::delete('/licenses/{license}', [LicenseDataController::class, 'destroy'])->name('licenses.destroy');
    });
});
