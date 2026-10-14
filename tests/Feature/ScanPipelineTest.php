<?php

use App\Jobs\GenerateComplianceReportJob;
use App\Jobs\ProcessScanResultJob;
use App\Models\Computer;
use App\Models\LicenseInventory;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Services\SoftwareCatalogService;
use App\Services\SoftwareFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('POST /api/scan-result creates ScanSession with pending status and dispatches ProcessScanResultJob', function () {
    Queue::fake();

    $computer = Computer::factory()->create([
        'hostname' => 'WS-PREV',
        'scan_requested' => true,
    ]);

    $scanUuid = (string) Str::uuid();
    $payload = [
        'scan_uuid' => $scanUuid,
        'hostname' => 'WS-UPDATED',
        'scan_mode' => 'on_demand',
        'agent_version' => '1.2.0',
        'installed_software' => [
            ['name' => 'Visual Studio Code', 'version' => '1.90.0'],
        ],
    ];

    Sanctum::actingAs($computer, ['scan:submit']);
    $response = $this->postJson('/api/scan-result', $payload);

    $response->assertStatus(202)
        ->assertJsonStructure([
            'status',
            'message',
            'computer',
            'scan_session_id',
        ]);

    $session = ScanSession::where('scan_uuid', $scanUuid)->first();
    expect($session)->not->toBeNull()
        ->and($session->computer_id)->toBe($computer->id)
        ->and($session->status)->toBe('pending')
        ->and($session->trigger)->toBe('on_demand')
        ->and($session->agent_version)->toBe('1.2.0');

    expect($response->json('scan_session_id'))->toBe($session->id);

    // Verify computer scan_requested reset to false
    expect($computer->fresh()->scan_requested)->toBeFalse();

    Queue::assertPushed(ProcessScanResultJob::class, function ($job) use ($session) {
        $target = $job->target ?? $job->scanSession ?? null;
        if ($target instanceof ScanSession) {
            return $target->id === $session->id;
        }

        return false;
    });
});

test('POST /api/scan-result generates fallback UUID when scan_uuid is omitted', function () {
    Queue::fake();

    $computer = Computer::factory()->create();

    $payload = [
        'hostname' => 'WS-FALLBACK',
        'installed_software' => [
            ['name' => 'Firefox', 'version' => '120.0'],
        ],
    ];

    Sanctum::actingAs($computer, ['scan:submit']);
    $response = $this->postJson('/api/scan-result', $payload);

    $response->assertStatus(202);

    $session = ScanSession::where('computer_id', $computer->id)->first();
    expect($session)->not->toBeNull()
        ->and($session->scan_uuid)->not->toBeNull()
        ->and(Str::isUuid($session->scan_uuid))->toBeTrue()
        ->and($session->status)->toBe('pending');

    expect($response->json('scan_session_id'))->toBe($session->id);
});

test('POST /api/scan-result is idempotent when duplicate scan_uuid is submitted', function () {
    Queue::fake();

    $computer = Computer::factory()->create();
    $existingSession = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'scan_uuid' => (string) Str::uuid(),
        'status' => 'completed',
    ]);

    $payload = [
        'scan_uuid' => $existingSession->scan_uuid,
        'hostname' => 'WS-DUPLICATE',
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '100.0'],
        ],
    ];

    Sanctum::actingAs($computer, ['scan:submit']);
    $response = $this->postJson('/api/scan-result', $payload);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'already_processed',
            'message' => 'Scan already processed',
            'scan_session_id' => $existingSession->id,
        ]);

    // Ensure no additional scan session created
    expect(ScanSession::where('scan_uuid', $existingSession->scan_uuid)->count())->toBe(1);

    // Ensure ProcessScanResultJob was NOT pushed
    Queue::assertNotPushed(ProcessScanResultJob::class);
});

test('POST /api/scan-result handles empty installed_software gracefully', function () {
    Queue::fake();

    $computer = Computer::factory()->create();

    $payload = [
        'hostname' => 'WS-EMPTY-SOFT',
        'installed_software' => [],
    ];

    Sanctum::actingAs($computer, ['scan:submit']);
    $response = $this->postJson('/api/scan-result', $payload);

    $response->assertStatus(202);

    $session = ScanSession::where('computer_id', $computer->id)->first();
    expect($session)->not->toBeNull()
        ->and($session->status)->toBe('pending');

    Queue::assertPushed(ProcessScanResultJob::class);
});

