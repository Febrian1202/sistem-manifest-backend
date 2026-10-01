<?php

use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->facultyA = Faculty::factory()->create([
        'name' => 'Fakultas Teknologi Informasi',
        'code' => 'FTI',
    ]);

    $this->facultyB = Faculty::factory()->create([
        'name' => 'Fakultas Teknik',
        'code' => 'FT',
    ]);

    $this->labA1 = Laboratory::factory()->create([
        'faculty_id' => $this->facultyA->id,
        'name' => 'Lab Jaringan',
        'code' => 'LAB-NET',
    ]);

    $this->labA2 = Laboratory::factory()->create([
        'faculty_id' => $this->facultyA->id,
        'name' => 'Lab Multimedia',
        'code' => 'LAB-MM',
    ]);

    $this->labB1 = Laboratory::factory()->create([
        'faculty_id' => $this->facultyB->id,
        'name' => 'Lab Mekatronika',
        'code' => 'LAB-MEK',
    ]);
});

it('assigns staff_lab role to a user scoped to a laboratory', function () {
    $response = $this->actingAs($this->admin)->post(route('accounts.store'), [
        'name' => 'Staff Lab Jaringan',
        'email' => 'staff.net@usn.ac.id',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'staff_lab',
        'laboratory_id' => $this->labA1->id,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('users', [
        'email' => 'staff.net@usn.ac.id',
        'laboratory_id' => $this->labA1->id,
        'faculty_id' => null,
    ]);

    $user = User::where('email', 'staff.net@usn.ac.id')->first();
    expect($user->hasRole('staff_lab'))->toBeTrue()
        ->and($user->getAccessibleLaboratoryIds())->toEqual([$this->labA1->id]);
});

it('assigns staff_lab role to a user scoped to a faculty', function () {
    $response = $this->actingAs($this->admin)->post(route('accounts.store'), [
        'name' => 'Staff FTI',
        'email' => 'staff.fti@usn.ac.id',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'staff_lab',
        'faculty_id' => $this->facultyA->id,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('users', [
        'email' => 'staff.fti@usn.ac.id',
        'faculty_id' => $this->facultyA->id,
        'laboratory_id' => null,
    ]);

    $user = User::where('email', 'staff.fti@usn.ac.id')->first();
    expect($user->hasRole('staff_lab'))->toBeTrue()
        ->and($user->getAccessibleLaboratoryIds())->toEqualCanonicalizing([$this->labA1->id, $this->labA2->id]);
});

it('requires either laboratory_id or faculty_id for staff_lab', function () {
    $response = $this->actingAs($this->admin)->post(route('accounts.store'), [
        'name' => 'Staff Tanpa Scope',
        'email' => 'staff.noscope@usn.ac.id',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'staff_lab',
        'laboratory_id' => '',
        'faculty_id' => '',
    ]);

    $response->assertSessionHasErrors(['laboratory_id', 'faculty_id']);
});

it('restricts staff_lab computer view to their assigned faculty laboratories', function () {
    $pcA1 = Computer::factory()->create([
        'laboratory_id' => $this->labA1->id,
        'hostname' => 'PC-NET-01',
    ]);

    $pcA2 = Computer::factory()->create([
        'laboratory_id' => $this->labA2->id,
        'hostname' => 'PC-MM-01',
    ]);

    $pcB1 = Computer::factory()->create([
        'laboratory_id' => $this->labB1->id,
        'hostname' => 'PC-MEK-01',
    ]);

    // Staff scoped to Faculty A (includes Lab A1 and Lab A2)
    $staffFaculty = User::factory()->create([
        'faculty_id' => $this->facultyA->id,
        'laboratory_id' => null,
    ]);
    $staffFaculty->assignRole('staff_lab');

    // Scoped query check
    $scopedComputers = Computer::forUserLab($staffFaculty)->get();
    expect($scopedComputers->pluck('id'))->toContain($pcA1->id, $pcA2->id)
        ->and($scopedComputers->pluck('id'))->not->toContain($pcB1->id);

    // Web view check
    $response = $this->actingAs($staffFaculty)->get(route('lab.inventory.index'));
    $response->assertStatus(200);
    $response->assertSee('PC-NET-01');
    $response->assertSee('PC-MM-01');
    $response->assertDontSee('PC-MEK-01');

    // Show computer in accessible lab
    $this->actingAs($staffFaculty)->get(route('lab.inventory.show', $pcA1))
        ->assertStatus(200);

    // Show computer in forbidden lab
    $this->actingAs($staffFaculty)->get(route('lab.inventory.show', $pcB1))
        ->assertStatus(403);
});

it('restricts staff_lab computer view to their assigned specific laboratory', function () {
    $pcA1 = Computer::factory()->create([
        'laboratory_id' => $this->labA1->id,
        'hostname' => 'PC-NET-01',
    ]);

    $pcA2 = Computer::factory()->create([
        'laboratory_id' => $this->labA2->id,
        'hostname' => 'PC-MM-01',
    ]);

    // Staff scoped only to Lab A1
    $staffLab = User::factory()->create([
        'laboratory_id' => $this->labA1->id,
        'faculty_id' => null,
    ]);
    $staffLab->assignRole('staff_lab');

    $response = $this->actingAs($staffLab)->get(route('lab.inventory.index'));
    $response->assertStatus(200);
    $response->assertSee('PC-NET-01');
    $response->assertDontSee('PC-MM-01');

    // Show allowed computer
    $this->actingAs($staffLab)->get(route('lab.inventory.show', $pcA1))
        ->assertStatus(200);

    // Show computer in other lab of same faculty -> 403
    $this->actingAs($staffLab)->get(route('lab.inventory.show', $pcA2))
        ->assertStatus(403);
});

it('allows staff_lab to access agent download page with their allowed labs only', function () {
    $staffFaculty = User::factory()->create([
        'faculty_id' => $this->facultyA->id,
        'laboratory_id' => null,
    ]);
    $staffFaculty->assignRole('staff_lab');

    $response = $this->actingAs($staffFaculty)->get(route('agent.download-page'));
    $response->assertStatus(200);
    $response->assertSee($this->labA1->name);
    $response->assertSee($this->labA2->name);
    $response->assertDontSee($this->labB1->name);

    // Download allowed lab
    $downloadSuccess = $this->actingAs($staffFaculty)->post(route('agent.download'), [
        'laboratory_id' => $this->labA1->id,
    ]);
    $downloadSuccess->assertStatus(200);
    $downloadSuccess->assertHeader('Content-Type', 'application/zip');

    // Download forbidden lab
    $downloadForbidden = $this->actingAs($staffFaculty)->post(route('agent.download'), [
        'laboratory_id' => $this->labB1->id,
    ]);
    $downloadForbidden->assertStatus(403);
});

it('denies staff_lab from accessing license management and allocation endpoints', function () {
    $staff = User::factory()->create([
        'laboratory_id' => $this->labA1->id,
    ]);
    $staff->assignRole('staff_lab');

    // Cannot access licenses
    $this->actingAs($staff)->get(route('licenses'))
        ->assertStatus(403);

    // Cannot mutate licenses
    $catalog = SoftwareCatalog::factory()->create();
    $this->actingAs($staff)->post(route('licenses.store'), [
        'catalog_id' => $catalog->id,
        'license_type' => 'OEM',
        'quota_limit' => 5,
    ])->assertStatus(403);

    // Cannot access account management
    $this->actingAs($staff)->get(route('accounts'))
        ->assertStatus(403);

    // Cannot manage faculties or laboratories
    $this->actingAs($staff)->get(route('faculties.index'))
        ->assertStatus(403);
    $this->actingAs($staff)->get(route('laboratories.index'))
        ->assertStatus(403);
});

it('redirects staff_lab accessing dashboard to lab inventory page', function () {
    $staff = User::factory()->create([
        'laboratory_id' => $this->labA1->id,
    ]);
    $staff->assignRole('staff_lab');

    $response = $this->actingAs($staff)->get(route('dashboard'));
    $response->assertRedirect(route('lab.inventory.index'));
});

it('allows updating staff_lab account and switching between lab and faculty scope', function () {
    $staff = User::factory()->create([
        'name' => 'Staff Initial',
        'email' => 'staff.switch@usn.ac.id',
        'laboratory_id' => $this->labA1->id,
        'faculty_id' => null,
    ]);
    $staff->assignRole('staff_lab');

    // Update to faculty scope
    $response = $this->actingAs($this->admin)->put(route('accounts.update', $staff), [
        'name' => 'Staff Switched',
        'email' => 'staff.switch@usn.ac.id',
        'role' => 'staff_lab',
        'faculty_id' => $this->facultyB->id,
    ]);

    $response->assertSessionHasNoErrors();
    $staff->refresh();

    expect($staff->name)->toBe('Staff Switched')
        ->and($staff->faculty_id)->toBe($this->facultyB->id)
        ->and($staff->laboratory_id)->toBeNull();
});
