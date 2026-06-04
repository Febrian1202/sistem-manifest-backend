<?php

namespace Database\Factories;

use App\Models\Computer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Computer>
 */
class ComputerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hostname' => $this->faker->unique()->domainWord(),
            'mac_address' => $this->faker->unique()->macAddress(),
            'processor' => 'Intel Core i5',
            'ram_gb' => 8,
            'disk_total_gb' => 256,
            'disk_free_gb' => 100,
            'manufacturer' => $this->faker->company(),
            'model' => $this->faker->word(),
            'serial_number' => $this->faker->unique()->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'os_name' => 'Windows 11 Pro',
            'os_version' => '22H2',
            'os_architecture' => '64-bit',
            'os_license_status' => 'Licensed',
            'last_seen_at' => now(),
        ];
    }
}
