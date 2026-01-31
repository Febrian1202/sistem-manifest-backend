<?php

namespace App\Http\Controllers\Api;

use App\Models\Computer;
use Illuminate\Http\Request;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ScanController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input (Gabungan Hardware Baru + Software)
        $request->validate([
            'computer_name' => 'required|string',

            // Field Hardware Baru
            'processor' => 'nullable|string',
            'ram_gb' => 'nullable|integer',
            'disk_total_gb' => 'nullable|integer',
            'disk_free_gb' => 'nullable|integer',
            'manufacturer' => 'nullable|string',
            'model' => 'nullable|string',
            'serial_number' => 'nullable|string',

            // Network & OS
            'ip_address' => 'nullable|string',
            'mac_address' => 'nullable|string',
            'os_name' => 'nullable|string', // Sesuai migration baru (dulu os_info)
            'os_version' => 'nullable|string',
            'os_architecture' => 'nullable|string',
            'os_license_status' => 'nullable|string',
            'os_partial_key' => 'nullable|string',

            // Software List
            'installed_software' => 'required|array',
        ]);

        DB::beginTransaction();

        try {
            // 2. Simpan/Update Data Komputer (Termasuk Spesifikasi Hardware)
            $computer = Computer::updateOrCreate(
                ['hostname' => $request->computer_name], // Kunci pencarian
                [
                    // Update spesifikasi hardware
                    'processor' => $request->processor,
                    'ram_gb' => $request->ram_gb,
                    'disk_total_gb' => $request->disk_total_gb,
                    'disk_free_gb' => $request->disk_free_gb,
                    'manufacturer' => $request->manufacturer,
                    'model' => $request->model,
                    'serial_number' => $request->serial_number,

                    // Network & Identitas
                    'ip_address' => $request->ip_address ?? $request->ip(),
                    'mac_address' => $request->mac_address,

                    // Info OS (Mapping os_info lama ke os_name baru jika perlu)
                    'os_name' => $request->os_name ?? $request->os_info,
                    'os_version' => $request->os_version,
                    'os_architecture' => $request->os_architecture,
                    'os_license_status' => $request->os_license_status,
                    'os_partial_key' => $request->os_partial_key,

                    'last_seen_at' => now(),
                ]
            );

            // 3. Reset Data Software Lama (Clean Slate)
            SoftwareDiscovery::where('computer_id', $computer->id)->delete();

            // 4. Proses Filtering Software (Logika Lama Anda Dipertahankan)
            $rawSoftwares = $request->installed_software;

            // A. Keywords Prioritas (Wajib Masuk: Bajakan/Game/Torrent)
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

            // B. Keywords Sampah (Filter Sampah Driver/Update/Runtime)
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

            foreach ($rawSoftwares as $soft) {
                // Skip jika nama kosong
                if (empty($soft['name']))
                    continue;

                $name = $soft['name'];
                $isPriority = false;

                // 4.1 Cek Priority (Case Insensitive)
                foreach ($priorityKeywords as $pk) {
                    if (stripos($name, $pk) !== false) {
                        $isPriority = true;
                        break;
                    }
                }

                // 4.2 Cek Filter Sampah (Hanya jika bukan prioritas)
                if (!$isPriority) {
                    foreach ($ignoredKeywords as $keyword) {
                        if (stripos($name, $keyword) !== false) {
                            // Skip loop software ini (jangan disimpan)
                            continue 2;
                        }
                    }
                }

                // 4.3 Cek Katalog (Auto Discovery)
                $catalog = SoftwareCatalog::firstOrCreate(
                    ['normalized_name' => $name],
                    ['status' => 'Unreviewed', 'category' => 'Freeware']
                );

                // 4.4 Simpan ke Database
                SoftwareDiscovery::create([
                    'computer_id' => $computer->id,
                    'catalog_id' => $catalog->id,
                    'raw_name' => $name,
                    'version' => $soft['version'] ?? null,
                    'vendor' => $soft['vendor'] ?? null,
                    'install_date' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data scan hardware & software berhasil disimpan',
                'computer' => $computer->hostname
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses data: ' . $e->getMessage()
            ], 500);
        }
    }
}