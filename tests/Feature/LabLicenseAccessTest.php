<?php

use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'kepala_lab']);
    Role::firstOrCreate(['name' => 'staff_lab']);
    Role::firstOrCreate(['name' => 'pimpinan']);
});

test('unauthenticated user cannot access lab licenses', function () {
    $this->get(route('lab.licenses.index'))
        ->assertRedirect(route('login'));
});

test('admin or pimpinan cannot access lab licenses route directly', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('lab.licenses.index'))
        ->assertForbidden();
});

test('kepala_lab and staff_lab can view licenses allocated to their faculty', function () {
    $facultyA = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $labA = Laboratory::factory()->create(['faculty_id' => $facultyA->id]);

    $facultyB = Faculty::factory()->create(['name' => 'Fakultas Pertanian']);

    $catalogA = SoftwareCatalog::factory()->create(['normalized_name' => 'Windows 11 Pro']);
    $licenseA = LicenseInventory::factory()->create([
        'catalog_id' => $catalogA->id,
        'license_key' => 'AAAA-BBBB-CCCC-DDDD',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $licenseA->id,
        'faculty_id' => $facultyA->id,
        'allocated_quota' => 30,
        'status' => 'active',
    ]);

    $catalogB = SoftwareCatalog::factory()->create(['normalized_name' => 'ArcGIS Pro']);
    $licenseB = LicenseInventory::factory()->create([
        'catalog_id' => $catalogB->id,
        'license_key' => 'XXXX-YYYY-ZZZZ-WWWW',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $licenseB->id,
        'faculty_id' => $facultyB->id,
        'allocated_quota' => 10,
        'status' => 'active',
    ]);

    $kepalaLab = User::factory()->create(['laboratory_id' => $labA->id]);
    $kepalaLab->assignRole('kepala_lab');

    $response = $this->actingAs($kepalaLab)->get(route('lab.licenses.index'));

    $response->assertOk();
    $response->assertSee('Windows 11 Pro');
    $response->assertDontSee('ArcGIS Pro');
});

test('user without laboratory or faculty sees graceful empty state', function () {
    $staff = User::factory()->create(['laboratory_id' => null, 'faculty_id' => null]);
    $staff->assignRole('staff_lab');

    $response = $this->actingAs($staff)->get(route('lab.licenses.index'));

    $response->assertOk();
    $response->assertSee('belum ditugaskan');
});

test('kepala_lab can retrieve real license key and it is logged to activity log', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);
    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'Windows 11 Pro']);
    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'license_key' => 'AAAA-BBBB-CCCC-DDDD',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 30,
        'status' => 'active',
    ]);

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $response = $this->actingAs($kepalaLab)
        ->postJson(route('lab.licenses.key', $license->id));

    $response->assertOk()
        ->assertJson(['key' => 'AAAA-BBBB-CCCC-DDDD']);

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $kepalaLab->id,
        'subject_id' => $license->id,
        'description' => 'Melihat license key alokasi fakultas',
    ]);
});

test('user cannot retrieve license key belonging to another faculty', function () {
    $facultyA = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $labA = Laboratory::factory()->create(['faculty_id' => $facultyA->id]);

    $facultyB = Faculty::factory()->create(['name' => 'Fakultas Pertanian']);
    $labB = Laboratory::factory()->create(['faculty_id' => $facultyB->id]);

    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'AutoCAD 2024']);
    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'license_key' => 'AUTOCAD-KEY-1234',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyB->id,
        'allocated_quota' => 15,
        'status' => 'active',
    ]);

    $staffLabA = User::factory()->create(['laboratory_id' => $labA->id]);
    $staffLabA->assignRole('staff_lab');

    $response = $this->actingAs($staffLabA)
        ->postJson(route('lab.licenses.key', $license->id));

    $response->assertForbidden();
});

test('lab licenses view renders search and allocations properly', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknik']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);
    $catalog = SoftwareCatalog::factory()->create(['normalized_name' => 'Microsoft Windows 11 Pro', 'category' => 'OS']);
    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'license_key' => 'VK7JG-NPHTM-C97JM-9MPGT',
        'expiry_date' => now()->addYear(),
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 50,
        'status' => 'active',
        'notes' => 'Gunakan key untuk PC Lab Jaringan',
    ]);

    $staff = User::factory()->create(['laboratory_id' => $lab->id]);
    $staff->assignRole('staff_lab');

    $response = $this->actingAs($staff)->get(route('lab.licenses.index'));

    $response->assertOk()
        ->assertSee('Fakultas Teknik')
        ->assertSee('Microsoft Windows 11 Pro')
        ->assertSee('50')
        ->assertSee('Gunakan key untuk PC Lab Jaringan')
        ->assertSee($license->masked_license_key)
        ->assertDontSee('VK7JG-NPHTM-C97JM-9MPGT'); // Plain key must not exist in HTML
});

test('sidebar shows Lisensi Fakultas link for kepala_lab and staff_lab but not for pimpinan', function () {
    $faculty = Faculty::factory()->create();
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $this->actingAs($kepalaLab)
        ->get(route('dashboard'))
        ->assertSee(route('lab.licenses.index'))
        ->assertSee('Lisensi Fakultas');

    $staffLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $staffLab->assignRole('staff_lab');

    $this->actingAs($staffLab)
        ->get(route('lab.inventory.index'))
        ->assertSee(route('lab.licenses.index'))
        ->assertSee('Lisensi Fakultas');

    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('pimpinan');

    $this->actingAs($pimpinan)
        ->get(route('dashboard'))
        ->assertDontSee(route('lab.licenses.index'));
});
