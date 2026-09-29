<?php

use App\Jobs\ProcessScanResultJob;
use App\Models\Computer;
use App\Models\ScanSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('pengiriman scan dengan scan_uuid yang sama bersifat idempoten', function () {
    $computer = Computer::factory()->create();
    Sanctum::actingAs($computer, ['scan:submit']);

    $scanUuid = (string) Str::uuid();

    $payload = [
        'scan_uuid' => $scanUuid,
        'hostname' => $computer->hostname,
        'scan_mode' => 'scheduled',
        'agent_version' => '1.1.0',
        'installed_software' => [
            ['name' => 'Notepad++', 'version' => '8.6.2', 'vendor' => 'Don Ho'],
        ],
    ];

    // Request pertama: diterima
    $res1 = $this->postJson('/api/scan-result', $payload);
    $res1->assertStatus(202)
        ->assertJson([
            'status' => 'received',
        ]);

    $sessionId = $res1->json('scan_session_id');

    // Request kedua (retry dengan payload dan scan_uuid yang sama)
    $res2 = $this->postJson('/api/scan-result', $payload);
    $res2->assertStatus(200)
        ->assertJson([
            'status' => 'already_processed',
            'message' => 'Scan already processed',
            'scan_session_id' => $sessionId,
        ]);

    // Memastikan hanya ada 1 record ScanSession di database
    expect(ScanSession::where('scan_uuid', $scanUuid)->count())->toBe(1);
});

test('request duplikat tidak men-dispatch job baru', function () {
    Queue::fake();

    $computer = Computer::factory()->create();
    $existingSession = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'scan_uuid' => (string) Str::uuid(),
        'status' => 'completed',
    ]);

    Sanctum::actingAs($computer, ['scan:submit']);

    $response = $this->postJson('/api/scan-result', [
        'scan_uuid' => $existingSession->scan_uuid,
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Git', 'version' => '2.44.0'],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'status' => 'already_processed',
            'scan_session_id' => $existingSession->id,
        ]);

    Queue::assertNotPushed(ProcessScanResultJob::class);
});

test('payload tanpa scan_uuid menghasilkan UUID otomatis dan berhasil diproses', function () {
    $computer = Computer::factory()->create();
    Sanctum::actingAs($computer, ['scan:submit']);

    $response = $this->postJson('/api/scan-result', [
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Firefox', 'version' => '125.0'],
        ],
    ]);

    $response->assertStatus(202);

    $session = ScanSession::where('computer_id', $computer->id)->first();
    expect($session)->not->toBeNull()
        ->and(Str::isUuid($session->scan_uuid))->toBeTrue()
        ->and($session->status)->toBe('completed');
});
