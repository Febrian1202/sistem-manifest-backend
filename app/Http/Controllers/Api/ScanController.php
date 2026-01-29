<?php

namespace App\Http\Controllers\Api;

use App\Models\Computer;
use Illuminate\Http\Request;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class ScanController extends Controller
{
    //
    public function store(Request $request)
    {
        // Debugging
        // Log::info('Data Scan Diterima:', $request->all());

        // Validasi input
        $request->validate([
            'computer_name' => 'required|string',
            'os_info' => 'nullable|string',
            'os_license_status' => 'nullable|string',
            'os_partial_key' => 'nullable|string',
            'installed_software' => 'required|array',
        ]);

        DB::beginTransaction();

        try {
            // Cari atau Buat Data Komputer
            $computer = Computer::updateOrCreate(
                ['hostname' => $request->computer_name],
                [
                    'ip_address' => $request->ip(),
                    'os_info' => $request->os_info,
                    'os_license_status' => $request->os_license_status,
                    'os_partial_key' => $request->os_partial_key,
                    'last_seen_at' => now(),
                ]
            );

            // Hapus data scan lama komputer ini (refresh)
            SoftwareDiscovery::where('computer_id', $computer->id)->delete();

            // Proses list software
            $rawSoftwares = $request->installed_software;



            foreach ($rawSoftwares as $soft) {
                // Skip jika nama kosong
                if (empty($soft['name']))
                    continue;

                $name = $soft['name'];

                // Cek Priority 
                // Daftar software yang Harus di deteksi bagaimanapun caranya
                $priorityKeywords = [
                    'Epic Games',
                    'Steam',
                    'Ubisoft',
                    'Crack',
                    'Patch',
                    'Keygen',
                    'Activator',
                    'Torrent',
                    'uTorrent',
                    'BitTorrent',
                    'Daemon Tools'
                ];
                $isPriority = false;
                foreach ($priorityKeywords as $pk) {
                    if (stripos($name, $pk) !== false) {
                        $isPriority = true;
                        break;
                    }
                }

                // Logic Filter Sampah
                if (!$isPriority) {

                    // Kata kunci sampah untuk difilter
                    $ignoredKeywords = [
                        'Redistributable',
                        'Runtime',
                        'Framework',
                        'Library',
                        'SDK',
                        'API',
                        'DirectX',
                        'Vulkan',
                        'OpenGL',
                        'Prerequisites',

                        'Driver',
                        'Chipset',
                        'PhysX',
                        'GeForce',
                        'Radeon',
                        'Intel(R)',
                        'Realtek',
                        'BIOS',
                        'Firmware',

                        'Update',
                        'KB',
                        'Patch',
                        'Service Pack',
                        'Language Pack',
                        'Feature Pack',
                        'Support',
                        'Bootstrap',
                        'Test Suite',
                        'Documentation',
                        'Help',
                        'Manual',

                        'Setup',
                        'Installer',
                        'Launcher',
                        'Helper',
                        'Agent',
                        'Updater',
                        'Assistant',
                        'Wizard',
                        'Tool',
                        'Bridge',
                        'Connector',
                        'Plugin',
                        'Extension',
                        'Add-in',
                        'Addon',
                        'WebResource',

                        'Click-to-Run',
                        'Extensibility',
                        'Localization',
                        'Licensing Component',
                        'AppHost',
                        'Host FX',
                        'vs_',
                        'Minshell',
                        'Redist',
                        'Client Profile',
                        'Targeting Pack',
                    ];

                    $isJunk = false;
                    foreach ($ignoredKeywords as $keyword) {
                        if (stripos($name, $keyword) !== false) {
                            $isJunk = true;
                            break;
                        }
                    }

                    // Skip jika terdeteksi sampah
                    if ($isJunk)
                        continue;
                }

                // Cek Katalog (Auto Discovery)
                // Coba cari di katalog, kalau belum ada, buat sebagai 'Unreviewed'
                // Kita cari yang namanya mirip (exact match)
                $catalog = SoftwareCatalog::firstOrCreate(
                    ['normalized_name' => $name],
                    ['status' => 'Unreviewed']
                );

                // Simpan ke Tabel Discovery
                SoftwareDiscovery::create([
                    'computer_id' => $computer->id,
                    'raw_name' => $name,
                    'version' => $soft['version'] ?? null,
                    'vendor' => $soft['vendor'] ?? null,
                    'catalog_id' => $catalog->id
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Scan data processed succesfully',
                'computer' => $computer->hostname
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
