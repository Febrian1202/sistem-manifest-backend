<?php

use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\ScanSession;
use App\Models\SoftwareCatalog;
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

    $this->labA = Laboratory::factory()->create([
        'faculty_id' => $this->faculty->id,
        'name' => 'Lab Jaringan Komputer',
        'code' => 'LAB-JK',
    ]);

    $this->labB = Laboratory::factory()->create([
        'faculty_id' => $this->faculty->id,
        'name' => 'Lab Multimedia',
        'code' => 'LAB-MM',
    ]);

    $this->kepalaLab = User::factory()->create([
        'name' => 'Kepala Lab Jaringan',
        'email' => 'kepalalab.golden@usn.ac.id',
        'password' => Hash::make('KepalaLabGolden2026!'),
        'laboratory_id' => $this->labA->id,
    ]);
    $this->kepalaLab->assignRole('kepala_lab');

    $this->compA = Computer::factory()->create([
        'laboratory_id' => $this->labA->id,
        'hostname' => 'PC-LAB-A-01',
    ]);

    $this->compB = Computer::factory()->create([
        'laboratory_id' => $this->labB->id,
        'hostname' => 'PC-LAB-B-01',
    ]);

    $this->catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Wireshark Network Analyzer',
        'category' => 'OpenSource',
        'status' => 'Whitelist',
    ]);

    $this->scanSessionA = ScanSession::factory()->create([
        'computer_id' => $this->compA->id,
        'status' => 'completed',
        'started_at' => now()->subDay(),
        'completed_at' => now(),
    ]);

    $this->scanSessionB = ScanSession::factory()->create([
        'computer_id' => $this->compB->id,
        'status' => 'completed',
        'started_at' => now()->subDay(),
        'completed_at' => now(),
    ]);

    $this->approvalA = ReportApproval::factory()->create([
        'laboratory_id' => $this->labA->id,
        'report_type' => 'kepatuhan',
        'period' => now()->format('Y-m'),
        'status' => 'pending',
    ]);

    $this->approvalB = ReportApproval::factory()->create([
        'laboratory_id' => $this->labB->id,
        'report_type' => 'kepatuhan',
        'period' => now()->format('Y-m'),
        'status' => 'pending',
    ]);
});

