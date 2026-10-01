<?php

use App\Jobs\GenerateComplianceReportJob;
use App\Jobs\ProcessScanResultJob;
use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use App\Services\LicenseComplianceService;
use App\Services\SoftwareCatalogService;
use App\Services\SoftwareFilterService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->pimpinan = User::factory()->create();
    $this->pimpinan->assignRole('pimpinan');

    $this->kepalaLab = User::factory()->create();
    $this->kepalaLab->assignRole('kepala_lab');

    $this->staffLab = User::factory()->create();
    $this->staffLab->assignRole('staff_lab');

    $this->service = app(LicenseComplianceService::class);
});

test('test case 1: alokasi lisensi valid dan perhitungan sisa kuota universitas', function () {
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'AutoCAD 2026',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 50,
        'purchase_order_number' => 'PO-USN-50',
    ]);

    $facultyFTI = Faculty::factory()->create(['name' => 'Fakultas Teknologi Informasi', 'code' => 'FTI']);
    $facultyFKIP = Faculty::factory()->create(['name' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'code' => 'FKIP']);

    // Alokasi 1: FTI = 20
    $res1 = $this->actingAs($this->admin)->post(route('license-allocations.store'), [
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyFTI->id,
        'allocated_quota' => 20,
        'allocation_date' => now()->toDateString(),
        'status' => 'active',
    ]);
    $res1->assertSessionHasNoErrors();
    $res1->assertRedirect(route('license-allocations.index'));

    // Alokasi 2: FKIP = 20
    $res2 = $this->actingAs($this->admin)->post(route('license-allocations.store'), [
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyFKIP->id,
        'allocated_quota' => 20,
        'allocation_date' => now()->toDateString(),
        'status' => 'active',
    ]);
    $res2->assertSessionHasNoErrors();
    $res2->assertRedirect(route('license-allocations.index'));

    // Assertions
    $totalAllocated = $this->service->getTotalAllocated($catalog->id);
    expect($totalAllocated)->toBe(40);

    $entitlement = $this->service->getActiveEntitlement($catalog->id);
    expect($entitlement)->toBe(50);

    $unallocated = $entitlement - $totalAllocated;
    expect($unallocated)->toBe(10);

    $this->assertDatabaseHas('license_allocations', [
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyFTI->id,
        'allocated_quota' => 20,
        'status' => 'active',
    ]);

    $this->assertDatabaseHas('license_allocations', [
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyFKIP->id,
        'allocated_quota' => 20,
        'status' => 'active',
    ]);
});

test('test case 2: penolakan alokasi lisensi yang melebihi kepemilikan universitas', function () {
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'MATLAB R2026',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 50,
    ]);

    $facultyFTI = Faculty::factory()->create(['name' => 'Fakultas Teknologi Informasi', 'code' => 'FTI']);
    $facultyFKIP = Faculty::factory()->create(['name' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'code' => 'FKIP']);

    // Alokasi 1: FTI = 30 (Berhasil)
    $res1 = $this->actingAs($this->admin)->post(route('license-allocations.store'), [
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyFTI->id,
        'allocated_quota' => 30,
        'allocation_date' => now()->toDateString(),
        'status' => 'active',
    ]);
    $res1->assertSessionHasNoErrors();

    // Alokasi 2: FKIP = 30 (Harus Ditolak karena Total 60 > 50)
    $res2 = $this->actingAs($this->admin)->post(route('license-allocations.store'), [
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyFKIP->id,
        'allocated_quota' => 30,
        'allocation_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $res2->assertSessionHasErrors(['allocated_quota']);

    // Database tidak menyimpan alokasi kedua
    $this->assertDatabaseMissing('license_allocations', [
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyFKIP->id,
    ]);

    // Total alokasi tetap 30
    expect($this->service->getTotalAllocated($catalog->id))->toBe(30);
});

