<?php

namespace Tests\Feature;

use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    }

    /** @test */
    public function admin_can_view_activity_logs_page()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/activity-logs');
        $response->assertStatus(200);
        $response->assertViewHas('logs');
        $response->assertViewHas('users');
    }

    /** @test */
    public function pimpinan_cannot_view_activity_logs_page()
    {
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');

        $response = $this->actingAs($pimpinan)->get('/activity-logs');
        $response->assertStatus(403);
    }

    /** @test */
    public function activity_logged_on_user_created()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->post('/accounts', [
            'name' => 'Budi Santoso',
            'email' => 'budi@usn.ac.id',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'pimpinan',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'created',
            'subject_type' => User::class,
            'causer_id' => $admin->id,
        ]);

        $createdUser = User::where('email', 'budi@usn.ac.id')->first();
        $log = Activity::where('subject_type', User::class)
            ->where('subject_id', $createdUser->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Akun pengguna Budi Santoso telah di-created', $log->description);
    }

    /** @test */
    public function activity_logged_on_user_updated()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($admin)->put("/accounts/{$user->id}", [
            'name' => 'New Name',
            'email' => $user->email,
            'role' => 'pimpinan',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'event' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        $log = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Old Name', $log->attribute_changes['old']['name']);
        $this->assertEquals('New Name', $log->attribute_changes['attributes']['name']);
    }

    /** @test */
    public function activity_logged_on_user_deleted()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('pimpinan');

        $this->actingAs($admin)->delete("/accounts/{$user->id}");

        $this->assertDatabaseHas('activity_log', [
            'event' => 'deleted',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
    }

    /** @test */
    public function activity_logged_on_password_reset()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $this->actingAs($admin)->put("/accounts/{$user->id}/reset-password", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => "Mereset password akun {$user->name}",
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'causer_id' => $admin->id,
        ]);
    }

    /** @test */
    public function activity_logged_on_password_change()
    {
        $user = User::factory()->create();
        $user->assignRole('pimpinan');

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Mengganti password sendiri',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'causer_id' => $user->id,
        ]);
    }

    /** @test */
    public function password_not_stored_in_log_properties()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        // 1. Reset password
        $this->actingAs($admin)->put("/accounts/{$user->id}/reset-password", [
            'password' => 'secret_password_reset',
            'password_confirmation' => 'secret_password_reset',
        ]);

        $log = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('description', "Mereset password akun {$user->name}")
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->properties->toArray());

        // 2. User created log properties (if any attributes are stored)
        $this->actingAs($admin)->post('/accounts', [
            'name' => 'Security Test User',
            'email' => 'security@usn.ac.id',
            'password' => 'secret_password_create',
            'password_confirmation' => 'secret_password_create',
            'role' => 'pimpinan',
        ]);

        $newCreatedUser = User::where('email', 'security@usn.ac.id')->first();
        $log = Activity::where('subject_type', User::class)
            ->where('subject_id', $newCreatedUser->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $attributeChanges = $log->attribute_changes;
        if (isset($attributeChanges['attributes'])) {
            $this->assertArrayNotHasKey('password', $attributeChanges['attributes']);
        }
        if (isset($attributeChanges['old'])) {
            $this->assertArrayNotHasKey('password', $attributeChanges['old']);
        }
    }

    /** @test */
    public function activity_logged_on_license_key_access()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $catalog = SoftwareCatalog::create([
            'normalized_name' => 'Visual Studio Code',
            'category' => 'Developer Tools',
            'status' => 'whitelist',
        ]);

        $license = LicenseInventory::create([
            'catalog_id' => $catalog->id,
            'purchase_order_number' => 'PO-999',
            'license_key' => 'SECRET-KEY-XYZ',
            'quota_limit' => 10,
            'purchase_date' => '2026-01-01',
        ]);

        $this->actingAs($admin)->post("/licenses/{$license->id}/key");

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Melihat license key',
            'subject_type' => LicenseInventory::class,
            'subject_id' => $license->id,
            'causer_id' => $admin->id,
        ]);

        $log = Activity::where('description', 'Melihat license key')
            ->where('subject_id', $license->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Visual Studio Code', $log->properties['software']);
    }

    /** @test */
    public function license_key_not_stored_in_log_properties()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $catalog = SoftwareCatalog::create([
            'normalized_name' => 'Visual Studio Code',
            'category' => 'Developer Tools',
            'status' => 'whitelist',
        ]);

        $this->actingAs($admin)->post('/licenses', [
            'catalog_id' => $catalog->id,
            'purchase_order_number' => 'PO-999',
            'license_key' => 'SECRET-KEY-ABC',
            'quota_limit' => 10,
            'purchase_date' => '2026-01-01',
        ]);

        $log = Activity::where('subject_type', LicenseInventory::class)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log);
        $attributeChanges = $log->attribute_changes;

        $this->assertArrayNotHasKey('license_key', $attributeChanges ?? []);
        if (isset($attributeChanges['attributes'])) {
            $this->assertArrayNotHasKey('license_key', $attributeChanges['attributes']);
        }
    }

    /** @test */
    public function activity_logs_page_supports_filtering()
    {
        $admin = User::factory()->create(['name' => 'Admin User']);
        $admin->assignRole('admin');

        $user = User::factory()->create(['name' => 'Test User']);
        $user->assignRole('pimpinan');

        // Create some logs
        activity()->causedBy($admin)->log('Log 1 by Admin');
        activity()->causedBy($user)->log('Log 2 by User');
        activity()->causedBy($admin)->log('Log 3 by Admin');

        // Filter by user
        $response = $this->actingAs($admin)->get("/activity-logs?user_id={$admin->id}");
        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        $this->assertCount(2, $logs);

        // Filter by search
        $response = $this->actingAs($admin)->get('/activity-logs?search=Log 2');
        $response->assertStatus(200);
        $logs = $response->viewData('logs');
        $this->assertCount(1, $logs);
    }
}