test('ProcessScanResultJob persists scan_software_results, completes session, and dispatches GenerateComplianceReportJob', function () {
    Queue::fake([GenerateComplianceReportJob::class]);

    $computer = Computer::factory()->create();
    $session = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'pending',
    ]);

    $softwareList = [
        [
            'name' => 'Visual Studio Code',
            'version' => '1.90.0',
            'vendor' => 'Microsoft',
            'install_date' => '2026-01-15',
        ],
        [
            'name' => 'Intel(R) Driver Update Utility', // Junk, will be filtered out
            'version' => '2.0',
        ],
    ];

    $job = new ProcessScanResultJob($session, $softwareList);
    $job->handle(new SoftwareFilterService, new SoftwareCatalogService);

    // 1. ScanSession status updated to completed, count is clean software count (1)
    $freshSession = $session->fresh();
    expect($freshSession->status)->toBe('completed')
        ->and($freshSession->software_count)->toBe(1)
        ->and($freshSession->completed_at)->not->toBeNull();

    // 2. ScanSoftwareResult records created
    $this->assertDatabaseHas('scan_software_results', [
        'scan_session_id' => $session->id,
        'raw_name' => 'Visual Studio Code',
        'version' => '1.90.0',
        'vendor' => 'Microsoft',
    ]);

    // 3. SoftwareDiscovery updated for current state
    $this->assertDatabaseHas('software_discoveries', [
        'computer_id' => $computer->id,
        'raw_name' => 'Visual Studio Code',
    ]);

    // 4. Downstream compliance job dispatched
    Queue::assertPushed(GenerateComplianceReportJob::class, function ($complianceJob) use ($session) {
        $target = $complianceJob->target ?? $complianceJob->scanSession ?? null;
        if ($target instanceof ScanSession) {
            return $target->id === $session->id;
        }

        return false;
    });
});

test('ProcessScanResultJob records failed status and error message on failure', function () {
    $computer = Computer::factory()->create();
    $session = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'pending',
    ]);

    $job = new ProcessScanResultJob($session, []);
    $exception = new RuntimeException('Database connection lost');

    $job->failed($exception);

    $freshSession = $session->fresh();
    expect($freshSession->status)->toBe('failed')
        ->and($freshSession->error_message)->toBe('Database connection lost')
        ->and($freshSession->completed_at)->not->toBeNull();
});

test('ProcessScanResultJob supports polymorphic Computer target by resolving ScanSession', function () {
    Queue::fake([GenerateComplianceReportJob::class]);

    $computer = Computer::factory()->create();

    $softwareList = [
        ['name' => 'Slack', 'version' => '4.35.0', 'vendor' => 'Slack Technologies'],
    ];

    $job = new ProcessScanResultJob($computer, $softwareList);
    $job->handle(new SoftwareFilterService, new SoftwareCatalogService);

    $session = ScanSession::where('computer_id', $computer->id)->latest()->first();
    expect($session)->not->toBeNull()
        ->and($session->status)->toBe('completed')
        ->and($session->software_count)->toBe(1);

    $this->assertDatabaseHas('scan_software_results', [
        'scan_session_id' => $session->id,
        'raw_name' => 'Slack',
    ]);
});

test('GenerateComplianceReportJob persists compliance_snapshots and synchronizes compliance_reports', function () {
    $computer = Computer::factory()->create();
    $session = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
    ]);

    $catalogCommercial = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Microsoft Office 2021',
        'category' => 'Commercial',
    ]);

    $catalogOpenSource = SoftwareCatalog::factory()->create([
        'normalized_name' => 'VLC media player',
        'category' => 'OpenSource',
    ]);

    // License for Office
    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalogCommercial->id,
        'quota_limit' => 5,
        'expiry_date' => now()->addMonths(6),
    ]);

    // Create scan software results for this session
    ScanSoftwareResult::factory()->create([
        'scan_session_id' => $session->id,
        'catalog_id' => $catalogCommercial->id,
        'raw_name' => 'Microsoft Office 2021',
        'version' => '16.0',
    ]);

    ScanSoftwareResult::factory()->create([
        'scan_session_id' => $session->id,
        'catalog_id' => $catalogOpenSource->id,
        'raw_name' => 'VLC media player',
        'version' => '3.0.18',
    ]);

    // Also populate discoveries for current state
    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalogCommercial->id,
        'raw_name' => 'Microsoft Office 2021',
    ]);

    SoftwareDiscovery::factory()->create([
        'computer_id' => $computer->id,
        'catalog_id' => $catalogOpenSource->id,
        'raw_name' => 'VLC media player',
    ]);

    $job = new GenerateComplianceReportJob($session);
    $job->handle();

    // 1. Verify compliance_snapshots created
    $this->assertDatabaseHas('compliance_snapshots', [
        'scan_session_id' => $session->id,
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalogCommercial->id,
        'status' => 'Berlisensi',
        'license_inventory_id' => $license->id,
    ]);

    $this->assertDatabaseHas('compliance_snapshots', [
        'scan_session_id' => $session->id,
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalogOpenSource->id,
        'status' => 'Berlisensi',
    ]);

    // 2. Verify compliance_reports current state updated
    $this->assertDatabaseHas('compliance_reports', [
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalogCommercial->id,
        'status' => 'Berlisensi',
    ]);
});

