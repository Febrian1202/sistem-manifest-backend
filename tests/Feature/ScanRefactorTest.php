<?php

use App\Jobs\ProcessScanResultJob;
use App\Models\Computer;
use App\Models\SoftwareCatalog;
use App\Services\SoftwareCatalogService;
use App\Services\SoftwareFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('SoftwareFilterService filters junk and flags priority keywords', function () {
    $service = new SoftwareFilterService;

    $softwareList = [
        ['name' => 'KMSPico', 'version' => '1.0'], // Priority
        ['name' => 'Intel(R) Driver Update Utility', 'version' => '2.0'], // Junk
        ['name' => 'Microsoft Office', 'version' => '2021'], // Clean
        ['name' => 'crack for something', 'version' => '1.0'], // Priority (case-insensitive)
    ];

    $result = $service->filter($softwareList);

    // KMSPico, Office, and crack are in clean. Intel(R) is junk.
    expect($result->clean)->toHaveCount(3);
    expect($result->junk)->toHaveCount(1);
    expect($result->flagged)->toHaveCount(2);

    expect($result->clean[0]['name'])->toBe('KMSPico');
    expect($result->junk[0]['name'])->toBe('Intel(R) Driver Update Utility');
    expect($result->flagged[1]['name'])->toBe('crack for something');
});

test('ScanController updates computer synchronously and dispatches job', function () {
    Queue::fake();
    $computer = Computer::factory()->create(['hostname' => 'WS-OLD']);

    $payload = [
        'hostname' => 'WS-NEW',
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '100.0'],
        ],
    ];

    Sanctum::actingAs($computer, ['scan:submit']);
    $response = $this->postJson('/api/scan-result', $payload);

    $response->assertStatus(202);

    $this->assertDatabaseHas('computers', [
        'id' => $computer->id,
        'hostname' => 'WS-NEW',
    ]);

    Queue::assertPushed(ProcessScanResultJob::class, function ($job) use ($computer, $payload) {
        return $job->computer->id === $computer->id
            && $job->softwareList === $payload['installed_software'];
    });
});

test('ProcessScanResultJob processes software correctly via services', function () {
    $computer = Computer::factory()->create(['hostname' => 'WS-JOB-TEST']);

    $softwareList = [
        ['name' => 'KMSPico', 'version' => '1.0'],
        ['name' => 'Slack', 'version' => '4.0'],
    ];

    $job = new ProcessScanResultJob($computer, $softwareList);

    // Execute job manually
    $job->handle(new SoftwareFilterService, new SoftwareCatalogService);

    // Check catalog entries were created with correct status
    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'KMSPico',
        'status' => 'Blacklist',
    ]);

    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'Slack',
        'status' => 'Unreviewed',
    ]);

    $this->assertDatabaseHas('software_discoveries', [
        'computer_id' => $computer->id,
        'raw_name' => 'KMSPico',
    ]);

    $this->assertDatabaseHas('software_discoveries', [
        'computer_id' => $computer->id,
        'raw_name' => 'Slack',
    ]);
});

test('ProcessScanResultJob auto-processes whitelisted freeware and open-source software', function () {
    $computer = Computer::factory()->create(['hostname' => 'WS-WHITELIST-TEST']);

    $softwareList = [
        ['name' => 'VLC media player', 'version' => '3.0.18'],
        ['name' => 'Unknown Hack Tool', 'version' => '1.0'],
        ['name' => 'VLC media player Activator', 'version' => '1.0'], // Matches whitelist but also matches a priority (blacklist) keyword
    ];

    $job = new ProcessScanResultJob($computer, $softwareList);

    // Execute job manually
    $job->handle(new SoftwareFilterService, new SoftwareCatalogService);

    // VLC media player should be OpenSource and Whitelist
    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'VLC media player',
        'category' => 'OpenSource',
        'status' => 'Whitelist',
    ]);

    // Unknown Hack Tool should be Freeware (default) and Unreviewed
    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'Unknown Hack Tool',
        'category' => 'Freeware',
        'status' => 'Unreviewed',
    ]);

    // VLC media player Activator contains 'Activator' (Priority/Blacklist keyword)
    // Even though it contains 'VLC media player' (Whitelist), it should be Blacklist and NOT Whitelist
    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'VLC media player Activator',
        'status' => 'Blacklist',
    ]);
});

test('ProcessScanResultJob updates existing unreviewed software to whitelist if matching whitelist', function () {
    $computer = Computer::factory()->create(['hostname' => 'WS-EXISTING-TEST']);

    // Pre-create the software catalog entry as Unreviewed/Freeware
    SoftwareCatalog::create([
        'normalized_name' => 'Git',
        'status' => 'Unreviewed',
        'category' => 'Freeware',
    ]);

    $softwareList = [
        ['name' => 'Git', 'version' => '2.40.0'],
    ];

    $job = new ProcessScanResultJob($computer, $softwareList);
    $job->handle(new SoftwareFilterService, new SoftwareCatalogService);

    // Git should now be OpenSource and Whitelist
    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'Git',
        'category' => 'OpenSource',
        'status' => 'Whitelist',
    ]);
});
