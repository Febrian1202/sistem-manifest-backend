<?php

use App\Jobs\ProcessScanResultJob;
use App\Models\Computer;
use App\Models\ScanSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('job failure mencatat status failed dan error_message pada ScanSession', function () {
    $computer = Computer::factory()->create();
    $session = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'pending',
    ]);

    $job = new ProcessScanResultJob($session, []);
    $exception = new RuntimeException('Koneksi database terputus saat ekstraksi manifest.');

    $job->failed($exception);

    $freshSession = $session->fresh();
    expect($freshSession->status)->toBe('failed')
        ->and($freshSession->error_message)->toBe('Koneksi database terputus saat ekstraksi manifest.')
        ->and($freshSession->completed_at)->not->toBeNull();
});

test('komputer tidak aktif dapat terdeteksi dari ambang batas last_seen_at', function () {
    $activeComputer = Computer::factory()->create([
        'hostname' => 'PC-ACTIVE',
        'last_seen_at' => now()->subMinutes(15),
    ]);

    $staleComputer = Computer::factory()->create([
        'hostname' => 'PC-STALE',
        'last_seen_at' => now()->subDays(3),
    ]);

    $unseenComputer = Computer::factory()->create([
        'hostname' => 'PC-NEVER-SEEN',
        'last_seen_at' => null,
    ]);

    $offlineThreshold = now()->subDay();

    $offlineComputers = Computer::where(function ($query) use ($offlineThreshold) {
        $query->whereNull('last_seen_at')
            ->orWhere('last_seen_at', '<', $offlineThreshold);
    })->get();

    expect($offlineComputers)->toHaveCount(2)
        ->and($offlineComputers->pluck('hostname')->all())->toContain('PC-STALE', 'PC-NEVER-SEEN')
        ->and($offlineComputers->pluck('hostname')->all())->not->toContain('PC-ACTIVE');
});

test('komputer dengan status retired ditolak saat mengirimkan hasil scan', function () {
    $retiredComputer = Computer::factory()->create([
        'status' => 'retired',
    ]);

    Sanctum::actingAs($retiredComputer, ['scan:submit']);

    $response = $this->postJson('/api/scan-result', [
        'hostname' => $retiredComputer->hostname,
        'installed_software' => [
            ['name' => 'Chrome', 'version' => '140'],
        ],
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'status' => 'error',
            'message' => 'Komputer dalam status retired tidak dapat mengirimkan hasil scan.',
        ]);
});
