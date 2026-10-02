<?php

namespace App\Http\Controllers;

use App\Models\Laboratory;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class AgentDownloadController extends Controller
{
    public function showDownloadPage(): View
    {
        $user = auth()->user();
        $accessibleLabIds = $user->getAccessibleLaboratoryIds();

        $laboratories = Laboratory::whereIn('id', $accessibleLabIds)
            ->orderBy('name')
            ->get();

        return view('pages.admin.agent-download', compact('laboratories'));
    }

    public function download(Request $request): BinaryFileResponse
    {
        $request->validate([
            'laboratory_id' => 'required|exists:laboratories,id',
        ]);

        $user = auth()->user();
        $accessibleLabIds = $user->getAccessibleLaboratoryIds();

        if (! in_array((int) $request->laboratory_id, array_map('intval', $accessibleLabIds))) {
            abort(403, 'Anda tidak memiliki hak untuk mengunduh scanner untuk laboratorium ini.');
        }

        $lab = Laboratory::findOrFail($request->laboratory_id);

        $zip = new ZipArchive;
        $tempDir = sys_get_temp_dir();
        $zipPath = $tempDir.DIRECTORY_SEPARATOR.'agent_download_'.uniqid().'.zip';

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Tidak dapat membuat file ZIP sementara.');
        }

        $scannerPath = base_path('script/agent/scanner.ps1');
        if (! file_exists($scannerPath)) {
            $zip->close();
            @unlink($zipPath);
            abort(404, 'File scanner.ps1 tidak ditemukan.');
        }
        $zip->addFile($scannerPath, 'scanner.ps1');

        $setupTasksPath = base_path('script/agent/setup_tasks.ps1');
        if (! file_exists($setupTasksPath)) {
            $zip->close();
            @unlink($zipPath);
            abort(404, 'File setup_tasks.ps1 tidak ditemukan.');
        }
        $zip->addFile($setupTasksPath, 'setup_tasks.ps1');

        $launcherScanPath = base_path('script/agent/1-Jalankan-Scan-Sekarang.bat');
        if (file_exists($launcherScanPath)) {
            $zip->addFile($launcherScanPath, '1-Jalankan-Scan-Sekarang.bat');
        }

        $launcherSetupPath = base_path('script/agent/2-Pasang-Jadwal-Otomatis.bat');
        if (file_exists($launcherSetupPath)) {
            $zip->addFile($launcherSetupPath, '2-Pasang-Jadwal-Otomatis.bat');
        }

        $launcherUninstallPath = base_path('script/agent/3-Hapus-Jadwal-Otomatis.bat');
        if (file_exists($launcherUninstallPath)) {
            $zip->addFile($launcherUninstallPath, '3-Hapus-Jadwal-Otomatis.bat');
        }

        $baseUrl = rtrim(config('app.url'), '/').'/api';
        $registrationKey = config('app.agent_registration_key') ?: env('AGENT_REGISTRATION_KEY', '');

        $configData = [
            'baseUrl' => $baseUrl,
            'registrationKey' => $registrationKey,
            'laboratoryId' => $lab->id,
            'laboratoryName' => $lab->name,
            'agentVersion' => '1.1.0',
        ];

        $configJson = json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $zip->addFromString('config.json', $configJson);

        $instructions = <<<'TEXT'
=========================================================
      PANDUAN PEMASANGAN TOOLS PEMINDAI USN MANIFEST      
=========================================================

1. Ekstrak seluruh isi file ZIP ini ke dalam sebuah folder permanen.
   SANGAT DISARANKAN meletakkannya di folder permanen seperti: 
   C:\USN-Manifest-Scanner\ atau direktori aplikasi lainnya.
   (Hindari meletakkannya di folder sementara seperti 'Downloads')

   Pastikan semua berkas berikut tetap berada di dalam satu folder yang sama:
   - 1-Jalankan-Scan-Sekarang.bat & scanner.ps1 (skrip pemindai)
   - 2-Pasang-Jadwal-Otomatis.bat & setup_tasks.ps1 (skrip penjadwalan)
   - 3-Hapus-Jadwal-Otomatis.bat
   - config.json & instruksi.txt

=========================================================
              CARA PENGGUNAAN (TINGGAL KLIK)
=========================================================

A. PEMINDAIAN LANGSUNG (INSTANT SCAN):
   - Klik 2x pada file: "1-Jalankan-Scan-Sekarang.bat"
   - Pemindai akan langsung memindai seluruh spesifikasi hardware 
     dan software terinstall lalu mengirimnya ke server.
   - Setelah selesai, tekan Enter untuk menutup jendela.

B. PASANG JADWAL PEMINDAIAN OTOMATIS (SANGAT DIREKOMENDASIKAN):
   - Klik 2x pada file: "2-Pasang-Jadwal-Otomatis.bat"
   - Jika muncul konfirmasi Administrator (UAC), pilih "Yes".
   - Jadwal otomatis akan terpasang di Windows Task Scheduler:
     * Pemindaian harian otomatis setiap jam 08:00 AM.
     * Pengecekan permintaan scan berkala setiap 15 menit.

C. MENGHAPUS JADWAL OTOMATIS (UNINSTALL):
   - Klik 2x pada file: "3-Hapus-Jadwal-Otomatis.bat"
   - Jika muncul konfirmasi Administrator (UAC), pilih "Yes".
   - Semua jadwal pemindaian USN Manifest akan dihapus secara bersih.

=========================================================
PENTING: Jangan menghapus atau memindahkan config.json, karena 
skrip membutuhkan konfigurasi token dan URL server dari file tersebut!
TEXT;

        $zip->addFromString('instruksi.txt', $instructions);

        $zip->close();

        return response()->download($zipPath, 'usn-manifest-agent.zip')->deleteFileAfterSend(true);
    }
}
