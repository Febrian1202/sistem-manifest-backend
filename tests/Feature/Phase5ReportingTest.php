<?php

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->lab1 = Laboratory::factory()->create(['name' => 'Lab RPL', 'code' => 'LAB-RPL']);
    $this->lab2 = Laboratory::factory()->create(['name' => 'Lab Multimedia', 'code' => 'LAB-MM']);

    $this->kepalaLab1 = User::factory()->create(['laboratory_id' => $this->lab1->id]);
    $this->kepalaLab1->assignRole('kepala_lab');

    $this->comp1 = Computer::factory()->create(['laboratory_id' => $this->lab1->id, 'hostname' => 'RPL-PC01']);
    $this->comp2 = Computer::factory()->create(['laboratory_id' => $this->lab2->id, 'hostname' => 'MM-PC01']);
});

test('admin can view monitoring recap report with statistics', function () {
    // Create scan sessions
    ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(2),
    ]);
    ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'failed',
        'started_at' => now()->startOfMonth()->addDays(3),
    ]);
    ScanSession::factory()->create([
        'computer_id' => $this->comp2->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(4),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.monitoring', [
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertStatus(200);
    $response->assertViewIs('reports.monitoring');
    $response->assertViewHas('summary', function ($summary) {
        return $summary['total_scans'] === 3
            && $summary['successful_scans'] === 2
            && $summary['failed_scans'] === 1;
    });
    $response->assertSee('RPL-PC01');
    $response->assertSee('MM-PC01');
    $response->assertSee('Lab RPL');
    $response->assertSee('Lab Multimedia');
});

test('kepala_lab only sees own lab in monitoring recap report', function () {
    ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(2),
    ]);
    ScanSession::factory()->create([
        'computer_id' => $this->comp2->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(4),
    ]);

    $response = $this->actingAs($this->kepalaLab1)->get(route('reports.monitoring'));

    $response->assertStatus(200);
    $response->assertSee('RPL-PC01');
    $response->assertDontSee('MM-PC01');
});

test('monitoring recap export works for PDF and Excel', function () {
    ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(1),
    ]);

    $pdfResponse = $this->actingAs($this->admin)->get(route('reports.monitoring.export', [
        'format' => 'pdf',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));
    $pdfResponse->assertStatus(200);
    $pdfResponse->assertHeader('Content-Type', 'application/pdf');

    $excelResponse = $this->actingAs($this->admin)->get(route('reports.monitoring.export', [
        'format' => 'excel',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));
    $excelResponse->assertStatus(200);
    expect($excelResponse->headers->get('content-disposition'))->toContain('.xlsx');
});

test('admin can view software changes report', function () {
    $session1 = ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(1),
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $session1->id,
        'raw_name' => 'Visual Studio Code',
        'version' => '1.85.0',
        'vendor' => 'Microsoft',
    ]);

    $session2 = ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(2),
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $session2->id,
        'raw_name' => 'Visual Studio Code',
        'version' => '1.86.0',
        'vendor' => 'Microsoft',
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $session2->id,
        'raw_name' => 'Git',
        'version' => '2.43.0',
        'vendor' => 'Git Community',
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.perubahan', [
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertStatus(200);
    $response->assertViewIs('reports.perubahan');
    $response->assertSee('Visual Studio Code');
    $response->assertSee('Git');
    $response->assertViewHas('summary', function ($summary) {
        return $summary['added'] >= 1 || $summary['version_changed'] >= 1;
    });
});

test('software changes export works for PDF and Excel', function () {
    $session = ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(1),
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $session->id,
        'raw_name' => '7-Zip',
        'version' => '23.01',
    ]);

    $pdfResponse = $this->actingAs($this->admin)->get(route('reports.perubahan.export', [
        'format' => 'pdf',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));
    $pdfResponse->assertStatus(200);
    $pdfResponse->assertHeader('Content-Type', 'application/pdf');

    $excelResponse = $this->actingAs($this->admin)->get(route('reports.perubahan.export', [
        'format' => 'excel',
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));
    $excelResponse->assertStatus(200);
    expect($excelResponse->headers->get('content-disposition'))->toContain('.xlsx');
});

