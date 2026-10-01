<?php

use App\Models\Faculty;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

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

    $this->catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'AutoCAD 2026',
        'category' => 'Commercial',
    ]);

    $this->license = LicenseInventory::factory()->create([
        'catalog_id' => $this->catalog->id,
        'quota_limit' => 20,
        'purchase_order_number' => 'PO-TEST-001',
    ]);

    $this->facultyA = Faculty::factory()->create([
        'code' => 'FTI',
        'name' => 'Fakultas Teknologi Informasi',
    ]);

    $this->facultyB = Faculty::factory()->create([
        'code' => 'FT',
        'name' => 'Fakultas Teknik',
    ]);
});

it('allows admin to view license allocations list and create page', function () {
    LicenseAllocation::factory()->create([
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 5,
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->admin)->get(route('license-allocations.index'));
    $response->assertStatus(200);
    $response->assertViewIs('licenses.allocations.index');
    $response->assertViewHas(['allocations', 'stats', 'faculties', 'catalogs']);
    $response->assertSee('AutoCAD 2026');
    $response->assertSee('FTI');

    $createResponse = $this->actingAs($this->admin)->get(route('license-allocations.create'));
    $createResponse->assertStatus(200);
    $createResponse->assertViewIs('licenses.allocations.create');
});

it('allows admin to create a valid license allocation', function () {
    $payload = [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 10,
        'allocation_date' => '2026-10-01',
        'start_date' => '2026-10-01',
        'end_date' => '2027-10-01',
        'status' => 'active',
        'notes' => 'Alokasi untuk Lab Komputer FTI',
    ];

    $response = $this->actingAs($this->admin)->post(route('license-allocations.store'), $payload);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('license-allocations.index'));

    $this->assertDatabaseHas('license_allocations', [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 10,
        'status' => 'active',
        'created_by' => $this->admin->id,
    ]);

    // Refresh model & check calculation
    $this->license->refresh();
    expect($this->license->total_allocated)->toBe(10);
    expect($this->license->remaining_unallocated)->toBe(10);
});

it('rejects allocation when requested quota exceeds available license quota', function () {
    $payload = [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 25, // quota_limit is 20
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ];

    $response = $this->actingAs($this->admin)->post(route('license-allocations.store'), $payload);

    $response->assertSessionHasErrors(['allocated_quota']);
    $this->assertDatabaseCount('license_allocations', 0);
});

it('allows multiple allocations for the same license across different faculties if sum <= total quota', function () {
    // Alokasi 1: 12 kursi ke Fakultas A
    $this->actingAs($this->admin)->post(route('license-allocations.store'), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 12,
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    // Alokasi 2: 8 kursi ke Fakultas B (Total = 20, pas dengan quota_limit 20)
    $this->actingAs($this->admin)->post(route('license-allocations.store'), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyB->id,
        'allocated_quota' => 8,
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    $this->license->refresh();
    expect($this->license->total_allocated)->toBe(20);
    expect($this->license->remaining_unallocated)->toBe(0);

    // Alokasi 3: Mencoba menambah 1 kursi lagi -> ditolak karena sisa 0
    $this->actingAs($this->admin)->post(route('license-allocations.store'), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 1,
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ])->assertSessionHasErrors(['allocated_quota']);
});

it('allows updating allocation quota within remaining limit', function () {
    $allocation = LicenseAllocation::factory()->create([
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 10,
        'status' => 'active',
    ]);

    // Update kuota dari 10 menjadi 15 (tersedia 20, jadi 15 valid)
    $response = $this->actingAs($this->admin)->put(route('license-allocations.update', $allocation), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 15,
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('license-allocations.index'));

    $allocation->refresh();
    expect($allocation->allocated_quota)->toBe(15);
    expect($this->license->fresh()->remaining_unallocated)->toBe(5);
});

it('prevents update when new quota exceeds remaining limit without double counting self', function () {
    // Allocation 1: 10 kursi
    $allocation1 = LicenseAllocation::factory()->create([
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 10,
        'status' => 'active',
    ]);

    // Allocation 2: 8 kursi (Sisa kuota universitas = 2)
    $allocation2 = LicenseAllocation::factory()->create([
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyB->id,
        'allocated_quota' => 8,
        'status' => 'active',
    ]);

    // Edit allocation2 dari 8 menjadi 11 (karena allocation1 pakai 10, sisa max untuk allocation2 adalah 20-10=10)
    // 11 > 10 sehingga harus ditolak!
    $response = $this->actingAs($this->admin)->put(route('license-allocations.update', $allocation2), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyB->id,
        'allocated_quota' => 11,
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ]);

    $response->assertSessionHasErrors(['allocated_quota']);
    expect($allocation2->fresh()->allocated_quota)->toBe(8);

    // Namun mengubah allocation2 menjadi 10 kursi harus valid (10 + 10 = 20)
    $responseValid = $this->actingAs($this->admin)->put(route('license-allocations.update', $allocation2), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyB->id,
        'allocated_quota' => 10,
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ]);

    $responseValid->assertSessionHasNoErrors();
    expect($allocation2->fresh()->allocated_quota)->toBe(10);
});

