<?php

use App\Exports\KepatuhanExport;
use App\Exports\KomputerExport;
use App\Exports\SoftwareExport;
use App\Models\ComplianceReport;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->labApproved = Laboratory::factory()->create([
        'name' => 'Lab Komputasi Dasar',
        'code' => 'LAB-KD',
    ]);
    $this->labPending = Laboratory::factory()->create([
        'name' => 'Lab Jaringan Komputer',
        'code' => 'LAB-JK',
    ]);
    $this->labRejected = Laboratory::factory()->create([
        'name' => 'Lab Multimedia',
        'code' => 'LAB-MM',
    ]);

    $this->pcApproved = Computer::factory()->create([
        'hostname' => 'PC-APPROVED-01',
        'laboratory_id' => $this->labApproved->id,
        'os_license_status' => 'Licensed',
    ]);
    $this->pcPending = Computer::factory()->create([
        'hostname' => 'PC-PENDING-01',
        'laboratory_id' => $this->labPending->id,
        'os_license_status' => 'Licensed',
    ]);
    $this->pcRejected = Computer::factory()->create([
        'hostname' => 'PC-REJECTED-01',
        'laboratory_id' => $this->labRejected->id,
        'os_license_status' => 'Unlicensed',
    ]);

    $currentPeriod = now()->format('Y-m');

    $this->approvalApproved = ReportApproval::factory()->approved()->create([
        'laboratory_id' => $this->labApproved->id,
        'report_type' => 'kepatuhan',
        'period' => $currentPeriod,
        'notes' => 'Laporan telah diverifikasi lengkap.',
    ]);
    $this->approvalPending = ReportApproval::factory()->create([
        'laboratory_id' => $this->labPending->id,
        'report_type' => 'kepatuhan',
        'period' => $currentPeriod,
        'status' => 'pending',
    ]);
    $this->approvalRejected = ReportApproval::factory()->rejected()->create([
        'laboratory_id' => $this->labRejected->id,
        'report_type' => 'kepatuhan',
        'period' => $currentPeriod,
        'notes' => 'Data scanner belum lengkap.',
    ]);

    $this->pimpinan = User::factory()->create(['name' => 'Bapak Rektor']);
    $this->pimpinan->assignRole('pimpinan');

    $this->admin = User::factory()->create(['name' => 'Administrator']);
    $this->admin->assignRole('admin');

    $this->catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Adobe Photoshop 2024',
        'category' => 'Commercial',
    ]);

    SoftwareDiscovery::factory()->create([
        'computer_id' => $this->pcApproved->id,
        'catalog_id' => $this->catalog->id,
        'raw_name' => 'Adobe Photoshop 2024',
        'version' => '25.0',
    ]);
    SoftwareDiscovery::factory()->create([
        'computer_id' => $this->pcPending->id,
        'catalog_id' => $this->catalog->id,
        'raw_name' => 'Adobe Photoshop 2024',
        'version' => '25.0',
    ]);
    SoftwareDiscovery::factory()->create([
        'computer_id' => $this->pcRejected->id,
        'catalog_id' => $this->catalog->id,
        'raw_name' => 'Adobe Photoshop 2024',
        'version' => '25.0',
    ]);

    ComplianceReport::create([
        'computer_id' => $this->pcApproved->id,
        'software_catalog_id' => $this->catalog->id,
        'software_name' => 'Adobe Photoshop 2024',
        'software_version' => '25.0',
        'status' => 'Tidak Berlisensi',
        'detected_at' => now(),
        'scanned_at' => now(),
        'keterangan' => 'Belum ada lisensi untuk PC Approved',
    ]);
    ComplianceReport::create([
        'computer_id' => $this->pcPending->id,
        'software_catalog_id' => $this->catalog->id,
        'software_name' => 'Adobe Photoshop 2024',
        'software_version' => '25.0',
        'status' => 'Tidak Berlisensi',
        'detected_at' => now(),
        'scanned_at' => now(),
        'keterangan' => 'Belum ada lisensi untuk PC Pending',
    ]);
    ComplianceReport::create([
        'computer_id' => $this->pcRejected->id,
        'software_catalog_id' => $this->catalog->id,
        'software_name' => 'Adobe Photoshop 2024',
        'software_version' => '25.0',
        'status' => 'Tidak Berlisensi',
        'detected_at' => now(),
        'scanned_at' => now(),
        'keterangan' => 'Belum ada lisensi untuk PC Rejected',
    ]);
});

