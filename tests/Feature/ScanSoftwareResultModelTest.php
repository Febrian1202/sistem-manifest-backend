<?php

use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create software result linked to a scan session and catalog', function () {
    $session = ScanSession::factory()->create();
    $catalog = SoftwareCatalog::create([
        'normalized_name' => 'Visual Studio Code',
        'category' => 'Freeware',
        'status' => 'Whitelist',
    ]);

    $result = ScanSoftwareResult::create([
        'scan_session_id' => $session->id,
        'catalog_id' => $catalog->id,
        'raw_name' => 'Microsoft Visual Studio Code (User)',
        'version' => '1.93.0',
        'vendor' => 'Microsoft Corporation',
        'install_date' => '2026-01-15',
    ]);

    expect($result->scanSession->id)->toBe($session->id)
        ->and($result->catalog->id)->toBe($catalog->id)
        ->and($session->softwareResults)->toHaveCount(1);
});

test('deleting scan session cascades to its software results', function () {
    $session = ScanSession::factory()->create();
    ScanSoftwareResult::factory()->count(3)->create(['scan_session_id' => $session->id]);

    expect(ScanSoftwareResult::count())->toBe(3);

    $session->delete();

    expect(ScanSoftwareResult::count())->toBe(0);
});
