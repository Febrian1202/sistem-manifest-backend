<?php

use App\Models\Computer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sensitive computer attributes are hidden from array and json serialization', function () {
    $computer = Computer::create([
        'hostname' => 'PC-LAB-01',
        'ip_address' => '192.168.1.100',
        'mac_address' => '00:11:22:33:44:55',
        'serial_number' => 'SN1234567890',
        'os_name' => 'Windows 11',
    ]);

    $array = $computer->toArray();
    $json = $computer->toJson();

    // Sensitive attributes should not be present in array or json serialization
    expect($array)->not->toHaveKey('mac_address');
    expect($array)->not->toHaveKey('serial_number');
    expect($array)->not->toHaveKey('ip_address');

    expect($json)->not->toContain('mac_address');
    expect($json)->not->toContain('serial_number');
    expect($json)->not->toContain('ip_address');

    expect($json)->not->toContain('00:11:22:33:44:55');
    expect($json)->not->toContain('SN1234567890');
    expect($json)->not->toContain('192.168.1.100');

    // Non-sensitive attributes should be visible
    expect($array)->toHaveKey('hostname');
    expect($array)->toHaveKey('os_name');
    expect($json)->toContain('PC-LAB-01');
    expect($json)->toContain('Windows 11');
});
