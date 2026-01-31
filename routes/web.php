<?php

use App\Http\Controllers\ComplianceDataController;
use App\Http\Controllers\ComputerDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LicenseDataController;
use App\Http\Controllers\SoftwareDataController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard', );
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/computers', [ComputerDataController::class, 'index'])->name('computers');
Route::put('/computers/{computer}', [ComputerDataController::class, 'update'])->name('computers.update');
Route::get('/softwares', [SoftwareDataController::class, 'index'])->name('softwares');
Route::get('/licenses', [LicenseDataController::class, 'index'])->name('licenses');
Route::get('/compliance', [ComplianceDataController::class, 'index'])->name('compliance');