<?php

use App\Models\Computer;
use App\Models\Laboratory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.agent_registration_key' => 'TEST_AGENT_KEY_123']);
});

test('registers a computer with laboratory_id', function () {
    $lab = Laboratory::factory()->create([
        'name' => 'Lab Jaringan Komputer',
    ]);

    $response = $this->withHeader('X-Agent-Key', 'TEST_AGENT_KEY_123')
        ->postJson('/api/agent/register', [
            'hostname' => 'PC-LAB1-01',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'serial_number' => 'SN-1001',
            'laboratory_id' => $lab->id,
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'message',
            'computer_id',
            'hostname',
            'laboratory_id',
            'token',
        ])
        ->assertJson([
            'status' => 'registered',
            'hostname' => 'PC-LAB1-01',
            'laboratory_id' => $lab->id,
        ]);

    $this->assertDatabaseHas('computers', [
        'hostname' => 'PC-LAB1-01',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'laboratory_id' => $lab->id,
    ]);
});

test('rejects registration without laboratory_id', function () {
    $response = $this->withHeader('X-Agent-Key', 'TEST_AGENT_KEY_123')
        ->postJson('/api/agent/register', [
            'hostname' => 'PC-LAB1-01',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['laboratory_id']);
});

test('rejects registration with invalid laboratory_id', function () {
    $response = $this->withHeader('X-Agent-Key', 'TEST_AGENT_KEY_123')
        ->postJson('/api/agent/register', [
            'hostname' => 'PC-LAB1-01',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'laboratory_id' => 99999,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['laboratory_id']);
});

test('updates laboratory_id on re-registration', function () {
    $lab1 = Laboratory::factory()->create(['name' => 'Lab 1']);
    $lab2 = Laboratory::factory()->create(['name' => 'Lab 2']);

    $mac = '11:22:33:44:55:66';

    $this->withHeader('X-Agent-Key', 'TEST_AGENT_KEY_123')
        ->postJson('/api/agent/register', [
            'hostname' => 'PC-01',
            'mac_address' => $mac,
            'laboratory_id' => $lab1->id,
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('computers', [
        'mac_address' => $mac,
        'laboratory_id' => $lab1->id,
    ]);

    $this->withHeader('X-Agent-Key', 'TEST_AGENT_KEY_123')
        ->postJson('/api/agent/register', [
            'hostname' => 'PC-01-MOVED',
            'mac_address' => $mac,
            'laboratory_id' => $lab2->id,
        ])
        ->assertStatus(201);

    $this->assertDatabaseHas('computers', [
        'mac_address' => $mac,
        'hostname' => 'PC-01-MOVED',
        'laboratory_id' => $lab2->id,
    ]);

    expect(Computer::where('mac_address', $mac)->count())->toBe(1);
});

test('rejects registration with invalid registration key', function () {
    $lab = Laboratory::factory()->create();

    $response = $this->withHeader('X-Agent-Key', 'WRONG_KEY')
        ->postJson('/api/agent/register', [
            'hostname' => 'PC-01',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'laboratory_id' => $lab->id,
        ]);

    $response->assertStatus(401)
        ->assertJson([
            'status' => 'error',
            'message' => 'Unauthorized: Invalid Registration Key',
        ]);
});

test('re-registration revokes old tokens', function () {
    $lab = Laboratory::factory()->create();
    $mac = 'AA:BB:CC:DD:EE:FF';

    $response1 = $this->withHeader('X-Agent-Key', 'TEST_AGENT_KEY_123')
        ->postJson('/api/agent/register', [
            'hostname' => 'PC-01',
            'mac_address' => $mac,
            'laboratory_id' => $lab->id,
        ]);

    $token1 = $response1->json('token');

    $computer = Computer::where('mac_address', $mac)->first();
    expect($computer->tokens()->count())->toBe(1);

    $response2 = $this->withHeader('X-Agent-Key', 'TEST_AGENT_KEY_123')
        ->postJson('/api/agent/register', [
            'hostname' => 'PC-01',
            'mac_address' => $mac,
            'laboratory_id' => $lab->id,
        ]);

    $token2 = $response2->json('token');

    expect($token1)->not->toBe($token2);
    expect($computer->fresh()->tokens()->count())->toBe(1);
});
