<?php

namespace Database\Factories;

use App\Models\Laboratory;
use App\Models\ReportApproval;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportApproval>
 */
class ReportApprovalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'laboratory_id' => Laboratory::factory(),
            'reviewed_by' => User::factory(),
            'report_type' => 'kepatuhan',
            'period' => now()->format('Y-m'),
            'status' => 'pending',
            'notes' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'reviewed_at' => now(),
            'notes' => 'Data sudah lengkap dan valid.',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'reviewed_at' => now(),
            'notes' => 'Data belum lengkap, perlu scan ulang.',
        ]);
    }
}
