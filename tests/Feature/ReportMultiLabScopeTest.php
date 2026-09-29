<?php

use App\Models\ComplianceReport;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->lab1 = Laboratory::factory()->create(['name' => 'Lab Sistem 1', 'code' => 'LAB1']);
    $this->lab2 = Laboratory::factory()->create(['name' => 'Lab Sistem 2', 'code' => 'LAB2']);

    $this->kepalaLab1 = User::factory()->create(['laboratory_id' => $this->lab1->id]);
    $this->kepalaLab1->assignRole('kepala_lab');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole('pimpinan');

    $this->comp1 = Computer::factory()->create([
        'hostname' => 'PC-LAB1-01',
        'laboratory_id' => $this->lab1->id,
        'os_license_status' => 'Licensed',
    ]);
    $this->comp2 = Computer::factory()->create([
        'hostname' => 'PC-LAB2-01',
        'laboratory_id' => $this->lab2->id,
        'os_license_status' => 'Licensed',
    ]);

    $this->catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'VS Code', 'category' => 'Commercial']);

    SoftwareDiscovery::factory()->create([
        'computer_id' => $this->comp1->id,
        'catalog_id' => $this->catalog->id,
        'raw_name' => 'VS Code',
    ]);
    SoftwareDiscovery::factory()->create([
        'computer_id' => $this->comp2->id,
        'catalog_id' => $this->catalog->id,
        'raw_name' => 'VS Code',
    ]);

    ComplianceReport::factory()->create([
        'computer_id' => $this->comp1->id,
        'software_catalog_id' => $this->catalog->id,
        'software_name' => 'VS Code',
        'status' => 'Berlisensi',
    ]);
    ComplianceReport::factory()->create([
        'computer_id' => $this->comp2->id,
        'software_catalog_id' => $this->catalog->id,
        'software_name' => 'VS Code',
        'status' => 'Berlisensi',
    ]);
});

test('kepala_lab can access /reports and views are scoped to their laboratory', function () {
    $responseIndex = $this->actingAs($this->kepalaLab1)->get(route('reports'));
    $responseIndex->assertStatus(200);

    $responseKomputer = $this->actingAs($this->kepalaLab1)->get(route('reports.komputer'));
    $responseKomputer->assertStatus(200);
    $responseKomputer->assertSee('PC-LAB1-01');
    $responseKomputer->assertDontSee('PC-LAB2-01');

    $responseKepatuhan = $this->actingAs($this->kepalaLab1)->get(route('reports.kepatuhan'));
    $responseKepatuhan->assertStatus(200);
    $reports = $responseKepatuhan->viewData('reports');
    expect($reports->total())->toBe(1);
    expect($reports->first()->computer_id)->toBe($this->comp1->id);
});

test('kepala_lab cannot override scope by passing laboratory_id of other lab', function () {
    $response = $this->actingAs($this->kepalaLab1)->get(route('reports.komputer', [
        'laboratory_id' => $this->lab2->id,
    ]));

    $response->assertStatus(200);
    $response->assertSee('PC-LAB1-01');
    $response->assertDontSee('PC-LAB2-01');
});

test('admin can filter reports by laboratory_id', function () {
    $responseAll = $this->actingAs($this->admin)->get(route('reports.komputer'));
    $responseAll->assertStatus(200);
    $responseAll->assertSee('PC-LAB1-01');
    $responseAll->assertSee('PC-LAB2-01');

    $responseFiltered = $this->actingAs($this->admin)->get(route('reports.komputer', [
        'laboratory_id' => $this->lab2->id,
    ]));
    $responseFiltered->assertStatus(200);
    $responseFiltered->assertDontSee('PC-LAB1-01');
    $responseFiltered->assertSee('PC-LAB2-01');
});

test('pimpinan can only filter by approved laboratory_id', function () {
    $currentPeriod = now()->format('Y-m');

    ReportApproval::factory()->create([
        'laboratory_id' => $this->lab1->id,
        'report_type' => 'kepatuhan',
        'period' => $currentPeriod,
        'status' => 'approved',
    ]);

    $response = $this->actingAs($this->pimpinan)->get(route('reports.komputer', [
        'laboratory_id' => $this->lab1->id,
    ]));
    $response->assertStatus(200);
    $response->assertSee('PC-LAB1-01');

    $responseUnapproved = $this->actingAs($this->pimpinan)->get(route('reports.komputer', [
        'laboratory_id' => $this->lab2->id,
    ]));
    $responseUnapproved->assertStatus(200);
    $responseUnapproved->assertDontSee('PC-LAB2-01');
});

test('kepala_lab cannot trigger compliance scan', function () {
    $response = $this->actingAs($this->kepalaLab1)->post(route('reports.kepatuhan.scan'));
    $response->assertStatus(403);
});
