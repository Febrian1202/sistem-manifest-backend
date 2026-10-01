<?php

use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\ReportApproval;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole('pimpinan');

    $this->kepalaLab = User::factory()->create();
    $this->kepalaLab->assignRole('kepala_lab');

    $this->staffLab = User::factory()->create();
    $this->staffLab->assignRole('staff_lab');
});

it('displays cross-faculty summary cards and comparison table on pimpinan dashboard', function () {
    $currentPeriod = now()->format('Y-m');

    $facultyA = Faculty::factory()->create(['name' => 'Fakultas Teknologi Informasi', 'code' => 'FTI']);
    $labA = Laboratory::factory()->create(['name' => 'Lab Jaringan', 'faculty_id' => $facultyA->id]);

    ReportApproval::factory()->approved()->create([
        'laboratory_id' => $labA->id,
        'report_type' => 'kepatuhan',
        'period' => $currentPeriod,
    ]);

    $facultyB = Faculty::factory()->create(['name' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'code' => 'FKIP']);
    $labB = Laboratory::factory()->create(['name' => 'Lab Bahasa', 'faculty_id' => $facultyB->id]);

    ReportApproval::factory()->approved()->create([
        'laboratory_id' => $labB->id,
        'report_type' => 'kepatuhan',
        'period' => $currentPeriod,
    ]);

    // Setup software & allocations
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'MATLAB R2025',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 30,
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyA->id,
        'allocated_quota' => 15,
        'status' => 'active',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyB->id,
        'allocated_quota' => 10,
        'status' => 'active',
    ]);

    // FTI has 10 computers installed (surplus 5)
    for ($i = 0; $i < 10; $i++) {
        $comp = Computer::factory()->create(['laboratory_id' => $labA->id, 'status' => 'active']);
        SoftwareDiscovery::factory()->create(['computer_id' => $comp->id, 'catalog_id' => $catalog->id]);
    }

    // FKIP has 12 computers installed (deficit 2)
    for ($i = 0; $i < 12; $i++) {
        $comp = Computer::factory()->create(['laboratory_id' => $labB->id, 'status' => 'active']);
        SoftwareDiscovery::factory()->create(['computer_id' => $comp->id, 'catalog_id' => $catalog->id]);
    }

    $response = $this->actingAs($this->pimpinan)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewIs('dashboard.pimpinan');

    $response->assertViewHasAll([
        'facultyMatrix',
        'globalStats',
        'topDeficitSoftwares',
        'stats',
        'approvedLabIds',
    ]);

    $globalStats = $response->viewData('globalStats');
    expect($globalStats['total_faculties'])->toBe(2)
        ->and($globalStats['total_laboratories'])->toBe(2)
        ->and($globalStats['total_computers'])->toBe(22)
        ->and($globalStats['total_owned_licenses'])->toBe(30)
        ->and($globalStats['total_allocated_seats'])->toBe(25)
        ->and($globalStats['total_software_deficits'])->toBe(2);

    $matrix = $response->viewData('facultyMatrix');
    $ftiRow = $matrix->firstWhere('faculty_code', 'FTI');
    $fkipRow = $matrix->firstWhere('faculty_code', 'FKIP');

    expect($ftiRow)->not->toBeNull()
        ->and($ftiRow['total_allocated_seats'])->toBe(15)
        ->and($ftiRow['total_installed_seats'])->toBe(10)
        ->and($ftiRow['total_surplus'])->toBe(5)
        ->and($ftiRow['total_deficit'])->toBe(0);

    expect($fkipRow)->not->toBeNull()
        ->and($fkipRow['total_allocated_seats'])->toBe(10)
        ->and($fkipRow['total_installed_seats'])->toBe(12)
        ->and($fkipRow['total_deficit'])->toBe(2);

    $response->assertSee('Fakultas Teknologi Informasi');
    $response->assertSee('Fakultas Keguruan dan Ilmu Pendidikan');
    $response->assertSee('Status Kepatuhan Antarfakultas');
});

