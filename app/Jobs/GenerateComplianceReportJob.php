<?php

namespace App\Jobs;

use App\Models\Computer;
use App\Models\ComplianceReport;
use App\Models\SoftwareDiscovery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateComplianceReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Computer $computer
    ) {
        $this->onQueue('compliance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Generating compliance report for computer: ' . $this->computer->hostname);

            // STEP 1 — Load Data
            $discoveries = SoftwareDiscovery::with(['catalog', 'catalog.licenses'])
                ->where('computer_id', $this->computer->id)
                ->get();

            $blockedSoftwareList = config('compliance.blocked_software', []);
            $records = [];
            $currentCatalogIds = [];

            // STEP 2 — Process Setiap Software
            foreach ($discoveries as $discovery) {
                if (!$discovery->catalog) {
                    continue;
                }

                $catalog = $discovery->catalog;
                $currentCatalogIds[] = $catalog->id;
                
                $status = 'Berlisensi';
                $keterangan = 'Lisensi aktif dan valid';
                $licenseId = null;

                // 1. CEK BLOCKLIST
                $isBlocked = false;
                foreach ($blockedSoftwareList as $blockedName) {
                    if (Str::contains(strtolower($discovery->software_name), strtolower($blockedName))) {
                        $isBlocked = true;
                        break;
                    }
                }

                if ($isBlocked) {
                    $status = 'Tidak Berlisensi';
                    $keterangan = 'Aplikasi terlarang terdeteksi';
                } 
                // 2. CEK KATEGORI NON-COMMERCIAL
                elseif ($catalog->category !== 'Commercial') {
                    $status = 'Berlisensi';
                    $keterangan = 'Software gratis, tidak memerlukan lisensi';
                } 
                else {
                    // Software is Commercial, need to check license
                    $license = $catalog->licenses->first(); // Assuming one primary license record per catalog for simplicity as per instructions

                    // 3. CEK LISENSI ADA ATAU TIDAK
                    if (!$license) {
                        $status = 'Tidak Berlisensi';
                        $keterangan = 'Lisensi tidak ditemukan dalam sistem';
                    } 
                    else {
                        $licenseId = $license->id;
                        $today = now()->startOfDay();

                        // 4. CEK EXPIRED
                        if ($license->expiry_date && $license->expiry_date->isPast() && !$license->expiry_date->isToday()) {
                            $status = 'Tidak Berlisensi';
                            $keterangan = 'Lisensi telah kedaluwarsa';
                        } 
                        // 5. CEK KUOTA
                        else {
                            $installationCount = SoftwareDiscovery::where('catalog_id', $catalog->id)->count();
                            if ($license->quota_limit > 0 && $installationCount > $license->quota_limit) {
                                $status = 'Tidak Berlisensi';
                                $keterangan = 'Kuota lisensi penuh';
                            } 
                            // 6. CEK HAMPIR EXPIRED (Grace Period)
                            elseif ($license->expiry_date && $license->expiry_date->lte($today->copy()->addDays(30))) {
                                $status = 'Grace Period';
                                $keterangan = 'Lisensi akan segera berakhir';
                            }
                        }
                    }
                }

                $records[] = [
                    'computer_id' => $this->computer->id,
                    'software_catalog_id' => $catalog->id,
                    'software_name' => $discovery->software_name,
                    'software_version' => $discovery->software_version,
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'license_inventory_id' => $licenseId,
                    'detected_at' => $discovery->detected_at ?? now(),
                    'scanned_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ];
            }

            // STEP 3 — Upsert ke Database
            if (!empty($records)) {
                // MySQL upsert
                ComplianceReport::upsert($records, 
                    ['computer_id', 'software_catalog_id'], 
                    ['status', 'keterangan', 'license_inventory_id', 'software_version', 'detected_at', 'scanned_at', 'updated_at']
                );
            }

            // STEP 4 — Hapus Record Stale
            ComplianceReport::where('computer_id', $this->computer->id)
                ->whereNotIn('software_catalog_id', $currentCatalogIds)
                ->delete();

            // STEP 5 — Clear Cache
            Cache::forget('dashboard_metrics');

            Log::info('Compliance report generation completed for computer: ' . $this->computer->hostname);

        } catch (\Throwable $e) {
            Log::error('GenerateComplianceReportJob failed', [
                'computer_id' => $this->computer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
