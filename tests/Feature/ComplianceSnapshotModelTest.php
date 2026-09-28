<?php

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Models\SoftwareCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a compliance snapshot associated with a scan session', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);
    $session = ScanSession::factory()->create(['computer_id' => $computer->id]);
    $catalog = SoftwareCatalog::create([
        'normalized_name' => 'AutoCAD 2024',
        'category' => 'Commercial',
        'status' => 'Unreviewed',
    ]);

    $snapshot = ComplianceSnapshot::create([
        'scan_session_id' => $session->id,
        'computer_id' => $computer->id,
        'software_catalog_id' => $catalog->id,
        'software_name' => 'AutoCAD 2024',
        'software_version' => '24.0',
        'status' => 'Tidak Berlisensi',
        'keterangan' => 'Belum ada lisensi terpasang.',
        'scanned_at' => now(),
    ]);

    expect($snapshot->scanSession->id)->toBe($session->id)
        ->and($snapshot->computer->id)->toBe($computer->id)
        ->and($snapshot->softwareCatalog->id)->toBe($catalog->id)
        ->and($session->complianceSnapshots)->toHaveCount(1);
});

test('deleting a computer retains compliance snapshots with null computer_id', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);
    $session = ScanSession::factory()->create(['computer_id' => $computer->id]);

    $snapshot = ComplianceSnapshot::factory()->create([
        'scan_session_id' => $session->id,
        'computer_id' => $computer->id,
        'status' => 'Berlisensi',
        'scanned_at' => now(),
    ]);

    $computer->delete();

    $snapshot->refresh();
    expect($snapshot->computer_id)->toBeNull();
});