it('shows top deficit software ranking correctly', function () {
    $currentPeriod = now()->format('Y-m');
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Sains', 'code' => 'FS']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    ReportApproval::factory()->approved()->create([
        'laboratory_id' => $lab->id,
        'report_type' => 'kepatuhan',
        'period' => $currentPeriod,
    ]);

    // Software 1: Owned 5, Installed 12 -> Deficit 7
    $cat1 = SoftwareCatalog::factory()->create(['normalized_name' => 'Adobe Premiere Pro', 'category' => 'Commercial']);
    LicenseInventory::factory()->create(['catalog_id' => $cat1->id, 'quota_limit' => 5]);

    // Software 2: Owned 10, Installed 13 -> Deficit 3
    $cat2 = SoftwareCatalog::factory()->create(['normalized_name' => 'CorelDRAW 2026', 'category' => 'Commercial']);
    LicenseInventory::factory()->create(['catalog_id' => $cat2->id, 'quota_limit' => 10]);

    // Software 3: Owned 20, Installed 15 -> Deficit 0
    $cat3 = SoftwareCatalog::factory()->create(['normalized_name' => 'IntelliJ IDEA', 'category' => 'Commercial']);
    LicenseInventory::factory()->create(['catalog_id' => $cat3->id, 'quota_limit' => 20]);

    for ($i = 0; $i < 12; $i++) {
        $c = Computer::factory()->create(['laboratory_id' => $lab->id, 'status' => 'active']);
        SoftwareDiscovery::factory()->create(['computer_id' => $c->id, 'catalog_id' => $cat1->id]);
        if ($i < 13) {
            SoftwareDiscovery::factory()->create(['computer_id' => $c->id, 'catalog_id' => $cat2->id]);
        }
    }
    // Create 1 more computer for CorelDRAW to reach 13
    $extraComp = Computer::factory()->create(['laboratory_id' => $lab->id, 'status' => 'active']);
    SoftwareDiscovery::factory()->create(['computer_id' => $extraComp->id, 'catalog_id' => $cat2->id]);

    $response = $this->actingAs($this->pimpinan)->get(route('dashboard'));

    $response->assertStatus(200);
    $topDeficits = $response->viewData('topDeficitSoftwares');

    expect($topDeficits)->toHaveCount(2)
        ->and($topDeficits[0]['name'])->toBe('Adobe Premiere Pro')
        ->and($topDeficits[0]['deficit'])->toBe(7)
        ->and($topDeficits[1]['name'])->toBe('CorelDRAW 2026')
        ->and($topDeficits[1]['deficit'])->toBe(3);

    $response->assertSee('Adobe Premiere Pro');
    $response->assertSee('+7 seat');
    $response->assertSee('CorelDRAW 2026');
    $response->assertSee('+3 seat');
});

it('renders license needs report preview successfully for admin and pimpinan', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik', 'code' => 'FT']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'AutoCAD 2026', 'category' => 'Commercial']);
    $license = LicenseInventory::factory()->create(['catalog_id' => $catalog->id, 'quota_limit' => 20]);
    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 15,
        'status' => 'active',
    ]);

    // 1. Admin access
    $resAdmin = $this->actingAs($this->admin)->get(route('reports.kebutuhan-lisensi'));
    $resAdmin->assertStatus(200);
    $resAdmin->assertViewIs('reports.kebutuhan-lisensi');
    $resAdmin->assertViewHasAll(['summary', 'facultyDistributions', 'procurementInsights']);
    $resAdmin->assertSee('Laporan Analisis Kebutuhan dan Alokasi Lisensi Software');
    $resAdmin->assertSee('Fakultas Teknik');
    $resAdmin->assertSee('AutoCAD 2026');

    // 2. Pimpinan access
    $resPimpinan = $this->actingAs($this->pimpinan)->get(route('reports.kebutuhan-lisensi'));
    $resPimpinan->assertStatus(200);
    $resPimpinan->assertViewIs('reports.kebutuhan-lisensi');
    $resPimpinan->assertSee('Laporan Analisis Kebutuhan dan Alokasi Lisensi Software');
});

it('exports license needs report as PDF without errors', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Hukum', 'code' => 'FH']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'SPSS Statistics', 'category' => 'Commercial']);
    LicenseInventory::factory()->create(['catalog_id' => $catalog->id, 'quota_limit' => 10]);

    // Admin PDF
    $resAdmin = $this->actingAs($this->admin)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'pdf']));
    $resAdmin->assertStatus(200);
    expect($resAdmin->headers->get('content-type'))->toContain('application/pdf');

    // Pimpinan PDF
    $resPimpinan = $this->actingAs($this->pimpinan)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'pdf']));
    $resPimpinan->assertStatus(200);
    expect($resPimpinan->headers->get('content-type'))->toContain('application/pdf');
});

it('exports license needs report as Excel spreadsheet successfully', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Pertanian', 'code' => 'FAPERTA']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'ArcGIS Pro', 'category' => 'Commercial']);
    $license = LicenseInventory::factory()->create(['catalog_id' => $catalog->id, 'quota_limit' => 25]);
    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 20,
        'status' => 'active',
    ]);

    // Admin Excel export
    $resAdmin = $this->actingAs($this->admin)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'excel']));
    $resAdmin->assertStatus(200);
    expect($resAdmin->headers->get('content-disposition'))->toContain('.xlsx');

    // Pimpinan Excel export
    $resPimpinan = $this->actingAs($this->pimpinan)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'excel']));
    $resPimpinan->assertStatus(200);
    expect($resPimpinan->headers->get('content-disposition'))->toContain('.xlsx');
});

it('prevents unauthorized roles from accessing executive report exports', function () {
    // Guest redirected to login
    $this->get(route('reports.kebutuhan-lisensi'))->assertRedirect(route('login'));
    $this->get(route('reports.kebutuhan-lisensi.export', ['format' => 'pdf']))->assertRedirect(route('login'));
    $this->get(route('reports.kebutuhan-lisensi.export', ['format' => 'excel']))->assertRedirect(route('login'));

    // Kepala Lab gets 403 Forbidden
    $this->actingAs($this->kepalaLab)->get(route('reports.kebutuhan-lisensi'))->assertForbidden();
    $this->actingAs($this->kepalaLab)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'pdf']))->assertForbidden();
    $this->actingAs($this->kepalaLab)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'excel']))->assertForbidden();

    // Staff Lab gets 403 Forbidden
    $this->actingAs($this->staffLab)->get(route('reports.kebutuhan-lisensi'))->assertForbidden();
    $this->actingAs($this->staffLab)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'pdf']))->assertForbidden();
    $this->actingAs($this->staffLab)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'excel']))->assertForbidden();
});
