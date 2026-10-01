<?php

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\ReportApproval;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->faculty = Faculty::factory()->create([
        'name' => 'Fakultas Teknologi Informasi',
        'code' => 'FTI',
    ]);

    $this->laboratory = Laboratory::factory()->create([
        'faculty_id' => $this->faculty->id,
        'name' => 'Lab Software Engineering',
        'code' => 'LAB-SE',
    ]);

    $this->admin = User::factory()->create([
        'name' => 'Super Administrator',
        'email' => 'admin.golden@usn.ac.id',
        'password' => Hash::make('AdminGolden2026!'),
    ]);
    $this->admin->assignRole('admin');

    $this->computer = Computer::factory()->create([
        'laboratory_id' => $this->laboratory->id,
        'hostname' => 'PC-ADMIN-TEST',
    ]);

    $this->catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Visual Studio Pro',
        'category' => 'Commercial',
        'status' => 'Whitelist',
    ]);
});

describe('Admin Authentication & Account Flow', function () {
    test('admin can login with valid credentials and redirect to dashboard', function () {
        $response = $this->post(route('login'), [
            'email' => 'admin.golden@usn.ac.id',
            'password' => 'AdminGolden2026!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->admin);
    });

    test('admin can change own password', function () {
        $response = $this->actingAs($this->admin)
            ->put(route('account.change-password'), [
                'current_password' => 'AdminGolden2026!',
                'password' => 'NewAdminPass2026!',
                'password_confirmation' => 'NewAdminPass2026!',
            ]);

        $response->assertSessionHas('status', 'success');
        $this->assertTrue(Hash::check('NewAdminPass2026!', $this->admin->fresh()->password));
    });

    test('admin can logout and redirect to login page', function () {
        $response = $this->actingAs($this->admin)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    });
});

describe('Admin Dashboard Flow', function () {
    test('admin can view dashboard with full administrative statistics', function () {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.admin.dashboard');
        $response->assertViewHas(['totalComputers', 'totalInstallations', 'uniqueSoftwares', 'criticalAlerts', 'systemHealth']);
    });

    test('admin can fetch chart trend data', function () {
        $response = $this->actingAs($this->admin)->getJson(route('dashboard.chart-data', ['period' => '7d']));

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

describe('Admin Computer Management Flow', function () {
    test('admin can list, view, update, request scan, and delete computers', function () {
        Queue::fake();

        // 1. List computers
        $this->actingAs($this->admin)
            ->get(route('computers'))
            ->assertStatus(200)
            ->assertSee($this->computer->hostname);

        // 2. View computer detail
        $this->actingAs($this->admin)
            ->get(route('computers.show', $this->computer))
            ->assertStatus(200)
            ->assertSee($this->computer->hostname);

        // 3. View computer software history
        $this->actingAs($this->admin)
            ->get(route('computers.history', $this->computer))
            ->assertStatus(200);

        // 4. Update computer
        $this->actingAs($this->admin)
            ->from(route('computers'))
            ->put(route('computers.update', $this->computer), [
                'location' => 'Meja 01 - Ruang Lab SE',
            ])
            ->assertRedirect(route('computers'))
            ->assertSessionHas('status', 'success');

        expect($this->computer->fresh()->location)->toBe('Meja 01 - Ruang Lab SE');

        // 5. Request single computer scan
        $this->actingAs($this->admin)
            ->post(route('computers.request-scan', $this->computer))
            ->assertRedirect()
            ->assertSessionHas('status', 'success');

        // 6. Request scan for all computers
        $this->actingAs($this->admin)
            ->post(route('computers.request-scan-all'))
            ->assertRedirect()
            ->assertSessionHas('status', 'success');

        // 7. Delete computer
        $this->actingAs($this->admin)
            ->delete(route('computers.destroy', $this->computer))
            ->assertRedirect(route('computers'))
            ->assertSessionHas('status', 'success');

        expect(Computer::find($this->computer->id))->toBeNull();
    });
});

describe('Admin Software Catalog Flow', function () {
    test('admin can view software catalog and update software metadata', function () {
        $this->actingAs($this->admin)
            ->get(route('softwares'))
            ->assertStatus(200)
            ->assertSee($this->catalog->normalized_name);

        $this->actingAs($this->admin)
            ->from(route('softwares'))
            ->put(route('softwares.update', $this->catalog), [
                'category' => 'Commercial',
                'status' => 'Whitelist',
                'description' => 'IDE untuk developer C# dan .NET',
            ])
            ->assertRedirect(route('softwares'))
            ->assertSessionHas('status', 'success');

        expect($this->catalog->fresh()->description)->toBe('IDE untuk developer C# dan .NET');
    });
});

describe('Admin License Lifecycle Flow', function () {
    test('admin can create, view, update, reveal key, and delete license', function () {
        // 1. View licenses list
        $this->actingAs($this->admin)
            ->get(route('licenses'))
            ->assertStatus(200);

        // 2. Create new license
        $this->actingAs($this->admin)
            ->from(route('licenses'))
            ->post(route('licenses.store'), [
                'catalog_id' => $this->catalog->id,
                'purchase_order_number' => 'PO-2026-001',
                'quota_limit' => 20,
                'purchase_date' => now()->toDateString(),
                'expiry_date' => now()->addYear()->toDateString(),
                'price_per_unit' => 1500000,
                'license_key' => 'MSVS-2026-KEY-SECRET',
            ])
            ->assertRedirect(route('licenses'))
            ->assertSessionHas('status', 'success');

        $license = LicenseInventory::where('purchase_order_number', 'PO-2026-001')->firstOrFail();
        expect($license->quota_limit)->toBe(20);

        // 3. View license detail
        $this->actingAs($this->admin)
            ->get(route('licenses.show', $license))
            ->assertStatus(200)
            ->assertSee('PO-2026-001');

        // 4. Update license
        $this->actingAs($this->admin)
            ->from(route('licenses'))
            ->put(route('licenses.update', $license), [
                'catalog_id' => $this->catalog->id,
                'purchase_order_number' => 'PO-2026-001-REV',
                'quota_limit' => 25,
                'purchase_date' => now()->toDateString(),
                'expiry_date' => now()->addYears(2)->toDateString(),
                'price_per_unit' => 1600000,
            ])
            ->assertRedirect(route('licenses'))
            ->assertSessionHas('status', 'success');

        expect($license->fresh()->quota_limit)->toBe(25);

        // 5. Reveal encrypted license key
        $keyResponse = $this->actingAs($this->admin)
            ->postJson(route('licenses.key', $license));

        $keyResponse->assertStatus(200)
            ->assertJson([
                'key' => 'MSVS-2026-KEY-SECRET',
            ]);

        // 6. Delete license
        $this->actingAs($this->admin)
            ->delete(route('licenses.destroy', $license))
            ->assertRedirect(route('licenses'))
            ->assertSessionHas('status', 'success');

        expect(LicenseInventory::find($license->id))->toBeNull();
    });
});

describe('Admin Academic Structure (Faculties & Laboratories) Flow', function () {
    test('admin can perform full CRUD on faculties', function () {
        // 1. Index & Create page
        $this->actingAs($this->admin)->get(route('faculties.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('faculties.create'))->assertStatus(200);

        // 2. Store faculty
        $this->actingAs($this->admin)
            ->post(route('faculties.store'), [
                'name' => 'Fakultas Keguruan dan Ilmu Pendidikan',
                'code' => 'FKIP',
                'description' => 'Fakultas Pendidikan',
            ])
            ->assertRedirect(route('faculties.index'))
            ->assertSessionHas('status', 'success');

        $fkip = Faculty::where('code', 'FKIP')->firstOrFail();

        // 3. Edit & Update
        $this->actingAs($this->admin)->get(route('faculties.edit', $fkip))->assertStatus(200);
        $this->actingAs($this->admin)
            ->put(route('faculties.update', $fkip), [
                'name' => 'FKIP USN Kolaka',
                'code' => 'FKIP',
                'description' => 'Updated desc',
            ])
            ->assertRedirect(route('faculties.index'))
            ->assertSessionHas('status', 'success');

        expect($fkip->fresh()->name)->toBe('FKIP USN Kolaka');

        // 4. Delete
        $this->actingAs($this->admin)
            ->delete(route('faculties.destroy', $fkip))
            ->assertRedirect(route('faculties.index'))
            ->assertSessionHas('status', 'success');

        expect(Faculty::find($fkip->id))->toBeNull();
    });

    test('admin can perform full CRUD on laboratories', function () {
        // 1. Index & Create page
        $this->actingAs($this->admin)->get(route('laboratories.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('laboratories.create'))->assertStatus(200);

        // 2. Store laboratory
        $this->actingAs($this->admin)
            ->post(route('laboratories.store'), [
                'faculty_id' => $this->faculty->id,
                'name' => 'Lab Jaringan Komputer',
                'code' => 'LAB-NET',
                'building' => 'Gedung C',
                'floor' => 'Lantai 2',
            ])
            ->assertRedirect(route('laboratories.index'))
            ->assertSessionHas('status', 'success');

        $lab = Laboratory::where('code', 'LAB-NET')->firstOrFail();

        // 3. Edit & Update
        $this->actingAs($this->admin)->get(route('laboratories.edit', $lab))->assertStatus(200);
        $this->actingAs($this->admin)
            ->put(route('laboratories.update', $lab), [
                'faculty_id' => $this->faculty->id,
                'name' => 'Lab Jaringan dan Keamanan Siber',
                'code' => 'LAB-NET',
                'building' => 'Gedung C',
                'floor' => 'Lantai 2',
            ])
            ->assertRedirect(route('laboratories.index'))
            ->assertSessionHas('status', 'success');

        expect($lab->fresh()->name)->toBe('Lab Jaringan dan Keamanan Siber');

        // 4. Delete
        $this->actingAs($this->admin)
            ->delete(route('laboratories.destroy', $lab))
            ->assertRedirect(route('laboratories.index'))
            ->assertSessionHas('status', 'success');

        expect(Laboratory::find($lab->id))->toBeNull();
    });
});

describe('Admin License Allocation Flow', function () {
    test('admin can manage faculty license allocations', function () {
        $license = LicenseInventory::factory()->create([
            'catalog_id' => $this->catalog->id,
            'quota_limit' => 30,
        ]);

        // 1. Index & Create
        $this->actingAs($this->admin)->get(route('license-allocations.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('license-allocations.create'))->assertStatus(200);

        // 2. Store allocation
        $this->actingAs($this->admin)
            ->post(route('license-allocations.store'), [
                'license_inventory_id' => $license->id,
                'faculty_id' => $this->faculty->id,
                'allocated_quota' => 10,
                'allocation_date' => now()->toDateString(),
                'status' => 'active',
                'notes' => 'Alokasi untuk FTI',
            ])
            ->assertRedirect(route('license-allocations.index'))
            ->assertSessionHas('status', 'success');

        $allocation = LicenseAllocation::where('license_inventory_id', $license->id)->firstOrFail();

        // 3. Show (redirects to edit) & Edit page
        $this->actingAs($this->admin)
            ->get(route('license-allocations.show', $allocation))
            ->assertRedirect(route('license-allocations.edit', $allocation));

        $this->actingAs($this->admin)->get(route('license-allocations.edit', $allocation))->assertStatus(200);

        // 4. Update
        $this->actingAs($this->admin)
            ->put(route('license-allocations.update', $allocation), [
                'license_inventory_id' => $license->id,
                'faculty_id' => $this->faculty->id,
                'allocated_quota' => 15,
                'allocation_date' => now()->toDateString(),
                'status' => 'active',
                'notes' => 'Alokasi ditambah',
            ])
            ->assertRedirect(route('license-allocations.index'))
            ->assertSessionHas('status', 'success');

        expect($allocation->fresh()->allocated_quota)->toBe(15);

        // 5. Delete
        $this->actingAs($this->admin)
            ->delete(route('license-allocations.destroy', $allocation))
            ->assertRedirect(route('license-allocations.index'))
            ->assertSessionHas('status', 'success');

        expect(LicenseAllocation::find($allocation->id))->toBeNull();
    });
});

describe('Admin Compliance & Monitoring Flow', function () {
    test('admin can navigate compliance and monitoring pages with scan sessions', function () {
        $scanSession = ScanSession::factory()->create([
            'computer_id' => $this->computer->id,
            'status' => 'completed',
            'started_at' => now()->subDay(),
            'completed_at' => now(),
        ]);

        ScanSoftwareResult::factory()->create([
            'scan_session_id' => $scanSession->id,
            'catalog_id' => $this->catalog->id,
        ]);

        ComplianceSnapshot::create([
            'scan_session_id' => $scanSession->id,
            'computer_id' => $this->computer->id,
            'status' => 'Berlisensi',
            'software_name' => 'Visual Studio Pro',
            'scanned_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);

        // 1. Compliance index
        $this->actingAs($this->admin)->get(route('compliance'))->assertStatus(200);

        // 2. Monitoring index
        $this->actingAs($this->admin)->get(route('monitoring.index'))->assertStatus(200);

        // 3. Monitoring session detail
        $this->actingAs($this->admin)->get(route('monitoring.show', $scanSession))->assertStatus(200);

        // 4. Monitoring software changes
        $this->actingAs($this->admin)->get(route('monitoring.changes'))->assertStatus(200);

        // 5. Monitoring compliance history
        $this->actingAs($this->admin)->get(route('monitoring.compliance'))->assertStatus(200);
    });
});

describe('Admin Reports Center Flow', function () {
    test('admin can view and export all 8 report types', function () {
        Queue::fake();

        // 1. Reports hub
        $this->actingAs($this->admin)->get(route('reports'))->assertStatus(200);

        // 2. Eksekutif
        $this->actingAs($this->admin)->get(route('reports.eksekutif'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.eksekutif.export'))->assertStatus(200);

        // 3. Komputer
        $this->actingAs($this->admin)->get(route('reports.komputer'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.komputer.export', ['format' => 'excel']))->assertStatus(200);

        // 4. Software
        $this->actingAs($this->admin)->get(route('reports.software'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.software.export', ['format' => 'excel']))->assertStatus(200);

        // 5. Kepatuhan
        $this->actingAs($this->admin)->get(route('reports.kepatuhan'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.kepatuhan.export', ['format' => 'excel']))->assertStatus(200);
        $this->actingAs($this->admin)->post(route('reports.kepatuhan.scan'))->assertRedirect();

        // 6. Lisensi
        $this->actingAs($this->admin)->get(route('reports.lisensi'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.lisensi.export', ['format' => 'excel']))->assertStatus(200);

        // 7. Monitoring
        $this->actingAs($this->admin)->get(route('reports.monitoring'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.monitoring.export', ['format' => 'excel']))->assertStatus(200);

        // 8. Perubahan
        $this->actingAs($this->admin)->get(route('reports.perubahan'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.perubahan.export', ['format' => 'excel']))->assertStatus(200);

        // 9. Kebutuhan Lisensi
        $this->actingAs($this->admin)->get(route('reports.kebutuhan-lisensi'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'excel']))->assertStatus(200);
    });
});

describe('Admin Report Submission & Approval Flow', function () {
    test('admin can submit report to lab and view/approve approvals', function () {
        // 1. View submission page
        $this->actingAs($this->admin)->get(route('report-submissions.index'))->assertStatus(200);

        // 2. Submit report
        $this->actingAs($this->admin)
            ->post(route('report-submissions.submit'), [
                'laboratory_id' => $this->laboratory->id,
                'period' => now()->format('Y-m'),
                'report_type' => 'kepatuhan',
            ])
            ->assertRedirect(route('report-submissions.index'))
            ->assertSessionHas('status', 'success');

        $approval = ReportApproval::where('laboratory_id', $this->laboratory->id)->firstOrFail();

        // 3. View approvals list
        $this->actingAs($this->admin)->get(route('lab.reports.index'))->assertStatus(200);

        // 4. View approval detail
        $this->actingAs($this->admin)->get(route('lab.reports.show', $approval))->assertStatus(200);

        // 5. Admin can approve report
        $this->actingAs($this->admin)
            ->post(route('lab.reports.approve', $approval), [
                'notes' => 'Disetujui langsung oleh Admin',
            ])
            ->assertRedirect(route('lab.reports.index'))
            ->assertSessionHas('status', 'success');

        expect($approval->fresh()->status)->toBe('approved');
    });
});

describe('Admin User Accounts, Activity Logs, and Agent Download', function () {
    test('admin can manage user accounts and view system logs', function () {
        // 1. Account Index
        $this->actingAs($this->admin)->get(route('accounts'))->assertStatus(200);

        // 2. Create User Account
        $this->actingAs($this->admin)
            ->post(route('accounts.store'), [
                'name' => 'Operator Lab Test',
                'email' => 'operator.test@usn.ac.id',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'role' => 'kepala_lab',
                'laboratory_id' => $this->laboratory->id,
            ])
            ->assertRedirect(route('accounts'))
            ->assertSessionHas('status', 'success');

        $newUser = User::where('email', 'operator.test@usn.ac.id')->firstOrFail();

        // 3. Update User Account
        $this->actingAs($this->admin)
            ->put(route('accounts.update', $newUser), [
                'name' => 'Operator Lab Renamed',
                'email' => 'operator.renamed@usn.ac.id',
                'role' => 'kepala_lab',
                'laboratory_id' => $this->laboratory->id,
            ])
            ->assertRedirect(route('accounts'))
            ->assertSessionHas('status', 'success');

        expect($newUser->fresh()->name)->toBe('Operator Lab Renamed');

        // 4. Reset User Password
        $this->actingAs($this->admin)
            ->put(route('accounts.reset-password', $newUser), [
                'password' => 'NewResetPass123!',
                'password_confirmation' => 'NewResetPass123!',
            ])
            ->assertRedirect(route('accounts'))
            ->assertSessionHas('status', 'success');

        $this->assertTrue(Hash::check('NewResetPass123!', $newUser->fresh()->password));

        // 5. Delete User Account
        $this->actingAs($this->admin)
            ->delete(route('accounts.destroy', $newUser))
            ->assertRedirect(route('accounts'))
            ->assertSessionHas('status', 'success');

        expect(User::find($newUser->id))->toBeNull();

        // 6. View Activity Logs
        $this->actingAs($this->admin)->get(route('activity-logs'))->assertStatus(200);

        // 7. Agent Download Page & ZIP Download
        $this->actingAs($this->admin)->get(route('agent.download-page'))->assertStatus(200);
        $this->actingAs($this->admin)
            ->post(route('agent.download'), [
                'laboratory_id' => $this->laboratory->id,
            ])
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/zip');
    });
});
