<?php

namespace Tests\Feature;

use App\Models\Computer;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceCacheTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_compliance_query_calculates_deficit_correctly()
    {
        // 1. Setup Data
        $software = SoftwareCatalog::create([
            'normalized_name' => 'Test Commercial Soft',
            'category' => 'Commercial',
            'status' => 'Unreviewed',
        ]);

        // 5 installations on 5 different computers
        for ($i = 0; $i < 5; $i++) {
            $computer = Computer::create(['hostname' => 'PC-0'.$i]);
            SoftwareDiscovery::create([
                'computer_id' => $computer->id,
                'catalog_id' => $software->id,
                'raw_name' => 'Test Commercial Soft',
            ]);
        }

        // 3 licenses
        LicenseInventory::create([
            'catalog_id' => $software->id,
            'quota_limit' => 3,
            'proof_image' => 'test.jpg',
        ]);

        // 2. Perform Request
        $response = $this->actingAs($this->admin)->get('/compliance');

        // 3. Assert Results
        $response->assertStatus(200);
        $softwareData = $response->viewData('softwares')->first();

        $this->assertEquals(5, $softwareData->installed_count);
        $this->assertEquals(3, $softwareData->owned_count);
        $this->assertEquals(2, $softwareData->deficit);
    }

    public function test_dashboard_statistics_are_cached()
    {
        // Use array driver for testing as it supports basic caching
        config(['cache.default' => 'array']);

        Computer::create(['hostname' => 'PC-CACHE-TEST']);

        // Warm up cache
        $this->actingAs($this->admin)->get('/dashboard');

        // Enable query log
        DB::enableQueryLog();

        // 2nd request - should hit cache
        $this->actingAs($this->admin)->get('/dashboard');

        // Assert no database queries were made for the statistics
        $queryCount = count(DB::getQueryLog());
        // Normal keys work with any driver
        $this->assertEquals(0, $queryCount, 'Dashboard metrics were recalculated instead of being served from cache.');

        DB::disableQueryLog();
    }

    public function test_data_change_invalidates_dashboard_cache()
    {
        config(['cache.default' => 'array']);

        $cacheKey = 'dashboard.stats.'.now()->format('Y-m');

        // Warm up cache
        $this->actingAs($this->admin)->get('/dashboard');
        $this->assertTrue(Cache::has($cacheKey));

        // Trigger invalidation via SoftwareCatalog status update
        $software = SoftwareCatalog::create([
            'normalized_name' => 'New Software',
            'category' => 'Commercial',
            'status' => 'Unreviewed',
        ]);

        // Assert cache is empty after creation
        $this->assertFalse(Cache::has($cacheKey));
    }
}
