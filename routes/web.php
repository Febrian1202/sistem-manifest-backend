<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AgentDownloadController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComplianceDataController;
use App\Http\Controllers\ComputerDataController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\LabInventoryController;
use App\Http\Controllers\LaboratoryController;
use App\Http\Controllers\LicenseAllocationController;
use App\Http\Controllers\LicenseDataController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ReportApprovalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportSubmissionController;
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
    Route::put('/account/password', [AccountController::class, 'changePassword'])->name('account.change-password');
});

Route::middleware(['auth', 'role:admin|pimpinan|kepala_lab|staff_lab'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');
});

Route::middleware(['auth', 'role:admin|pimpinan|kepala_lab'])->group(function () {
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/changes', [MonitoringController::class, 'changes'])->name('monitoring.changes');
    Route::get('/monitoring/compliance', [MonitoringController::class, 'compliance'])->name('monitoring.compliance');
    Route::get('/monitoring/{scanSession}', [MonitoringController::class, 'show'])->name('monitoring.show');
    Route::get('/computers/{computer}/history', [ComputerDataController::class, 'history'])->name('computers.history');
});

Route::middleware(['auth', 'role:admin|pimpinan|kepala_lab'])->group(function () {
    Route::get('/compliance', [ComplianceDataController::class, 'index'])->name('compliance');
});

Route::middleware(['auth', 'role:admin|pimpinan'])->group(function () {
    // Shared Read-only access
    Route::get('/computers', [ComputerDataController::class, 'index'])->name('computers');
    Route::get('/computers/{computer}', [ComputerDataController::class, 'show'])->name('computers.show');
    Route::get('/softwares', [SoftwareDataController::class, 'index'])->name('softwares');
    Route::get('/licenses', [LicenseDataController::class, 'index'])->name('licenses');
    Route::get('/licenses/{license}', [LicenseDataController::class, 'show'])->name('licenses.show');
});

Route::middleware(['auth', 'role:admin|pimpinan|kepala_lab'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');

    // Detailed reports & exports
    Route::prefix('reports')->name('reports.')->group(function () {
        // Preview pages
        Route::get('/eksekutif', [ReportController::class, 'showEksekutif'])->name('eksekutif');
        Route::get('/komputer', [ReportController::class, 'showKomputer'])->name('komputer');
        Route::get('/software', [ReportController::class, 'showSoftware'])->name('software');
        Route::get('/kepatuhan', [ReportController::class, 'showKepatuhan'])->name('kepatuhan');
        Route::get('/lisensi', [ReportController::class, 'showLisensi'])->name('lisensi');
        Route::get('/monitoring', [ReportController::class, 'showMonitoring'])->name('monitoring');
        Route::get('/perubahan', [ReportController::class, 'showPerubahan'])->name('perubahan');
        Route::get('/kebutuhan-lisensi', [ReportController::class, 'showKebutuhanLisensi'])
            ->middleware('role:admin|pimpinan')
            ->name('kebutuhan-lisensi');

        // Export endpoints
        Route::get('/eksekutif/export', [ReportController::class, 'exportEksekutif'])->name('eksekutif.export');
        Route::get('/komputer/export', [ReportController::class, 'exportKomputer'])->name('komputer.export');
        Route::get('/software/export', [ReportController::class, 'exportSoftware'])->name('software.export');
        Route::get('/kepatuhan/export', [ReportController::class, 'exportKepatuhan'])->name('kepatuhan.export');
        Route::get('/lisensi/export', [ReportController::class, 'exportLisensi'])->name('lisensi.export');
        Route::get('/monitoring/export', [ReportController::class, 'exportMonitoring'])->name('monitoring.export');
        Route::get('/perubahan/export', [ReportController::class, 'exportPerubahan'])->name('perubahan.export');
        Route::get('/kebutuhan-lisensi/export', [ReportController::class, 'exportKebutuhanLisensi'])
            ->middleware('role:admin|pimpinan')
            ->name('kebutuhan-lisensi.export');

        Route::post('/kepatuhan/scan', [ReportController::class, 'runComplianceScan'])
            ->middleware('role:admin')
            ->name('kepatuhan.scan');
    });
});

Route::middleware(['auth', 'role:admin|kepala_lab|staff_lab'])->group(function () {
    Route::get('/agent/download', [AgentDownloadController::class, 'showDownloadPage'])->name('agent.download-page');
    Route::post('/agent/download', [AgentDownloadController::class, 'download'])->name('agent.download');
});

Route::middleware(['auth', 'role:admin|pimpinan'])->group(function () {
    // Admin-only Mutations
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/computers/request-scan-all', [ComputerDataController::class, 'requestScanAll'])->name('computers.request-scan-all');
        Route::put('/computers/{computer}', [ComputerDataController::class, 'update'])->name('computers.update');
        Route::delete('/computers/{computer}', [ComputerDataController::class, 'destroy'])->name('computers.destroy');
        Route::post('/computers/{computer}/request-scan', [ComputerDataController::class, 'requestScan'])->name('computers.request-scan');

        Route::put('softwares/{software}', [SoftwareDataController::class, 'update'])->name('softwares.update');

        Route::post('/licenses', [LicenseDataController::class, 'store'])->name('licenses.store');
        Route::post('/licenses/{license}/key', [LicenseDataController::class, 'getKey'])->name('licenses.key')->middleware('throttle:10,1');
        Route::put('/licenses/{license}', [LicenseDataController::class, 'update'])->name('licenses.update');
        Route::delete('/licenses/{license}', [LicenseDataController::class, 'destroy'])->name('licenses.destroy');

        // Manajemen Akun
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::put('/accounts/{user}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');
        Route::put('/accounts/{user}/reset-password', [AccountController::class, 'resetPassword'])->name('accounts.reset-password');

        // Manajemen Fakultas
        Route::resource('faculties', FacultyController::class);

        // Manajemen Laboratorium
        Route::resource('laboratories', LaboratoryController::class);

        // Alokasi Lisensi
        Route::resource('license-allocations', LicenseAllocationController::class)
            ->parameters(['license-allocations' => 'license_allocation']);

        // Kirim Laporan ke PJ Lab
        Route::get('/reports/submit-to-lab', [ReportSubmissionController::class, 'index'])->name('report-submissions.index');
        Route::post('/reports/submit-to-lab', [ReportSubmissionController::class, 'submit'])->name('report-submissions.submit');

        // Activity Log
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs');
    });
});

Route::middleware(['auth', 'role:kepala_lab|staff_lab'])->group(function () {
    // Inventaris Lab
    Route::get('/lab/inventory', [LabInventoryController::class, 'index'])->name('lab.inventory.index');
    Route::get('/lab/inventory/{computer}', [LabInventoryController::class, 'show'])->name('lab.inventory.show');
});

Route::middleware(['auth', 'role:kepala_lab|admin'])->group(function () {
    // Review Laporan
    Route::get('/lab/reports', [ReportApprovalController::class, 'index'])->name('lab.reports.index');
    Route::get('/lab/reports/{reportApproval}', [ReportApprovalController::class, 'show'])->name('lab.reports.show');
    Route::post('/lab/reports/{reportApproval}/approve', [ReportApprovalController::class, 'approve'])->name('lab.reports.approve');
    Route::post('/lab/reports/{reportApproval}/reject', [ReportApprovalController::class, 'reject'])->name('lab.reports.reject');
    Route::get('/lab/reports/{reportApproval}/preview-pdf', [ReportApprovalController::class, 'previewPdf'])->name('lab.reports.preview-pdf');
});