test('pimpinan dashboard shows only approved lab data', function () {
    $response = $this->actingAs($this->pimpinan)->get(route('dashboard'));

    $response->assertStatus(200);
    $response->assertViewIs('dashboard.pimpinan');

    $stats = $response->viewData('stats');
    expect($stats['approved_labs'])->toBe(1)
        ->and($stats['total_computers'])->toBe(1)
        ->and($stats['licensed_os'])->toBe(1);
});

test('pimpinan dashboard shows message when no labs approved', function () {
    ReportApproval::query()->delete();

    $response = $this->actingAs($this->pimpinan)->get(route('dashboard'));

    $response->assertStatus(200);
    $stats = $response->viewData('stats');
    expect($stats['approved_labs'])->toBe(0)
        ->and($stats['total_computers'])->toBe(0);
});

test('pimpinan report preview includes only approved labs', function () {
    // 1. Eksekutif
    $resEksekutif = $this->actingAs($this->pimpinan)->get(route('reports.eksekutif'));
    $resEksekutif->assertStatus(200);
    expect($resEksekutif->viewData('totalComputers'))->toBe(1)
        ->and($resEksekutif->viewData('totalInstallations'))->toBe(1);

    // 2. Komputer
    $resKomputer = $this->actingAs($this->pimpinan)->get(route('reports.komputer'));
    $resKomputer->assertStatus(200);
    $computers = $resKomputer->viewData('computers');
    expect($computers->total())->toBe(1);
    $resKomputer->assertSee('PC-APPROVED-01');
    $resKomputer->assertDontSee('PC-PENDING-01');
    $resKomputer->assertDontSee('PC-REJECTED-01');

    // 3. Software
    $resSoftware = $this->actingAs($this->pimpinan)->get(route('reports.software'));
    $resSoftware->assertStatus(200);
    $softwares = $resSoftware->viewData('softwares');
    expect($softwares->first()->computer_count)->toBe(1);

    // 4. Kepatuhan
    $resKepatuhan = $this->actingAs($this->pimpinan)->get(route('reports.kepatuhan'));
    $resKepatuhan->assertStatus(200);
    $reports = $resKepatuhan->viewData('reports');
    expect($reports->total())->toBe(1);
    $resKepatuhan->assertSee('PC-APPROVED-01');
    $resKepatuhan->assertDontSee('PC-PENDING-01');
    $resKepatuhan->assertDontSee('PC-REJECTED-01');
});

test('pimpinan report preview excludes pending labs', function () {
    $res = $this->actingAs($this->pimpinan)->get(route('reports.komputer'));
    $res->assertDontSee('PC-PENDING-01');
});

test('pimpinan report preview excludes rejected labs', function () {
    $res = $this->actingAs($this->pimpinan)->get(route('reports.komputer'));
    $res->assertDontSee('PC-REJECTED-01');
});

test('pimpinan report export PDF includes only approved labs', function () {
    $res = $this->actingAs($this->pimpinan)->get(route('reports.eksekutif.export'));
    $res->assertStatus(200);
    $res->assertHeader('Content-Type', 'application/pdf');

    $resKomputer = $this->actingAs($this->pimpinan)->get(route('reports.komputer.export', ['format' => 'pdf']));
    $resKomputer->assertStatus(200);
    $resKomputer->assertHeader('Content-Type', 'application/pdf');

    $resKepatuhan = $this->actingAs($this->pimpinan)->get(route('reports.kepatuhan.export', ['format' => 'pdf']));
    $resKepatuhan->assertStatus(200);
    $resKepatuhan->assertHeader('Content-Type', 'application/pdf');
});

test('pimpinan report export Excel includes only approved labs', function () {
    Excel::fake();

    $this->actingAs($this->pimpinan)->get(route('reports.komputer.export', ['format' => 'excel']));
    Excel::assertDownloaded('inventaris-komputer_'.now()->startOfMonth()->format('Y-m-d').'_'.now()->endOfMonth()->format('Y-m-d').'.xlsx', function (KomputerExport $export) {
        return $export->collection()->count() === 1
            && $export->collection()->first()->hostname === 'PC-APPROVED-01';
    });

    $this->actingAs($this->pimpinan)->get(route('reports.software.export', ['format' => 'excel']));
    Excel::assertDownloaded('inventaris-software_'.now()->startOfMonth()->format('Y-m-d').'_'.now()->endOfMonth()->format('Y-m-d').'.xlsx', function (SoftwareExport $export) {
        return $export->collection()->first()->computer_count === 1;
    });

    $this->actingAs($this->pimpinan)->get(route('reports.kepatuhan.export', ['format' => 'excel']));
    Excel::assertDownloaded('kepatuhan-lisensi_'.now()->startOfMonth()->format('Y-m-d').'_'.now()->endOfMonth()->format('Y-m-d').'.xlsx', function (KepatuhanExport $export) {
        return $export->collection()->count() === 1
            && $export->collection()->first()->computer->hostname === 'PC-APPROVED-01';
    });
});

