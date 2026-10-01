<?php

use App\Jobs\GenerateComplianceReportJob;
use App\Models\ComplianceReport;
use App\Models\Computer;
use App\Models\Faculty;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use App\Services\LicenseComplianceService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->service = app(LicenseComplianceService::class);
});

it('aggregates multiple license inventories for the same software catalog correctly', function () {
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'AutoCAD 2026',
        'category' => 'Commercial',
    ]);

    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 20,
        'purchase_order_number' => 'PO-BATCH-1',
        'expiry_date' => now()->addYear(),
    ]);

    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 30,
        'purchase_order_number' => 'PO-BATCH-2',
        'expiry_date' => now()->addMonths(6),
    ]);

    $entitlement = $this->service->getActiveEntitlement($catalog->id);

    expect($entitlement)->toBe(50);
});

it('marks compliance as compliant when installations exceed first license but are within total licenses', function () {
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Matlab R2026a',
        'category' => 'Commercial',
    ]);

    // Lic 1 = 10 seats, Lic 2 = 15 seats -> Total 25 seats
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 10,
        'purchase_order_number' => 'PO-MATLAB-1',
        'expiry_date' => now()->addYear(),
    ]);

    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 15,
        'purchase_order_number' => 'PO-MATLAB-2',
        'expiry_date' => now()->addYear(),
    ]);

    // Create 18 active computers with Matlab installed (18 > 10, but 18 <= 25)
    $computers = Computer::factory()->count(18)->create(['status' => 'active']);

    foreach ($computers as $computer) {
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
            'raw_name' => 'Matlab R2026a',
        ]);
    }

    $targetComputer = $computers->first();
    $job = new GenerateComplianceReportJob($targetComputer);
    $job->handle();

    $this->assertDatabaseHas('compliance_reports', [
        'computer_id' => $targetComputer->id,
        'software_catalog_id' => $catalog->id,
        'status' => 'Berlisensi',
    ]);

    $report = ComplianceReport::where('computer_id', $targetComputer->id)
        ->where('software_catalog_id', $catalog->id)
        ->first();

    expect($report->keterangan)->toBe('Lisensi aktif dan valid');
});

it('excludes expired licenses from active entitlement calculation', function () {
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Adobe Premiere',
        'category' => 'Commercial',
    ]);

    // Active license: 15 seats
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 15,
        'expiry_date' => now()->addYear(),
    ]);

    // Expired license: 25 seats (expired yesterday)
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 25,
        'expiry_date' => now()->subDay(),
    ]);

    // Permanent license (null expiry): 10 seats
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 10,
        'expiry_date' => null,
    ]);

    $entitlement = $this->service->getActiveEntitlement($catalog->id);

    // 15 + 10 = 25 (excluding 25 expired)
    expect($entitlement)->toBe(25);
});

it('calculates unallocated remaining quota accurately', function () {
    $facultyA = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $facultyB = Faculty::factory()->create(['name' => 'Fakultas Hukum']);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'SPSS Statistics',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 50,
        'expiry_date' => now()->addYear(),
    ]);

    // Active allocation 1: 15 seats to Faculty A
    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyA->id,
        'allocated_quota' => 15,
        'status' => 'active',
    ]);

    // Active allocation 2: 20 seats to Faculty B
    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyB->id,
        'allocated_quota' => 20,
        'status' => 'active',
    ]);

    // Revoked/inactive allocation: 10 seats
    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyA->id,
        'allocated_quota' => 10,
        'status' => 'revoked',
    ]);

    expect($this->service->getTotalAllocated($catalog->id))->toBe(35);
    expect($license->total_allocated)->toBe(35);
    expect($license->remaining_unallocated)->toBe(15);

    $compliance = $this->service->evaluateUniversityCompliance($catalog);
    expect($compliance['owned'])->toBe(50)
        ->and($compliance['allocated'])->toBe(35)
        ->and($compliance['unallocated'])->toBe(15);
});

it('handles zero license commercial software with unlicenced status', function () {
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'CorelDRAW 2026',
        'category' => 'Commercial',
    ]);

    $computer = Computer::factory()->create(['status' => 'active']);

    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'CorelDRAW 2026',
    ]);

    $job = new GenerateComplianceReportJob($computer);
    $job->handle();

    $this->assertDatabaseHas('compliance_reports', [
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalog->id,
        'status' => 'Tidak Berlisensi',
    ]);

    $report = ComplianceReport::where('computer_id', $computer->id)
        ->where('software_catalog_id', $catalog->id)
        ->first();

    expect($report->keterangan)->toContain('tidak ditemukan');

    $compliance = $this->service->evaluateUniversityCompliance($catalog);
    expect($compliance['owned'])->toBe(0)
        ->and($compliance['status'])->toBe('Tidak Berlisensi')
        ->and($compliance['is_compliant'])->toBeFalse();
});

it('marks compliance as unlicensed with full quota when installations exceed total quota of multiple licenses', function () {
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'SolidWorks 2026',
        'category' => 'Commercial',
    ]);

    // Total quota = 10 + 15 = 25
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 10,
        'expiry_date' => now()->addYear(),
    ]);

    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 15,
        'expiry_date' => now()->addYear(),
    ]);

    // 26 installations (26 > 25)
    $computers = Computer::factory()->count(26)->create(['status' => 'active']);
    foreach ($computers as $computer) {
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
            'raw_name' => 'SolidWorks 2026',
        ]);
    }

    $targetComputer = $computers->first();
    $job = new GenerateComplianceReportJob($targetComputer);
    $job->handle();

    $this->assertDatabaseHas('compliance_reports', [
        'computer_id' => $targetComputer->id,
        'software_catalog_id' => $catalog->id,
        'status' => 'Tidak Berlisensi',
        'keterangan' => 'Kuota lisensi penuh',
    ]);
});

it('calculates proportional license usage in license report without false over-limit', function () {
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Ansys Fluent',
        'category' => 'Commercial',
    ]);

    // Lic 1 = 20 seats, Lic 2 = 30 seats -> Total 50 seats
    $lic1 = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 20,
        'expiry_date' => now()->addYear(),
        'created_at' => now(),
    ]);

    $lic2 = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 30,
        'expiry_date' => now()->addYear(),
        'created_at' => now(),
    ]);

    // 35 installations (35 / 50 = 70%)
    $computers = Computer::factory()->count(35)->create(['status' => 'active']);
    foreach ($computers as $computer) {
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
            'raw_name' => 'Ansys Fluent',
        ]);
    }

    $response = $this->actingAs($this->admin)->get(route('reports.lisensi'));
    $response->assertOk();

    $paginatedLicenses = $response->viewData('licenses');
    $items = collect($paginatedLicenses->items());

    $row1 = $items->firstWhere('id', $lic1->id);
    $row2 = $items->firstWhere('id', $lic2->id);

    expect($row1)->not->toBeNull()
        ->and($row2)->not->toBeNull()
        ->and($row1->usage_pct)->toBe(70.0)
        ->and($row2->usage_pct)->toBe(70.0)
        ->and($row1->used_count)->toBe(14)
        ->and($row2->used_count)->toBe(21)
        ->and($row1->remaining)->toBe(6)
        ->and($row2->remaining)->toBe(9);
});
