<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SoftwareCatalog>
 */
class SoftwareCatalogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'normalized_name' => $this->faker->unique()->word(),
            'category' => $this->faker->randomElement(['Commercial', 'Freeware', 'OpenSource']),
            'status' => $this->faker->randomElement(['Whitelist', 'Blacklist', 'Unreviewed']),
            'description' => $this->faker->sentence(),
        ];
    }
}
