<?php

namespace Database\Factories;

use App\Models\SoftwareCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LicenseInventory>
 */
class LicenseInventoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'catalog_id' => SoftwareCatalog::factory(),
            'license_key' => $this->faker->regexify('[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}'),
            'quota_limit' => $this->faker->numberBetween(1, 100),
            'purchase_date' => $this->faker->date(),
            'expiry_date' => $this->faker->dateTimeBetween('+1 month', '+2 years'),
            'price_per_unit' => $this->faker->numberBetween(100, 1000),
            'notes' => $this->faker->sentence(),
            'purchase_order_number' => $this->faker->unique()->bothify('PO-####'),
            'proof_image' => 'dummy.jpg',
        ];
    }
}
