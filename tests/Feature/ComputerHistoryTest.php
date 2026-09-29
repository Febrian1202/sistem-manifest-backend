<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->labA = Laboratory::factory()->create(['name' => 'Lab Komputer A']);
    $this->labB = Laboratory::factory()->create(['name' => 'Lab Komputer B']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole('pimpinan');

    $this->kepalaLabA = User::factory()->create(['laboratory_id' => $this->labA->id]);
    $this->kepalaLabA->assignRole('kepala_lab');

    $this->computerA = Computer::factory()->create(['laboratory_id' => $this->labA->id, 'hostname' => 'PC-A-01']);
    $this->computerB = Computer::factory()->create(['laboratory_id' => $this->labB->id, 'hostname' => 'PC-B-01']);

    $this->session1 = ScanSession::factory()->create([
        'computer_id' => $this->computerA->id,
        'status' => 'completed',
        'started_at' => now()->subDays(2),
    ]);

    ScanSoftwareResult::create([
        'scan_session_id' => $this->session1->id,
        'raw_name' => 'Git',
        'version' => '2.40.0',
    ]);

    $this->session2 = ScanSession::factory()->create([
        'computer_id' => $this->computerA->id,
        'status' => 'completed',
        'started_at' => now()->subDay(),
    ]);

    ScanSoftwareResult::create([
        'scan_session_id' => $this->session2->id,
        'raw_name' => 'Git',
        'version' => '2.41.0',
    ]);
});

test('unauthenticated users are redirected to login for computer history', function () {
    $this->get(route('computers.history', $this->computerA))->assertRedirect(route('login'));
});

test('admin and pimpinan can view computer history', function () {
    $this->actingAs($this->admin)
        ->get(route('computers.history', $this->computerA))
        ->assertOk();

    $this->actingAs($this->pimpinan)
        ->get(route('computers.history', $this->computerA))
        ->assertOk();
});

test('kepala_lab can view history of their own lab computer', function () {
    $this->actingAs($this->kepalaLabA)
        ->get(route('computers.history', $this->computerA))
        ->assertOk();
});

test('kepala_lab cannot view history of computer in another lab', function () {
    $this->actingAs($this->kepalaLabA)
        ->get(route('computers.history', $this->computerB))
        ->assertForbidden();
});
