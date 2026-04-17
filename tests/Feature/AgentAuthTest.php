<?php

use App\Models\Computer;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('agent can register and receive token', function () {
    $mac = 'AA:BB:CC:DD:EE:FF';
    
    $response = $this->postJson('/api/agent/register', [
        'mac_address' => $mac,
        'hostname' => 'TEST-PC',
        'serial_number' => 'SN123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['token', 'computer_id', 'hostname']);

    $this->assertDatabaseHas('computers', [
        'mac_address' => $mac,
        'hostname' => 'TEST-PC',
    ]);
});

test('scan result requires authentication', function () {
    $response = $this->postJson('/api/scan-result', []);
    $response->assertStatus(401);
});

test('authenticated agent can submit scan', function () {
    $computer = Computer::factory()->create([
        'mac_address' => '11:22:33:44:55:66',
        'hostname' => 'INITIAL-NAME'
    ]);

    // Issue token with correct ability
    $token = $computer->createToken('agent', ['scan:submit'])->plainTextToken;

    $payload = [
        'computer_name' => 'UPDATED-NAME',
        'processor' => 'Intel i7',
        'ram_gb' => 16,
        'disk_total_gb' => 512,
        'disk_free_gb' => 200,
        'manufacturer' => 'Dell',
        'model' => 'XPS 15',
        'serial_number' => 'SERIAL-XPS',
        'ip_address' => '192.168.1.10',
        'mac_address' => '11:22:33:44:55:66',
        'os_name' => 'Windows 11',
        'installed_software' => [
            ['name' => 'Chrome', 'version' => '120', 'vendor' => 'Google'],
        ]
    ];

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/scan-result', $payload);

    $response->assertStatus(202);
    
    // Verify computer was updated
    $computer->refresh();
    expect($computer->hostname)->toBe('UPDATED-NAME');
    expect($computer->processor)->toBe('Intel i7');
});

test('agent cannot submit scan without scan:submit ability', function () {
    $computer = Computer::factory()->create();
    
    // Issue token WITHOUT scan:submit ability
    $token = $computer->createToken('agent', ['wrong:ability'])->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/scan-result', [
            'computer_name' => 'TEST',
            'installed_software' => []
        ]);

    $response->assertStatus(403);
});