test('test case 3: pendeteksian fakultas dengan status defisit lisensi', function () {
    $facultyFTI = Faculty::factory()->create(['name' => 'Fakultas Teknologi Informasi', 'code' => 'FTI']);
    $labFTI = Laboratory::factory()->create(['faculty_id' => $facultyFTI->id]);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'AutoCAD 2026',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 50,
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyFTI->id,
        'allocated_quota' => 20,
        'status' => 'active',
    ]);

    // 25 komputer di lab FTI menginstall AutoCAD
    for ($i = 0; $i < 25; $i++) {
        $computer = Computer::factory()->create([
            'laboratory_id' => $labFTI->id,
            'status' => 'active',
        ]);
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
            'raw_name' => 'AutoCAD 2026',
        ]);
    }

    $breakdown = $this->service->getFacultyComplianceBreakdown($facultyFTI->id);
    $item = $breakdown->firstWhere('catalog_id', $catalog->id);

    expect($item)->not->toBeNull()
        ->and($item['allocated'])->toBe(20)
        ->and($item['installed'])->toBe(25)
        ->and($item['deficit'])->toBe(5)
        ->and($item['surplus'])->toBe(0)
        ->and($item['status'])->toBe('Defisit')
        ->and($item['utilization_rate'])->toEqual(125.0)
        ->and($item['is_compliant'])->toBeFalse();
});

test('test case 4: pendeteksian fakultas dengan status surplus lisensi', function () {
    $facultyFKIP = Faculty::factory()->create(['name' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'code' => 'FKIP']);
    $labFKIP = Laboratory::factory()->create(['faculty_id' => $facultyFKIP->id]);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'CorelDRAW 2026',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 50,
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $facultyFKIP->id,
        'allocated_quota' => 30,
        'status' => 'active',
    ]);

    // 20 komputer terpasang
    for ($i = 0; $i < 20; $i++) {
        $computer = Computer::factory()->create([
            'laboratory_id' => $labFKIP->id,
            'status' => 'active',
        ]);
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
            'raw_name' => 'CorelDRAW 2026',
        ]);
    }

    $breakdown = $this->service->getFacultyComplianceBreakdown($facultyFKIP->id);
    $item = $breakdown->firstWhere('catalog_id', $catalog->id);

    expect($item)->not->toBeNull()
        ->and($item['allocated'])->toBe(30)
        ->and($item['installed'])->toBe(20)
        ->and($item['deficit'])->toBe(0)
        ->and($item['surplus'])->toBe(10)
        ->and($item['status'])->toBe('Surplus')
        ->and($item['utilization_rate'])->toEqual(66.7)
        ->and($item['is_compliant'])->toBeTrue();
});

test('test case 5: penanganan software terpasang tanpa alokasi tanpa error division by zero', function () {
    $facultyFISIP = Faculty::factory()->create(['name' => 'Fakultas Ilmu Sosial dan Ilmu Politik', 'code' => 'FISIP']);
    $labFISIP = Laboratory::factory()->create(['faculty_id' => $facultyFISIP->id]);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'SPSS Statistics',
        'category' => 'Commercial',
    ]);

    // Tidak ada alokasi sama sekali ke FISIP (allocated = 0)
    for ($i = 0; $i < 10; $i++) {
        $computer = Computer::factory()->create([
            'laboratory_id' => $labFISIP->id,
            'status' => 'active',
        ]);
        SoftwareDiscovery::factory()->create([
            'computer_id' => $computer->id,
            'catalog_id' => $catalog->id,
            'raw_name' => 'SPSS Statistics',
        ]);
    }

    // Eksekusi fungsi kalkulasi tanpa memicu DivisionByZeroError
    $breakdown = $this->service->getFacultyComplianceBreakdown($facultyFISIP->id);
    $item = $breakdown->firstWhere('catalog_id', $catalog->id);

    expect($item)->not->toBeNull()
        ->and($item['allocated'])->toBe(0)
        ->and($item['installed'])->toBe(10)
        ->and($item['deficit'])->toBe(10)
        ->and($item['surplus'])->toBe(0)
        ->and($item['utilization_rate'])->toBeNull()
        ->and($item['status'])->toBe('Tanpa Alokasi (Defisit Penuh)')
        ->and($item['is_compliant'])->toBeFalse();
});

