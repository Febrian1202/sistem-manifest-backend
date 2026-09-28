<?php

use App\Models\Laboratory;
use App\Models\ReportApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('report approval supports period_start and period_end while keeping period string', function () {
    $lab = Laboratory::factory()->create();

    $report = ReportApproval::create([
        'laboratory_id' => $lab->id,
        'report_type' => 'compliance',
        'period' => '2026-09',
        'period_start' => '2026-09-01',
        'period_end' => '2026-09-30',
        'status' => 'pending',
    ]);

    expect($report->period)->toBe('2026-09')
        ->and($report->period_start->format('Y-m-d'))->toBe('2026-09-01')
        ->and($report->period_end->format('Y-m-d'))->toBe('2026-09-30');
});
