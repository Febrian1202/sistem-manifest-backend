<?php

namespace Database\Factories;

use App\Models\Computer;
use App\Models\ScanSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ScanSessionFactory extends Factory
{
    protected $model = ScanSession::class;

    public function definition(): array
    {
        return [
            'computer_id' => Computer::factory(),
            'scan_uuid' => (string) Str::uuid(),
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'status' => 'completed',
            'trigger' => 'scheduled',
            'software_count' => fake()->numberBetween(10, 40),
            'error_message' => null,
            'agent_version' => '1.0.0',
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'error_message' => 'Agent scan encountered registry read timeout.',
            'software_count' => 0,
        ]);
    }
}
