<?php

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\LicenseInventory;
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

    $this->laboratory = Laboratory::factory()->create([
        'faculty_id' => $this->faculty->id,
        'name' => 'Lab Sistem Cerdas',
        'code' => 'LAB-SC',
    ]);

    $this->pimpinan = User::factory()->create([
        'name' => 'Dekan Fakultas',
        'email' => 'dekan.golden@usn.ac.id',
        'password' => Hash::make('PimpinanGolden2026!'),
    ]);
    $this->pimpinan->assignRole('pimpinan');

    $this->computer = Computer::factory()->create([
        'laboratory_id' => $this->laboratory->id,
        'hostname' => 'PC-PIMPINAN-TEST',
        'os_license_status' => 'Licensed',
    ]);

    $this->catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'MATLAB Executive Edition',
        'category' => 'Commercial',
        'status' => 'Whitelist',
    ]);

    $this->license = LicenseInventory::factory()->create([
        'catalog_id' => $this->catalog->id,
        'quota_limit' => 20,
    ]);

    $currentPeriod = now()->format('Y-m');

    $this->approval = ReportApproval::factory()->approved()->create([
        'laboratory_id' => $this->laboratory->id,
        'report_type' => 'kepatuhan',
        'period' => $currentPeriod,
        'notes' => 'Laporan telah diverifikasi oleh Kepala Lab.',
    ]);
});