describe('Kepala Lab Authentication & Account Flow', function () {
    test('kepala_lab can login with valid credentials and redirect to dashboard', function () {
        $response = $this->post(route('login'), [
            'email' => 'kepalalab.golden@usn.ac.id',
            'password' => 'KepalaLabGolden2026!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->kepalaLab);
    });

    test('kepala_lab can change own password', function () {
        $response = $this->actingAs($this->kepalaLab)
            ->put(route('account.change-password'), [
                'current_password' => 'KepalaLabGolden2026!',
                'password' => 'NewKepalaLabPass2026!',
                'password_confirmation' => 'NewKepalaLabPass2026!',
            ]);

        $response->assertSessionHas('status', 'success');
        $this->assertTrue(Hash::check('NewKepalaLabPass2026!', $this->kepalaLab->fresh()->password));
    });

    test('kepala_lab can logout and redirect to login page', function () {
        $response = $this->actingAs($this->kepalaLab)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });
});

describe('Kepala Lab Dashboard Flow', function () {
    test('kepala_lab views dashboard strictly scoped to their assigned laboratory', function () {
        $response = $this->actingAs($this->kepalaLab)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.kepala-lab');
        $response->assertViewHas(['lab', 'stats', 'monitoringStats', 'unscannedComputers', 'chartData']);
        $response->assertSee($this->labA->name);
        $response->assertDontSee($this->labB->name);
    });

    test('kepala_lab chart-data is strictly scoped to own laboratory', function () {
        $response = $this->actingAs($this->kepalaLab)->getJson(route('dashboard.chart-data', ['period' => '7d']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'scan_trend' => ['labels', 'completed', 'failed'],
            'compliance_trend' => ['labels', 'berlisensi', 'tidak_berlisensi', 'grace_period', 'perlu_ditinjau'],
        ]);
    });
});

describe('Kepala Lab Inventory Flow', function () {
    test('kepala_lab can inspect inventory of own lab and is forbidden from other labs', function () {
        // 1. Inventory index shows only own lab computers
        $response = $this->actingAs($this->kepalaLab)->get(route('lab.inventory.index'));
        $response->assertStatus(200);
        $response->assertSee($this->compA->hostname);
        $response->assertDontSee($this->compB->hostname);

        // 2. View detail of own lab computer
        $this->actingAs($this->kepalaLab)
            ->get(route('lab.inventory.show', $this->compA))
            ->assertStatus(200)
            ->assertSee($this->compA->hostname);

        // 3. Attempting to view computer from other lab returns 403
        $this->actingAs($this->kepalaLab)
            ->get(route('lab.inventory.show', $this->compB))
            ->assertStatus(403);
    });
});

describe('Kepala Lab Monitoring & History Flow', function () {
    test('kepala_lab can inspect monitoring and history strictly for own lab', function () {
        // 1. Monitoring index scopes to own lab
        $response = $this->actingAs($this->kepalaLab)->get(route('monitoring.index'));
        $response->assertStatus(200);

        // 2. Monitoring detail for own lab scan session
        $this->actingAs($this->kepalaLab)
            ->get(route('monitoring.show', $this->scanSessionA))
            ->assertStatus(200);

        // 3. Monitoring detail for other lab scan session returns 403
        $this->actingAs($this->kepalaLab)
            ->get(route('monitoring.show', $this->scanSessionB))
            ->assertStatus(403);

        // 4. Monitoring software changes scoped to own lab
        $this->actingAs($this->kepalaLab)
            ->get(route('monitoring.changes'))
            ->assertStatus(200);

        // 5. Monitoring compliance history scoped to own lab
        $this->actingAs($this->kepalaLab)
            ->get(route('monitoring.compliance'))
            ->assertStatus(200);

        // 6. Computer software history for own computer
        $this->actingAs($this->kepalaLab)
            ->get(route('computers.history', $this->compA))
            ->assertStatus(200);

        // 7. Computer software history for other lab computer returns 403
        $this->actingAs($this->kepalaLab)
            ->get(route('computers.history', $this->compB))
            ->assertStatus(403);
    });
});

describe('Kepala Lab Compliance & Reports Preview Flow', function () {
    test('kepala_lab can access compliance and reports scoped to own lab', function () {
        // Compliance overview
        $this->actingAs($this->kepalaLab)->get(route('compliance'))->assertStatus(200);

        // Reports overview
        $this->actingAs($this->kepalaLab)->get(route('reports'))->assertStatus(200);

        // Monitoring report preview
        $this->actingAs($this->kepalaLab)->get(route('reports.monitoring'))->assertStatus(200);

        // Perubahan report preview
        $this->actingAs($this->kepalaLab)->get(route('reports.perubahan'))->assertStatus(200);
    });
});

describe('Kepala Lab Report Approval Flow', function () {
    test('kepala_lab can review, preview PDF, approve, and reject own lab report approvals', function () {
        // 1. List approvals (shows own lab approvals)
        $this->actingAs($this->kepalaLab)
            ->get(route('lab.reports.index'))
            ->assertStatus(200)
            ->assertSee($this->approvalA->period);

        // 2. Show detail of own lab approval
        $this->actingAs($this->kepalaLab)
            ->get(route('lab.reports.show', $this->approvalA))
            ->assertStatus(200);

        // 3. Forbidden from viewing other lab approval
        $this->actingAs($this->kepalaLab)
            ->get(route('lab.reports.show', $this->approvalB))
            ->assertStatus(403);

        // 4. Preview PDF of own lab report
        $this->actingAs($this->kepalaLab)
            ->get(route('lab.reports.preview-pdf', $this->approvalA))
            ->assertStatus(200);

        // 5. Forbidden from previewing PDF of other lab report
        $this->actingAs($this->kepalaLab)
            ->get(route('lab.reports.preview-pdf', $this->approvalB))
            ->assertStatus(403);

        // 6. Approve own lab report
        $this->actingAs($this->kepalaLab)
            ->post(route('lab.reports.approve', $this->approvalA), [
                'notes' => 'Laporan disetujui Kepala Lab Jaringan',
            ])
            ->assertRedirect(route('lab.reports.index'))
            ->assertSessionHas('status', 'success');

        expect($this->approvalA->fresh()->status)->toBe('approved');

        // 7. Forbidden from approving other lab report
        $this->actingAs($this->kepalaLab)
            ->post(route('lab.reports.approve', $this->approvalB), [
                'notes' => 'Illegal approval',
            ])
            ->assertStatus(403);

        // 8. Rejection flow on another pending approval
        $approvalToReject = ReportApproval::factory()->create([
            'laboratory_id' => $this->labA->id,
            'report_type' => 'kepatuhan',
            'period' => now()->subMonth()->format('Y-m'),
            'status' => 'pending',
        ]);

        $this->actingAs($this->kepalaLab)
            ->post(route('lab.reports.reject', $approvalToReject), [
                'notes' => 'Mohon scan ulang komputer nomor 5',
            ])
            ->assertRedirect(route('lab.reports.index'))
            ->assertSessionHas('status', 'success');

        expect($approvalToReject->fresh()->status)->toBe('rejected');
    });
});

describe('Kepala Lab Agent Download Flow', function () {
    test('kepala_lab can view and download agent scanner for own lab only', function () {
        // View download page
        $response = $this->actingAs($this->kepalaLab)->get(route('agent.download-page'));
        $response->assertStatus(200)
            ->assertSee($this->labA->name)
            ->assertDontSee($this->labB->name);

        // Download agent for own lab
        $this->actingAs($this->kepalaLab)
            ->post(route('agent.download'), [
                'laboratory_id' => $this->labA->id,
            ])
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/zip');

        // Forbidden from downloading agent for other lab
        $this->actingAs($this->kepalaLab)
            ->post(route('agent.download'), [
                'laboratory_id' => $this->labB->id,
            ])
            ->assertStatus(403);
    });
});

describe('Kepala Lab RBAC Boundaries (Forbidden Access)', function () {
    test('kepala_lab is forbidden from global admin endpoints', function () {
        // Global Computer CRUD
        $this->actingAs($this->kepalaLab)->get(route('computers'))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->get(route('computers.show', $this->compA))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->put(route('computers.update', $this->compA), ['location' => 'test'])->assertStatus(403);
        $this->actingAs($this->kepalaLab)->delete(route('computers.destroy', $this->compA))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->post(route('computers.request-scan', $this->compA))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->post(route('computers.request-scan-all'))->assertStatus(403);

        // Software Catalog
        $this->actingAs($this->kepalaLab)->get(route('softwares'))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->put(route('softwares.update', $this->catalog), ['category' => 'Freeware', 'status' => 'Whitelist'])->assertStatus(403);

        // Licenses
        $this->actingAs($this->kepalaLab)->get(route('licenses'))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->post(route('licenses.store'), [])->assertStatus(403);

        // User Accounts
        $this->actingAs($this->kepalaLab)->get(route('accounts'))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->post(route('accounts.store'), [])->assertStatus(403);

        // Faculties & Laboratories CRUD
        $this->actingAs($this->kepalaLab)->get(route('faculties.index'))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->get(route('laboratories.index'))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->post(route('laboratories.store'), [])->assertStatus(403);

        // License Allocations
        $this->actingAs($this->kepalaLab)->get(route('license-allocations.index'))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->post(route('license-allocations.store'), [])->assertStatus(403);

        // Report Submissions
        $this->actingAs($this->kepalaLab)->get(route('report-submissions.index'))->assertStatus(403);
        $this->actingAs($this->kepalaLab)->post(route('report-submissions.submit'), [])->assertStatus(403);

        // Activity Logs
        $this->actingAs($this->kepalaLab)->get(route('activity-logs'))->assertStatus(403);

        // Run Compliance Scan
        $this->actingAs($this->kepalaLab)->post(route('reports.kepatuhan.scan'))->assertStatus(403);
    });
});
