<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin can view laboratory list', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Laboratory::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get(route('laboratories.index'));

    $response->assertStatus(200);
    $response->assertViewIs('laboratories.index');
    $response->assertViewHas('laboratories');
});

test('admin can view create laboratory page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('laboratories.create'));

    $response->assertStatus(200);
    $response->assertViewIs('laboratories.create');
});

test('admin can create a laboratory', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $data = [
        'name' => 'Lab Rekayasa Perangkat Lunak',
        'code' => 'LAB-RPL',
        'building' => 'Gedung C',
        'floor' => '2',
        'description' => 'Laboratorium untuk pengembangan software',
    ];

    $response = $this->actingAs($admin)->post(route('laboratories.store'), $data);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('laboratories.index'));

    $this->assertDatabaseHas('laboratories', [
        'name' => 'Lab Rekayasa Perangkat Lunak',
        'code' => 'LAB-RPL',
        'building' => 'Gedung C',
        'floor' => '2',
    ]);
});

test('admin can view edit laboratory page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();

    $response = $this->actingAs($admin)->get(route('laboratories.edit', $lab));

    $response->assertStatus(200);
    $response->assertViewIs('laboratories.edit');
    $response->assertViewHas('laboratory');
});

test('admin can update a laboratory', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create([
        'name' => 'Lab Lama',
        'code' => 'LAB-OLD',
    ]);

    $response = $this->actingAs($admin)->put(route('laboratories.update', $lab), [
        'name' => 'Lab Baru Diperbarui',
        'code' => 'LAB-OLD',
        'building' => 'Gedung Baru',
        'floor' => '3',
        'description' => 'Deskripsi baru',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('laboratories.index'));

    $this->assertDatabaseHas('laboratories', [
        'id' => $lab->id,
        'name' => 'Lab Baru Diperbarui',
        'building' => 'Gedung Baru',
        'floor' => '3',
    ]);
});

test('admin can delete a laboratory without computers', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();

    $pjLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $pjLab->assignRole('kepala_lab');

    $response = $this->actingAs($admin)->delete(route('laboratories.destroy', $lab));

    $response->assertRedirect(route('laboratories.index'));
    $response->assertSessionHas('status', 'success');

    $this->assertDatabaseMissing('laboratories', [
        'id' => $lab->id,
    ]);

    // Unlinked PJ Lab
    expect($pjLab->fresh()->laboratory_id)->toBeNull();
});

test('admin cannot delete a laboratory that has computers', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();
    Computer::factory()->create(['laboratory_id' => $lab->id]);

    $response = $this->actingAs($admin)->delete(route('laboratories.destroy', $lab));

    $response->assertRedirect();
    $response->assertSessionHas('status', 'destructive');

    $this->assertDatabaseHas('laboratories', [
        'id' => $lab->id,
    ]);
});

test('kepala_lab cannot access laboratory CRUD', function () {
    $lab = Laboratory::factory()->create();

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $this->actingAs($kepalaLab)->get(route('laboratories.index'))->assertStatus(403);
    $this->actingAs($kepalaLab)->get(route('laboratories.create'))->assertStatus(403);
    $this->actingAs($kepalaLab)->post(route('laboratories.store'), ['name' => 'Test', 'code' => 'T1'])->assertStatus(403);
    $this->actingAs($kepalaLab)->get(route('laboratories.edit', $lab))->assertStatus(403);
    $this->actingAs($kepalaLab)->put(route('laboratories.update', $lab), ['name' => 'Test', 'code' => 'T1'])->assertStatus(403);
    $this->actingAs($kepalaLab)->delete(route('laboratories.destroy', $lab))->assertStatus(403);
});

test('pimpinan cannot access laboratory CRUD', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('pimpinan');

    $lab = Laboratory::factory()->create();

    $this->actingAs($pimpinan)->get(route('laboratories.index'))->assertStatus(403);
    $this->actingAs($pimpinan)->get(route('laboratories.create'))->assertStatus(403);
    $this->actingAs($pimpinan)->post(route('laboratories.store'), ['name' => 'Test', 'code' => 'T1'])->assertStatus(403);
    $this->actingAs($pimpinan)->get(route('laboratories.edit', $lab))->assertStatus(403);
    $this->actingAs($pimpinan)->put(route('laboratories.update', $lab), ['name' => 'Test', 'code' => 'T1'])->assertStatus(403);
    $this->actingAs($pimpinan)->delete(route('laboratories.destroy', $lab))->assertStatus(403);
});

test('laboratory code must be unique', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Laboratory::factory()->create(['code' => 'LAB-UNIQUE']);

    $response = $this->actingAs($admin)->post(route('laboratories.store'), [
        'name' => 'Lab Baru',
        'code' => 'LAB-UNIQUE',
    ]);

    $response->assertSessionHasErrors('code');
});

test('laboratory name is required', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('laboratories.store'), [
        'name' => '',
        'code' => 'LAB-NEW',
    ]);

    $response->assertSessionHasErrors('name');
});

test('laboratory code is required', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('laboratories.store'), [
        'name' => 'Lab Baru',
        'code' => '',
    ]);

    $response->assertSessionHasErrors('code');
});

test('laboratory index shows computer count', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create(['name' => 'Lab Jaringan Komputer']);
    Computer::factory()->count(4)->create(['laboratory_id' => $lab->id]);

    $response = $this->actingAs($admin)->get(route('laboratories.index'));

    $response->assertStatus(200);
    $response->assertSee('Lab Jaringan Komputer');
    $response->assertSee('4');
});

test('laboratory index shows assigned PJ Lab name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create(['name' => 'Lab Multimedia']);
    $pjLab = User::factory()->create([
        'name' => 'Ahmad Dahlan',
        'laboratory_id' => $lab->id,
    ]);
    $pjLab->assignRole('kepala_lab');

    $response = $this->actingAs($admin)->get(route('laboratories.index'));

    $response->assertStatus(200);
    $response->assertSee('Lab Multimedia');
    $response->assertSee('Ahmad Dahlan');
});

test('activity log is recorded for laboratory CRUD', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    // Create
    $this->actingAs($admin)->post(route('laboratories.store'), [
        'name' => 'Lab Robotika',
        'code' => 'LAB-ROBOT',
    ]);

    $lab = Laboratory::where('code', 'LAB-ROBOT')->first();
    expect($lab)->not->toBeNull();

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Laboratory::class,
        'subject_id' => $lab->id,
        'event' => 'created',
    ]);

    // Update
    $this->actingAs($admin)->put(route('laboratories.update', $lab), [
        'name' => 'Lab Robotika & AI',
        'code' => 'LAB-ROBOT',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Laboratory::class,
        'subject_id' => $lab->id,
        'event' => 'updated',
    ]);

    // Delete
    $this->actingAs($admin)->delete(route('laboratories.destroy', $lab));

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Laboratory::class,
        'subject_id' => $lab->id,
        'event' => 'deleted',
    ]);
});
