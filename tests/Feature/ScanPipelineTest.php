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