it('recalculates remaining unallocated license quota when an allocation is marked inactive, revoked, or deleted', function () {
    $allocation = LicenseAllocation::factory()->create([
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 15,
        'status' => 'active',
    ]);

    expect($this->license->fresh()->remaining_unallocated)->toBe(5);

    // Ubah status menjadi inactive -> kuota kembali ke universitas
    $this->actingAs($this->admin)->put(route('license-allocations.update', $allocation), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 15,
        'allocation_date' => '2026-10-01',
        'status' => 'inactive',
    ])->assertSessionHasNoErrors();

    expect($this->license->fresh()->remaining_unallocated)->toBe(20);

    // Aktifkan kembali
    $this->actingAs($this->admin)->put(route('license-allocations.update', $allocation), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 15,
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ])->assertSessionHasNoErrors();

    expect($this->license->fresh()->remaining_unallocated)->toBe(5);

    // Hapus alokasi -> kuota kembali penuh ke universitas
    $deleteResponse = $this->actingAs($this->admin)->delete(route('license-allocations.destroy', $allocation));
    $deleteResponse->assertRedirect(route('license-allocations.index'));

    $this->assertDatabaseMissing('license_allocations', ['id' => $allocation->id]);
    expect($this->license->fresh()->remaining_unallocated)->toBe(20);
});

it('forbids non-admin users from creating, editing, or deleting allocations', function () {
    $allocation = LicenseAllocation::factory()->create([
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 5,
        'status' => 'active',
    ]);

    $nonAdmins = [$this->pimpinan, $this->kepalaLab, $this->staffLab];

    foreach ($nonAdmins as $user) {
        // Create page
        $this->actingAs($user)->get(route('license-allocations.create'))->assertStatus(403);

        // Store
        $this->actingAs($user)->post(route('license-allocations.store'), [
            'license_inventory_id' => $this->license->id,
            'faculty_id' => $this->facultyA->id,
            'allocated_quota' => 1,
            'allocation_date' => '2026-10-01',
            'status' => 'active',
        ])->assertStatus(403);

        // Edit page
        $this->actingAs($user)->get(route('license-allocations.edit', $allocation))->assertStatus(403);

        // Update
        $this->actingAs($user)->put(route('license-allocations.update', $allocation), [
            'license_inventory_id' => $this->license->id,
            'faculty_id' => $this->facultyA->id,
            'allocated_quota' => 2,
            'allocation_date' => '2026-10-01',
            'status' => 'active',
        ])->assertStatus(403);

        // Delete
        $this->actingAs($user)->delete(route('license-allocations.destroy', $allocation))->assertStatus(403);
    }
});

it('filters allocations by faculty, status, and search query', function () {
    $otherCatalog = SoftwareCatalog::factory()->create(['normalized_name' => 'Matlab R2026a']);
    $otherLicense = LicenseInventory::factory()->create(['catalog_id' => $otherCatalog->id, 'purchase_order_number' => 'PO-MATLAB-99']);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'status' => 'active',
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $otherLicense->id,
        'faculty_id' => $this->facultyB->id,
        'status' => 'inactive',
    ]);

    // Filter by Faculty A
    $responseFaculty = $this->actingAs($this->admin)->get(route('license-allocations.index', ['faculty_id' => $this->facultyA->id]));
    $responseFaculty->assertStatus(200);
    $responseFaculty->assertSee('AutoCAD 2026');
    $responseFaculty->assertDontSee('Matlab R2026a');

    // Filter by Status inactive
    $responseStatus = $this->actingAs($this->admin)->get(route('license-allocations.index', ['status' => 'inactive']));
    $responseStatus->assertStatus(200);
    $responseStatus->assertSee('Matlab R2026a');
    $responseStatus->assertDontSee('AutoCAD 2026');

    // Search by PO
    $responseSearch = $this->actingAs($this->admin)->get(route('license-allocations.index', ['search' => 'MATLAB']));
    $responseSearch->assertStatus(200);
    $responseSearch->assertSee('Matlab R2026a');
    $responseSearch->assertDontSee('AutoCAD 2026');
});

it('records activity log on allocation creation, update, and deletion', function () {
    $response = $this->actingAs($this->admin)->post(route('license-allocations.store'), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 5,
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ]);
    $response->assertSessionHasNoErrors();

    $allocation = LicenseAllocation::latest()->first();

    $createActivity = Activity::forSubject($allocation)->where('event', 'created')->first();
    expect($createActivity)->not->toBeNull();
    expect($createActivity->description)->toContain('Alokasi lisensi AutoCAD 2026 untuk fakultas Fakultas Teknologi Informasi telah di-created');

    // Update
    $this->actingAs($this->admin)->put(route('license-allocations.update', $allocation), [
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 8,
        'allocation_date' => '2026-10-01',
        'status' => 'active',
    ]);

    $updateActivity = Activity::forSubject($allocation)->where('event', 'updated')->first();
    expect($updateActivity)->not->toBeNull();

    // Delete
    $this->actingAs($this->admin)->delete(route('license-allocations.destroy', $allocation));

    $deleteActivity = Activity::forSubject($allocation)->where('event', 'deleted')->first();
    expect($deleteActivity)->not->toBeNull();
});

it('verifies eloquent relationships between license allocation, faculty, and license inventory', function () {
    $allocation = LicenseAllocation::factory()->create([
        'license_inventory_id' => $this->license->id,
        'faculty_id' => $this->facultyA->id,
        'allocated_quota' => 4,
        'status' => 'active',
        'created_by' => $this->admin->id,
    ]);

    expect($allocation->licenseInventory->id)->toBe($this->license->id);
    expect($allocation->faculty->id)->toBe($this->facultyA->id);
    expect($allocation->creator->id)->toBe($this->admin->id);

    expect($this->facultyA->licenseAllocations)->toHaveCount(1);
    expect($this->facultyA->licenseAllocations->first()->id)->toBe($allocation->id);

    expect($this->license->allocations)->toHaveCount(1);
    expect($this->license->activeAllocations)->toHaveCount(1);
});
