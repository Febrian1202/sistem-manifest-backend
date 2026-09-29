<?php

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Services\SoftwareChangeDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('compareSessions detects added, removed, and version changed software between two sessions', function () {
    $computer = Computer::factory()->create();

    $session1 = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
        'started_at' => now()->subDays(2),
        'completed_at' => now()->subDays(2)->addMinutes(5),
    ]);

    ScanSoftwareResult::create([
        'scan_session_id' => $session1->id,
        'raw_name' => 'Google Chrome',
        'version' => '120.0.0',
        'vendor' => 'Google LLC',
    ]);

    ScanSoftwareResult::create([
        'scan_session_id' => $session1->id,
        'raw_name' => 'Microsoft Office',
        'version' => '2021',
        'vendor' => 'Microsoft',
    ]);

    $session2 = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
        'started_at' => now()->subDay(),
        'completed_at' => now()->subDay()->addMinutes(5),
    ]);

    // Google Chrome upgraded to 121.0.0
    ScanSoftwareResult::create([
        'scan_session_id' => $session2->id,
        'raw_name' => 'Google Chrome',
        'version' => '121.0.0',
        'vendor' => 'Google LLC',
    ]);

    // VS Code added
    ScanSoftwareResult::create([
        'scan_session_id' => $session2->id,
        'raw_name' => 'Visual Studio Code',
        'version' => '1.85.0',
        'vendor' => 'Microsoft',
    ]);

    // Microsoft Office was removed (not present in session2)

    $service = app(SoftwareChangeDetectionService::class);
    $diff = $service->compareSessions($session2, $session1);

    expect($diff['added'])->toHaveCount(1)
        ->and($diff['added'][0]['raw_name'])->toBe('Visual Studio Code')
        ->and($diff['added'][0]['type'])->toBe('added')
        ->and($diff['removed'])->toHaveCount(1)
        ->and($diff['removed'][0]['raw_name'])->toBe('Microsoft Office')
        ->and($diff['removed'][0]['type'])->toBe('removed')
        ->and($diff['changed'])->toHaveCount(1)
        ->and($diff['changed'][0]['raw_name'])->toBe('Google Chrome')
        ->and($diff['changed'][0]['old_version'])->toBe('120.0.0')
        ->and($diff['changed'][0]['new_version'])->toBe('121.0.0')
        ->and($diff['changed'][0]['type'])->toBe('version_changed')
        ->and($diff['summary']['added_count'])->toBe(1)
        ->and($diff['summary']['removed_count'])->toBe(1)
        ->and($diff['summary']['changed_count'])->toBe(1)
        ->and($diff['summary']['total_changes'])->toBe(3);
});

test('compareSessions automatically resolves preceding completed session if none is passed', function () {
    $computer = Computer::factory()->create();

    $session1 = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
        'started_at' => now()->subDays(3),
        'completed_at' => now()->subDays(3)->addMinutes(5),
    ]);

    ScanSoftwareResult::create([
        'scan_session_id' => $session1->id,
        'raw_name' => 'VLC Media Player',
        'version' => '3.0.18',
    ]);

    $session2 = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
        'started_at' => now()->subDay(),
        'completed_at' => now()->subDay()->addMinutes(5),
    ]);

    ScanSoftwareResult::create([
        'scan_session_id' => $session2->id,
        'raw_name' => 'VLC Media Player',
        'version' => '3.0.20',
    ]);

    $service = app(SoftwareChangeDetectionService::class);
    $diff = $service->compareSessions($session2);

    expect($diff['previous_session'])->not->toBeNull()
        ->and($diff['previous_session']->id)->toBe($session1->id)
        ->and($diff['changed'])->toHaveCount(1)
        ->and($diff['changed'][0]['raw_name'])->toBe('VLC Media Player')
        ->and($diff['changed'][0]['old_version'])->toBe('3.0.18')
        ->and($diff['changed'][0]['new_version'])->toBe('3.0.20');
});

test('compareSessions detects returned software that was absent in previous session but existed earlier', function () {
    $computer = Computer::factory()->create();

    // Session 1: Had GIMP
    $session1 = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
        'started_at' => now()->subDays(5),
        'completed_at' => now()->subDays(5)->addMinutes(5),
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $session1->id,
        'raw_name' => 'GIMP',
        'version' => '2.10.32',
    ]);

    // Session 2: GIMP removed
    $session2 = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
        'started_at' => now()->subDays(3),
        'completed_at' => now()->subDays(3)->addMinutes(5),
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $session2->id,
        'raw_name' => '7-Zip',
        'version' => '23.01',
    ]);

    // Session 3: GIMP re-installed
    $session3 = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
        'started_at' => now()->subDay(),
        'completed_at' => now()->subDay()->addMinutes(5),
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $session3->id,
        'raw_name' => '7-Zip',
        'version' => '23.01',
    ]);
    ScanSoftwareResult::create([
        'scan_session_id' => $session3->id,
        'raw_name' => 'GIMP',
        'version' => '2.10.34',
    ]);

    $service = app(SoftwareChangeDetectionService::class);
    $diff = $service->compareSessions($session3, $session2);

    expect($diff['returned'])->toHaveCount(1)
        ->and($diff['returned'][0]['raw_name'])->toBe('GIMP')
        ->and($diff['returned'][0]['type'])->toBe('returned')
        ->and($diff['added'])->toBeEmpty();
});

