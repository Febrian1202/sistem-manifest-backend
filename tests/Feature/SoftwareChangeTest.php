<?php

use App\Models\Computer;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Services\SoftwareChangeDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('software baru terdeteksi di scan kedua tersimpan di histori', function () {
    $computer = Computer::factory()->create();
    Sanctum::actingAs($computer, ['scan:submit']);

    // Scan 1: Hanya Google Chrome
    $this->postJson('/api/scan-result', [
        'scan_uuid' => (string) Str::uuid(),
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '140.0.0', 'vendor' => 'Google LLC'],
        ],
    ])->assertStatus(202);

    // Scan 2: Google Chrome + Visual Studio Code
    $this->postJson('/api/scan-result', [
        'scan_uuid' => (string) Str::uuid(),
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '140.0.0', 'vendor' => 'Google LLC'],
            ['name' => 'Visual Studio Code', 'version' => '1.90.0', 'vendor' => 'Microsoft'],
        ],
    ])->assertStatus(202);

    $sessions = ScanSession::where('computer_id', $computer->id)->orderBy('id')->get();
    expect($sessions)->toHaveCount(2);

    expect($sessions[0]->softwareResults)->toHaveCount(1)
        ->and($sessions[1]->softwareResults)->toHaveCount(2);

    $service = app(SoftwareChangeDetectionService::class);
    $diff = $service->compareSessions($sessions[1], $sessions[0]);

    expect($diff['added'])->toHaveCount(1)
        ->and($diff['added'][0]['raw_name'])->toBe('Visual Studio Code')
        ->and($diff['removed'])->toBeEmpty()
        ->and($diff['changed'])->toBeEmpty();
});

test('software yang dihapus di scan berikutnya tetap ada di histori', function () {
    $computer = Computer::factory()->create();
    Sanctum::actingAs($computer, ['scan:submit']);

    // Scan 1: Chrome + Office
    $this->postJson('/api/scan-result', [
        'scan_uuid' => (string) Str::uuid(),
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '140.0.0', 'vendor' => 'Google LLC'],
            ['name' => 'Microsoft Office', 'version' => '2021', 'vendor' => 'Microsoft'],
        ],
    ])->assertStatus(202);

    // Scan 2: hanya Chrome (Office dihapus)
    $this->postJson('/api/scan-result', [
        'scan_uuid' => (string) Str::uuid(),
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '140.0.0', 'vendor' => 'Google LLC'],
        ],
    ])->assertStatus(202);

    $sessions = ScanSession::where('computer_id', $computer->id)->orderBy('id')->get();
    expect($sessions)->toHaveCount(2);

    // Sesi 1 tetap memiliki 2 software
    expect($sessions[0]->softwareResults)->toHaveCount(2);
    // Sesi 2 memiliki 1 software
    expect($sessions[1]->softwareResults)->toHaveCount(1);

    // Microsoft Office tetap ada di scan_software_results sesi 1 (tidak hilang/terhapus)
    $officeHistorical = ScanSoftwareResult::where('scan_session_id', $sessions[0]->id)
        ->where('raw_name', 'Microsoft Office')
        ->first();
    expect($officeHistorical)->not->toBeNull();

    // Verifikasi service diff
    $service = app(SoftwareChangeDetectionService::class);
    $diff = $service->compareSessions($sessions[1], $sessions[0]);

    expect($diff['removed'])->toHaveCount(1)
        ->and($diff['removed'][0]['raw_name'])->toBe('Microsoft Office')
        ->and($diff['added'])->toBeEmpty();
});

test('perubahan versi software tercatat di histori antar-scan', function () {
    $computer = Computer::factory()->create();
    Sanctum::actingAs($computer, ['scan:submit']);

    // Scan 1: Chrome v140
    $this->postJson('/api/scan-result', [
        'scan_uuid' => (string) Str::uuid(),
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '140.0.0', 'vendor' => 'Google LLC'],
        ],
    ])->assertStatus(202);

    // Scan 2: Chrome v141
    $this->postJson('/api/scan-result', [
        'scan_uuid' => (string) Str::uuid(),
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Google Chrome', 'version' => '141.0.0', 'vendor' => 'Google LLC'],
        ],
    ])->assertStatus(202);

    $sessions = ScanSession::where('computer_id', $computer->id)->orderBy('id')->get();
    expect($sessions)->toHaveCount(2);

    $results = ScanSoftwareResult::where('raw_name', 'Google Chrome')
        ->orderBy('id')
        ->pluck('version')
        ->all();

    expect($results)->toEqual(['140.0.0', '141.0.0']);

    $service = app(SoftwareChangeDetectionService::class);
    $diff = $service->compareSessions($sessions[1], $sessions[0]);

    expect($diff['changed'])->toHaveCount(1)
        ->and($diff['changed'][0]['raw_name'])->toBe('Google Chrome')
        ->and($diff['changed'][0]['old_version'])->toBe('140.0.0')
        ->and($diff['changed'][0]['new_version'])->toBe('141.0.0');
});
