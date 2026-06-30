<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivityLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample activities over the last few days
        $activities = [
            [
                'log_name' => 'default',
                'description' => 'User Admin Utama telah login ke sistem.',
                'subject_type' => 'App\Models\User',
                'subject_id' => 1,
                'event' => 'login',
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => json_encode(['ip' => '192.168.1.10', 'browser' => 'Chrome']),
                'created_at' => Carbon::now()->subDays(2)->subHours(5),
                'updated_at' => Carbon::now()->subDays(2)->subHours(5),
            ],
            [
                'log_name' => 'default',
                'description' => 'Lisensi untuk software Microsoft Office 2021 (PO: PO-2024-001) telah di-created',
                'subject_type' => 'App\Models\LicenseInventory',
                'subject_id' => 1, // Assuming ID 1 exists from LicenseInventorySeeder
                'event' => 'created',
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => json_encode(['attributes' => ['quota_limit' => 50, 'purchase_order_number' => 'PO-2024-001']]),
                'created_at' => Carbon::now()->subDays(2)->subHours(2),
                'updated_at' => Carbon::now()->subDays(2)->subHours(2),
            ],
            [
                'log_name' => 'default',
                'description' => 'Data komputer PC-LAB-01 telah di-updated',
                'subject_type' => 'App\Models\Computer',
                'subject_id' => 1, // Assuming ID 1 exists
                'event' => 'updated',
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => json_encode([
                    'attributes' => ['location' => 'Laboratorium Komputer A'],
                    'old' => ['location' => 'Belum Diatur']
                ]),
                'created_at' => Carbon::now()->subDays(1)->subHours(8),
                'updated_at' => Carbon::now()->subDays(1)->subHours(8),
            ],
            [
                'log_name' => 'default',
                'description' => 'Meminta scan ulang ke 15 komputer',
                'subject_type' => null,
                'subject_id' => null,
                'event' => 'custom',
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => json_encode(['affected_count' => 15]),
                'created_at' => Carbon::now()->subDays(1)->subHours(4),
                'updated_at' => Carbon::now()->subDays(1)->subHours(4),
            ],
            [
                'log_name' => 'default',
                'description' => 'User Pimpinan Fakultas telah login ke sistem.',
                'subject_type' => 'App\Models\User',
                'subject_id' => 2, // Assuming ID 2 is Pimpinan
                'event' => 'login',
                'causer_type' => 'App\Models\User',
                'causer_id' => 2,
                'properties' => json_encode(['ip' => '192.168.1.15', 'browser' => 'Safari']),
                'created_at' => Carbon::now()->subHours(12),
                'updated_at' => Carbon::now()->subHours(12),
            ],
            [
                'log_name' => 'default',
                'description' => 'Status software KMSPico diubah menjadi Blacklist',
                'subject_type' => 'App\Models\SoftwareCatalog',
                'subject_id' => 5, // Random ID
                'event' => 'updated',
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => json_encode([
                    'attributes' => ['status' => 'Blacklist'],
                    'old' => ['status' => 'Unreviewed']
                ]),
                'created_at' => Carbon::now()->subHours(5),
                'updated_at' => Carbon::now()->subHours(5),
            ],
            [
                'log_name' => 'default',
                'description' => 'User Admin Utama telah login ke sistem.',
                'subject_type' => 'App\Models\User',
                'subject_id' => 1,
                'event' => 'login',
                'causer_type' => 'App\Models\User',
                'causer_id' => 1,
                'properties' => json_encode(['ip' => '192.168.1.10', 'browser' => 'Chrome']),
                'created_at' => Carbon::now()->subMinutes(30),
                'updated_at' => Carbon::now()->subMinutes(30),
            ],
        ];

        DB::table('activity_log')->insert($activities);
    }
}
