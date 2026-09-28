<?php

use App\Models\Computer;
use App\Models\Laboratory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new computer has default active status', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);

    expect($computer->status)->toBe('active');
});

test('scopeActive filters only active computers', function () {
    $lab = Laboratory::factory()->create();
    $active = Computer::factory()->create(['laboratory_id' => $lab->id, 'status' => 'active']);
    $inactive = Computer::factory()->create(['laboratory_id' => $lab->id, 'status' => 'inactive']);
    $retired = Computer::factory()->create(['laboratory_id' => $lab->id, 'status' => 'retired']);

    $activeComputers = Computer::active()->get();

    expect($activeComputers->pluck('id'))->toContain($active->id)
        ->and($activeComputers->pluck('id'))->not->toContain($inactive->id)
        ->and($activeComputers->pluck('id'))->not->toContain($retired->id);
});
