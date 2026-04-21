<?php

namespace Database\Seeders;

use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LicenseInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Definisikan Software yang harus NON-COMPLIANT (Tanpa Lisensi)
        // Kita hanya pastikan Katalog-nya ada dan bertipe Commercial
        $nonCompliantSoftwares = [
            'WinRAR',
            'Adobe Photoshop 2022',
            'Microsoft Office 2019'
        ];

        foreach ($nonCompliantSoftwares as $name) {
            SoftwareCatalog::firstOrCreate(
                ['normalized_name' => $name],
                ['category' => 'Commercial']
            );
        }

        // 2. Definisikan Software yang COMPLIANT (Memiliki Lisensi)
        $compliantLicenses = [
            [
                'name' => 'Google Chrome',
                'quota' => 999,
                'expiry' => Carbon::now()->addYears(10),
                'vendor' => 'Google LLC',
            ],
            [
                'name' => 'VLC Media Player',
                'quota' => 999,
                'expiry' => Carbon::now()->addYears(10),
                'vendor' => 'VideoLAN',
            ],
            [
                'name' => 'Microsoft Office 365',
                'quota' => 50,
                'expiry' => Carbon::create(2027, 12, 31),
                'vendor' => 'Microsoft Corporation',
            ],
        ];

        foreach ($compliantLicenses as $lic) {
            $catalog = SoftwareCatalog::firstOrCreate(
                ['normalized_name' => $lic['name']],
                ['category' => 'Commercial']
            );

            LicenseInventory::updateOrCreate(
                ['catalog_id' => $catalog->id],
                [
                    'purchase_order_number' => 'PO-' . Str::upper(Str::random(8)),
                    'quota_limit' => $lic['quota'],
                    'purchase_date' => Carbon::now()->subMonths(6),
                    'expiry_date' => $lic['expiry'],
                    'price_per_unit' => rand(0, 500000),
                    'license_key' => $this->generateFakeKey(),
                    'proof_image' => 'dummy_proof.jpg',
                    'notes' => 'Lisensi resmi untuk operasional.',
                ]
            );
        }

        // 3. Definisikan Software GRACE PERIOD (Hampir Kadaluwarsa)
        $graceCatalog = SoftwareCatalog::firstOrCreate(
            ['normalized_name' => 'Microsoft Office 2021'],
            ['category' => 'Commercial']
        );

        LicenseInventory::updateOrCreate(
            ['catalog_id' => $graceCatalog->id],
            [
                'purchase_order_number' => 'PO-' . Str::upper(Str::random(8)),
                'quota_limit' => 30,
                'purchase_date' => Carbon::now()->subYears(2),
                'expiry_date' => Carbon::now()->addDays(20),
                'price_per_unit' => 2500000,
                'license_key' => $this->generateFakeKey(),
                'proof_image' => 'dummy_proof.jpg',
                'notes' => 'Perlu pembaruan segera.',
            ]
        );
    }

    /**
     * Generate fake license key format XXXXX-XXXXX-XXXXX-XXXXX
     */
    private function generateFakeKey(): string
    {
        $parts = [];
        for ($i = 0; $i < 4; $i++) {
            $parts[] = Str::upper(Str::random(5));
        }
        return implode('-', $parts);
    }
}
