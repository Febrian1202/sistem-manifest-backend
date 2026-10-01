<?php

use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('allows admin to view faculties list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Faculty::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get(route('faculties.index'));

    $response->assertStatus(200);
    $response->assertViewIs('faculties.index');
    $response->assertViewHas('faculties');
});

test('allows admin to view create faculty page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('faculties.create'));

    $response->assertStatus(200);
    $response->assertViewIs('faculties.create');
});

test('successfully creates a new faculty with valid payload', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $data = [
        'code' => 'FTI',
        'name' => 'Fakultas Teknologi Informasi',
        'description' => 'Fakultas yang menaungi bidang komputasi dan teknologi informasi',
    ];

    $response = $this->actingAs($admin)->post(route('faculties.store'), $data);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('faculties.index'));

    $this->assertDatabaseHas('faculties', [
        'code' => 'FTI',
        'name' => 'Fakultas Teknologi Informasi',
    ]);
});

test('allows admin to view edit faculty page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $faculty = Faculty::factory()->create();

    $response = $this->actingAs($admin)->get(route('faculties.edit', $faculty));

    $response->assertStatus(200);
    $response->assertViewIs('faculties.edit');
    $response->assertViewHas('faculty');
});

test('successfully updates an existing faculty', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $faculty = Faculty::factory()->create([
        'code' => 'FKIP',
        'name' => 'Fakultas Keguruan',
    ]);

    $response = $this->actingAs($admin)->put(route('faculties.update', $faculty), [
        'code' => 'FKIP',
        'name' => 'Fakultas Keguruan dan Ilmu Pendidikan',
        'description' => 'Deskripsi baru diperbarui',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('faculties.index'));

    $this->assertDatabaseHas('faculties', [
        'id' => $faculty->id,
        'code' => 'FKIP',
        'name' => 'Fakultas Keguruan dan Ilmu Pendidikan',
        'description' => 'Deskripsi baru diperbarui',
    ]);
});

test('allows deletion of faculty when no laboratories are linked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $faculty = Faculty::factory()->create();

    $response = $this->actingAs($admin)->delete(route('faculties.destroy', $faculty));

    $response->assertRedirect(route('faculties.index'));
    $response->assertSessionHas('status', 'success');

    $this->assertDatabaseMissing('faculties', [
        'id' => $faculty->id,
    ]);
});

test('prevents deletion of faculty when laboratories exist', function () {
    if (! Schema::hasColumn('laboratories', 'faculty_id')) {
        $this->markTestSkipped('Laboratories table does not have faculty_id column yet (handled in Task 02).');
    }

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $faculty = Faculty::factory()->create();
    Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $response = $this->actingAs($admin)->delete(route('faculties.destroy', $faculty));

    $response->assertRedirect();
    $response->assertSessionHas('status', 'destructive');

    $this->assertDatabaseHas('faculties', [
        'id' => $faculty->id,
    ]);
});

