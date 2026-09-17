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

test('admin can view report submission page', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Laboratory::factory()->count(2)->create();

    $response = $this->actingAs($admin)->get(route('report-submissions.index'));

    $response->assertStatus(200);
    $response->assertViewIs('report-submissions.index');
    $response->assertViewHas(['laboratories', 'period']);
});

test('admin can submit report to lab', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();
    $pjLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $pjLab->assignRole('kepala_lab');

    $period = now()->format('Y-m');

    $response = $this->actingAs($admin)->post(route('report-submissions.submit'), [
        'laboratory_id' => $lab->id,
        'period' => $period,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $response->assertSessionHas('status', 'success');

    $this->assertDatabaseHas('report_approvals', [
        'laboratory_id' => $lab->id,
        'report_type' => 'kepatuhan',
        'period' => $period,
        'status' => 'pending',
        'reviewed_by' => $pjLab->id,
    ]);
});

test('admin cannot submit report if pending exists', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();
    $period = now()->format('Y-m');

    ReportApproval::factory()->create([
        'laboratory_id' => $lab->id,
        'report_type' => 'kepatuhan',
        'period' => $period,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($admin)->post(route('report-submissions.submit'), [
        'laboratory_id' => $lab->id,
        'period' => $period,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'destructive');

    expect(ReportApproval::where('laboratory_id', $lab->id)->count())->toBe(1);
});

test('admin can resubmit report after approval', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();
    $period = now()->format('Y-m');

    ReportApproval::factory()->approved()->create([
        'laboratory_id' => $lab->id,
        'report_type' => 'kepatuhan',
        'period' => $period,
    ]);

    $response = $this->actingAs($admin)->post(route('report-submissions.submit'), [
        'laboratory_id' => $lab->id,
        'period' => $period,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $response->assertSessionHas('status', 'success');

    expect(ReportApproval::where('laboratory_id', $lab->id)->count())->toBe(2);
    expect(ReportApproval::where('laboratory_id', $lab->id)->where('status', 'pending')->count())->toBe(1);
});

test('admin can resubmit report after rejection', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();
    $period = now()->format('Y-m');

    ReportApproval::factory()->rejected()->create([
        'laboratory_id' => $lab->id,
        'report_type' => 'kepatuhan',
        'period' => $period,
    ]);

    $response = $this->actingAs($admin)->post(route('report-submissions.submit'), [
        'laboratory_id' => $lab->id,
        'period' => $period,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
    $response->assertSessionHas('status', 'success');

    expect(ReportApproval::where('laboratory_id', $lab->id)->count())->toBe(2);
    expect(ReportApproval::where('laboratory_id', $lab->id)->where('status', 'pending')->count())->toBe(1);
});

test('kepala_lab cannot access report submission', function () {
    $lab = Laboratory::factory()->create();
    $kepalaLab = User::factory()->create(['laboratory_id' => $lab->id]);
    $kepalaLab->assignRole('kepala_lab');

    $this->actingAs($kepalaLab)->get(route('report-submissions.index'))->assertStatus(403);
    $this->actingAs($kepalaLab)->post(route('report-submissions.submit'), [
        'laboratory_id' => $lab->id,
        'period' => now()->format('Y-m'),
    ])->assertStatus(403);
});

test('pimpinan cannot access report submission', function () {
    $pimpinan = User::factory()->create();
    $pimpinan->assignRole('pimpinan');

    $lab = Laboratory::factory()->create();

    $this->actingAs($pimpinan)->get(route('report-submissions.index'))->assertStatus(403);
    $this->actingAs($pimpinan)->post(route('report-submissions.submit'), [
        'laboratory_id' => $lab->id,
        'period' => now()->format('Y-m'),
    ])->assertStatus(403);
});

test('requires valid laboratory_id', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post(route('report-submissions.submit'), [
        'laboratory_id' => 99999,
        'period' => now()->format('Y-m'),
    ]);

    $response->assertSessionHasErrors('laboratory_id');
});

test('requires valid period format', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();

    $response = $this->actingAs($admin)->post(route('report-submissions.submit'), [
        'laboratory_id' => $lab->id,
        'period' => 'invalid-period',
    ]);

    $response->assertSessionHasErrors('period');
});

test('submission is logged in activity log', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lab = Laboratory::factory()->create();
    $period = now()->format('Y-m');

    $this->actingAs($admin)->post(route('report-submissions.submit'), [
        'laboratory_id' => $lab->id,
        'period' => $period,
    ]);

    $this->assertDatabaseHas('activity_log', [
        'causer_id' => $admin->id,
        'causer_type' => User::class,
    ]);
});
