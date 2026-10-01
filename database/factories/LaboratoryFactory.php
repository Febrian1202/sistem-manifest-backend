<?php

namespace Database\Factories;

use App\Models\Faculty;
use App\Models\Laboratory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Laboratory>
 */
class LaboratoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'faculty_id' => Faculty::factory(),
            'name' => 'Lab '.fake()->word(),
            'code' => 'LAB-'.strtoupper(fake()->unique()->lexify('???')),
            'building' => fake()->optional()->word(),
            'floor' => fake()->optional()->randomElement(['1', '2', '3']),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
