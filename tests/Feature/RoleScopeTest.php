<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->labA = Laboratory::factory()->create(['name' => 'Lab Komputasi A', 'code' => 'LKA']);
    $this->labB = Laboratory::factory()->create(['name' => 'Lab Komputasi B', 'code' => 'LKB']);

    $this->kepalaLabA = User::factory()->create(['laboratory_id' => $this->labA->id]);
    $this->kepalaLabA->assignRole('kepala_lab');

    $this->kepalaLabB = User::factory()->create(['laboratory_id' => $this->labB->id]);
    $this->kepalaLabB->assignRole('kepala_lab');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole('pimpinan');
});

test('kepala_lab tidak bisa mengakses halaman manajemen akun admin', function () {
    $this->actingAs($this->kepalaLabA)
        ->get(route('accounts'))
        ->assertStatus(403);
});

test('kepala_lab tidak bisa mengakses halaman CRUD laboratorium admin', function () {
    $this->actingAs($this->kepalaLabA)
        ->get(route('laboratories.create'))
        ->assertStatus(403);
});

test('kepala_lab dapat mengakses halaman unduh agent scanner untuk laboratoriumnya sendiri', function () {
    $this->actingAs($this->kepalaLabA)
        ->get(route('agent.download-page'))
        ->assertStatus(200)
        ->assertSee($this->labA->name)
        ->assertDontSee($this->labB->name);
});

test('pimpinan tidak bisa melakukan mutasi data lisensi', function () {
    $catalog = SoftwareCatalog::factory()->create();

    $this->actingAs($this->pimpinan)
        ->post(route('licenses.store'), [
            'catalog_id' => $catalog->id,
            'license_type' => 'OEM',
            'quota_limit' => 10,
        ])
        ->assertStatus(403);
});

test('pimpinan tidak bisa melakukan mutasi data komputer', function () {
    $computer = Computer::factory()->create(['laboratory_id' => $this->labA->id]);

    $this->actingAs($this->pimpinan)
        ->put(route('computers.update', $computer), [
            'hostname' => 'PC-TAMPERED',
        ])
        ->assertStatus(403);

    $this->actingAs($this->pimpinan)
        ->delete(route('computers.destroy', $computer))
        ->assertStatus(403);
});

test('pimpinan tidak bisa membuat akun pengguna baru', function () {
    $this->actingAs($this->pimpinan)
        ->post(route('accounts.store'), [
            'name' => 'New User',
            'email' => 'newuser@usn.ac.id',
            'role' => 'kepala_lab',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertStatus(403);
});

test('kepala_lab dapat menyetujui laporan kepatuhan laboratoriumnya', function () {
    $approval = ReportApproval::factory()->create([
        'laboratory_id' => $this->labA->id,
        'report_type' => 'kepatuhan',
        'period' => now()->format('Y-m'),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->kepalaLabA)
        ->post(route('lab.reports.approve', $approval), [
            'notes' => 'Disetujui oleh Kepala Lab A',
        ]);

    $response->assertRedirect(route('lab.reports.index'));
    expect($approval->fresh()->status)->toBe('approved')
        ->and($approval->fresh()->reviewed_by)->toBe($this->kepalaLabA->id);
});

test('kepala_lab tidak bisa menyetujui laporan kepatuhan laboratorium lain', function () {
    $approvalB = ReportApproval::factory()->create([
        'laboratory_id' => $this->labB->id,
        'report_type' => 'kepatuhan',
        'period' => now()->format('Y-m'),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->kepalaLabA)
        ->post(route('lab.reports.approve', $approvalB), [
            'notes' => 'Percobaan menyetujui lab lain',
        ]);

    $response->assertStatus(403);
    expect($approvalB->fresh()->status)->toBe('pending');
});

test('kepala_lab tidak bisa menolak laporan kepatuhan laboratorium lain', function () {
    $approvalB = ReportApproval::factory()->create([
        'laboratory_id' => $this->labB->id,
        'report_type' => 'kepatuhan',
        'period' => now()->format('Y-m'),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->kepalaLabA)
        ->post(route('lab.reports.reject', $approvalB), [
            'notes' => 'Ditolak dari lab lain',
        ]);

    $response->assertStatus(403);
    expect($approvalB->fresh()->status)->toBe('pending');
});