test('GenerateComplianceReportJob invalidates dashboard and compliance cache keys', function () {
    Cache::put('dashboard.charts', 'test-data', 60);
    Cache::put('compliance.global_stats', 'test-data', 60);

    $computer = Computer::factory()->create();
    $session = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
    ]);

    $job = new GenerateComplianceReportJob($session);
    $job->handle();

    expect(Cache::has('dashboard.charts'))->toBeFalse()
        ->and(Cache::has('compliance.global_stats'))->toBeFalse();
});

test('POST /api/scan-result accepts numeric versions, registry dword install_dates, and filters out blank names', function () {
    Queue::fake();

    $computer = Computer::factory()->create();

    $payload = [
        'hostname' => 'LAB-PC-01',
        'processor' => ['Intel Core i5', 'Intel Core i5'], // multi-socket / array processor
        'installed_software' => [
            [
                'name' => '7-Zip',
                'version' => 2201, // integer version
                'install_date' => 20240115, // integer DWORD from registry
                'vendor' => 1234, // numeric vendor
            ],
            [
                'name' => 'Windows App',
                'version' => ['Major' => 1, 'Minor' => 2, 'Build' => 3, 'Revision' => 0], // System.Version object
                'install_date' => null,
            ],
            [
                'name' => '', // blank/whitespace name from corrupt registry entry
                'version' => '1.0',
            ],
        ],
    ];

    Sanctum::actingAs($computer, ['scan:submit']);
    $response = $this->postJson('/api/scan-result', $payload);

    $response->assertStatus(202);

    Queue::assertPushed(ProcessScanResultJob::class, function ($job) {
        // Ensure blank entry is pruned and valid entries have strings
        expect($job->softwareList)->toHaveCount(2)
            ->and($job->softwareList[0]['name'])->toBe('7-Zip')
            ->and($job->softwareList[0]['version'])->toBe('2201')
            ->and($job->softwareList[0]['install_date'])->toBe('20240115')
            ->and($job->softwareList[1]['version'])->toBe('1.2.3.0');

        return true;
    });
});

test('POST /api/scan-result is resilient against device quirks: missing hostname, cisco MAC, single-object software, oversized fields, and invalid UUID', function () {
    Queue::fake();

    $computer = Computer::factory()->create([
        'hostname' => 'FALLBACK-PC-NAME',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
    ]);

    $payload = [
        'hostname' => '', // missing/empty hostname -> should fall back to $computer->hostname
        'mac_address' => '0011.2233.4455', // cisco dot notation
        'scan_uuid' => 'corrupted-non-uuid-string', // corrupted scan_uuid
        'scan_mode' => 'Scheduled', // uppercase scan mode
        'client_started_at' => 'invalid-date-string', // corrupted timestamp
        'processor' => str_repeat('A', 350), // oversized processor string (>255)
        'manufacturer' => str_repeat('B', 300), // oversized manufacturer (>255)
        'model' => str_repeat('C', 300), // oversized model (>255)
        'ram_gb' => 16.0, // float
        'disk_total_gb' => '500', // string integer
        'disk_free_gb' => -5, // negative number
        'installed_software' => [
            'name' => str_repeat('X', 300), // single object (PowerShell 1-item array) + oversized
            'version' => ['1', '2', '3'], // array version
            'vendor' => ['Vendor', 'Corp'], // array vendor
            'install_date' => 20230501, // numeric install date
        ],
    ];

    Sanctum::actingAs($computer, ['scan:submit']);
    $response = $this->postJson('/api/scan-result', $payload);

    $response->assertStatus(202);

    $computer->refresh();
    expect($computer->hostname)->toBe('FALLBACK-PC-NAME')
        ->and($computer->mac_address)->toBe('00:11:22:33:44:55')
        ->and(strlen($computer->processor))->toBe(255)
        ->and(strlen($computer->manufacturer))->toBe(255)
        ->and(strlen($computer->model))->toBe(255)
        ->and($computer->ram_gb)->toBe(16)
        ->and($computer->disk_total_gb)->toBe(500)
        ->and($computer->disk_free_gb)->toBe(0);

    Queue::assertPushed(ProcessScanResultJob::class, function ($job) {
        expect($job->softwareList)->toHaveCount(1)
            ->and(strlen($job->softwareList[0]['name']))->toBe(255)
            ->and($job->softwareList[0]['version'])->toBe('1')
            ->and($job->softwareList[0]['vendor'])->toBe('Vendor')
            ->and($job->softwareList[0]['install_date'])->toBe('20230501');

        return true;
    });
});