test('test case 6: agregasi multi-inventory lisensi menghilangkan bug first()', function () {
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Microsoft Office 2019',
        'category' => 'Commercial',
    ]);

    // 3 License Inventory (20 + 15 + 10 = 45 seat)
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 20,
        'purchase_order_number' => 'PO-OFFICE-01',
    ]);
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 15,
        'purchase_order_number' => 'PO-OFFICE-02',
    ]);
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 10,
        'purchase_order_number' => 'PO-OFFICE-03',
    ]);

    $entitlement = $this->service->getActiveEntitlement($catalog->id);
    expect($entitlement)->toBe(45);

    // 25 komputer aktif memasang Office 2019 (25 > 20 dari inventory pertama, tapi 25 <= 45 total kapasitas)
    $computers = Computer::factory()->count(25)->create(['status' => 'active']);
    foreach ($computers as $pc) {
        SoftwareDiscovery::factory()->create([
            'computer_id' => $pc->id,
            'catalog_id' => $catalog->id,
            'raw_name' => 'Microsoft Office 2019',
        ]);
    }

    // Jalankan audit evaluasi untuk komputer ke-25
    $targetComputer = $computers->last();
    $job = new GenerateComplianceReportJob($targetComputer);
    $job->handle();

    $this->assertDatabaseHas('compliance_reports', [
        'computer_id' => $targetComputer->id,
        'software_catalog_id' => $catalog->id,
        'status' => 'Berlisensi',
    ]);
});

test('test case 7: filter hierarkis fakultas dan laboratorium pada data komputer dan monitoring', function () {
    $facultyFTI = Faculty::factory()->create(['name' => 'Fakultas Teknologi Informasi', 'code' => 'FTI']);
    $labFTI1 = Laboratory::factory()->create(['faculty_id' => $facultyFTI->id, 'name' => 'Lab Jaringan']);
    $labFTI2 = Laboratory::factory()->create(['faculty_id' => $facultyFTI->id, 'name' => 'Lab Multimedia']);

    $facultyFKIP = Faculty::factory()->create(['name' => 'Fakultas Keguruan dan Ilmu Pendidikan', 'code' => 'FKIP']);
    $labFKIP = Laboratory::factory()->create(['faculty_id' => $facultyFKIP->id, 'name' => 'Lab Bahasa']);

    // FTI: 3 komputer di Lab 1, 2 komputer di Lab 2 (total 5)
    Computer::factory()->count(3)->create(['laboratory_id' => $labFTI1->id, 'status' => 'active']);
    Computer::factory()->count(2)->create(['laboratory_id' => $labFTI2->id, 'status' => 'active']);

    // FKIP: 4 komputer di Lab Bahasa
    Computer::factory()->count(4)->create(['laboratory_id' => $labFKIP->id, 'status' => 'active']);

    // 1. Filter komputer berdasarkan faculty_id = FTI
    $responseFTI = $this->actingAs($this->admin)->get(route('computers', ['faculty_id' => $facultyFTI->id]));
    $responseFTI->assertStatus(200);
    $ftiComputers = $responseFTI->viewData('computers');
    expect($ftiComputers->total())->toBe(5);

    // 2. Filter komputer spesifik laboratorium di bawah FTI
    $responseLab1 = $this->actingAs($this->admin)->get(route('computers', [
        'faculty_id' => $facultyFTI->id,
        'laboratory_id' => $labFTI1->id,
    ]));
    $responseLab1->assertStatus(200);
    $lab1Computers = $responseLab1->viewData('computers');
    expect($lab1Computers->total())->toBe(3);

    // 3. Filter komputer FKIP
    $responseFKIP = $this->actingAs($this->admin)->get(route('computers', ['faculty_id' => $facultyFKIP->id]));
    $responseFKIP->assertStatus(200);
    expect($responseFKIP->viewData('computers')->total())->toBe(4);
});

