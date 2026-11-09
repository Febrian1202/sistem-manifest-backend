<?php

use App\Models\Computer;
use App\Models\Laboratory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('agent can register and receive token', function () {
    $mac = 'AA:BB:CC:DD:EE:FF';
    $lab = Laboratory::factory()->create();

    $response = $this->withHeader('X-Agent-Key', config('app.agent_registration_key'))
        ->postJson('/api/agent/register', [
            'mac_address' => $mac,
            'hostname' => 'TEST-PC',
            'serial_number' => 'SN123',
            'laboratory_id' => $lab->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['token', 'computer_id', 'hostname', 'laboratory_id']);

    $this->assertDatabaseHas('computers', [
        'mac_address' => $mac,
        'hostname' => 'TEST-PC',
        'laboratory_id' => $lab->id,
    ]);
});

test('scan result requires authentication', function () {
    $response = $this->postJson('/api/scan-result', []);
    $response->assertStatus(401);
});

test('authenticated agent can submit scan', function () {
    $computer = Computer::factory()->create([
        'mac_address' => '11:22:33:44:55:66',
        'hostname' => 'INITIAL-NAME',
    ]);

    // Issue token with correct ability
    $token = $computer->createToken('agent', ['scan:submit'])->plainTextToken;

    $payload = [
        'hostname' => 'UPDATED-NAME',
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
        ],
    ];

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
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

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/scan-result', [
            'computer_name' => 'TEST',
            'installed_software' => [],
        ]);

    $response->assertStatus(403);
});

test('multiple computers with identical hostname can submit scans independently', function () {
    $comp1 = Computer::factory()->create([
        'mac_address' => 'AA:BB:CC:DD:EE:01',
        'hostname' => 'MYPCONEPROK-24',
    ]);
    $comp2 = Computer::factory()->create([
        'mac_address' => 'AA:BB:CC:DD:EE:02',
        'hostname' => 'MYPCONEPROK-24',
    ]);

    // Comp 1 submits scan
    Sanctum::actingAs($comp1, ['scan:submit']);
    $this->postJson('/api/scan-result', [
        'hostname' => 'MYPCONEPROK-24',
        'mac_address' => 'AA:BB:CC:DD:EE:01',
        'ip_address' => '192.168.1.101',
        'installed_software' => [
            ['name' => 'Chrome', 'version' => '120'],
        ],
    ])->assertStatus(202);

    // Comp 2 submits scan with same hostname
    Sanctum::actingAs($comp2, ['scan:submit']);
    $this->postJson('/api/scan-result', [
        'hostname' => 'MYPCONEPROK-24',
        'mac_address' => 'AA:BB:CC:DD:EE:02',
        'ip_address' => '192.168.1.102',
        'installed_software' => [
            ['name' => 'Firefox', 'version' => '121'],
        ],
    ])->assertStatus(202);

    expect($comp1->fresh()->ip_address)->toBe('192.168.1.101');
    expect($comp2->fresh()->ip_address)->toBe('192.168.1.102');
});

test('retired computer cannot submit scan results', function () {
    $computer = Computer::factory()->create([
        'status' => 'retired',
    ]);

    $token = $computer->createToken('agent', ['scan:submit'])->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/scan-result', [
            'hostname' => 'RETIRED-PC',
            'installed_software' => [],
        ]);

    $response->assertStatus(403)
        ->assertJson([
            'status' => 'error',
            'message' => 'Komputer dalam status retired tidak dapat mengirimkan hasil scan.',
        ]);
});
