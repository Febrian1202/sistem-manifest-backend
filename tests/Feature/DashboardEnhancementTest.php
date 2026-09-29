<?php

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin sees periodic monitoring statistics on dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab1 = Laboratory::factory()->create(['name' => 'Lab RPL']);
    $lab2 = Laboratory::factory()->create(['name' => 'Lab Jaringan']);

    $comp1 = Computer::factory()->create(['laboratory_id' => $lab1->id, 'status' => 'active']);
    $comp2 = Computer::factory()->create(['laboratory_id' => $lab1->id, 'status' => 'active']);
    $comp3 = Computer::factory()->create(['laboratory_id' => $lab2->id, 'status' => 'active']);

    // Comp1 scanned today completed
    $session1 = ScanSession::factory()->create([
        'computer_id' => $comp1->id,
        'status' => 'completed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    // Comp2 scanned today failed
    $session2 = ScanSession::factory()->create([
        'computer_id' => $comp2->id,
        'status' => 'failed',
        'started_at' => now(),
        'completed_at' => now(),
    ]);

    // Catalog & software result
    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'Visual Studio Code']);
    ScanSoftwareResult::create([
        'scan_session_id' => $session1->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Visual Studio Code 1.85',
        'version' => '1.85',
    ]);

    // Compliance snapshot needing review
    ComplianceSnapshot::create([
        'scan_session_id' => $session1->id,
        'computer_id' => $comp1->id,
        'catalog_id' => $catalog->id,
        'status' => 'Perlu Ditinjau',
        'software_name' => 'Visual Studio Code',
        'scanned_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewHas('monitoringStats');
    $stats = $response->viewData('monitoringStats');

    expect($stats['total_labs'])->toBe(2)
        ->and($stats['total_computers'])->toBe(3)
        ->and($stats['scanned_today'])->toBe(1) // Comp1 completed today
        ->and($stats['unscanned_today'])->toBe(2) // Comp2 (only failed) and Comp3
        ->and($stats['scans_completed_today'])->toBe(1)
        ->and($stats['scans_failed_today'])->toBe(1)
        ->and($stats['actionable_findings'])->toBe(1);
});

test('admin can filter dashboard by laboratory and period', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab1 = Laboratory::factory()->create(['name' => 'Lab RPL']);
    $lab2 = Laboratory::factory()->create(['name' => 'Lab Jaringan']);

    $comp1 = Computer::factory()->create(['laboratory_id' => $lab1->id, 'status' => 'active']);
    $comp2 = Computer::factory()->create(['laboratory_id' => $lab2->id, 'status' => 'active']);

    ScanSession::factory()->create([
        'computer_id' => $comp1->id,
        'status' => 'completed',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard', [
        'period' => '30d',
        'laboratory_id' => $lab1->id,
    ]));

    $response->assertStatus(200);
    $response->assertViewHas('monitoringStats');
    $stats = $response->viewData('monitoringStats');

    expect($stats['total_computers'])->toBe(1)
        ->and($stats['scanned_today'])->toBe(1)
        ->and($stats['unscanned_today'])->toBe(0);
});

test('kepala_lab only sees own laboratory monitoring statistics and unscanned computers', function () {
    $lab1 = Laboratory::factory()->create(['name' => 'Lab RPL']);
    $lab2 = Laboratory::factory()->create(['name' => 'Lab Jaringan']);

    $user = User::factory()->create(['laboratory_id' => $lab1->id]);
    $user->assignRole('kepala_lab');

    $comp1 = Computer::factory()->create(['laboratory_id' => $lab1->id, 'status' => 'active', 'hostname' => 'PC-RPL-01']);
    $comp2 = Computer::factory()->create(['laboratory_id' => $lab1->id, 'status' => 'active', 'hostname' => 'PC-RPL-02']);
    $comp3 = Computer::factory()->create(['laboratory_id' => $lab2->id, 'status' => 'active', 'hostname' => 'PC-JAR-01']);

    ScanSession::factory()->create([
        'computer_id' => $comp1->id,
        'status' => 'completed',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewHas('monitoringStats');
    $response->assertViewHas('unscannedComputers');

    $stats = $response->viewData('monitoringStats');
    $unscanned = $response->viewData('unscannedComputers');

    expect($stats['total_computers'])->toBe(2)
        ->and($stats['scanned_today'])->toBe(1)
        ->and($stats['unscanned_today'])->toBe(1)
        ->and($unscanned->pluck('hostname'))->toContain('PC-RPL-02')
        ->and($unscanned->pluck('hostname'))->not->toContain('PC-JAR-01');
});

test('chart-data endpoint returns json trend data for scans and compliance', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();
    $comp = Computer::factory()->create(['laboratory_id' => $lab->id, 'status' => 'active']);

    $session = ScanSession::factory()->create([
        'computer_id' => $comp->id,
        'status' => 'completed',
        'started_at' => now()->subDay(),
        'completed_at' => now()->subDay(),
    ]);

    ComplianceSnapshot::create([
        'scan_session_id' => $session->id,
        'computer_id' => $comp->id,
        'catalog_id' => null,
        'status' => 'Berlisensi',
        'software_name' => 'Windows 11 Pro',
        'scanned_at' => now()->subDay(),
        'created_at' => now()->subDay(),
    ]);

    $response = $this->actingAs($admin)->getJson(route('dashboard.chart-data', ['period' => '7d']));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'scan_trend' => [
                'labels',
                'completed',
                'failed',
            ],
            'compliance_trend' => [
                'labels',
                'berlisensi',
                'tidak_berlisensi',
                'grace_period',
                'perlu_ditinjau',
            ],
        ]);
});

test('kepala_lab chart-data endpoint is scoped strictly to own laboratory', function () {
    $lab1 = Laboratory::factory()->create();
    $lab2 = Laboratory::factory()->create();

    $user = User::factory()->create(['laboratory_id' => $lab1->id]);
    $user->assignRole('kepala_lab');

    $comp1 = Computer::factory()->create(['laboratory_id' => $lab1->id]);
    $comp2 = Computer::factory()->create(['laboratory_id' => $lab2->id]);

    ScanSession::factory()->create([
        'computer_id' => $comp1->id,
        'status' => 'completed',
        'started_at' => now(),
    ]);

    ScanSession::factory()->create([
        'computer_id' => $comp2->id,
        'status' => 'completed',
        'started_at' => now(),
    ]);

    // kepala_lab requests chart-data, even attempting to pass laboratory_id of lab2
    $response = $this->actingAs($user)->getJson(route('dashboard.chart-data', [
        'laboratory_id' => $lab2->id,
    ]));

    $response->assertStatus(200);
    $data = $response->json();

    // Total completed today across all labels should only be 1 (from lab1)
    $totalCompleted = array_sum($data['scan_trend']['completed']);
    expect($totalCompleted)->toBe(1);
});
