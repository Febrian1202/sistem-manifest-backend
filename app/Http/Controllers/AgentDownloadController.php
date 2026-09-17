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
        $laboratories = Laboratory::orderBy('name')->get();

        return view('pages.admin.agent-download', compact('laboratories'));
    }

    public function download(Request $request): BinaryFileResponse
    {
        $request->validate([
            'laboratory_id' => 'required|exists:laboratories,id',
        ]);

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

        $baseUrl = rtrim(config('app.url'), '/').'/api';
        $registrationKey = config('app.agent_registration_key') ?: env('AGENT_REGISTRATION_KEY', '');

        $configData = [
            'baseUrl' => $baseUrl,
            'registrationKey' => $registrationKey,
            'laboratoryId' => $lab->id,
            'laboratoryName' => $lab->name,
        ];

        $configJson = json_encode($configData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $zip->addFromString('config.json', $configJson);

        $instructions = <<<'TEXT'
=========================================================
      PANDUAN PEMASANGAN TOOLS PEMINDAI USN MANIFEST      
=========================================================

1. Ekstrak seluruh isi file ZIP ini ke dalam sebuah folder permanen.
   SANGAT DISARANKAN meletakkannya di folder permanen seperti: 
   C:\USN-Manifest-Agent\ atau direktori aplikasi lainnya.
   (Hindari diletakkan di folder sementara seperti 'Downloads')

   (Pastikan keempat file: scanner.ps1, setup_tasks.ps1, config.json, 
   dan instruksi.txt berada di dalam satu folder ekstraksi yang sama).

2. Buka folder ekstraksi tersebut.

3. Klik kanan pada file "scanner.ps1", lalu pilih "Run with PowerShell".
   (Jika muncul peringatan dari OS Windows, ketik 'Y' lalu Enter 
   untuk mengizinkan eksekusi).

4. Pemindai akan secara otomatis memindai aplikasi yang terinstall dan mengirimnya ke server.

5. (Sangat Direkomendasikan) Klik kanan pada file "setup_tasks.ps1", 
   lalu pilih "Run with PowerShell" (sebagai Administrator). Skrip ini akan 
   mendaftarkan pemindai ke Task Scheduler agar berjalan otomatis secara berkala.

PENTING: Jangan memisahkan scanner.ps1 dengan config.json, karena skrip membutuhkan 
konfigurasi integrasi dari file tersebut!

=========================================================
      CARA MENGHAPUS / UNINSTALL SCHEDULER PEMINDAI
=========================================================

Jika Anda ingin menghentikan pemindai agar tidak berjalan otomatis lagi, Anda dapat menghapus tugas yang sudah didaftarkan di Task Scheduler.
1. Buka aplikasi PowerShell sebagai Administrator.
2. Salin dan jalankan kedua perintah berikut:

   Unregister-ScheduledTask -TaskName "USN-Manifest-DailyScan" -Confirm:$false
   Unregister-ScheduledTask -TaskName "USN-Manifest-Polling" -Confirm:$false
TEXT;

        $zip->addFromString('instruksi.txt', $instructions);

        $zip->close();

        return response()->download($zipPath, 'usn-manifest-agent.zip')->deleteFileAfterSend(true);
    }
}