test('test case 8: drill-down dan konsistensi data dari scan agent ke compliance hingga laporan eksekutif', function () {
    $faculty = Faculty::factory()->create(['name' => 'Fakultas Teknologi Informasi', 'code' => 'FTI']);
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id, 'name' => 'Lab RPL']);

    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'AutoCAD 2026',
        'category' => 'Commercial',
    ]);

    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 20,
    ]);

    LicenseAllocation::factory()->create([
        'license_inventory_id' => $license->id,
        'faculty_id' => $faculty->id,
        'allocated_quota' => 10,
        'status' => 'active',
    ]);

    $computer = Computer::factory()->create([
        'laboratory_id' => $lab->id,
        'hostname' => 'WS-RPL-01',
        'status' => 'active',
    ]);

    // 1. Agent submit scan result
    Sanctum::actingAs($computer, ['scan:submit']);
    $payload = [
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'AutoCAD 2026', 'version' => '2026.1'],
        ],
    ];
    $apiResponse = $this->postJson('/api/scan-result', $payload);
    $apiResponse->assertStatus(202);

    // 2. Process scan result
    $job = new ProcessScanResultJob($computer, $payload['installed_software']);
    $job->handle(new SoftwareFilterService, new SoftwareCatalogService);

    // 3. Verifikasi SoftwareDiscovery tercipta
    $this->assertDatabaseHas('software_discoveries', [
        'computer_id' => $computer->id,
        'catalog_id' => $catalog->id,
    ]);

    // 4. Verifikasi LicenseComplianceService mencatat instalasi
    $installed = $this->service->getInstalledCount($catalog->id, $faculty->id);
    expect($installed)->toBe(1);

    // 5. Verifikasi Laporan Kebutuhan Lisensi (Preview)
    $reportPreview = $this->actingAs($this->admin)->get(route('reports.kebutuhan-lisensi'));
    $reportPreview->assertStatus(200);
    $reportPreview->assertSee('AutoCAD 2026');
    $reportPreview->assertSee('Fakultas Teknologi Informasi');

    $summary = $reportPreview->viewData('summary');
    expect($summary['total_installed'])->toBe(1);
    expect($summary['total_allocated'])->toBe(10);

    // 6. Verifikasi Ekspor PDF dan Excel sukses
    $pdfExport = $this->actingAs($this->admin)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'pdf']));
    $pdfExport->assertStatus(200);
    expect($pdfExport->headers->get('content-type'))->toContain('application/pdf');

    $excelExport = $this->actingAs($this->admin)->get(route('reports.kebutuhan-lisensi.export', ['format' => 'excel']));
    $excelExport->assertStatus(200);
    expect($excelExport->headers->get('content-disposition'))->toContain('.xlsx');
});

test('test case 9: navigasi sidebar menampilkan menu sesuai hak akses peran (RBAC)', function () {
    // 1. Admin melihat seksi Organisasi, Infrastruktur, Software & Lisensi, Laporan, Pengaturan
    $adminRes = $this->actingAs($this->admin)->get(route('dashboard'));
    $adminRes->assertStatus(200);
    $adminRes->assertSee('Organisasi');
    $adminRes->assertSee('Fakultas');
    $adminRes->assertSee('Laboratorium');
    $adminRes->assertSee('Infrastruktur');
    $adminRes->assertSee('Data Komputer');
    $adminRes->assertSee('Software & Lisensi', false);
    $adminRes->assertSee('Alokasi Lisensi');
    $adminRes->assertSee('Laporan');
    $adminRes->assertSee('Analisis Kebutuhan Lisensi');
    $adminRes->assertSee('Pengaturan');
    $adminRes->assertSee('Manajemen Akun');

    // 2. Pimpinan melihat menu eksekutif tanpa hak mutasi alokasi lisensi atau pengaturan akun
    $pimpinanRes = $this->actingAs($this->pimpinan)->get(route('dashboard'));
    $pimpinanRes->assertStatus(200);
    $pimpinanRes->assertSee('Infrastruktur');
    $pimpinanRes->assertSee('Data Komputer');
    $pimpinanRes->assertSee('Analisis Kebutuhan Lisensi');
    $pimpinanRes->assertDontSee('Alokasi Lisensi');
    $pimpinanRes->assertDontSee('Manajemen Akun');

    // 3. Kepala Lab melihat Menu PJ Lab
    $kepalaLabRes = $this->actingAs($this->kepalaLab)->get(route('dashboard'));
    $kepalaLabRes->assertStatus(200);
    $kepalaLabRes->assertSee('Menu PJ Lab');
    $kepalaLabRes->assertSee('Inventaris Lab');
    $kepalaLabRes->assertSee('Review Laporan');
    $kepalaLabRes->assertDontSee('Alokasi Lisensi');
    $kepalaLabRes->assertDontSee('Manajemen Akun');

    // 4. Staff Lab diarahkan ke inventaris lab dan melihat Menu Staff Lab pada sidebar
    $staffLab = Laboratory::factory()->create();
    $this->staffLab->update(['laboratory_id' => $staffLab->id]);

    $staffLabRes = $this->actingAs($this->staffLab)->get(route('lab.inventory.index'));
    $staffLabRes->assertStatus(200);
    $staffLabRes->assertSee('Menu Staff Lab');
    $staffLabRes->assertSee('Komputer & Aset');
    $staffLabRes->assertSee('Download Scanner');
    $staffLabRes->assertDontSee('Alokasi Lisensi');
    $staffLabRes->assertDontSee('Manajemen Akun');
});
