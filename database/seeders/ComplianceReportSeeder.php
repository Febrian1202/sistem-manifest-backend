<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComplianceReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $computerIds = DB::table('computers')->pluck('id')->toArray();
        $catalogs = DB::table('software_catalogs')->get();
        $licenseMap = DB::table('license_inventories')->pluck('id', 'catalog_id');

        if (empty($computerIds) || $catalogs->isEmpty()) {
            $this->command->warn("Data komputer atau katalog software kosong. Jalankan DashboardSeeder terlebih dahulu.");
            return;
        }

        $reports = [];

        foreach ($computerIds as $compId) {
            // Pilih 3-5 software secara acak untuk setiap komputer
            $selectedCatalogs = $catalogs->random(rand(3, 5));

            foreach ($selectedCatalogs as $catalog) {
                $status = 'Berlisensi';
                $ket = 'Lisensi ditemukan dan valid.';
                $licId = $licenseMap[$catalog->id] ?? null;

                // Logika Status untuk Seeding
                if ($catalog->status === 'Blacklist') {
                    $status = 'Tidak Berlisensi';
                    $ket = 'Software ini masuk dalam daftar hitam (Blocklist).';
                } elseif ($catalog->category === 'Commercial') {
                    if (!$licId) {
                        $status = 'Tidak Berlisensi';
                        $ket = 'Software komersial namun lisensi tidak ditemukan di inventaris.';
                    } else {
                        // Variasi status untuk testing
                        $rand = rand(1, 4);
                        if ($rand === 1) {
                            $status = 'Grace Period';
                            $ket = 'Masa berlaku lisensi akan segera habis (kurang dari 30 hari).';
                        } elseif ($rand === 2 && rand(1, 2) === 1) {
                            $status = 'Tidak Berlisensi';
                            $ket = 'Lisensi telah kedaluwarsa.';
                        }
                    }
                } else {
                    $status = 'Berlisensi';
                    $ket = 'Software gratis / open source.';
                }

                $reports[] = [
                    'computer_id' => $compId,
                    'software_catalog_id' => $catalog->id,
                    'software_name' => $catalog->normalized_name,
                    'software_version' => 'v' . rand(1, 15) . '.' . rand(0, 9),
                    'status' => $status,
                    'keterangan' => $ket,
                    'license_inventory_id' => $licId,
                    'detected_at' => Carbon::now()->subDays(rand(1, 10)),
                    'scanned_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Simpan data
        DB::table('compliance_reports')->insertOrIgnore($reports);

        $this->command->info("Berhasil men-seed " . count($reports) . " laporan kepatuhan.");
    }
}
