<?php

use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('license_key is encrypted at rest', function () {
    $catalog = SoftwareCatalog::create([
        'normalized_name' => 'Test Software',
        'category' => 'Commercial',
        'status' => 'Whitelist'
    ]);
    
    $rawKey = 'ABCD-1234-EFGH-5678';
    
    $license = LicenseInventory::create([
        'catalog_id' => $catalog->id,
        'license_key' => $rawKey,
        'proof_image' => 'test.jpg'
    ]);
    
    // Check database directly (bypass Eloquent)
    $dbValue = DB::table('license_inventories')->where('id', $license->id)->value('license_key');
    
    expect($dbValue)->not->toBe($rawKey);
    // Laravel default encryption is a JSON payload starting with eyJ if base64 encoded
    expect($dbValue)->toContain('eyJ');
});

test('license_key is correctly decrypted when accessed via model', function () {
    $catalog = SoftwareCatalog::create([
        'normalized_name' => 'Test Software'
    ]);
    
    $rawKey = 'DEFG-5678-HIJK-9012';
    
    $license = LicenseInventory::create([
        'catalog_id' => $catalog->id,
        'license_key' => $rawKey,
        'proof_image' => 'test.jpg'
    ]);
    
    $found = LicenseInventory::find($license->id);
    expect($found->license_key)->toBe($rawKey);
});

test('masked accessor returns correct format', function () {
    $rawKey = 'ABCD-1234-EFGH-5678';
    
    $license = new LicenseInventory([
        'license_key' => $rawKey
    ]);
    
    // Expected format: ABCD-1234-****-****
    expect($license->masked_license_key)->toBe('ABCD-1234-****-****');
});