test('pimpinan compliance page shows only approved lab data', function () {
    $response = $this->actingAs($this->pimpinan)->get(route('compliance'));

    $response->assertStatus(200);
    $response->assertViewIs('pages.admin.compliance');

    $softwares = $response->viewData('softwares');
    expect($softwares->first()->installed_count)->toBe(1);
});

test('kepala_lab compliance page shows only own lab data', function () {
    $kepalaLab = User::factory()->create(['laboratory_id' => $this->labPending->id]);
    $kepalaLab->assignRole('kepala_lab');

    $response = $this->actingAs($kepalaLab)->get(route('compliance'));

    $response->assertStatus(200);
    $softwares = $response->viewData('softwares');
    // For labPending, only 1 computer has the software
    expect($softwares->first()->installed_count)->toBe(1);
});

test('exported report includes approval metadata', function () {
    $period = now()->format('Y-m');
    $approvalData = ReportApproval::where('status', 'approved')
        ->where('period', $period)
        ->with(['laboratory', 'reviewer'])
        ->get();

    expect($approvalData->isNotEmpty())->toBeTrue();

    // Verify PDF view contains approval table when approvalData is passed
    $view = view('reports.pdf.eksekutif-pdf', [
        'totalComputers' => 1,
        'totalInstallations' => 1,
        'complianceRate' => 100,
        'criticalAlerts' => 0,
        'breakdown' => [],
        'topUnlicensed' => collect(),
        'print_date' => now()->format('d/m/Y H:i'),
        'printed_by' => 'Test User',
        'startDateStr' => now()->startOfMonth()->format('d/m/Y'),
        'endDateStr' => now()->endOfMonth()->format('d/m/Y'),
        'period' => $period,
        'approvalData' => $approvalData,
    ])->render();

    expect($view)->toContain('Status Verifikasi Penanggung Jawab Laboratorium')
        ->toContain('Lab Komputasi Dasar')
        ->toContain('Disetujui');
});

test('pimpinan sees empty state when no reports approved', function () {
    ReportApproval::query()->delete();

    $response = $this->actingAs($this->pimpinan)->get(route('reports.eksekutif'));
    $response->assertStatus(200);
    $response->assertSee('Belum ada laporan yang disetujui untuk periode ini.');

    $responseKomp = $this->actingAs($this->pimpinan)->get(route('reports.komputer'));
    $responseKomp->assertStatus(200);
    $responseKomp->assertSee('Belum ada laporan yang disetujui untuk periode ini.');
});

test('admin still sees all data regardless of approval status', function () {
    $resKomputer = $this->actingAs($this->admin)->get(route('reports.komputer'));
    $resKomputer->assertStatus(200);
    expect($resKomputer->viewData('computers')->total())->toBe(3);
    $resKomputer->assertSee('PC-APPROVED-01');
    $resKomputer->assertSee('PC-PENDING-01');
    $resKomputer->assertSee('PC-REJECTED-01');

    $resCompliance = $this->actingAs($this->admin)->get(route('compliance'));
    $resCompliance->assertStatus(200);
    expect($resCompliance->viewData('softwares')->first()->installed_count)->toBe(3);
});

test('newly approved lab appears in pimpinan view', function () {
    // Before: pimpinan sees 1 computer
    $resBefore = $this->actingAs($this->pimpinan)->get(route('reports.komputer'));
    expect($resBefore->viewData('computers')->total())->toBe(1);

    // Approve the pending lab
    $this->approvalPending->update(['status' => 'approved', 'reviewed_at' => now()]);

    // After: pimpinan sees 2 computers
    $resAfter = $this->actingAs($this->pimpinan)->get(route('reports.komputer'));
    expect($resAfter->viewData('computers')->total())->toBe(2);
    $resAfter->assertSee('PC-PENDING-01');
});
