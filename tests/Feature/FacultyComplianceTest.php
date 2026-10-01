<?php

use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
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

it('calculates faculty compliance with zero deficit when installed equals allocated', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik', 'code' => 'FT']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'AutoCAD 2026',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 20,
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 10,
        'status' => 'active',
    ]);

    for ($i = 0; $i < 10; $i++) {
        $computer = Computer::factory()->create([
            'laboratory_id' => $lab->id,
            'status' => 'active',
        ]);
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
        ]);
    }

    $breakdown = $this->service->getFacultyComplianceBreakdown($faculty->id);
    $item = $breakdown->firstWhere('catalog_id', $catalog->id);

    expect($item)->not->toBeNull()
        ->and($item['allocated'])->toBe(10)
        ->and($item['installed'])->toBe(10)
        ->and($item['deficit'])->toBe(0)
        ->and($item['surplus'])->toBe(0)
        ->and($item['utilization_rate'])->toEqual(100.0)
        ->and($item['is_compliant'])->toBeTrue()
        ->and($item['status'])->toBe('Cukup (Sesuai Alokasi)');
});

it('detects deficit when installed software exceeds allocated quota in a faculty', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik', 'code' => 'FT']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'AutoCAD 2026',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 20,
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 10,
        'status' => 'active',
    ]);

    for ($i = 0; $i < 15; $i++) {
        $computer = Computer::factory()->create([
            'laboratory_id' => $lab->id,
            'status' => 'active',
        ]);
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
        ]);
    }

    $breakdown = $this->service->getFacultyComplianceBreakdown($faculty->id);
    $item = $breakdown->firstWhere('catalog_id', $catalog->id);

    expect($item)->not->toBeNull()
        ->and($item['allocated'])->toBe(10)
        ->and($item['installed'])->toBe(15)
        ->and($item['deficit'])->toBe(5)
        ->and($item['surplus'])->toBe(0)
        ->and($item['utilization_rate'])->toEqual(150.0)
        ->and($item['is_compliant'])->toBeFalse()
        ->and($item['status'])->toBe('Defisit');
});

it('detects surplus when installed software is less than allocated quota', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik', 'code' => 'FT']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'AutoCAD 2026',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 30,
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 20,
        'status' => 'active',
    ]);

    for ($i = 0; $i < 12; $i++) {
        $computer = Computer::factory()->create([
            'laboratory_id' => $lab->id,
            'status' => 'active',
        ]);
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
        ]);
    }

    $breakdown = $this->service->getFacultyComplianceBreakdown($faculty->id);
    $item = $breakdown->firstWhere('catalog_id', $catalog->id);

    expect($item)->not->toBeNull()
        ->and($item['allocated'])->toBe(20)
        ->and($item['installed'])->toBe(12)
        ->and($item['deficit'])->toBe(0)
        ->and($item['surplus'])->toBe(8)
        ->and($item['utilization_rate'])->toEqual(60.0)
        ->and($item['is_compliant'])->toBeTrue()
        ->and($item['status'])->toBe('Surplus');
});

it('handles unallocated software installation gracefully without division by zero', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik', 'code' => 'FT']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Adobe Photoshop 2026',
        'category' => 'Commercial',
    ]);

    // Tidak ada alokasi untuk software ini di fakultas
    for ($i = 0; $i < 5; $i++) {
        $computer = Computer::factory()->create([
            'laboratory_id' => $lab->id,
            'status' => 'active',
        ]);
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
        ]);
    }

    $breakdown = $this->service->getFacultyComplianceBreakdown($faculty->id);
    $item = $breakdown->firstWhere('catalog_id', $catalog->id);

    expect($item)->not->toBeNull()
        ->and($item['allocated'])->toBe(0)
        ->and($item['installed'])->toBe(5)
        ->and($item['deficit'])->toBe(5)
        ->and($item['surplus'])->toBe(0)
        ->and($item['utilization_rate'])->toBeNull()
        ->and($item['is_compliant'])->toBeFalse()
        ->and($item['status'])->toBe('Tanpa Alokasi (Defisit Penuh)');
});

