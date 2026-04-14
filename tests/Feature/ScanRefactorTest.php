<?php

use App\Models\Computer;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Services\SoftwareFilterService;
use App\Jobs\ProcessScanResultJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('SoftwareFilterService filters junk and flags priority keywords', function () {
    $service = new SoftwareFilterService();
    
    $softwareList = [
        ['name' => 'Steam', 'version' => '1.0'], // Priority
        ['name' => 'Intel(R) Driver Update Utility', 'version' => '2.0'], // Junk
        ['name' => 'Microsoft Office', 'version' => '2021'], // Clean
        ['name' => 'crack for something', 'version' => '1.0'], // Priority (case-insensitive)
    ];
    
    $result = $service->filter($softwareList);
    
    // Steam, Office, and crack are in clean. Intel(R) is junk.
    expect($result->clean)->toHaveCount(3); 
    expect($result->junk)->toHaveCount(1); 
    expect($result->flagged)->toHaveCount(2); 
    
    expect($result->clean[0]['name'])->toBe('Steam');
    expect($result->junk[0]['name'])->toBe('Intel(R) Driver Update Utility');
    expect($result->flagged[1]['name'])->toBe('crack for something');
});

test('ScanController creates computer synchronously and dispatches job', function () {
    Queue::fake();
    
    $payload = [
        'computer_name' => 'WS-TEST-01',
        'mac_address' => '00:11:22:33:44:55',
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '100.0']
        ]
    ];
    
    $response = $this->postJson('/api/scan-result', $payload);
    
    // Assert 202 Accepted
    $response->assertStatus(202);
    $response->assertJsonPath('status', 'received');
    
    // Check computer created synchronously (before job)
    $this->assertDatabaseHas('computers', [
        'hostname' => 'WS-TEST-01',
        'mac_address' => '00:11:22:33:44:55',
    ]);
    
    // Check job dispatched to 'scans' queue
    Queue::assertPushed(ProcessScanResultJob::class, function ($job) use ($payload) {
        return $job->hardwareData['computer_name'] === $payload['computer_name']
            && $job->softwareList === $payload['installed_software']
            && $job->queue === 'scans';
    });
});

test('ProcessScanResultJob processes software correctly via services', function () {
    $computer = Computer::create(['hostname' => 'WS-JOB-TEST']);
    
    $softwareList = [
        ['name' => 'Steam', 'version' => '1.0'],
        ['name' => 'Slack', 'version' => '4.0'],
    ];
    
    $job = new ProcessScanResultJob(['computer_name' => 'WS-JOB-TEST'], $softwareList);
    
    // Execute job manually
    $job->handle(new SoftwareFilterService(), new \App\Services\SoftwareCatalogService());
    
    // Check catalog entries were created with correct status
    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'Steam',
        'status' => 'Blacklist' // Auto-flagged by service
    ]);
    
    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'Slack',
        'status' => 'Unreviewed'
    ]);
    
    // Check discoveries were linked correctly
    $this->assertDatabaseHas('software_discoveries', [
        'computer_id' => $computer->id,
        'raw_name' => 'Steam'
    ]);
    
    $this->assertDatabaseHas('software_discoveries', [
        'computer_id' => $computer->id,
        'raw_name' => 'Slack'
    ]);
});