test('software report uses historical scan results when present', function () {
    $catalog = SoftwareCatalog::create([
        'raw_name' => 'Node.js',
        'normalized_name' => 'Node.js LTS',
        'vendor' => 'OpenJS Foundation',
        'category' => 'Open Source',
    ]);

    $session = ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(2),
    ]);

    ScanSoftwareResult::create([
        'scan_session_id' => $session->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Node.js',
        'version' => '20.11.0',
        'vendor' => 'OpenJS Foundation',
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.software', [
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertStatus(200);
    $response->assertSee('Node.js LTS');
});

test('compliance report uses historical snapshots when present', function () {
    $catalog = SoftwareCatalog::create([
        'raw_name' => 'KMSPico',
        'normalized_name' => 'KMSPico',
        'category' => 'Commercial',
    ]);

    $session = ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(3),
    ]);

    ComplianceSnapshot::create([
        'scan_session_id' => $session->id,
        'computer_id' => $this->comp1->id,
        'software_catalog_id' => $catalog->id,
        'software_name' => 'KMSPico',
        'software_version' => '10.2.0',
        'status' => 'Tidak Berlisensi',
        'keterangan' => 'Software terlarang terdeteksi',
        'detected_at' => now(),
        'scanned_at' => now()->startOfMonth()->addDays(3),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.kepatuhan', [
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertStatus(200);
    $response->assertSee('KMSPico');
    $response->assertSee('Tidak Berlisensi');
});

test('executive report includes monitoring summary', function () {
    ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(1),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.eksekutif', [
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertStatus(200);
    $response->assertViewHas('monitoringSummary', function ($monitoring) {
        return $monitoring['total_scans'] === 1 && $monitoring['successful_scans'] === 1;
    });
    $response->assertSee('Aktivitas Monitoring Berkala Periode Ini');
});

test('computer report includes total scans count', function () {
    ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(1),
    ]);
    ScanSession::factory()->create([
        'computer_id' => $this->comp1->id,
        'status' => 'completed',
        'started_at' => now()->startOfMonth()->addDays(2),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.komputer', [
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->endOfMonth()->toDateString(),
    ]));

    $response->assertStatus(200);
    $response->assertSee('Total Scan');
});

test('report submission saves period_start and period_end', function () {
    $response = $this->actingAs($this->admin)->post(route('report-submissions.submit'), [
        'laboratory_id' => $this->lab1->id,
        'period' => '2026-10',
        'period_start' => '2026-10-01',
        'period_end' => '2026-10-31',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertSessionHas('status', 'success');

    $approval = ReportApproval::where('laboratory_id', $this->lab1->id)
        ->where('period', '2026-10')
        ->first();

    expect($approval)->not->toBeNull()
        ->and($approval->period_start->format('Y-m-d'))->toBe('2026-10-01')
        ->and($approval->period_end->format('Y-m-d'))->toBe('2026-10-31');
});

test('admin can view and approve report approval across any lab', function () {
    $approval = ReportApproval::factory()->create([
        'laboratory_id' => $this->lab1->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->admin)->get(route('lab.reports.index'));
    $response->assertStatus(200);

    $approveResponse = $this->actingAs($this->admin)->post(route('lab.reports.approve', $approval), [
        'notes' => 'Approved by Admin',
    ]);

    $approveResponse->assertSessionHasNoErrors();
    expect($approval->fresh()->status)->toBe('approved');
});

test('pusat laporan page displays cards for new monitoring and perubahan reports', function () {
    $response = $this->actingAs($this->admin)->get(route('reports'));

    $response->assertStatus(200);
    $response->assertSee('Rekap Monitoring Berkala');
    $response->assertSee('Rekap Perubahan Software');
});
