<?php

namespace Database\Factories;

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\ScanSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplianceSnapshotFactory extends Factory
{
    protected $model = ComplianceSnapshot::class;

    public function definition(): array
    {
        return [
            'scan_session_id' => ScanSession::factory(),
            'computer_id' => Computer::factory(),
            'software_catalog_id' => null,
            'software_name' => fake()->randomElement(['Microsoft Office 2021', 'AutoCAD 2024', 'Matlab R2023b', 'SPSS Statistics 29']),
            'software_version' => fake()->numerify('#.#'),
            'status' => fake()->randomElement(['Berlisensi', 'Tidak Berlisensi', 'Grace Period', 'Perlu Ditinjau']),
            'keterangan' => fake()->optional()->sentence(),
            'license_inventory_id' => null,
            'detected_at' => now()->subDays(30),
            'scanned_at' => now(),
        ];
    }
}
