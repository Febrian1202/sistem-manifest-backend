<?php

use App\Http\Controllers\ComplianceDataController;
use App\Http\Controllers\ComputerDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenseDataController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SoftwareDataController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard', );
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/computers', [ComputerDataController::class, 'index'])->name('computers');
Route::put('/computers/{computer}', [ComputerDataController::class, 'update'])->name('computers.update');
Route::post('/computers/{computer}/request-scan', [ComputerDataController::class, 'requestScan'])->name('computers.request-scan');

Route::get('/softwares', [SoftwareDataController::class, 'index'])->name('softwares');
Route::put('softwares/{software}', [SoftwareDataController::class, 'update'])->name('softwares.update');

Route::get('/licenses', [LicenseDataController::class, 'index'])->name('licenses');
Route::post('/licenses', [LicenseDataController::class, 'store'])->name('licenses.store');
Route::put('/licenses/{license}', [LicenseDataController::class, 'update'])->name('licenses.update');
Route::delete('/licenses/{license}', [LicenseDataController::class, 'destroy'])->name('licenses.destroy');

Route::get('/compliance', [ComplianceDataController::class, 'index'])->name('compliance');

Route::get('/reports', [ReportController::class, 'index'])->name('reports');
Route::post('/reports/export', [ReportController::class, 'export'])->name('reports.export');