<?php

use App\Models\ComplianceReport;
use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('Computer::forUserLab scopes correctly for kepala_lab and admin', function () {
    $lab1 = Laboratory::factory()->create(['name' => 'Lab 1']);
    $lab2 = Laboratory::factory()->create(['name' => 'Lab 2']);

    $kepalaLab1 = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab1->assignRole('kepala_lab');

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $comp1 = Computer::factory()->create(['laboratory_id' => $lab1->id]);
    $comp2 = Computer::factory()->create(['laboratory_id' => $lab2->id]);

    expect(Computer::forUserLab($kepalaLab1)->pluck('id')->all())->toEqual([$comp1->id]);
    expect(Computer::forUserLab($admin)->count())->toBe(2);

    $this->actingAs($kepalaLab1);
    expect(Computer::forUserLab()->pluck('id')->all())->toEqual([$comp1->id]);

    expect(Computer::forLaboratory($lab2->id)->pluck('id')->all())->toEqual([$comp2->id]);
});

test('ScanSession and ScanSoftwareResult scope correctly through relationships', function () {
    $lab1 = Laboratory::factory()->create();
    $lab2 = Laboratory::factory()->create();

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab->assignRole('kepala_lab');

    $comp1 = Computer::factory()->create(['laboratory_id' => $lab1->id]);
    $comp2 = Computer::factory()->create(['laboratory_id' => $lab2->id]);

    $session1 = ScanSession::factory()->create(['computer_id' => $comp1->id]);
    $session2 = ScanSession::factory()->create(['computer_id' => $comp2->id]);

    $catalog = SoftwareCatalog::factory()->create();

    $result1 = ScanSoftwareResult::factory()->create([
        'scan_session_id' => $session1->id,
        'catalog_id' => $catalog->id,
    ]);
    $result2 = ScanSoftwareResult::factory()->create([
        'scan_session_id' => $session2->id,
        'catalog_id' => $catalog->id,
    ]);

    expect(ScanSession::forUserLab($kepalaLab)->pluck('id')->all())->toEqual([$session1->id]);
    expect(ScanSoftwareResult::forUserLab($kepalaLab)->pluck('id')->all())->toEqual([$result1->id]);

    expect(ScanSession::forLaboratory($lab2->id)->pluck('id')->all())->toEqual([$session2->id]);
    expect(ScanSoftwareResult::forLaboratory($lab2->id)->pluck('id')->all())->toEqual([$result2->id]);
});

test('ComplianceSnapshot and ComplianceReport scope correctly', function () {
    $lab1 = Laboratory::factory()->create();
    $lab2 = Laboratory::factory()->create();

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab->assignRole('kepala_lab');

    $comp1 = Computer::factory()->create(['laboratory_id' => $lab1->id]);
    $comp2 = Computer::factory()->create(['laboratory_id' => $lab2->id]);

    $catalog = SoftwareCatalog::factory()->create();

    $snap1 = ComplianceSnapshot::factory()->create([
        'computer_id' => $comp1->id,
        'software_catalog_id' => $catalog->id,
    ]);
    $snap2 = ComplianceSnapshot::factory()->create([
        'computer_id' => $comp2->id,
        'software_catalog_id' => $catalog->id,
    ]);

    $rep1 = ComplianceReport::factory()->create([
        'computer_id' => $comp1->id,
        'software_catalog_id' => $catalog->id,
    ]);
    $rep2 = ComplianceReport::factory()->create([
        'computer_id' => $comp2->id,
        'software_catalog_id' => $catalog->id,
    ]);

    expect(ComplianceSnapshot::forUserLab($kepalaLab)->pluck('id')->all())->toEqual([$snap1->id]);
    expect(ComplianceReport::forUserLab($kepalaLab)->pluck('id')->all())->toEqual([$rep1->id]);

    expect(ComplianceSnapshot::forLaboratory($lab2->id)->pluck('id')->all())->toEqual([$snap2->id]);
    expect(ComplianceReport::forLaboratory($lab2->id)->pluck('id')->all())->toEqual([$rep2->id]);
});

test('SoftwareDiscovery and ReportApproval scope correctly', function () {
    $lab1 = Laboratory::factory()->create();
    $lab2 = Laboratory::factory()->create();

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab->assignRole('kepala_lab');

    $comp1 = Computer::factory()->create(['laboratory_id' => $lab1->id]);
    $comp2 = Computer::factory()->create(['laboratory_id' => $lab2->id]);

    $disc1 = SoftwareDiscovery::factory()->create(['computer_id' => $comp1->id]);
    $disc2 = SoftwareDiscovery::factory()->create(['computer_id' => $comp2->id]);

    $app1 = ReportApproval::factory()->create(['laboratory_id' => $lab1->id]);
    $app2 = ReportApproval::factory()->create(['laboratory_id' => $lab2->id]);

    expect(SoftwareDiscovery::forUserLab($kepalaLab)->pluck('id')->all())->toEqual([$disc1->id]);
    expect(ReportApproval::forUserLab($kepalaLab)->pluck('id')->all())->toEqual([$app1->id]);

    expect(SoftwareDiscovery::forLaboratory($lab2->id)->pluck('id')->all())->toEqual([$disc2->id]);
    expect(ReportApproval::forLaboratory($lab2->id)->pluck('id')->all())->toEqual([$app2->id]);
});