it('filters compliance view by faculty_id correctly', function () {
    $facultyA = Faculty::factory()->create(['name' => 'Fakultas Teknik', 'code' => 'FT']);
    $facultyB = Faculty::factory()->create(['name' => 'Fakultas Pertanian', 'code' => 'FP']);

    $labA = Laboratory::factory()->create(['faculty_id' => $facultyA->id]);
    $labB = Laboratory::factory()->create(['faculty_id' => $facultyB->id]);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'MATLAB R2026a',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 20,
    ]);

    // Alokasi 5 untuk FT
    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyA->id,
        'allocated_quota' => 5,
        'status' => 'active',
    ]);

    // 5 komputer di lab FT
    for ($i = 0; $i < 5; $i++) {
        $comp = Computer::factory()->create(['laboratory_id' => $labA->id, 'status' => 'active']);
        SoftwareDiscovery::factory()->create(['computer_id' => $comp->id, 'catalog_id' => $catalog->id]);
    }

    // 3 komputer di lab FP (tanpa alokasi)
    for ($i = 0; $i < 3; $i++) {
        $comp = Computer::factory()->create(['laboratory_id' => $labB->id, 'status' => 'active']);
        SoftwareDiscovery::factory()->create(['computer_id' => $comp->id, 'catalog_id' => $catalog->id]);
    }

    // Request dengan faculty_id Fakultas A
    $responseA = $this->actingAs($this->admin)->get(route('compliance', ['faculty_id' => $facultyA->id]));
    $responseA->assertStatus(200);
    $responseA->assertViewHas('selectedFaculty');
    $softwaresA = $responseA->viewData('softwares');
    $itemA = collect($softwaresA->items())->firstWhere('catalog_id', $catalog->id);

    expect($itemA)->not->toBeNull()
        ->and($itemA->allocated)->toBe(5)
        ->and($itemA->installed)->toBe(5)
        ->and($itemA->deficit)->toBe(0);

    // Request dengan faculty_id Fakultas B
    $responseB = $this->actingAs($this->admin)->get(route('compliance', ['faculty_id' => $facultyB->id]));
    $responseB->assertStatus(200);
    $softwaresB = $responseB->viewData('softwares');
    $itemB = collect($softwaresB->items())->firstWhere('catalog_id', $catalog->id);

    expect($itemB)->not->toBeNull()
        ->and($itemB->allocated)->toBe(0)
        ->and($itemB->installed)->toBe(3)
        ->and($itemB->deficit)->toBe(3);
});

it('generates cross-faculty comparison matrix accurately', function () {
    $facultyA = Faculty::factory()->create(['name' => 'Fakultas Teknik', 'code' => 'FT']);
    $facultyB = Faculty::factory()->create(['name' => 'Fakultas Pertanian', 'code' => 'FP']);

    $labA = Laboratory::factory()->create(['faculty_id' => $facultyA->id]);
    $labB = Laboratory::factory()->create(['faculty_id' => $facultyB->id]);

    $compA1 = Computer::factory()->create(['laboratory_id' => $labA->id, 'status' => 'active']);
    $compA2 = Computer::factory()->create(['laboratory_id' => $labA->id, 'status' => 'active']);
    $compB1 = Computer::factory()->create(['laboratory_id' => $labB->id, 'status' => 'active']);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'AutoCAD 2026',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 10,
    ]);

    // Alokasi 5 untuk FT, 0 untuk FP
    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyA->id,
        'allocated_quota' => 5,
        'status' => 'active',
    ]);

    // FT: 2 terpasang (surplus 3)
    SoftwareDiscovery::factory()->create(['computer_id' => $compA1->id, 'catalog_id' => $catalog->id]);
    SoftwareDiscovery::factory()->create(['computer_id' => $compA2->id, 'catalog_id' => $catalog->id]);

    // FP: 1 terpasang (defisit 1, tanpa alokasi)
    SoftwareDiscovery::factory()->create(['computer_id' => $compB1->id, 'catalog_id' => $catalog->id]);

    $matrix = $this->service->getCrossFacultyMatrix();

    expect($matrix)->toHaveCount(2);

    $rowA = $matrix->firstWhere('faculty_id', $facultyA->id);
    expect($rowA)->not->toBeNull()
        ->and($rowA['faculty_code'])->toBe('FT')
        ->and($rowA['total_labs'])->toBe(1)
        ->and($rowA['total_computers'])->toBe(2)
        ->and($rowA['total_allocated_seats'])->toBe(5)
        ->and($rowA['total_installed_seats'])->toBe(2)
        ->and($rowA['total_deficit'])->toBe(0)
        ->and($rowA['total_surplus'])->toBe(3)
        ->and($rowA['non_compliant_software_count'])->toBe(0);

    $rowB = $matrix->firstWhere('faculty_id', $facultyB->id);
    expect($rowB)->not->toBeNull()
        ->and($rowB['faculty_code'])->toBe('FP')
        ->and($rowB['total_labs'])->toBe(1)
        ->and($rowB['total_computers'])->toBe(1)
        ->and($rowB['total_allocated_seats'])->toBe(0)
        ->and($rowB['total_installed_seats'])->toBe(1)
        ->and($rowB['total_deficit'])->toBe(1)
        ->and($rowB['total_surplus'])->toBe(0)
        ->and($rowB['non_compliant_software_count'])->toBe(1);
});