test('compareComplianceSnapshots detects status transitions between sessions', function () {
    $computer = Computer::factory()->create();

    $session1 = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
        'started_at' => now()->subDays(2),
    ]);

    ComplianceSnapshot::create([
        'scan_session_id' => $session1->id,
        'computer_id' => $computer->id,
        'software_name' => 'WinRAR',
        'software_version' => '6.0',
        'status' => 'unlicensed',
        'keterangan' => 'Trial expired',
        'scanned_at' => now()->subDays(2),
    ]);

    $session2 = ScanSession::factory()->create([
        'computer_id' => $computer->id,
        'status' => 'completed',
        'started_at' => now()->subDay(),
    ]);

    ComplianceSnapshot::create([
        'scan_session_id' => $session2->id,
        'computer_id' => $computer->id,
        'software_name' => 'WinRAR',
        'software_version' => '6.0',
        'status' => 'licensed',
        'keterangan' => 'License key applied',
        'scanned_at' => now()->subDay(),
    ]);

    $service = app(SoftwareChangeDetectionService::class);
    $complianceChanges = $service->compareComplianceSnapshots($session2, $session1);

    expect($complianceChanges)->toHaveCount(1)
        ->and($complianceChanges[0]['software_name'])->toBe('WinRAR')
        ->and($complianceChanges[0]['old_status'])->toBe('unlicensed')
        ->and($complianceChanges[0]['new_status'])->toBe('licensed');
});

test('getGlobalChanges returns all detected changes across computers with filtering', function () {
    $lab1 = Laboratory::factory()->create(['name' => 'Lab Komputasi']);
    $lab2 = Laboratory::factory()->create(['name' => 'Lab Jaringan']);

    $pc1 = Computer::factory()->create(['laboratory_id' => $lab1->id, 'hostname' => 'PC-KOMP-01']);
    $pc2 = Computer::factory()->create(['laboratory_id' => $lab2->id, 'hostname' => 'PC-JAR-01']);

    // PC1 Session 1
    $pc1S1 = ScanSession::factory()->create([
        'computer_id' => $pc1->id,
        'status' => 'completed',
        'started_at' => now()->subDays(5),
    ]);
    ScanSoftwareResult::create(['scan_session_id' => $pc1S1->id, 'raw_name' => 'Python', 'version' => '3.11']);

    // PC1 Session 2 (Python upgraded)
    $pc1S2 = ScanSession::factory()->create([
        'computer_id' => $pc1->id,
        'status' => 'completed',
        'started_at' => now()->subDays(2),
    ]);
    ScanSoftwareResult::create(['scan_session_id' => $pc1S2->id, 'raw_name' => 'Python', 'version' => '3.12']);

    // PC2 Session 1
    $pc2S1 = ScanSession::factory()->create([
        'computer_id' => $pc2->id,
        'status' => 'completed',
        'started_at' => now()->subDays(4),
    ]);
    ScanSoftwareResult::create(['scan_session_id' => $pc2S1->id, 'raw_name' => 'Wireshark', 'version' => '4.0.0']);

    // PC2 Session 2 (Wireshark removed, Nmap added)
    $pc2S2 = ScanSession::factory()->create([
        'computer_id' => $pc2->id,
        'status' => 'completed',
        'started_at' => now()->subDays(1),
    ]);
    ScanSoftwareResult::create(['scan_session_id' => $pc2S2->id, 'raw_name' => 'Nmap', 'version' => '7.94']);

    $service = app(SoftwareChangeDetectionService::class);

    // Filter by Lab 1
    $changesLab1 = $service->getGlobalChanges(['laboratory_id' => $lab1->id]);
    expect($changesLab1)->toHaveCount(1)
        ->and($changesLab1->first()['raw_name'])->toBe('Python')
        ->and($changesLab1->first()['type'])->toBe('version_changed');

    // Filter by type 'added' across all labs
    $addedChanges = $service->getGlobalChanges(['change_type' => 'added']);
    expect($addedChanges)->toHaveCount(1)
        ->and($addedChanges->first()['raw_name'])->toBe('Nmap');
});
