<?php

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->labA = Laboratory::factory()->create(['name' => 'Lab Komputer A']);
    $this->labB = Laboratory::factory()->create(['name' => 'Lab Komputer B']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole('pimpinan');

    $this->kepalaLabA = User::factory()->create(['laboratory_id' => $this->labA->id]);
    $this->kepalaLabA->assignRole('kepala_lab');

    $this->computerA = Computer::factory()->create(['laboratory_id' => $this->labA->id, 'hostname' => 'PC-A-01']);
    $this->computerB = Computer::factory()->create(['laboratory_id' => $this->labB->id, 'hostname' => 'PC-B-01']);

    $this->sessionA = ScanSession::factory()->create([
        'computer_id' => $this->computerA->id,
        'status' => 'completed',
        'trigger' => 'scheduled',
        'started_at' => now()->subHours(2),
        'completed_at' => now()->subHours(2)->addMinutes(3),
    ]);

    $this->sessionB = ScanSession::factory()->create([
        'computer_id' => $this->computerB->id,
        'status' => 'completed',
        'trigger' => 'manual',
        'started_at' => now()->subHours(1),
        'completed_at' => now()->subHours(1)->addMinutes(3),
    ]);
});

test('unauthenticated users are redirected to login for monitoring routes', function () {
    $this->get(route('monitoring.index'))->assertRedirect(route('login'));
    $this->get(route('monitoring.show', $this->sessionA))->assertRedirect(route('login'));
    $this->get(route('monitoring.changes'))->assertRedirect(route('login'));
    $this->get(route('monitoring.compliance'))->assertRedirect(route('login'));
});

test('admin can view all monitoring pages', function () {
    $this->actingAs($this->admin)
        ->get(route('monitoring.index'))
        ->assertOk()
        ->assertSee('PC-A-01')
        ->assertSee('PC-B-01');

    $this->actingAs($this->admin)
        ->get(route('monitoring.show', $this->sessionA))
        ->assertOk();

    $this->actingAs($this->admin)
        ->get(route('monitoring.changes'))
        ->assertOk();

    $this->actingAs($this->admin)
        ->get(route('monitoring.compliance'))
        ->assertOk();
});

test('pimpinan can view all monitoring pages as read-only', function () {
    $this->actingAs($this->pimpinan)
        ->get(route('monitoring.index'))
        ->assertOk()
        ->assertSee('PC-A-01')
        ->assertSee('PC-B-01');

    $this->actingAs($this->pimpinan)
        ->get(route('monitoring.show', $this->sessionA))
        ->assertOk();

    $this->actingAs($this->pimpinan)
        ->get(route('monitoring.changes'))
        ->assertOk();

    $this->actingAs($this->pimpinan)
        ->get(route('monitoring.compliance'))
        ->assertOk();
});

test('kepala_lab only sees scan sessions of their own laboratory in index', function () {
    $response = $this->actingAs($this->kepalaLabA)
        ->get(route('monitoring.index'));

    $response->assertOk();
    $response->assertSee('PC-A-01');
    $response->assertDontSee('PC-B-01');
});

test('kepala_lab can view scan session details for computer in their laboratory', function () {
    $this->actingAs($this->kepalaLabA)
        ->get(route('monitoring.show', $this->sessionA))
        ->assertOk();
});

test('kepala_lab receives 403 forbidden when accessing scan session of another laboratory', function () {
    $this->actingAs($this->kepalaLabA)
        ->get(route('monitoring.show', $this->sessionB))
        ->assertForbidden();
});

test('monitoring index supports filtering by status, trigger, and laboratory', function () {
    ScanSession::factory()->create([
        'computer_id' => $this->computerA->id,
        'status' => 'failed',
        'trigger' => 'on_demand',
        'started_at' => now(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('monitoring.index', ['status' => 'failed']))
        ->assertOk()
        ->assertSee('failed');

    $this->actingAs($this->admin)
        ->get(route('monitoring.index', ['trigger' => 'on_demand']))
        ->assertOk();
});

test('monitoring changes scopes changes to kepala_lab laboratory', function () {
    // Create second scan sessions with newly added software
    $sessionA2 = ScanSession::factory()->create([
        'computer_id' => $this->computerA->id,
        'status' => 'completed',
        'started_at' => now()->subMinutes(30),
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $sessionA2->id,
        'raw_name' => 'Software Lab A',
        'version' => '1.0',
    ]);

    $sessionB2 = ScanSession::factory()->create([
        'computer_id' => $this->computerB->id,
        'status' => 'completed',
        'started_at' => now()->subMinutes(20),
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $sessionB2->id,
        'raw_name' => 'Software Lab B',
        'version' => '1.0',
    ]);

    $response = $this->actingAs($this->kepalaLabA)
        ->get(route('monitoring.changes'));

    $response->assertOk();
    $response->assertSee('Software Lab A');
    $response->assertDontSee('Software Lab B');
});

test('monitoring compliance displays compliance snapshots and scopes to kepala_lab', function () {
    ComplianceSnapshot::create([
        'scan_session_id' => $this->sessionA->id,
        'computer_id' => $this->computerA->id,
        'software_name' => 'App Lab A',
        'software_version' => '1.0',
        'status' => 'compliant',
        'keterangan' => 'Valid',
        'scanned_at' => now(),
    ]);

    ComplianceSnapshot::create([
        'scan_session_id' => $this->sessionB->id,
        'computer_id' => $this->computerB->id,
        'software_name' => 'App Lab B',
        'software_version' => '1.0',
        'status' => 'unlicensed',
        'keterangan' => 'No key',
        'scanned_at' => now(),
    ]);

    $response = $this->actingAs($this->kepalaLabA)
        ->get(route('monitoring.compliance'));

    $response->assertOk();
    $response->assertSee('App Lab A');
    $response->assertDontSee('App Lab B');
});
