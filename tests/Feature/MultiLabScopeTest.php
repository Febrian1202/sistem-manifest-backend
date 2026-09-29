<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->labFTI = Laboratory::factory()->create(['name' => 'Lab FTI', 'code' => 'FTI']);
    $this->labFKIP = Laboratory::factory()->create(['name' => 'Lab FKIP', 'code' => 'FKIP']);

    $this->pjFTI = User::factory()->create(['laboratory_id' => $this->labFTI->id]);
    $this->pjFTI->assignRole('kepala_lab');

    $this->pjFKIP = User::factory()->create(['laboratory_id' => $this->labFKIP->id]);
    $this->pjFKIP->assignRole('kepala_lab');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole('pimpinan');

    $this->compFTI = Computer::factory()->create([
        'hostname' => 'PC-FTI-001',
        'laboratory_id' => $this->labFTI->id,
    ]);

    $this->compFKIP = Computer::factory()->create([
        'hostname' => 'PC-FKIP-001',
        'laboratory_id' => $this->labFKIP->id,
    ]);
});

test('PJ Lab FTI hanya melihat komputer dari lab FTI di inventaris', function () {
    $response = $this->actingAs($this->pjFTI)->get(route('lab.inventory.index'));

    $response->assertStatus(200);
    $response->assertSee('PC-FTI-001');
    $response->assertDontSee('PC-FKIP-001');
});

test('PJ Lab FKIP hanya melihat komputer dari lab FKIP di inventaris', function () {
    $response = $this->actingAs($this->pjFKIP)->get(route('lab.inventory.index'));

    $response->assertStatus(200);
    $response->assertSee('PC-FKIP-001');
    $response->assertDontSee('PC-FTI-001');
});

test('admin dapat melihat seluruh komputer lintas laboratorium', function () {
    $response = $this->actingAs($this->admin)->get(route('computers'));

    $response->assertStatus(200);
    $response->assertSee('PC-FTI-001');
    $response->assertSee('PC-FKIP-001');
});

test('pimpinan dapat melihat seluruh komputer lintas laboratorium secara read-only', function () {
    $response = $this->actingAs($this->pimpinan)->get(route('computers'));

    $response->assertStatus(200);
    $response->assertSee('PC-FTI-001');
    $response->assertSee('PC-FKIP-001');
});

test('PJ Lab tidak dapat mengakses detail komputer laboratorium lain', function () {
    $response = $this->actingAs($this->pjFTI)->get(route('lab.inventory.show', $this->compFKIP));

    $response->assertStatus(403);
});

test('PJ Lab hanya melihat monitoring sesi komputer lab-nya', function () {
    $sessionFTI = ScanSession::factory()->create(['computer_id' => $this->compFTI->id]);
    $sessionFKIP = ScanSession::factory()->create(['computer_id' => $this->compFKIP->id]);

    $response = $this->actingAs($this->pjFTI)->get(route('monitoring.index'));

    $response->assertStatus(200);
    $sessions = $response->viewData('scanSessions');
    expect($sessions->pluck('id'))->toContain($sessionFTI->id)
        ->and($sessions->pluck('id'))->not->toContain($sessionFKIP->id);
});

test('PJ Lab tidak dapat membuka detail scan session milik lab lain', function () {
    $sessionFKIP = ScanSession::factory()->create(['computer_id' => $this->compFKIP->id]);

    $response = $this->actingAs($this->pjFTI)->get(route('monitoring.show', $sessionFKIP));

    $response->assertStatus(403);
});
