<?php

namespace Database\Factories;

use App\Models\Computer;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SoftwareDiscovery>
 */
class SoftwareDiscoveryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'computer_id' => Computer::factory(),
            'catalog_id' => SoftwareCatalog::factory(),
            'raw_name' => $this->faker->word(),
            'version' => $this->faker->numerify('#.#.#'),
            'vendor' => $this->faker->company(),
            'install_date' => $this->faker->date(),
        ];
    }
}
