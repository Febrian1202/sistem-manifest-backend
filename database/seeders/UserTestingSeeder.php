<?php

namespace Database\Seeders;

use App\Models\ComplianceReport;
use App\Models\Computer;
use App\Models\Faculty;
use App\Models\Laboratory;
use App\Models\LicenseAllocation;
use App\Models\LicenseInventory;
use App\Models\ReportApproval;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class UserTestingSeeder extends Seeder
{
    /**
     * Run the deterministic database seeds for User Testing.
     */
    public function run(): void
    {
        $this->command?->info(
            'Menjalankan UserTestingSeeder (Deterministic Test Dataset)...',
        );

        // 1. Pastikan Role & Permission serta Fakultas dasar ada
        $this->call([RoleAndPermissionSeeder::class, FacultySeeder::class]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@usn.ac.id'],
            [
                'name' => 'Administrator TI',
                'password' => env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!'),
            ],
        );
        $admin->syncRoles(['admin']);

        $pimpinan = User::firstOrCreate(
            ['email' => 'pimpinan@usn.ac.id'],
            [
                'name' => 'Dekan FTI',
                'password' => env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!'),
            ],
        );
        $pimpinan->syncRoles(['pimpinan']);

        // 2. Setup Fakultas Target (FTI)
        $fti = Faculty::where('code', 'FTI')->first();
        if (! $fti) {
            $fti = Faculty::create([
                'name' => 'Fakultas Teknologi Informasi',
                'code' => 'FTI',
                'description' => 'Fakultas Teknologi Informasi USN Kolaka',
            ]);
        }

        // Setup Laboratorium
        $lab1 = Laboratory::firstOrCreate(
            ['code' => 'LAB-KOM1'],
            [
                'faculty_id' => $fti->id,
                'name' => 'Laboratorium Komputer 1',
                'building' => 'Gedung A',
                'floor' => '2',
                'description' => 'Lab Komputer Dasar FTI',
            ],
        );

        $lab2 = Laboratory::firstOrCreate(
            ['code' => 'LAB-KOM2'],
            [
                'faculty_id' => $fti->id,
                'name' => 'Laboratorium Komputer 2',
                'building' => 'Gedung A',
                'floor' => '3',
                'description' => 'Lab Rekayasa Perangkat Lunak FTI',
            ],
        );

        // Setup Akun Kepala Lab dan Staf Lab
        $kepalaLab = User::firstOrCreate(
            ['email' => 'kepalalab@usn.ac.id'],
            [
                'name' => 'Kepala Lab Komputer',
                'password' => env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!'),
                'faculty_id' => $fti->id,
                'laboratory_id' => $lab1->id,
            ],
        );
        $kepalaLab->syncRoles(['kepala_lab']);

        $staffLab = User::firstOrCreate(
            ['email' => 'stafflab@usn.ac.id'],
            [
                'name' => 'Teknisi Lab Komputer',
                'password' => env('DEFAULT_USER_PASSWORD', 'ManifestUSN_2026!'),
                'faculty_id' => $fti->id,
                'laboratory_id' => $lab1->id,
            ],
        );
        $staffLab->syncRoles(['staff_lab']);

        // 3. Setup Katalog Software Deterministik
        $catOffice = SoftwareCatalog::firstOrCreate(
            ['normalized_name' => 'Microsoft Office 2019 Professional Plus'],
            [
                'category' => 'Commercial',
                'status' => 'Whitelist',
                'description' => 'Suite produktivitas kantor resmi Microsoft',
            ],
        );

        $catPhotoshop = SoftwareCatalog::firstOrCreate(
            ['normalized_name' => 'Adobe Photoshop CC 2022'],
            [
                'category' => 'Commercial',
                'status' => 'Whitelist',
                'description' => 'Software pengolah grafis dan desain',
            ],
        );

        $catAutoCAD = SoftwareCatalog::firstOrCreate(
            ['normalized_name' => 'Autodesk AutoCAD 2024'],
            [
                'category' => 'Commercial',
                'status' => 'Whitelist',
                'description' => 'Software desain teknik & CAD',
            ],
        );

        $catChrome = SoftwareCatalog::firstOrCreate(
            ['normalized_name' => 'Google Chrome'],
            [
                'category' => 'Freeware',
                'status' => 'Whitelist',
                'description' => 'Peramban web standar',
            ],
        );

        $catVSCode = SoftwareCatalog::firstOrCreate(
            ['normalized_name' => 'Visual Studio Code'],
            [
                'category' => 'OpenSource',
                'status' => 'Whitelist',
                'description' => 'Text editor pemrograman open-source',
            ],
        );

        $catKMSPico = SoftwareCatalog::firstOrCreate(
            ['normalized_name' => 'KMSPico Activator'],
            [
                'category' => 'Other',
                'status' => 'Blacklist',
                'description' => 'Software aktivator ilegal berbahaya (masuk daftar hitam)',
            ],
        );

        // 4. Setup Inventaris Lisensi & Alokasi Kuota
        // Office: Owned = 20, Alokasi FTI = 15
        $licOffice = LicenseInventory::firstOrCreate(
            ['catalog_id' => $catOffice->id],
            [
                'license_key' => Crypt::encryptString(
                    'OFFICE-2019-PRO-USN-KOLAKA-0001',
                ),
                'purchase_order_number' => 'PO/USN/2026/01/001',
                'quota_limit' => 20,
                'purchase_date' => Carbon::now()->subMonths(6),
                'expiry_date' => Carbon::now()->addMonths(18),
                'price_per_unit' => 3500000,
                'notes' => 'Lisensi volume resmi Microsoft untuk universitas',
            ],
        );
        $licOffice->update(['quota_limit' => 20]);

        LicenseAllocation::updateOrCreate(
            [
                'license_inventory_id' => $licOffice->id,
                'faculty_id' => $fti->id,
            ],
            [
                'allocated_quota' => 15,
                'allocation_date' => Carbon::now()->subMonths(5),
                'start_date' => Carbon::now()->subMonths(5),
                'end_date' => Carbon::now()->addMonths(18),
                'status' => 'active',
                'notes' => 'Alokasi kuota Office FTI untuk 15 kursi lab',
                'created_by' => $admin->id,
            ],
        );

        // Photoshop: Owned = 10, Alokasi FTI = 8
        $licPhotoshop = LicenseInventory::firstOrCreate(
            ['catalog_id' => $catPhotoshop->id],
            [
                'license_key' => Crypt::encryptString(
                    'ADOBE-PHOTO-2022-USN-KOLAKA-0002',
                ),
                'purchase_order_number' => 'PO/USN/2026/01/002',
                'quota_limit' => 10,
                'purchase_date' => Carbon::now()->subMonths(4),
                'expiry_date' => Carbon::now()->addMonths(8),
                'price_per_unit' => 4200000,
                'notes' => 'Lisensi Adobe Creative Cloud lab komputer',
            ],
        );
        $licPhotoshop->update(['quota_limit' => 10]);

        LicenseAllocation::updateOrCreate(
            [
                'license_inventory_id' => $licPhotoshop->id,
                'faculty_id' => $fti->id,
            ],
            [
                'allocated_quota' => 8,
                'allocation_date' => Carbon::now()->subMonths(4),
                'start_date' => Carbon::now()->subMonths(4),
                'end_date' => Carbon::now()->addMonths(8),
                'status' => 'active',
                'notes' => 'Alokasi kuota Photoshop FTI untuk 8 kursi lab',
                'created_by' => $admin->id,
            ],
        );

        // AutoCAD: Owned = 5, Alokasi FTI = 5
        $licAutoCAD = LicenseInventory::firstOrCreate(
            ['catalog_id' => $catAutoCAD->id],
            [
                'license_key' => Crypt::encryptString(
                    'AUTOCAD-2024-USN-KOLAKA-0003',
                ),
                'purchase_order_number' => 'PO/USN/2026/01/003',
                'quota_limit' => 5,
                'purchase_date' => Carbon::now()->subMonths(3),
                'expiry_date' => Carbon::now()->addMonths(9),
                'price_per_unit' => 7800000,
                'notes' => 'Lisensi CAD Lab Desain',
            ],
        );
        $licAutoCAD->update(['quota_limit' => 5]);

        LicenseAllocation::updateOrCreate(
            [
                'license_inventory_id' => $licAutoCAD->id,
                'faculty_id' => $fti->id,
            ],
            [
                'allocated_quota' => 5,
                'allocation_date' => Carbon::now()->subMonths(3),
                'start_date' => Carbon::now()->subMonths(3),
                'end_date' => Carbon::now()->addMonths(9),
                'status' => 'active',
                'notes' => 'Alokasi kuota AutoCAD FTI untuk 5 kursi lab',
                'created_by' => $admin->id,
            ],
        );

        // 5. Setup Komputer Deterministik
        // 20 PC di LAB-KOM1 (PC-LAB1-001 s/d PC-LAB1-020)
        // 10 PC di LAB-KOM2 (PC-LAB2-001 s/d PC-LAB2-010)
        $computersLab1 = [];
        for ($i = 1; $i <= 20; $i++) {
            $hostname = 'PC-LAB1-'.str_pad($i, 3, '0', STR_PAD_LEFT);
            $computersLab1[$i] = Computer::firstOrCreate(
                ['hostname' => $hostname],
                [
                    'laboratory_id' => $lab1->id,
                    'ip_address' => '192.168.10.'.(10 + $i),
                    'mac_address' => '00:1B:44:11:3A:'.
                        str_pad(dechex($i), 2, '0', STR_PAD_LEFT),
                    'os_name' => 'Microsoft Windows 11 Pro',
                    'os_version' => '10.0.22631',
                    'os_architecture' => '64-bit',
                    'os_license_status' => 'Licensed',
                    'os_partial_key' => 'XXXXX-XXXXX-XXXXX-AB'.$i,
                    'processor' => 'Intel(R) Core(TM) i5-12400F CPU @ 2.50GHz',
                    'ram_gb' => 16,
                    'disk_total_gb' => 512,
                    'disk_free_gb' => 320,
                    'status' => $i > 18 ? 'unscanned' : 'active',
                    'last_seen_at' => $i > 18 ? null : Carbon::now()->subDays(2),
                ],
            );
        }

        $computersLab2 = [];
        for ($i = 1; $i <= 10; $i++) {
            $hostname = 'PC-LAB2-'.str_pad($i, 3, '0', STR_PAD_LEFT);
            $computersLab2[$i] = Computer::firstOrCreate(
                ['hostname' => $hostname],
                [
                    'laboratory_id' => $lab2->id,
                    'ip_address' => '192.168.20.'.(10 + $i),
                    'mac_address' => '00:1B:44:22:5B:'.
                        str_pad(dechex($i), 2, '0', STR_PAD_LEFT),
                    'os_name' => 'Microsoft Windows 10 Pro',
                    'os_version' => '10.0.19045',
                    'os_architecture' => '64-bit',
                    'os_license_status' => 'Licensed',
                    'os_partial_key' => 'XXXXX-XXXXX-XXXXX-CD'.$i,
                    'processor' => 'AMD Ryzen 5 5600X 6-Core Processor',
                    'ram_gb' => 16,
                    'disk_total_gb' => 512,
                    'disk_free_gb' => 280,
                    'status' => 'active',
                    'last_seen_at' => Carbon::now()->subDays(1),
                ],
            );
        }

        // 6. Setup Riwayat Scan & Instalasi Software
        // Office terpasang di 25 PC: 18 PC di Lab1 + 7 PC di Lab2
        // Alokasi FTI = 15 => Defisit = 25 - 15 = 10 Unit!
        // Photoshop terpasang di 16 PC: 10 PC di Lab1 + 6 PC di Lab2
        // Alokasi FTI = 8 => Defisit = 16 - 8 = 8 Unit!
        // AutoCAD terpasang di 5 PC di Lab2 => Defisit = 5 - 5 = 0 Unit!
        // KMSPico terpasang di PC-LAB1-005 (Pelanggaran / Blacklist!)

        // Scan Session & Software Results Lab1
        foreach ($computersLab1 as $idx => $comp) {
            if ($comp->status === 'unscanned') {
                continue;
            }

            $scan = ScanSession::firstOrCreate(
                ['computer_id' => $comp->id],
                [
                    'scan_uuid' => (string) Str::uuid(),
                    'started_at' => Carbon::now()->subDays(2),
                    'completed_at' => Carbon::now()->subDays(2),
                    'trigger' => 'scheduled',
                    'software_count' => 5,
                    'status' => 'completed',
                ],
            );

            // Seluruh 18 PC aktif Lab1 punya Office & Chrome & VSCode
            ScanSoftwareResult::firstOrCreate(
                [
                    'scan_session_id' => $scan->id,
                    'raw_name' => 'Microsoft Office 2019 Professional Plus',
                ],
                [
                    'version' => '16.0.10396',
                    'vendor' => 'Microsoft Corporation',
                    'catalog_id' => $catOffice->id,
                ],
            );
            ScanSoftwareResult::firstOrCreate(
                ['scan_session_id' => $scan->id, 'raw_name' => 'Google Chrome'],
                [
                    'version' => '120.0.6099',
                    'vendor' => 'Google LLC',
                    'catalog_id' => $catChrome->id,
                ],
            );
            ScanSoftwareResult::firstOrCreate(
                [
                    'scan_session_id' => $scan->id,
                    'raw_name' => 'Visual Studio Code',
                ],
                [
                    'version' => '1.85.1',
                    'vendor' => 'Microsoft Corporation',
                    'catalog_id' => $catVSCode->id,
                ],
            );

            // Photoshop di 10 PC pertama Lab1
            if ($idx <= 10) {
                ScanSoftwareResult::firstOrCreate(
                    [
                        'scan_session_id' => $scan->id,
                        'raw_name' => 'Adobe Photoshop CC 2022',
                    ],
                    [
                        'version' => '23.0.0',
                        'vendor' => 'Adobe Inc.',
                        'catalog_id' => $catPhotoshop->id,
                    ],
                );
            }

            // KMSPico di PC 5 Lab1 (Violation)
            if ($idx === 5) {
                ScanSoftwareResult::firstOrCreate(
                    [
                        'scan_session_id' => $scan->id,
                        'raw_name' => 'KMSPico Activator',
                    ],
                    [
                        'version' => '10.2.0',
                        'vendor' => 'Team Daz',
                        'catalog_id' => $catKMSPico->id,
                    ],
                );

                ComplianceReport::updateOrCreate(
                    [
                        'computer_id' => $comp->id,
                        'software_catalog_id' => $catKMSPico->id,
                    ],
                    [
                        'software_name' => 'KMSPico Activator',
                        'software_version' => '10.2.0',
                        'status' => 'Critical',
                        'keterangan' => 'Aktivator ilegal terdeteksi - masuk dalam daftar blokir sistem.',
                        'detected_at' => Carbon::now()->subDays(2),
                        'scanned_at' => Carbon::now()->subDays(2),
                    ],
                );
            }
        }

        // Scan Session & Software Results Lab2
        foreach ($computersLab2 as $idx => $comp) {
            $scan = ScanSession::firstOrCreate(
                ['computer_id' => $comp->id],
                [
                    'scan_uuid' => (string) Str::uuid(),
                    'started_at' => Carbon::now()->subDays(1),
                    'completed_at' => Carbon::now()->subDays(1),
                    'trigger' => 'scheduled',
                    'software_count' => 5,
                    'status' => 'completed',
                ],
            );

            ScanSoftwareResult::firstOrCreate(
                ['scan_session_id' => $scan->id, 'raw_name' => 'Google Chrome'],
                [
                    'version' => '120.0.6099',
                    'vendor' => 'Google LLC',
                    'catalog_id' => $catChrome->id,
                ],
            );

            // Office di 7 PC pertama Lab2 (Total Office FTI = 18 + 7 = 25 PC!)
            if ($idx <= 7) {
                ScanSoftwareResult::firstOrCreate(
                    [
                        'scan_session_id' => $scan->id,
                        'raw_name' => 'Microsoft Office 2019 Professional Plus',
                    ],
                    [
                        'version' => '16.0.10396',
                        'vendor' => 'Microsoft Corporation',
                        'catalog_id' => $catOffice->id,
                    ],
                );
            }

            // Photoshop di 6 PC pertama Lab2 (Total Photoshop FTI = 10 + 6 = 16 PC!)
            if ($idx <= 6) {
                ScanSoftwareResult::firstOrCreate(
                    [
                        'scan_session_id' => $scan->id,
                        'raw_name' => 'Adobe Photoshop CC 2022',
                    ],
                    [
                        'version' => '23.0.0',
                        'vendor' => 'Adobe Inc.',
                        'catalog_id' => $catPhotoshop->id,
                    ],
                );
            }

            // AutoCAD di 5 PC pertama Lab2 (Total AutoCAD FTI = 5 PC!)
            if ($idx <= 5) {
                ScanSoftwareResult::firstOrCreate(
                    [
                        'scan_session_id' => $scan->id,
                        'raw_name' => 'Autodesk AutoCAD 2024',
                    ],
                    [
                        'version' => '24.3.0',
                        'vendor' => 'Autodesk Inc.',
                        'catalog_id' => $catAutoCAD->id,
                    ],
                );
            }
        }

        // 7. Setup Report Approvals (Untuk Skenario K-02 dan P-01/P-02)
        $currentPeriod = Carbon::now()->format('Y-m');

        // LAB-KOM1: Status PENDING (Untuk diuji oleh Kepala Lab pada Skenario K-02)
        ReportApproval::updateOrCreate(
            [
                'laboratory_id' => $lab1->id,
                'period' => $currentPeriod,
                'report_type' => 'kepatuhan',
            ],
            [
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'notes' => null,
            ],
        );

        // LAB-KOM2: Status APPROVED (Agar data FTI langsung tampil resmi di Executive Dashboard Pimpinan)
        ReportApproval::updateOrCreate(
            [
                'laboratory_id' => $lab2->id,
                'period' => $currentPeriod,
                'report_type' => 'kepatuhan',
            ],
            [
                'status' => 'approved',
                'reviewed_by' => $kepalaLab->id,
                'reviewed_at' => Carbon::now()->subDay(),
                'notes' => 'Laporan kepatuhan Lab Komputer 2 telah divalidasi dan disetujui.',
            ],
        );

        // 8. Bersihkan Cache Redis dan App
        Artisan::call('cache:clear');
        $this->command?->info(
            'UserTestingSeeder selesai. Cache telah dibersihkan.',
        );
    }
}
