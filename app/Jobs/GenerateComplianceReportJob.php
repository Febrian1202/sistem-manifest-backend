<?php

namespace App\Jobs;

use App\Models\ComplianceReport;
use App\Models\ComplianceSnapshot;
use App\Models\Computer;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
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

    public ScanSession|Computer $target;

    public Computer $computer;

    public ?ScanSession $scanSession = null;

    /**
     * Create a new job instance.
     */
    public function __construct(
        ScanSession|Computer $target
    ) {
        $this->target = $target;

        if ($target instanceof ScanSession) {
            $this->scanSession = $target;
            $this->computer = $target->computer ?? Computer::find($target->computer_id);
        } else {
            $this->computer = $target;
        }

        $this->onQueue('compliance');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Generating compliance report for computer: '.$this->computer->hostname);

            // STEP 1 — Load Data
            $softwareItems = collect();

            if ($this->scanSession) {
                $softwareItems = ScanSoftwareResult::with(['catalog', 'catalog.licenses'])
                    ->where('scan_session_id', $this->scanSession->id)
                    ->get();
            }

            if ($softwareItems->isEmpty()) {
                $softwareItems = SoftwareDiscovery::with(['catalog', 'catalog.licenses'])
                    ->where('computer_id', $this->computer->id)
                    ->get();
            }

            $blockedSoftwareList = config('compliance.blocked_software', []);
            $complianceReportRecords = [];
            $complianceSnapshotRecords = [];
            $currentCatalogIds = [];
            $scannedAt = $this->scanSession?->started_at ?? now();

            // STEP 2 — Process Setiap Software
            foreach ($softwareItems as $item) {
                if (! $item->catalog) {
                    continue;
                }

                $catalog = $item->catalog;
                $rawName = $item->raw_name ?? $item->software_name;
                $version = $item->version ?? $item->software_version ?? null;
                $installDate = $item->install_date ?? $item->detected_at ?? null;

                $currentCatalogIds[] = $catalog->id;

                $status = 'Berlisensi';
                $keterangan = 'Lisensi aktif dan valid';
                $licenseId = null;

                // 1. CEK BLOCKLIST
                $isBlocked = false;
                foreach ($blockedSoftwareList as $blockedName) {
                    if (Str::contains(strtolower($rawName), strtolower($blockedName))) {
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
                } else {
                    // Software is Commercial, need to check license
                    $license = $catalog->licenses->first();

                    // 3. CEK LISENSI ADA ATAU TIDAK
                    if (! $license) {
                        $status = 'Tidak Berlisensi';
                        $keterangan = 'Lisensi tidak ditemukan dalam sistem';
                    } else {
                        $licenseId = $license->id;
                        $today = now()->startOfDay();

                        // 4. CEK EXPIRED
                        if ($license->expiry_date && $license->expiry_date->isPast() && ! $license->expiry_date->isToday()) {
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
                            elseif ($license->expiry_date && $license->expiry_date->isBetween($today, $today->copy()->addDays(30))) {
                                $status = 'Grace Period';
                                $keterangan = 'Lisensi akan segera berakhir';
                            }
                        }
                    }
                }

                $complianceReportRecords[] = [
                    'computer_id' => $this->computer->id,
                    'software_catalog_id' => $catalog->id,
                    'software_name' => $rawName,
                    'software_version' => $version,
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'license_inventory_id' => $licenseId,
                    'detected_at' => $installDate ?? now(),
                    'scanned_at' => $scannedAt,
                    'updated_at' => now(),
                    'created_at' => now(),
                ];

                if ($this->scanSession) {
                    $complianceSnapshotRecords[] = [
                        'scan_session_id' => $this->scanSession->id,
                        'computer_id' => $this->computer->id,
                        'software_catalog_id' => $catalog->id,
                        'software_name' => $rawName,
                        'software_version' => $version,
                        'status' => $status,
                        'keterangan' => $keterangan,
                        'license_inventory_id' => $licenseId,
                        'detected_at' => $installDate,
                        'scanned_at' => $scannedAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // STEP 3 — Persist Snapshots & Upsert Reports
            if (! empty($complianceSnapshotRecords)) {
                ComplianceSnapshot::insert($complianceSnapshotRecords);
            }

            if (! empty($complianceReportRecords)) {
                // MySQL / SQLite upsert
                ComplianceReport::upsert(
                    $complianceReportRecords,
                    ['computer_id', 'software_catalog_id'],
                    ['status', 'keterangan', 'license_inventory_id', 'software_version', 'detected_at', 'scanned_at', 'updated_at']
                );
            }

            // STEP 4 — Hapus Record Stale
            ComplianceReport::where('computer_id', $this->computer->id)
                ->whereNotIn('software_catalog_id', $currentCatalogIds)
                ->delete();

            // STEP 5 — Clear Cache
            Cache::forget('dashboard.stats.'.now()->format('Y-m'));
            Cache::forget('dashboard.stats.'.now()->subMonth()->format('Y-m'));
            Cache::forget('dashboard.charts');
            Cache::forget('compliance.global_stats');

            Log::info('Compliance report generation completed for computer: '.$this->computer->hostname);

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
