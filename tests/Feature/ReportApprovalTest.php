<?php

use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('kepala_lab can view report approval list', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    ReportApproval::factory()->count(2)->create([
        'laboratory_id' => $lab->id,
    ]);

    $response = $this->actingAs($kepalaLab)->get(route('lab.reports.index'));

    $response->assertStatus(200);
    $response->assertViewIs('report-approvals.index');
    $response->assertViewHas('approvals');
});

test('kepala_lab only sees own lab reports', function () {
    $lab1 = Laboratory::factory()->create(['name' => 'Lab Fisika']);
    $lab2 = Laboratory::factory()->create(['name' => 'Lab Kimia']);

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report1 = ReportApproval::factory()->create([
        'laboratory_id' => $lab1->id,
        'period' => '2026-05',
    ]);

    $report2 = ReportApproval::factory()->create([
        'laboratory_id' => $lab2->id,
        'period' => '2026-06',
    ]);

    $response = $this->actingAs($kepalaLab)->get(route('lab.reports.index'));

    $response->assertStatus(200);
    $response->assertSee('2026-05');
    $response->assertDontSee('2026-06');
});

test('kepala_lab can approve pending report', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report = ReportApproval::factory()->create([
        'laboratory_id' => $lab->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($kepalaLab)->post(route('lab.reports.approve', $report), [
        'notes' => 'Semua data telah diverifikasi dan sesuai.',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('lab.reports.index'));
    $response->assertSessionHas('status', 'success');

    $report->refresh();
    expect($report->status)->toBe('approved')
        ->and($report->reviewed_by)->toBe($kepalaLab->id)
        ->and($report->reviewed_at)->not->toBeNull()
        ->and($report->notes)->toBe('Semua data telah diverifikasi dan sesuai.');
});

test('approval sets status and reviewed_at with optional notes', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report = ReportApproval::factory()->create([
        'laboratory_id' => $lab->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($kepalaLab)->post(route('lab.reports.approve', $report), [
        'notes' => null,
    ]);

    $response->assertSessionHasNoErrors();
    $report->refresh();
    expect($report->status)->toBe('approved')
        ->and($report->notes)->toBeNull()
        ->and($report->reviewed_at)->not->toBeNull();
});

test('kepala_lab can reject pending report with notes', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report = ReportApproval::factory()->create([
        'laboratory_id' => $lab->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($kepalaLab)->post(route('lab.reports.reject', $report), [
        'notes' => 'Terdapat 3 PC yang belum terinstal antivirus resmi.',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('lab.reports.index'));

    $report->refresh();
    expect($report->status)->toBe('rejected')
        ->and($report->reviewed_by)->toBe($kepalaLab->id)
        ->and($report->reviewed_at)->not->toBeNull()
        ->and($report->notes)->toBe('Terdapat 3 PC yang belum terinstal antivirus resmi.');
});

test('rejection requires notes', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report = ReportApproval::factory()->create([
        'laboratory_id' => $lab->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($kepalaLab)->post(route('lab.reports.reject', $report), [
        'notes' => '',
    ]);

    $response->assertSessionHasErrors('notes');
    expect($report->fresh()->status)->toBe('pending');
});

test('cannot approve already approved report', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report = ReportApproval::factory()->approved()->create([
        'laboratory_id' => $lab->id,
    ]);

    $response = $this->actingAs($kepalaLab)->post(route('lab.reports.approve', $report), [
        'notes' => 'Attempt re-approve',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'destructive');
});

test('cannot reject already rejected report', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report = ReportApproval::factory()->rejected()->create([
        'laboratory_id' => $lab->id,
    ]);

    $response = $this->actingAs($kepalaLab)->post(route('lab.reports.reject', $report), [
        'notes' => 'Attempt re-reject',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'destructive');
});

test('cannot approve report from other lab', function () {
    $lab1 = Laboratory::factory()->create();
    $lab2 = Laboratory::factory()->create();

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report2 = ReportApproval::factory()->create([
        'laboratory_id' => $lab2->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($kepalaLab)->post(route('lab.reports.approve', $report2), [
        'notes' => 'Approve other lab',
    ]);

    $response->assertStatus(403);
});

test('preview pdf streams report for own lab', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report = ReportApproval::factory()->create([
        'laboratory_id' => $lab->id,
    ]);

    $response = $this->actingAs($kepalaLab)->get(route('lab.reports.preview-pdf', $report));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('preview pdf denies access to other lab', function () {
    $lab1 = Laboratory::factory()->create();
    $lab2 = Laboratory::factory()->create();

    $kepalaLab = User::factory()->create(['laboratory_id' => $lab1->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report2 = ReportApproval::factory()->create([
        'laboratory_id' => $lab2->id,
    ]);

    $response = $this->actingAs($kepalaLab)->get(route('lab.reports.preview-pdf', $report2));

    $response->assertStatus(403);
});

test('approval is logged in activity log', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report = ReportApproval::factory()->create([
        'laboratory_id' => $lab->id,
        'status' => 'pending',
    ]);

    $this->actingAs($kepalaLab)->post(route('lab.reports.approve', $report), [
        'notes' => 'OK',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $kepalaLab->id,
        'causer_type' => User::class,
    ]);
});

test('rejection is logged in activity log', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $report = ReportApproval::factory()->create([
        'laboratory_id' => $lab->id,
        'status' => 'pending',
    ]);

    $this->actingAs($kepalaLab)->post(route('lab.reports.reject', $report), [
        'notes' => 'Ada kekurangan',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $kepalaLab->id,
        'causer_type' => User::class,
    ]);
});
