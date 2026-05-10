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
            ['name' => 'Google Chrome', 'version' => '100.0']
        ]
    ];
    
    $response = \Laravel\Sanctum\Sanctum::actingAs($computer, ['scan:submit'])
        ->postJson('/api/scan-result', $payload);
    
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
    $job->handle(new SoftwareFilterService(), new \App\Services\SoftwareCatalogService());
    
    // Check catalog entries were created with correct status
    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'KMSPico',
        'status' => 'Blacklist'
    ]);
    
    $this->assertDatabaseHas('software_catalogs', [
        'normalized_name' => 'Slack',
        'status' => 'Unreviewed'
    ]);
    
    $this->assertDatabaseHas('software_discoveries', [
        'computer_id' => $computer->id,
        'raw_name' => 'KMSPico'
    ]);
    
    $this->assertDatabaseHas('software_discoveries', [
        'computer_id' => $computer->id,
        'raw_name' => 'Slack'
    ]);
});
