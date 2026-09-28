<?php

use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('can create a scan session linked to a computer', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);

    $session = ScanSession::create([
        'computer_id' => $computer->id,
        'scan_uuid' => (string) Str::uuid(),
        'started_at' => now()->subMinutes(5),
        'completed_at' => now(),
        'status' => 'completed',
        'trigger' => 'scheduled',
        'software_count' => 15,
        'agent_version' => '1.0.0',
    ]);

    expect($session->computer->id)->toBe($computer->id)
        ->and($computer->scanSessions)->toHaveCount(1)
        ->and($computer->latestScanSession->id)->toBe($session->id);
});

test('deleting a computer preserves scan sessions with nullOnDelete', function () {
    $lab = Laboratory::factory()->create();
    $computer = Computer::factory()->create(['laboratory_id' => $lab->id]);

    $session = ScanSession::create([
        'computer_id' => $computer->id,
        'scan_uuid' => (string) Str::uuid(),
        'status' => 'completed',
        'trigger' => 'scheduled',
        'software_count' => 10,
    ]);

    $computer->delete();

    $session->refresh();
    expect($session->computer_id)->toBeNull();
});

test('scan session enforces unique scan_uuid', function () {
    $uuid = (string) Str::uuid();
    ScanSession::factory()->create(['scan_uuid' => $uuid]);

    expect(fn () => ScanSession::factory()->create(['scan_uuid' => $uuid]))
        ->toThrow(QueryException::class);
});
