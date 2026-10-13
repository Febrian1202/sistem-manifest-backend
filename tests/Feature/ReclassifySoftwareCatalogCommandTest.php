<?php

use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('software:reclassify command prunes junk catalogs and their discoveries', function () {
    $faculty = Faculty::factory()->create();
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);

    $junkCatalog = SoftwareCatalog::create([
        'normalized_name' => 'Application Verifier x64 External Package (DesktopEditions)',
        'category' => 'Freeware',
        'status' => 'Unreviewed',
    ]);

    SoftwareDiscovery::create([
        'computer_id' => $computer->id,
        'catalog_id' => $junkCatalog->id,
        'raw_name' => 'Application Verifier x64 External Package (DesktopEditions)',
    ]);

    $cleanCatalog = SoftwareCatalog::create([
        'normalized_name' => 'Adobe Premiere Pro 2019',
        'category' => 'Freeware',
        'status' => 'Unreviewed',
    ]);

    $this->artisan('software:reclassify')
        ->expectsOutputToContain('Katalog sampah dibersihkan')
        ->assertSuccessful();

    expect(SoftwareCatalog::find($junkCatalog->id))->toBeNull();
    expect(SoftwareDiscovery::where('raw_name', 'Application Verifier x64 External Package (DesktopEditions)')->exists())->toBeFalse();
    expect(SoftwareCatalog::find($cleanCatalog->id))->not->toBeNull();
    expect($cleanCatalog->fresh()->category)->toBe('Commercial');
    expect($cleanCatalog->fresh()->status)->toBe('Whitelist');
});

test('software:reclassify does not prune junk catalog if licenses are attached', function () {
    $faculty = Faculty::factory()->create();
    $lab = Laboratory::factory()->create(['faculty_id' => $faculty->id]);

    $junkCatalog = SoftwareCatalog::create([
        'normalized_name' => 'WinAppDeploy Custom Tool',
        'category' => 'Commercial',
        'status' => 'Whitelist',
    ]);

    LicenseInventory::create([
        'catalog_id' => $junkCatalog->id,
        'license_name' => 'Special WinAppDeploy License',
        'license_key' => 'ABCDE-12345',
        'total_quota' => 5,
        'purchase_date' => now()->subMonth(),
    ]);

    $this->artisan('software:reclassify')
        ->assertSuccessful();

    expect(SoftwareCatalog::find($junkCatalog->id))->not->toBeNull();
});

test('software:reclassify dry-run mode does not modify the database', function () {
    $junkCatalog = SoftwareCatalog::create([
        'normalized_name' => 'EPSON L120 Series Printer Uninstall',
        'category' => 'Freeware',
        'status' => 'Unreviewed',
    ]);

    $cleanCatalog = SoftwareCatalog::create([
        'normalized_name' => 'TablePlus 6.8.1',
        'category' => 'Freeware',
        'status' => 'Unreviewed',
    ]);

    $this->artisan('software:reclassify --dry-run')
        ->expectsOutputToContain('[DRY-RUN]')
        ->assertSuccessful();

    expect(SoftwareCatalog::find($junkCatalog->id))->not->toBeNull();
    expect($cleanCatalog->fresh()->category)->toBe('Freeware');
    expect($cleanCatalog->fresh()->status)->toBe('Unreviewed');
});

test('software:reclassify clears cache when changes are made', function () {
    Cache::put('dashboard.charts', 'some_cached_value', 300);

    SoftwareCatalog::create([
        'normalized_name' => 'CapCut',
        'category' => 'Freeware',
        'status' => 'Unreviewed',
    ]);

    $this->artisan('software:reclassify')
        ->assertSuccessful();

    expect(Cache::has('dashboard.charts'))->toBeFalse();
});
