<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('kepala_lab can only see computers in own lab', function () {
    $lab1 = Laboratory::factory()->create(['name' => 'Lab Komputer 1']);
    $lab2 = Laboratory::factory()->create(['name' => 'Lab Komputer 2']);

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab->assignRole('kepala_lab');

    $comp1 = Computer::factory()->create([
        'hostname' => 'PC-LAB1-01',
        'laboratory_id' => $lab1->id,
    ]);

    $comp2 = Computer::factory()->create([
        'hostname' => 'PC-LAB2-01',
        'laboratory_id' => $lab2->id,
    ]);

    $response = $this->actingAs($kepalaLab)->get(route('lab.inventory.index'));

    $response->assertStatus(200);
    $response->assertSee('PC-LAB1-01');
    $response->assertDontSee('PC-LAB2-01');
});

test('kepala_lab cannot access global computer CRUD routes', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $comp = Computer::factory()->create(['laboratory_id' => $lab->id]);

    $this->actingAs($kepalaLab)->get(route('computers'))->assertStatus(403);
    $this->actingAs($kepalaLab)->get(route('computers.show', $comp))->assertStatus(403);
    $this->actingAs($kepalaLab)->put(route('computers.update', $comp), ['hostname' => 'NEW-NAME'])->assertStatus(403);
    $this->actingAs($kepalaLab)->delete(route('computers.destroy', $comp))->assertStatus(403);
    $this->actingAs($kepalaLab)->post(route('computers.request-scan', $comp))->assertStatus(403);
});

test('kepala_lab cannot access license routes', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $catalog = SoftwareCatalog::factory()->create();
    $license = LicenseInventory::factory()->create(['catalog_id' => $catalog->id]);

    $this->actingAs($kepalaLab)->get(route('licenses'))->assertStatus(403);
    $this->actingAs($kepalaLab)->get(route('licenses.show', $license))->assertStatus(403);
    $this->actingAs($kepalaLab)->post(route('licenses.store'), [])->assertStatus(403);
    $this->actingAs($kepalaLab)->put(route('licenses.update', $license), [])->assertStatus(403);
    $this->actingAs($kepalaLab)->delete(route('licenses.destroy', $license))->assertStatus(403);
    $this->actingAs($kepalaLab)->post(route('licenses.key', $license))->assertStatus(403);
});

test('kepala_lab cannot access software catalog routes', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $catalog = SoftwareCatalog::factory()->create();

    $this->actingAs($kepalaLab)->get(route('softwares'))->assertStatus(403);
    $this->actingAs($kepalaLab)->put(route('softwares.update', $catalog), [])->assertStatus(403);
});

test('kepala_lab cannot access account management', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $otherUser = User::factory()->create();

    $this->actingAs($kepalaLab)->get(route('accounts'))->assertStatus(403);
    $this->actingAs($kepalaLab)->post(route('accounts.store'), [])->assertStatus(403);
    $this->actingAs($kepalaLab)->put(route('accounts.update', $otherUser), [])->assertStatus(403);
    $this->actingAs($kepalaLab)->delete(route('accounts.destroy', $otherUser))->assertStatus(403);
    $this->actingAs($kepalaLab)->put(route('accounts.reset-password', $otherUser), [])->assertStatus(403);
});

test('kepala_lab can view lab inventory', function () {
    $lab = Laboratory::factory()->create(['name' => 'Lab Rekayasa']);
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $response = $this->actingAs($kepalaLab)->get(route('lab.inventory.index'));

    $response->assertStatus(200);
    $response->assertViewIs('lab-inventory.index');
    $response->assertViewHas(['computers', 'lab', 'stats']);
    $response->assertSee('Lab Rekayasa');
});

test('kepala_lab can view computer detail in own lab', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $comp = Computer::factory()->create([
        'hostname' => 'PC-OWN-LAB',
        'laboratory_id' => $lab->id,
    ]);

    $response = $this->actingAs($kepalaLab)->get(route('lab.inventory.show', $comp));

    $response->assertStatus(200);
    $response->assertViewIs('lab-inventory.show');
    $response->assertSee('PC-OWN-LAB');
});

test('kepala_lab cannot view computer detail in other lab', function () {
    $lab1 = Laboratory::factory()->create();
    $lab2 = Laboratory::factory()->create();

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab->assignRole('kepala_lab');

    $otherComp = Computer::factory()->create([
        'hostname' => 'PC-OTHER-LAB',
        'laboratory_id' => $lab2->id,
    ]);

    $response = $this->actingAs($kepalaLab)->get(route('lab.inventory.show', $otherComp));

    $response->assertStatus(403);
});

test('kepala_lab without lab gets 403 on inventory', function () {
    $kepalaLab = User::factory()->create(['laboratory_id' => null]);
    $kepalaLab->assignRole('kepala_lab');

    $response = $this->actingAs($kepalaLab)->get(route('lab.inventory.index'));

    $response->assertStatus(403);
});
