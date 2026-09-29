<?php

namespace Database\Factories;

use App\Models\ComplianceReport;
use App\Models\Computer;
use App\Models\SoftwareCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplianceReport>
 */
class ComplianceReportFactory extends Factory
{
    protected $model = ComplianceReport::class;

    public function definition(): array
    {
        return [
            'computer_id' => Computer::factory(),
            'software_catalog_id' => SoftwareCatalog::factory(),
            'software_name' => fake()->word(),
            'software_version' => '1.0.0',
            'status' => 'Berlisensi',
            'keterangan' => 'Sesuai Lisensi',
            'license_inventory_id' => null,
            'detected_at' => now(),
            'scanned_at' => now(),
        ];
    }
}
