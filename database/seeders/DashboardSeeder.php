<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. SEED DATA KOMPUTER
        // ==========================================

        $computers = [];
        $statuses = ['Licensed', 'Licensed', 'Licensed', 'Grace Period', 'Unlicensed'];
        $osTypes = [
            ['name' => 'Microsoft Windows 11 Pro', 'ver' => '10.0.22631'],
            ['name' => 'Microsoft Windows 10 Pro', 'ver' => '10.0.19045'],
            ['name' => 'Microsoft Windows 10 Home', 'ver' => '10.0.19044'],
            ['name' => 'Ubuntu 22.04 LTS', 'ver' => '5.15.0-91-generic'],
        ];
        $processors = [
            'Intel(R) Core(TM) i5-12400F CPU @ 2.50GHz',
            'Intel(R) Core(TM) i7-13700K CPU @ 3.40GHz',
            'AMD Ryzen 5 5600X 6-Core Processor',
            'AMD Ryzen 7 5800H with Radeon Graphics'
        ];
        $vendors = ['Dell Inc.', 'HP', 'Lenovo', 'ASUSTeK COMPUTER INC.'];
        $locations = ['Lab Komputer 1', 'Lab Komputer 2', 'Ruang Dosen', 'Perpustakaan', 'Server Room'];

        for ($i = 1; $i <= 50; $i++) {
            $selectedOs = $osTypes[array_rand($osTypes)];
            $diskTotal = [256, 512, 1024][rand(0, 2)];
            $manufacture = $vendors[array_rand($vendors)];

            $computers[] = [
                'hostname' => 'PC-LAB-' . str_pad($i, 3, '0', STR_PAD_LEFT),

                // OS Info
                'os_name' => $selectedOs['name'],
                'os_version' => $selectedOs['ver'],
                'os_architecture' => '64-bit',
                'os_license_status' => $statuses[array_rand($statuses)],
                'os_partial_key' => 'XXXXX-XXXXX-XXXXX-' . strtoupper(Str::random(5)),

                // Hardware
                'processor' => $processors[array_rand($processors)],
                'ram_gb' => [8, 16, 32][rand(0, 2)],
                'disk_total_gb' => $diskTotal,
                'disk_free_gb' => rand(20, $diskTotal - 50),

                // Identitas
                'ip_address' => '192.168.1.' . $i,
                'mac_address' => implode(':', str_split(strtoupper(bin2hex(random_bytes(6))), 2)),
                'serial_number' => strtoupper(Str::random(10)),
                'manufacturer' => $manufacture,
                'model' => $manufacture . ' Workstation ' . rand(100, 900),

                // Meta
                'location' => $locations[array_rand($locations)],
                'last_seen_at' => Carbon::now()->subMinutes(rand(1, 10000)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('computers')->insertOrIgnore($computers);

        // Ambil ID komputer yang baru dibuat
        $computerIds = DB::table('computers')->pluck('id')->toArray();


        // ==========================================
        // 2. SEED KATALOG SOFTWARE (Sama seperti sebelumnya)
        // ==========================================

        $catalogs = [
            ['normalized_name' => 'Microsoft Office 2019', 'category' => 'Commercial', 'status' => 'Whitelist', 'description' => 'Office Suite standar'],
            ['normalized_name' => 'Google Chrome', 'category' => 'Freeware', 'status' => 'Whitelist', 'description' => 'Browser'],
            ['normalized_name' => 'Adobe Photoshop 2022', 'category' => 'Commercial', 'status' => 'Whitelist', 'description' => 'Design tool'],
            ['normalized_name' => 'WinRAR', 'category' => 'Commercial', 'status' => 'Unreviewed', 'description' => 'Archiver'],
            ['normalized_name' => 'Cheat Engine', 'category' => 'Freeware', 'status' => 'Blacklist', 'description' => 'Hacking tool'],
            ['normalized_name' => 'uTorrent', 'category' => 'Freeware', 'status' => 'Blacklist', 'description' => 'Torrent client'],
            ['normalized_name' => 'VLC Media Player', 'category' => 'OpenSource', 'status' => 'Whitelist', 'description' => 'Media player'],
        ];

        foreach ($catalogs as $cat) {
            DB::table('software_catalogs')->insertOrIgnore([
                'normalized_name' => $cat['normalized_name'],
                'category' => $cat['category'],
                'status' => $cat['status'],
                'description' => $cat['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $catalogIds = DB::table('software_catalogs')->pluck('id', 'normalized_name');


        // ==========================================
        // 3. SEED DISCOVERY (Sama seperti sebelumnya)
        // ==========================================

        $discoveries = [];
        $vendorsList = ['Microsoft', 'Google', 'Adobe', 'VideoLAN', 'RARLAB'];

        foreach ($computerIds as $compId) {
            $numSoftwares = rand(3, 8);
            for ($j = 0; $j < $numSoftwares; $j++) {
                $selectedCatalog = $catalogs[array_rand($catalogs)];
                $name = $selectedCatalog['normalized_name'];

                $discoveries[] = [
                    'computer_id' => $compId,
                    'catalog_id' => $catalogIds[$name] ?? null,
                    'raw_name' => $name,
                    'version' => 'v' . rand(1, 10) . '.' . rand(0, 9),
                    'vendor' => $vendorsList[array_rand($vendorsList)],
                    'install_date' => Carbon::now()->subDays(rand(1, 365)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($discoveries, 100) as $chunk) {
            DB::table('software_discoveries')->insert($chunk);
        }
    }
}