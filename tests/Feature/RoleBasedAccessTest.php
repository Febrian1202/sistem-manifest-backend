<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Computer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    }

    /** @test */
    public function unauthenticated_users_are_redirected_to_401_if_json()
    {
        $response = $this->getJson('/dashboard');
        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_access_dashboard()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
    }

    /** @test */
    public function pimpinan_can_access_dashboard()
    {
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');

        $response = $this->actingAs($pimpinan)->get('/dashboard');
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_create_license()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post('/licenses', [
            'catalog_id' => 1,
            'quota_limit' => 10,
            // ... other fields usually checked by store method
        ]);

        // Even if validation fails, it shouldn't be 403
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function pimpinan_cannot_create_license()
    {
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');

        $response = $this->actingAs($pimpinan)->post('/licenses', [
            'catalog_id' => 1,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function computers_using_tokens_cannot_access_web_routes()
    {
        $computer = Computer::factory()->create(['mac_address' => '00:11:22:33:44:55']);
        
        // Simulating the agent guard which is for agents, not web users
        $response = $this->actingAs($computer, 'agent')->get('/dashboard');
        
        // Since /dashboard is protected by 'auth' which usually defaults to 'web' session
        // a computer authenticated via 'agent' guard should not be allowed into 'web' routes
        // unless it's explicitly allowed in the middleware.
        $response->assertStatus(403); 
    }
}
