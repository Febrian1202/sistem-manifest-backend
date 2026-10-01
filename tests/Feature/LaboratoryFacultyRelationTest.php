<?php

use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('requires faculty_id when storing a laboratory', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('laboratories.store'), [
        'name' => 'Lab Baru',
        'code' => 'LAB-NEW',
        'faculty_id' => '',
    ]);

    $response->assertSessionHasErrors('faculty_id');
});

test('creates a laboratory linked to a faculty', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknologi Informasi', 'code' => 'FTI']);

    $response = $this->actingAs($admin)->post(route('laboratories.store'), [
        'faculty_id' => $faculty->id,
        'name' => 'Lab Komputasi Awan',
        'code' => 'LAB-CLOUD',
        'building' => 'Gedung FTI',
        'floor' => '2',
        'description' => 'Lab untuk komputasi terdistribusi',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('laboratories.index'));

    $this->assertDatabaseHas('laboratories', [
        'faculty_id' => $faculty->id,
        'name' => 'Lab Komputasi Awan',
        'code' => 'LAB-CLOUD',
    ]);

    $lab = Laboratory::where('code', 'LAB-CLOUD')->first();
    expect($lab->faculty)->not->toBeNull()
        ->and($lab->faculty->id)->toBe($faculty->id)
        ->and($lab->faculty->code)->toBe('FTI');
});

test('updates laboratory faculty correctly', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $facultyOld = Faculty::factory()->create(['name' => 'Fakultas A', 'code' => 'FA']);
    $facultyNew = Faculty::factory()->create(['name' => 'Fakultas B', 'code' => 'FB']);

    $lab = Laboratory::factory()->create([
        'faculty_id' => $facultyOld->id,
        'code' => 'LAB-P1',
    ]);

    $response = $this->actingAs($admin)->put(route('laboratories.update', $lab), [
        'faculty_id' => $facultyNew->id,
        'name' => 'Lab P1 Diperbarui',
        'code' => 'LAB-P1',
        'building' => 'Gedung Baru',
        'floor' => '1',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('laboratories.index'));

    expect($lab->fresh()->faculty_id)->toBe($facultyNew->id);
});

test('filters laboratories by faculty_id on index page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $facultyA = Faculty::factory()->create(['name' => 'Fakultas Komputer', 'code' => 'FKOM']);
    $facultyB = Faculty::factory()->create(['name' => 'Fakultas Hukum', 'code' => 'FHUK']);

    $labA = Laboratory::factory()->create([
        'faculty_id' => $facultyA->id,
        'name' => 'Lab Pemrograman C',
    ]);

    $labB = Laboratory::factory()->create([
        'faculty_id' => $facultyB->id,
        'name' => 'Lab Peradilan Semu',
    ]);

    $response = $this->actingAs($admin)->get(route('laboratories.index', [
        'faculty_id' => $facultyA->id,
    ]));

    $response->assertStatus(200);
    $response->assertSee('Lab Pemrograman C');
    $response->assertDontSee('Lab Peradilan Semu');
});

test('retains laboratory with null faculty_id if parent faculty is deleted', function () {
    $faculty = Faculty::factory()->create();
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    expect($lab->faculty_id)->toBe($faculty->id);

    // Delete faculty directly in database to test DB cascade nullOnDelete
    $faculty->delete();

    expect($lab->fresh()->faculty_id)->toBeNull();
});

test('accesses computers through faculty hasManyThrough relationship', function () {
    $faculty = Faculty::factory()->create();
    $lab1 = Laboratory::factory()->create(['faculty_id' => $faculty->id]);
    $lab2 = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    Computer::factory()->count(2)->create(['laboratory_id' => $lab1->id]);
    Computer::factory()->count(3)->create(['laboratory_id' => $lab2->id]);

    expect($faculty->computers)->toHaveCount(5);
});
