<?php

namespace Tests\Feature;

use App\Models\Laboratory;
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
    public function guest_cannot_access_download_page()
    {
        $response = $this->get(route('agent.download-page'));
        $response->assertRedirect('/login');
    }

    /** @test */
    public function guest_cannot_download_agent()
    {
        $response = $this->post(route('agent.download'));
        $response->assertRedirect('/login');
    }

    /** @test */
    public function pimpinan_cannot_access_download_page()
    {
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');

        $response = $this->actingAs($pimpinan)->get(route('agent.download-page'));
        $response->assertStatus(403);
    }

    /** @test */
    public function pimpinan_cannot_download_agent()
    {
        $pimpinan = User::factory()->create();
        $pimpinan->assignRole('pimpinan');

        $response = $this->actingAs($pimpinan)->post(route('agent.download'));
        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_download_page()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $lab = Laboratory::factory()->create([
            'name' => 'Laboratorium Multimedia',
            'code' => 'LAB-MM',
        ]);

        $response = $this->actingAs($admin)->get(route('agent.download-page'));

        $response->assertStatus(200);
        $response->assertSee('Download Tools Pemindai');
        $response->assertSee('Laboratorium Multimedia');
        $response->assertSee('LAB-MM');
        $response->assertSee('1-Jalankan-Scan-Sekarang.bat');
        $response->assertSee('2-Pasang-Jadwal-Otomatis.bat');
        $response->assertSee('3-Hapus-Jadwal-Otomatis.bat');
    }

    /** @test */
    public function admin_cannot_download_agent_without_laboratory()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('agent.download'), []);

        $response->assertSessionHasErrors('laboratory_id');
    }

    /** @test */
    public function admin_cannot_download_agent_with_invalid_laboratory()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('agent.download'), [
            'laboratory_id' => 99999,
        ]);

        $response->assertSessionHasErrors('laboratory_id');
    }

    /** @test */
    public function admin_can_download_agent_and_receive_valid_zip()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $lab = Laboratory::factory()->create([
            'name' => 'Laboratorium Jaringan',
            'code' => 'LAB-NET',
        ]);

        // Set test config/env values
        config(['app.url' => 'https://test-manifest.usn.ac.id']);
        config(['app.agent_registration_key' => 'TEST_SECRET_KEY_123']);

        $response = $this->actingAs($admin)->post(route('agent.download'), [
            'laboratory_id' => $lab->id,
        ]);

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
        $this->assertNotFalse($zip->locateName('1-Jalankan-Scan-Sekarang.bat'));
        $this->assertNotFalse($zip->locateName('2-Pasang-Jadwal-Otomatis.bat'));
        $this->assertNotFalse($zip->locateName('3-Hapus-Jadwal-Otomatis.bat'));
        $this->assertNotFalse($zip->locateName('config.json'));
        $this->assertNotFalse($zip->locateName('instruksi.txt'));

        // Verify config.json content
        $configContent = $zip->getFromName('config.json');
        $this->assertNotFalse($configContent);

        $configData = json_decode($configContent, true);
        $this->assertNotNull($configData);
        $this->assertEquals('https://test-manifest.usn.ac.id/api', $configData['baseUrl']);
        $this->assertEquals('TEST_SECRET_KEY_123', $configData['registrationKey']);
        $this->assertEquals($lab->id, $configData['laboratoryId']);
        $this->assertEquals('Laboratorium Jaringan', $configData['laboratoryName']);

        // Verify instruksi.txt content
        $instructions = $zip->getFromName('instruksi.txt');
        $this->assertNotFalse($instructions);
        $this->assertStringContainsString('PANDUAN PEMASANGAN TOOLS PEMINDAI USN MANIFEST', $instructions);
        $this->assertStringContainsString('scanner.ps1', $instructions);
        $this->assertStringContainsString('setup_tasks.ps1', $instructions);
        $this->assertStringContainsString('1-Jalankan-Scan-Sekarang.bat', $instructions);
        $this->assertStringContainsString('2-Pasang-Jadwal-Otomatis.bat', $instructions);
        $this->assertStringContainsString('3-Hapus-Jadwal-Otomatis.bat', $instructions);

        $zip->close();
        @unlink($filePath);
    }

    /** @test */
    public function batch_launcher_files_have_valid_cmd_syntax_and_no_premature_parenthesis_blocks()
    {
        $batFiles = [
            '1-Jalankan-Scan-Sekarang.bat' => base_path('script/agent/1-Jalankan-Scan-Sekarang.bat'),
            '2-Pasang-Jadwal-Otomatis.bat' => base_path('script/agent/2-Pasang-Jadwal-Otomatis.bat'),
            '3-Hapus-Jadwal-Otomatis.bat' => base_path('script/agent/3-Hapus-Jadwal-Otomatis.bat'),
        ];

        foreach ($batFiles as $filename => $path) {
            $this->assertFileExists($path, "File {$filename} must exist.");
            $content = file_get_contents($path);

            // Ensure no raw unescaped parentheses like '(Extract All)' or '(UAC)' that break CMD compound blocks
            $this->assertStringNotContainsString('(Extract All)', $content, "{$filename} must not contain raw (Extract All) inside parentheses blocks.");
            $this->assertStringNotContainsString('(UAC)', $content, "{$filename} must not contain raw (UAC) inside parentheses blocks.");

            // On Windows platforms, execute cmd.exe syntax check
            if (PHP_OS_FAMILY === 'Windows') {
                $output = shell_exec('cmd.exe /c "'.str_replace('/', '\\', $path).'" < nul 2>&1');
                $this->assertNotNull($output);
                $this->assertStringNotContainsString('was unexpected at this time', $output, "{$filename} caused a CMD parsing syntax error.");
            }
        }
    }
}
