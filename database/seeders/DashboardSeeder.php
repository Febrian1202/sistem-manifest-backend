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
        // 2. SEED KATALOG SOFTWARE
        // ==========================================

        $catalogs = [
            ['normalized_name' => 'Microsoft Office 2019', 'category' => 'Commercial', 'status' => 'Whitelist', 'description' => 'Office Suite standar'],
            ['normalized_name' => 'Google Chrome', 'category' => 'Freeware', 'status' => 'Whitelist', 'description' => 'Browser'],
            ['normalized_name' => 'Adobe Photoshop 2022', 'category' => 'Commercial', 'status' => 'Whitelist', 'description' => 'Design tool'],
            ['normalized_name' => 'WinRAR', 'category' => 'Commercial', 'status' => 'Unreviewed', 'description' => 'Archiver'],
            ['normalized_name' => 'Cheat Engine', 'category' => 'Freeware', 'status' => 'Blacklist', 'description' => 'Hacking tool'],
            ['normalized_name' => 'uTorrent', 'category' => 'Freeware', 'status' => 'Blacklist', 'description' => 'Torrent client'],
            ['normalized_name' => 'VLC Media Player', 'category' => 'OpenSource', 'status' => 'Whitelist', 'description' => 'Media player'],
            ['normalized_name' => 'VS Code', 'category' => 'OpenSource', 'status' => 'Whitelist', 'description' => 'Code editor'],
        ];

        foreach ($catalogs as $cat) {
            DB::table('software_catalogs')->updateOrInsert(
                ['normalized_name' => $cat['normalized_name']],
                [
                    'category' => $cat['category'],
                    'status' => $cat['status'],
                    'description' => $cat['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $catalogRecords = DB::table('software_catalogs')->get();
        $catalogIds = $catalogRecords->pluck('id', 'normalized_name');


        // ==========================================
        // 3. SEED LISENSI
        // ==========================================

        $licenses = [];
        foreach ($catalogRecords as $cat) {
            if ($cat->category === 'Commercial') {
                $licenses[] = [
                    'catalog_id' => $cat->id,
                    'license_key' => strtoupper(Str::random(5) . '-' . Str::random(5) . '-' . Str::random(5)),
                    'quota_limit' => rand(5, 20),
                    'purchase_date' => Carbon::now()->subMonths(rand(6, 24)),
                    'expiry_date' => Carbon::now()->addMonths(rand(-3, 12)), // some expired
                    'price_per_unit' => rand(100, 500),
                    'purchase_order_number' => 'PO-' . rand(1000, 9999),
                    'proof_image' => 'dummy_license.jpg',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('license_inventories')->insertOrIgnore($licenses);
        $licenseMap = DB::table('license_inventories')->pluck('id', 'catalog_id');


        // ==========================================
        // 4. SEED DISCOVERY & COMPLIANCE REPORTS
        // ==========================================

        $discoveries = [];
        $reports = [];
        $vendorsList = ['Microsoft', 'Google', 'Adobe', 'VideoLAN', 'RARLAB'];

        foreach ($computerIds as $compId) {
            $numSoftwares = rand(4, 7);
            $selectedIndices = (array) array_rand($catalogs, $numSoftwares);
            
            foreach ($selectedIndices as $idx) {
                $selectedCatalog = $catalogs[$idx];
                $name = $selectedCatalog['normalized_name'];
                $catId = $catalogIds[$name];
                $version = 'v' . rand(1, 10) . '.' . rand(0, 9);

                $discoveries[] = [
                    'computer_id' => $compId,
                    'catalog_id' => $catId,
                    'raw_name' => $name,
                    'version' => $version,
                    'vendor' => $vendorsList[array_rand($vendorsList)],
                    'install_date' => Carbon::now()->subDays(rand(1, 365)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Determine Status for Seeder
                $status = 'Berlisensi';
                $ket = 'Lisensi aktif';
                $licId = $licenseMap[$catId] ?? null;

                if ($selectedCatalog['status'] === 'Blacklist') {
                    $status = 'Tidak Berlisensi';
                    $ket = 'Aplikasi terlarang';
                } elseif ($selectedCatalog['category'] === 'Commercial') {
                    if (!$licId) {
                        $status = 'Tidak Berlisensi';
                        $ket = 'Lisensi tidak ditemukan';
                    } else {
                        // Check if it should be expired or grace period randomly
                        $rand = rand(1, 10);
                        if ($rand === 1) {
                            $status = 'Tidak Berlisensi';
                            $ket = 'Lisensi kedaluwarsa';
                        } elseif ($rand === 2) {
                            $status = 'Grace Period';
                            $ket = 'Lisensi hampir habis';
                        }
                    }
                } else {
                    $status = 'Berlisensi';
                    $ket = 'Software gratis';
                }

                $reports[] = [
                    'computer_id' => $compId,
                    'software_catalog_id' => $catId,
                    'software_name' => $name,
                    'software_version' => $version,
                    'status' => $status,
                    'keterangan' => $ket,
                    'license_inventory_id' => $licId,
                    'detected_at' => now(),
                    'scanned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($discoveries, 100) as $chunk) {
            DB::table('software_discoveries')->insertOrIgnore($chunk);
        }

        foreach (array_chunk($reports, 100) as $chunk) {
            DB::table('compliance_reports')->insertOrIgnore($chunk);
        }
    }
}