test('forbids unauthenticated users and unauthorized roles from accessing faculty crud', function () {
    $faculty = Faculty::factory()->create();

    // Guest
    $this->get(route('faculties.index'))->assertRedirect(route('login'));
    $this->get(route('faculties.create'))->assertRedirect(route('login'));
    $this->post(route('faculties.store'), ['name' => 'Test', 'code' => 'T1'])->assertRedirect(route('login'));
    $this->get(route('faculties.edit', $faculty))->assertRedirect(route('login'));
    $this->put(route('faculties.update', $faculty), ['name' => 'Test', 'code' => 'T1'])->assertRedirect(route('login'));
    $this->delete(route('faculties.destroy', $faculty))->assertRedirect(route('login'));

    // Kepala Lab
    $kepalaLab = User::factory()->create();
    $kepalaLab->assignRole('kepala_lab');

    $this->actingAs($kepalaLab)->get(route('faculties.index'))->assertStatus(403);
    $this->actingAs($kepalaLab)->get(route('faculties.create'))->assertStatus(403);
    $this->actingAs($kepalaLab)->post(route('faculties.store'), ['name' => 'Test', 'code' => 'T1'])->assertStatus(403);
    $this->actingAs($kepalaLab)->get(route('faculties.edit', $faculty))->assertStatus(403);
    $this->actingAs($kepalaLab)->put(route('faculties.update', $faculty), ['name' => 'Test', 'code' => 'T1'])->assertStatus(403);
    $this->actingAs($kepalaLab)->delete(route('faculties.destroy', $faculty))->assertStatus(403);

    // Pimpinan
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('pimpinan');

    $this->actingAs($pimpinan)->get(route('faculties.index'))->assertStatus(403);
    $this->actingAs($pimpinan)->get(route('faculties.create'))->assertStatus(403);
    $this->actingAs($pimpinan)->post(route('faculties.store'), ['name' => 'Test', 'code' => 'T1'])->assertStatus(403);
    $this->actingAs($pimpinan)->get(route('faculties.edit', $faculty))->assertStatus(403);
    $this->actingAs($pimpinan)->put(route('faculties.update', $faculty), ['name' => 'Test', 'code' => 'T1'])->assertStatus(403);
    $this->actingAs($pimpinan)->delete(route('faculties.destroy', $faculty))->assertStatus(403);
});

test('validates faculty code uniqueness on store and update', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Faculty::factory()->create(['code' => 'FTI-UNIQUE']);

    // Duplicate on store
    $response = $this->actingAs($admin)->post(route('faculties.store'), [
        'name' => 'Fakultas Baru',
        'code' => 'FTI-UNIQUE',
    ]);
    $response->assertSessionHasErrors('code');

    // Duplicate on update with another faculty's code
    $otherFaculty = Faculty::factory()->create(['code' => 'OTHER-CODE']);
    $responseUpdate = $this->actingAs($admin)->put(route('faculties.update', $otherFaculty), [
        'name' => 'Fakultas Update',
        'code' => 'FTI-UNIQUE',
    ]);
    $responseUpdate->assertSessionHasErrors('code');

    // Same faculty can retain its own code on update
    $responseSelf = $this->actingAs($admin)->put(route('faculties.update', $otherFaculty), [
        'name' => 'Fakultas Tetap',
        'code' => 'OTHER-CODE',
    ]);
    $responseSelf->assertSessionHasNoErrors();
});

test('faculty code and name are required', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('faculties.store'), [
        'name' => '',
        'code' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'code']);
});

test('faculties can be filtered by search query', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Faculty::factory()->create(['name' => 'Fakultas Pertanian', 'code' => 'FP-01']);
    Faculty::factory()->create(['name' => 'Fakultas Teknik', 'code' => 'FT-01']);

    $response = $this->actingAs($admin)->get(route('faculties.index', ['search' => 'Pertanian']));

    $response->assertStatus(200);
    $response->assertSee('Fakultas Pertanian');
    $response->assertDontSee('Fakultas Teknik');
});

test('activity log is recorded for faculty CRUD', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create
    $this->actingAs($admin)->post(route('faculties.store'), [
        'name' => 'Fakultas Kedokteran',
        'code' => 'FK-MED',
    ]);

    $faculty = Faculty::where('code', 'FK-MED')->first();
    expect($faculty)->not->toBeNull();

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Faculty::class,
        'subject_id' => $faculty->id,
        'event' => 'created',
    ]);

    // Update
    $this->actingAs($admin)->put(route('faculties.update', $faculty), [
        'name' => 'Fakultas Kedokteran dan Ilmu Kesehatan',
        'code' => 'FK-MED',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Faculty::class,
        'subject_id' => $faculty->id,
        'event' => 'updated',
    ]);

    // Delete
    $this->actingAs($admin)->delete(route('faculties.destroy', $faculty));

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Faculty::class,
        'subject_id' => $faculty->id,
        'event' => 'deleted',
    ]);
});
