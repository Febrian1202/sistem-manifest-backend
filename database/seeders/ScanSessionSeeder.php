<?php

namespace Database\Seeders;

use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\Laboratory;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ScanSessionSeeder extends Seeder
{
    public function run(): void
    {
        $computers = Computer::all();

        if ($computers->isEmpty()) {
            $lab = Laboratory::first() ?? Laboratory::create([
                'name' => 'Laboratorium Komputer 1',
                'code' => 'LAB-KOM1',
                'building' => 'Gedung A',
                'floor' => '2',
                'description' => 'Lab komputer umum lantai 2',
            ]);

            $computers = Computer::factory()->count(5)->create([
                'laboratory_id' => $lab->id,
                'status' => 'active',
            ]);
        }

        $sampleCatalogsData = [
            ['normalized_name' => 'Visual Studio Code', 'category' => 'Freeware', 'status' => 'Whitelist'],
            ['normalized_name' => 'Google Chrome', 'category' => 'Freeware', 'status' => 'Whitelist'],
            ['normalized_name' => 'AutoCAD 2024', 'category' => 'Commercial', 'status' => 'Unreviewed'],
            ['normalized_name' => 'Microsoft Office 2021', 'category' => 'Commercial', 'status' => 'Whitelist'],
            ['normalized_name' => 'WinRAR 6.24', 'category' => 'Shareware', 'status' => 'Unreviewed'],
        ];

        foreach ($sampleCatalogsData as $catData) {
            SoftwareCatalog::firstOrCreate(
                ['normalized_name' => $catData['normalized_name']],
                $catData
            );
        }

        $catalogs = SoftwareCatalog::all();

        // 3 periodic scan cycles: 60 days ago, 30 days ago, and 2 days ago
        $periods = [
            now()->subDays(60),
            now()->subDays(30),
            now()->subDays(2),
        ];

        foreach ($computers->take(10) as $computer) {
            foreach ($periods as $index => $scanDate) {
                $session = ScanSession::create([
                    'computer_id' => $computer->id,
                    'scan_uuid' => (string) Str::uuid(),
                    'started_at' => (clone $scanDate)->subMinutes(5),
                    'completed_at' => $scanDate,
                    'status' => 'completed',
                    'trigger' => 'scheduled',
                    'software_count' => 4,
                    'agent_version' => '1.0.0',
                ]);

                $sampleSoftwares = [
                    ['raw_name' => 'Visual Studio Code', 'vendor' => 'Microsoft Corporation', 'version' => '1.9'.$index, 'status' => 'Berlisensi'],
                    ['raw_name' => 'Google Chrome', 'vendor' => 'Google LLC', 'version' => '120.0.'.$index, 'status' => 'Berlisensi'],
                    ['raw_name' => 'AutoCAD 2024', 'vendor' => 'Autodesk', 'version' => '24.'.$index, 'status' => 'Tidak Berlisensi'],
                    ['raw_name' => 'WinRAR 6.24', 'vendor' => 'win.rar GmbH', 'version' => '6.24', 'status' => 'Perlu Ditinjau'],
                ];

                foreach ($sampleSoftwares as $item) {
                    $cat = $catalogs->firstWhere('normalized_name', $item['raw_name']);

                    ScanSoftwareResult::create([
                        'scan_session_id' => $session->id,
                        'catalog_id' => $cat?->id,
                        'raw_name' => $item['raw_name'],
                        'version' => $item['version'],
                        'vendor' => $item['vendor'],
                        'install_date' => Carbon::parse($scanDate)->subMonths(2)->toDateString(),
                    ]);

                    ComplianceSnapshot::create([
                        'scan_session_id' => $session->id,
                        'computer_id' => $computer->id,
                        'software_catalog_id' => $cat?->id,
                        'software_name' => $item['raw_name'],
                        'software_version' => $item['version'],
                        'status' => $item['status'],
                        'keterangan' => 'Audit status snapshot berkala',
                        'detected_at' => Carbon::parse($scanDate)->subMonths(2),
                        'scanned_at' => $scanDate,
                    ]);
                }
            }
        }
    }
}
