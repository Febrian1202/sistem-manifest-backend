<?php

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\LicenseInventory;
use App\Models\ScanSession;
use App\Models\SoftwareCatalog;
use App\Services\SoftwareChangeDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('perubahan status compliance tercatat di histori compliance_snapshots', function () {
    $computer = Computer::factory()->create();
    Sanctum::actingAs($computer, ['scan:submit']);

    // Buat katalog software komersial
    $catalog = SoftwareCatalog::factory()->create([
        'normalized_name' => 'Adobe Photoshop',
        'category' => 'Commercial',
        'status' => 'Commercial',
    ]);

    // Scan 1: Adobe Photoshop tanpa lisensi -> menghasilkan status Tidak Berlisensi
    $this->postJson('/api/scan-result', [
        'scan_uuid' => (string) Str::uuid(),
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Adobe Photoshop', 'version' => '2024', 'vendor' => 'Adobe Systems'],
        ],
    ])->assertStatus(202);

    $session1 = ScanSession::where('computer_id', $computer->id)->first();
    expect($session1)->not->toBeNull();

    $snapshot1 = ComplianceSnapshot::where('scan_session_id', $session1->id)
        ->where('software_name', 'Adobe Photoshop')
        ->first();

    expect($snapshot1)->not->toBeNull()
        ->and($snapshot1->status)->toBe('Tidak Berlisensi');

    // Admin menambahkan lisensi untuk Adobe Photoshop
    $license = LicenseInventory::factory()->create([
        'catalog_id' => $catalog->id,
        'quota_limit' => 10,
        'expiry_date' => now()->addYear(),
    ]);

    // Scan 2: Adobe Photoshop dengan lisensi tersedia -> menghasilkan status Berlisensi
    $this->postJson('/api/scan-result', [
        'scan_uuid' => (string) Str::uuid(),
        'hostname' => $computer->hostname,
        'installed_software' => [
            ['name' => 'Adobe Photoshop', 'version' => '2024', 'vendor' => 'Adobe Systems'],
        ],
    ])->assertStatus(202);

    $session2 = ScanSession::where('computer_id', $computer->id)->latest('id')->first();
    expect($session2->id)->not->toBe($session1->id);

    $snapshot2 = ComplianceSnapshot::where('scan_session_id', $session2->id)
        ->where('software_name', 'Adobe Photoshop')
        ->first();

    expect($snapshot2)->not->toBeNull()
        ->and($snapshot2->status)->toBe('Berlisensi')
        ->and($snapshot2->license_inventory_id)->toBe($license->id);

    // Verifikasi kedua status terekam utuh secara berurutan
    $snapshots = ComplianceSnapshot::where('software_name', 'Adobe Photoshop')
        ->orderBy('id')
        ->pluck('status')
        ->all();

    expect($snapshots)->toEqual(['Tidak Berlisensi', 'Berlisensi']);

    // Verifikasi deteksi perubahan compliance oleh service
    $service = app(SoftwareChangeDetectionService::class);
    $complianceDiff = $service->compareComplianceSnapshots($session2, $session1);

    expect($complianceDiff)->toHaveCount(1)
        ->and($complianceDiff[0]['software_name'])->toBe('Adobe Photoshop')
        ->and($complianceDiff[0]['old_status'])->toBe('Tidak Berlisensi')
        ->and($complianceDiff[0]['new_status'])->toBe('Berlisensi');
});
