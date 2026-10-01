<?php

use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->faculty = Faculty::factory()->create([
        'name' => 'Fakultas Teknologi Informasi',
        'code' => 'FTI',
    ]);

    $this->labStaff = Laboratory::factory()->create([
        'faculty_id' => $this->faculty->id,
        'name' => 'Lab Komputer Dasar',
        'code' => 'LAB-KD',
    ]);

    $this->labOther = Laboratory::factory()->create([
        'faculty_id' => $this->faculty->id,
        'name' => 'Lab Riset Lanjut',
        'code' => 'LAB-RL',
    ]);

    $this->staffLab = User::factory()->create([
        'name' => 'Teknisi Lab Dasar',
        'email' => 'stafflab.golden@usn.ac.id',
        'password' => Hash::make('StaffLabGolden2026!'),
        'laboratory_id' => $this->labStaff->id,
    ]);
    $this->staffLab->assignRole('staff_lab');

    $this->compStaff = Computer::factory()->create([
        'laboratory_id' => $this->labStaff->id,
        'hostname' => 'PC-STAFF-01',
    ]);

    $this->compOther = Computer::factory()->create([
        'laboratory_id' => $this->labOther->id,
        'hostname' => 'PC-OTHER-01',
    ]);

    $this->approval = ReportApproval::factory()->create([
        'laboratory_id' => $this->labStaff->id,
        'report_type' => 'kepatuhan',
        'period' => now()->format('Y-m'),
        'status' => 'pending',
    ]);
});

describe('Staff Lab Authentication & Session Flow', function () {
    test('staff_lab can login with valid credentials and is redirected to lab inventory', function () {
        $response = $this->post(route('login'), [
            'email' => 'stafflab.golden@usn.ac.id',
            'password' => 'StaffLabGolden2026!',
        ]);

        // Login redirects to dashboard, which redirects to lab inventory
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->staffLab);

        $dashboardResponse = $this->actingAs($this->staffLab)->get(route('dashboard'));
        $dashboardResponse->assertRedirect(route('lab.inventory.index'));
    });

    test('staff_lab can change own password', function () {
        $response = $this->actingAs($this->staffLab)
            ->put(route('account.change-password'), [
                'current_password' => 'StaffLabGolden2026!',
                'password' => 'NewStaffPass2026!',
                'password_confirmation' => 'NewStaffPass2026!',
            ]);

        $response->assertSessionHas('status', 'success');
        $this->assertTrue(Hash::check('NewStaffPass2026!', $this->staffLab->fresh()->password));
    });

    test('staff_lab can logout and redirect to login page', function () {
        $response = $this->actingAs($this->staffLab)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });
});

describe('Staff Lab Inventory Flow', function () {
    test('staff_lab can view inventory of assigned lab and inspect computer detail', function () {
        // 1. Inventory index
        $response = $this->actingAs($this->staffLab)->get(route('lab.inventory.index'));
        $response->assertStatus(200);
        $response->assertSee($this->compStaff->hostname);
        $response->assertDontSee($this->compOther->hostname);

        // 2. View detail of assigned lab computer
        $this->actingAs($this->staffLab)
            ->get(route('lab.inventory.show', $this->compStaff))
            ->assertStatus(200)
            ->assertSee($this->compStaff->hostname);

        // 3. Forbidden from viewing computer of another laboratory
        $this->actingAs($this->staffLab)
            ->get(route('lab.inventory.show', $this->compOther))
            ->assertStatus(403);
    });
});

describe('Staff Lab Agent Scanner Flow', function () {
    test('staff_lab can download scanner for assigned lab only', function () {
        // Download page shows only assigned laboratory
        $response = $this->actingAs($this->staffLab)->get(route('agent.download-page'));
        $response->assertStatus(200);
        $response->assertSee($this->labStaff->name);
        $response->assertDontSee($this->labOther->name);

        // Download agent scanner ZIP for assigned lab
        $this->actingAs($this->staffLab)
            ->post(route('agent.download'), [
                'laboratory_id' => $this->labStaff->id,
            ])
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/zip');

        // Forbidden from downloading for other laboratory
        $this->actingAs($this->staffLab)
            ->post(route('agent.download'), [
                'laboratory_id' => $this->labOther->id,
            ])
            ->assertStatus(403);
    });
});

describe('Staff Lab RBAC Boundaries (Forbidden Endpoints)', function () {
    test('staff_lab cannot access global computer or software endpoints', function () {
        $this->actingAs($this->staffLab)->get(route('computers'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('computers.show', $this->compStaff))->assertStatus(403);
        $this->actingAs($this->staffLab)->put(route('computers.update', $this->compStaff), ['location' => 'test'])->assertStatus(403);
        $this->actingAs($this->staffLab)->delete(route('computers.destroy', $this->compStaff))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('softwares'))->assertStatus(403);
    });

    test('staff_lab cannot access licenses or allocations', function () {
        $this->actingAs($this->staffLab)->get(route('licenses'))->assertStatus(403);
        $this->actingAs($this->staffLab)->post(route('licenses.store'), [])->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('license-allocations.index'))->assertStatus(403);
        $this->actingAs($this->staffLab)->post(route('license-allocations.store'), [])->assertStatus(403);
    });

    test('staff_lab cannot access monitoring, compliance, or reports', function () {
        $this->actingAs($this->staffLab)->get(route('monitoring.index'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('monitoring.changes'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('monitoring.compliance'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('compliance'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('reports'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('reports.monitoring'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('reports.eksekutif'))->assertStatus(403);
    });

    test('staff_lab cannot access report approvals or submissions', function () {
        $this->actingAs($this->staffLab)->get(route('lab.reports.index'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('lab.reports.show', $this->approval))->assertStatus(403);
        $this->actingAs($this->staffLab)->post(route('lab.reports.approve', $this->approval))->assertStatus(403);
        $this->actingAs($this->staffLab)->post(route('lab.reports.reject', $this->approval))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('report-submissions.index'))->assertStatus(403);
    });

    test('staff_lab cannot access accounts, faculties, laboratories, or activity logs', function () {
        $this->actingAs($this->staffLab)->get(route('accounts'))->assertStatus(403);
        $this->actingAs($this->staffLab)->post(route('accounts.store'), [])->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('faculties.index'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('laboratories.index'))->assertStatus(403);
        $this->actingAs($this->staffLab)->get(route('activity-logs'))->assertStatus(403);
    });
});
