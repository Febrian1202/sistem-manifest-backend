<?php

namespace Database\Factories;

use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScanSoftwareResultFactory extends Factory
{
    protected $model = ScanSoftwareResult::class;

    public function definition(): array
    {
        return [
            'scan_session_id' => ScanSession::factory(),
            'catalog_id' => null,
            'raw_name' => fake()->randomElement(['Google Chrome', 'VLC Media Player', 'Adobe Acrobat Reader', 'Git for Windows']),
            'version' => fake()->numerify('#.#.##'),
            'vendor' => fake()->company(),
            'install_date' => fake()->date(),
        ];
    }
}
