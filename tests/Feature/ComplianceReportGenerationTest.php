<?php

use App\Models\Computer;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Models\LicenseInventory;
use App\Models\ComplianceReport;
use App\Jobs\GenerateComplianceReportJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('Job Ter-dispatch Setelah Scan', function () {
    Queue::fake();
    
    $computer = Computer::factory()->create();
    Sanctum::actingAs($computer, ['scan:submit']);

    $payload = [
        'hostname' => 'WS-TEST-01',
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '100.0']
        ]
    ];
    
    $response = $this->postJson('/api/scan-result', $payload);
    
    $response->assertStatus(202);
    
    Queue::assertPushed(GenerateComplianceReportJob::class, function ($job) use ($computer) {
        return $job->computer->id === $computer->id && $job->queue === 'compliance';
    });
});

test('Software Berlisensi -> Status Benar', function () {
    $computer = Computer::factory()->create();
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Office 2021',
        'category' => 'Commercial'
    ]);
    
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 10,
        'expiry_date' => now()->addYear()
    ]);
    
    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Office 2021'
    ]);
    
    $job = new GenerateComplianceReportJob($computer);
    $job->handle();
    
    $this->assertDatabaseHas('compliance_reports', [
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalog->id,
        'status' => 'Berlisensi'
    ]);
});

test('Software Tanpa Lisensi -> Status Benar', function () {
    $computer = Computer::factory()->create();
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Photoshop',
        'category' => 'Commercial'
    ]);
    
    // No LicenseInventory record
    
    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Photoshop'
    ]);
    
    $job = new GenerateComplianceReportJob($computer);
    $job->handle();
    
    $this->assertDatabaseHas('compliance_reports', [
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalog->id,
        'status' => 'Tidak Berlisensi'
    ]);
    
    $report = ComplianceReport::first();
    expect($report->keterangan)->toContain('tidak ditemukan');
});

test('Software Terlarang -> Terdeteksi', function () {
    Config::set('compliance.blocked_software', ['uTorrent']);
    
    $computer = Computer::factory()->create();
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'uTorrent',
        'category' => 'Freeware'
    ]);
    
    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'uTorrent 3.6'
    ]);
    
    $job = new GenerateComplianceReportJob($computer);
    $job->handle();
    
    $this->assertDatabaseHas('compliance_reports', [
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalog->id,
        'status' => 'Tidak Berlisensi'
    ]);
    
    $report = ComplianceReport::first();
    expect($report->keterangan)->toContain('terlarang');
});

test('Record Stale Terhapus', function () {
    $computer = Computer::factory()->create();
    
    $catalogA = SoftwareCatalog::factory()->create(['normalized_name' => 'App A']);
    $catalogB = SoftwareCatalog::factory()->create(['normalized_name' => 'App B']);
    
    // Initial reports for both
    ComplianceReport::create([
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalogA->id,
        'software_name' => 'App A',
        'status' => 'Berlisensi',
        'keterangan' => 'test',
        'scanned_at' => now(),
        'detected_at' => now(),
    ]);
    
    ComplianceReport::create([
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalogB->id,
        'software_name' => 'App B',
        'status' => 'Berlisensi',
        'keterangan' => 'test',
        'scanned_at' => now(),
        'detected_at' => now(),
    ]);
    
    // Current scan only has App A
    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalogA->id,
        'raw_name' => 'App A'
    ]);
    
    $job = new GenerateComplianceReportJob($computer);
    $job->handle();
    
    // Assert App B report is deleted
    $this->assertDatabaseMissing('compliance_reports', ['software_catalog_id' => $catalogB->id]);
    // Assert App A report still exists
    $this->assertDatabaseHas('compliance_reports', ['software_catalog_id' => $catalogA->id]);
});

test('Lisensi Expired -> Status Benar', function () {
    $computer = Computer::factory()->create();
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Old Software',
        'category' => 'Commercial'
    ]);
    
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'expiry_date' => now()->subDay()
    ]);
    
    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Old Software'
    ]);
    
    $job = new GenerateComplianceReportJob($computer);
    $job->handle();
    
    $this->assertDatabaseHas('compliance_reports', [
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalog->id,
        'status' => 'Tidak Berlisensi'
    ]);
    
    $report = ComplianceReport::first();
    expect($report->keterangan)->toContain('kedaluwarsa');
});

test('Software Gratis (Non-Commercial) -> Otomatis Berlisensi', function () {
    $computer = Computer::factory()->create();
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'VS Code',
        'category' => 'OpenSource'
    ]);
    
    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'VS Code'
    ]);
    
    $job = new GenerateComplianceReportJob($computer);
    $job->handle();
    
    $this->assertDatabaseHas('compliance_reports', [
        'status' => 'Berlisensi'
    ]);
    
    $report = ComplianceReport::first();
    expect($report->keterangan)->toContain('gratis');
});

test('Kuota Lisensi Penuh -> Status Benar', function () {
    $computer = Computer::factory()->create();
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Limited App',
        'category' => 'Commercial'
    ]);
    
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 1,
        'expiry_date' => now()->addYear()
    ]);
    
    // Existing installation on another computer
    SoftwareDiscovery::factory()->create([
        'computer_id' => Computer::factory()->create()->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Limited App'
    ]);
    
    // Current installation on this computer (makes it 2 installations, quota is 1)
    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Limited App'
    ]);
    
    $job = new GenerateComplianceReportJob($computer);
    $job->handle();
    
    $this->assertDatabaseHas('compliance_reports', [
        'status' => 'Tidak Berlisensi',
        'keterangan' => 'Kuota lisensi penuh'
    ]);
});

test('Lisensi Hampir Expired -> Grace Period', function () {
    $computer = Computer::factory()->create();
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Expiring Soon',
        'category' => 'Commercial'
    ]);
    
    LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'expiry_date' => now()->addDays(15)
    ]);
    
    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Expiring Soon'
    ]);
    
    $job = new GenerateComplianceReportJob($computer);
    $job->handle();
    
    $this->assertDatabaseHas('compliance_reports', [
        'status' => 'Grace Period',
        'keterangan' => 'Lisensi akan segera berakhir'
    ]);
});
