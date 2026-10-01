<?php

namespace Database\Factories;

use App\Models\Faculty;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseAllocation>
 */
class LicenseAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'license_inventory_id' => LicenseInventory::factory(),
            'faculty_id' => Faculty::factory(),
            'allocated_quota' => fake()->numberBetween(1, 10),
            'allocation_date' => fake()->date(),
            'start_date' => fake()->date(),
            'end_date' => fake()->dateTimeBetween('+6 months', '+2 years'),
            'status' => 'active',
            'notes' => fake()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'revoked',
        ]);
    }
}
