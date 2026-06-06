<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class AgentDownloadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    }

    /** @test */
    public function guest_cannot_download_agent()
    {
        $response = $this->get(route('agent.download'));
        $response->assertRedirect('/login');
    }

    /** @test */
    public function pimpinan_cannot_download_agent()
    {
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');

        $response = $this->actingAs($pimpinan)->get(route('agent.download'));
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_download_agent_and_receive_valid_zip()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Set test config/env values
        config(['app.url' => 'https://test-manifest.usn.ac.id']);
        config(['app.agent_registration_key' => 'TEST_SECRET_KEY_123']);

        $response = $this->actingAs($admin)->get(route('agent.download'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
        $response->assertHeader('Content-Disposition', 'attachment; filename=usn-manifest-agent.zip');

        // We can inspect the file content by writing the response content to a temp file
        $filePath = $response->getFile()->getPathname();
        $this->assertTrue(file_exists($filePath));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($filePath));

        // Verify all expected files are in the zip
        $this->assertNotFalse($zip->locateName('scanner.ps1'));
        $this->assertNotFalse($zip->locateName('setup_tasks.ps1'));
        $this->assertNotFalse($zip->locateName('config.json'));
        $this->assertNotFalse($zip->locateName('instruksi.txt'));

        // Verify config.json content
        $configContent = $zip->getFromName('config.json');
        $this->assertNotFalse($configContent);

        $configData = json_decode($configContent, true);
        $this->assertNotNull($configData);
        $this->assertEquals('https://test-manifest.usn.ac.id/api', $configData['baseUrl']);
        $this->assertEquals('TEST_SECRET_KEY_123', $configData['registrationKey']);

        // Verify instruksi.txt content
        $instructions = $zip->getFromName('instruksi.txt');
        $this->assertNotFalse($instructions);
        $this->assertStringContainsString('PANDUAN PEMASANGAN AGENT SCANNER USN MANIFEST', $instructions);
        $this->assertStringContainsString('scanner.ps1', $instructions);
        $this->assertStringContainsString('setup_tasks.ps1', $instructions);

        $zip->close();
        @unlink($filePath);
    }
}
