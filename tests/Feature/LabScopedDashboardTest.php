<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin sees full dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewIs('pages.admin.dashboard');
    $response->assertViewHas('totalComputers');
});

test('kepala_lab sees lab-scoped dashboard', function () {
    $lab1 = Laboratory::factory()->create(['name' => 'Lab Algoritma dan Pemrograman']);
    $lab2 = Laboratory::factory()->create(['name' => 'Lab Sistem Tertanam']);

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab->assignRole('kepala_lab');

    Computer::factory()->count(3)->create(['laboratory_id' => $lab1->id]);
    Computer::factory()->count(5)->create(['laboratory_id' => $lab2->id]);

    $response = $this->actingAs($kepalaLab)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewIs('dashboard.kepala-lab');
    $response->assertViewHas('lab');
    $response->assertViewHas('stats');
    $response->assertSee('Lab Algoritma dan Pemrograman');
    $response->assertDontSee('Lab Sistem Tertanam');

    $stats = $response->viewData('stats');
    expect($stats['total_computers'])->toBe(3);
});

test('pimpinan sees approved-only dashboard', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('pimpinan');

    $currentPeriod = now()->format('Y-m');

    $approvedLab = Laboratory::factory()->create(['name' => 'Lab Terverifikasi']);
    $unapprovedLab = Laboratory::factory()->create(['name' => 'Lab Belum Diverifikasi']);

    ReportApproval::factory()->approved()->create([
        'laboratory_id' => $approvedLab->id,
        'report_type' => 'kepatuhan',
        'period' => $currentPeriod,
    ]);

    Computer::factory()->count(4)->create(['laboratory_id' => $approvedLab->id]);
    Computer::factory()->count(6)->create(['laboratory_id' => $unapprovedLab->id]);

    $response = $this->actingAs($pimpinan)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewIs('dashboard.pimpinan');
    $response->assertViewHas(['stats', 'approvedLabIds']);

    $stats = $response->viewData('stats');
    // Only computers in approved labs are counted in pimpinan dashboard stats
    expect($stats['total_computers'])->toBe(4)
        ->and($stats['approved_labs'])->toBe(1);
});

test('kepala_lab without lab sees informative message', function () {
    $kepalaLab = User::factory()->create(['laboratory_id' => null]);
    $kepalaLab->assignRole('kepala_lab');

    $response = $this->actingAs($kepalaLab)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewIs('dashboard.no-lab');
    $response->assertSee('Laboratorium Belum Ditugaskan');
});