describe('Pimpinan Authentication & Session Flow', function () {
    test('pimpinan can login with valid credentials and redirect to dashboard', function () {
        $response = $this->post(route('login'), [
            'email' => 'dekan.golden@usn.ac.id',
            'password' => 'PimpinanGolden2026!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->pimpinan);
    });

    test('pimpinan can change own password', function () {
        $response = $this->actingAs($this->pimpinan)
            ->put(route('account.change-password'), [
                'current_password' => 'PimpinanGolden2026!',
                'password' => 'NewPimpinanPass2026!',
                'password_confirmation' => 'NewPimpinanPass2026!',
            ]);

        $response->assertSessionHas('status', 'success');
        $this->assertTrue(Hash::check('NewPimpinanPass2026!', $this->pimpinan->fresh()->password));
    });

    test('pimpinan can logout and redirect to login page', function () {
        $response = $this->actingAs($this->pimpinan)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });
});

describe('Pimpinan Dashboard & Analytics Flow', function () {
    test('pimpinan can view executive dashboard with cross-faculty summaries', function () {
        $response = $this->actingAs($this->pimpinan)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.pimpinan');
        $response->assertViewHas(['stats', 'globalStats', 'facultyMatrix', 'topDeficitSoftwares', 'approvedLabIds']);
    });

    test('pimpinan can fetch chart trend data scoped to approved laboratories', function () {
        $response = $this->actingAs($this->pimpinan)->getJson(route('dashboard.chart-data', ['period' => '7d']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'scan_trend' => [
                'labels',
                'completed',
                'failed',
            ],
            'compliance_trend' => [
                'labels',
                'berlisensi',
                'tidak_berlisensi',
                'grace_period',
                'perlu_ditinjau',
            ],
        ]);
    });
});

describe('Pimpinan Read-Only Asset & Compliance Inspection Flow', function () {
    test('pimpinan can inspect computers, software catalog, and licenses', function () {
        // 1. Computers list & detail
        $this->actingAs($this->pimpinan)
            ->get(route('computers'))
            ->assertStatus(200)
            ->assertSee($this->computer->hostname);

        $this->actingAs($this->pimpinan)
            ->get(route('computers.show', $this->computer))
            ->assertStatus(200)
            ->assertSee($this->computer->hostname);

        $this->actingAs($this->pimpinan)
            ->get(route('computers.history', $this->computer))
            ->assertStatus(200);

        // 2. Softwares list
        $this->actingAs($this->pimpinan)
            ->get(route('softwares'))
            ->assertStatus(200)
            ->assertSee($this->catalog->normalized_name);

        // 3. Licenses list & detail
        $this->actingAs($this->pimpinan)
            ->get(route('licenses'))
            ->assertStatus(200);

        $this->actingAs($this->pimpinan)
            ->get(route('licenses.show', $this->license))
            ->assertStatus(200);
    });

    test('pimpinan can inspect compliance and monitoring recap', function () {
        $scanSession = ScanSession::factory()->create([
            'computer_id' => $this->computer->id,
            'status' => 'completed',
            'started_at' => now()->subDay(),
            'completed_at' => now(),
        ]);

        ComplianceSnapshot::create([
            'scan_session_id' => $scanSession->id,
            'computer_id' => $this->computer->id,
            'status' => 'Berlisensi',
            'software_name' => 'MATLAB Executive Edition',
            'scanned_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);

        // Compliance overview
        $this->actingAs($this->pimpinan)->get(route('compliance'))->assertStatus(200);

        // Monitoring list, detail, changes, and compliance
        $this->actingAs($this->pimpinan)->get(route('monitoring.index'))->assertStatus(200);
        $this->actingAs($this->pimpinan)->get(route('monitoring.show', $scanSession))->assertStatus(200);
        $this->actingAs($this->pimpinan)->get(route('monitoring.changes'))->assertStatus(200);
        $this->actingAs($this->pimpinan)->get(route('monitoring.compliance'))->assertStatus(200);
    });
});

describe('Pimpinan Reports & Executive Export Flow', function () {
    test('pimpinan can view and export executive and compliance reports', function () {
        // Reports hub
        $this->actingAs($this->pimpinan)->get(route('reports'))->assertStatus(200);

        // Eksekutif view & export
        $this->actingAs($this->pimpinan)->get(route('reports.eksekutif'))->assertStatus(200);
        $this->actingAs($this->pimpinan)->get(route('reports.eksekutif.export'))->assertStatus(200);

        // Komputer view
        $this->actingAs($this->pimpinan)->get(route('reports.komputer'))->assertStatus(200);

        // Software view
        $this->actingAs($this->pimpinan)->get(route('reports.software'))->assertStatus(200);

        // Kepatuhan view
        $this->actingAs($this->pimpinan)->get(route('reports.kepatuhan'))->assertStatus(200);

        // Lisensi view
        $this->actingAs($this->pimpinan)->get(route('reports.lisensi'))->assertStatus(200);

        // Monitoring view
        $this->actingAs($this->pimpinan)->get(route('reports.monitoring'))->assertStatus(200);

        // Perubahan view
        $this->actingAs($this->pimpinan)->get(route('reports.perubahan'))->assertStatus(200);

        // Kebutuhan Lisensi view & export
        $this->actingAs($this->pimpinan)->get(route('reports.kebutuhan-lisensi'))->assertStatus(200);
        $this->actingAs($this->pimpinan)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'excel']))->assertStatus(200);
    });
});

describe('Pimpinan RBAC Boundaries (Forbidden Operations)', function () {
    test('pimpinan cannot mutate computers or trigger scans', function () {
        // Update computer
        $this->actingAs($this->pimpinan)
            ->put(route('computers.update', $this->computer), [
                'location' => 'Illegal Update',
            ])
            ->assertStatus(403);

        // Request scan
        $this->actingAs($this->pimpinan)
            ->post(route('computers.request-scan', $this->computer))
            ->assertStatus(403);

        // Request scan all
        $this->actingAs($this->pimpinan)
            ->post(route('computers.request-scan-all'))
            ->assertStatus(403);

        // Delete computer
        $this->actingAs($this->pimpinan)
            ->delete(route('computers.destroy', $this->computer))
            ->assertStatus(403);
    });

    test('pimpinan cannot mutate software catalog or licenses', function () {
        // Update software catalog
        $this->actingAs($this->pimpinan)
            ->put(route('softwares.update', $this->catalog), [
                'category' => 'Commercial',
                'status' => 'Blacklist',
            ])
            ->assertStatus(403);

        // Create license
        $this->actingAs($this->pimpinan)
            ->post(route('licenses.store'), [
                'catalog_id' => $this->catalog->id,
                'quota_limit' => 10,
            ])
            ->assertStatus(403);

        // Update license
        $this->actingAs($this->pimpinan)
            ->put(route('licenses.update', $this->license), [
                'quota_limit' => 50,
            ])
            ->assertStatus(403);

        // Delete license
        $this->actingAs($this->pimpinan)
            ->delete(route('licenses.destroy', $this->license))
            ->assertStatus(403);

        // Reveal decrypted key
        $this->actingAs($this->pimpinan)
            ->postJson(route('licenses.key', $this->license))
            ->assertStatus(403);
    });

    test('pimpinan cannot access admin management endpoints', function () {
        // Accounts CRUD
        $this->actingAs($this->pimpinan)->get(route('accounts'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->post(route('accounts.store'), [])->assertStatus(403);

        // Faculties CRUD
        $this->actingAs($this->pimpinan)->get(route('faculties.index'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->get(route('faculties.create'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->post(route('faculties.store'), [])->assertStatus(403);

        // Laboratories CRUD
        $this->actingAs($this->pimpinan)->get(route('laboratories.index'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->get(route('laboratories.create'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->post(route('laboratories.store'), [])->assertStatus(403);

        // License Allocations CRUD
        $this->actingAs($this->pimpinan)->get(route('license-allocations.index'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->get(route('license-allocations.create'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->post(route('license-allocations.store'), [])->assertStatus(403);

        // Report Submissions
        $this->actingAs($this->pimpinan)->get(route('report-submissions.index'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->post(route('report-submissions.submit'), [])->assertStatus(403);

        // Activity Logs
        $this->actingAs($this->pimpinan)->get(route('activity-logs'))->assertStatus(403);

        // Agent Download
        $this->actingAs($this->pimpinan)->get(route('agent.download-page'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->post(route('agent.download'), [])->assertStatus(403);

        // Run Compliance Scan
        $this->actingAs($this->pimpinan)->post(route('reports.kepatuhan.scan'))->assertStatus(403);
    });

    test('pimpinan cannot access lab inventory or report approvals', function () {
        $this->actingAs($this->pimpinan)->get(route('lab.inventory.index'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->get(route('lab.reports.index'))->assertStatus(403);
        $this->actingAs($this->pimpinan)->post(route('lab.reports.approve', $this->approval))->assertStatus(403);
        $this->actingAs($this->pimpinan)->post(route('lab.reports.reject', $this->approval))->assertStatus(403);
    });
});
