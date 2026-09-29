<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('tiga scan berkala berurutan menghasilkan tiga scan session dan histori tersimpan', function () {
    $lab = Laboratory::factory()->create(['name' => 'Lab Software']);
    $computer = Computer::factory()->create([
        'laboratory_id' => $lab->id,
        'hostname' => 'PC-PERIODIC-01',
    ]);

    Sanctum::actingAs($computer, ['scan:submit']);

    for ($i = 1; $i <= 3; $i++) {
        $uuid = (string) Str::uuid();
        $startedAt = now()->subMinutes(10 * (4 - $i))->toIso8601String();
        $completedAt = now()->subMinutes(10 * (4 - $i))->addMinutes(2)->toIso8601String();

        $response = $this->postJson('/api/scan-result', [
            'scan_uuid' => $uuid,
            'scan_mode' => 'scheduled',
            'agent_version' => '1.1.0',
            'client_started_at' => $startedAt,
            'client_completed_at' => $completedAt,
            'hostname' => 'PC-PERIODIC-01',
            'installed_software' => [
                [
                    'name' => 'Google Chrome',
                    'version' => "14{$i}.0.0",
                    'vendor' => 'Google LLC',
                ],
            ],
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'status' => 'received',
                'computer' => 'PC-PERIODIC-01',
            ]);
    }

    $sessions = ScanSession::where('computer_id', $computer->id)
        ->orderBy('id')
        ->get();

    expect($sessions)->toHaveCount(3);

    foreach ($sessions as $session) {
        expect($session->status)->toBe('completed')
            ->and($session->trigger)->toBe('scheduled')
            ->and($session->agent_version)->toBe('1.1.0')
            ->and($session->software_count)->toBe(1);
    }

    $softwareResults = ScanSoftwareResult::whereIn('scan_session_id', $sessions->pluck('id'))->get();
    expect($softwareResults)->toHaveCount(3);

    $versions = $softwareResults->pluck('version')->all();
    expect($versions)->toEqual(['141.0.0', '142.0.0', '143.0.0']);
});

test('scan berkala memperbarui last_seen_at pada komputer', function () {
    $computer = Computer::factory()->create([
        'last_seen_at' => now()->subDays(5),
    ]);

    Sanctum::actingAs($computer, ['scan:submit']);

    $response = $this->postJson('/api/scan-result', [
        'hostname' => $computer->hostname,
        'scan_mode' => 'scheduled',
        'installed_software' => [
            ['name' => '7-Zip', 'version' => '23.01'],
        ],
    ]);

    $response->assertStatus(202);

    expect($computer->fresh()->last_seen_at->diffInMinutes(now()))->toBeLessThan(2);
});
