<?php

namespace App\Jobs;

use App\Models\Computer;
use App\Models\ScanSession;
use App\Models\ScanSoftwareResult;
use App\Models\SoftwareCatalog;
use App\Services\SoftwareCatalogService;
use App\Services\SoftwareFilterService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessScanResultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [30, 60, 120];

    public ScanSession|Computer $target;

    public Computer $computer;

    public ?ScanSession $scanSession = null;

    public array $softwareList;

    /**
     * Create a new job instance.
     */
    public function __construct(
        ScanSession|Computer $target,
        array $softwareList = [],
    ) {
        $this->target = $target;
        $this->softwareList = $softwareList;

        if ($target instanceof ScanSession) {
            $this->scanSession = $target;
            $this->computer = $target->computer ?? Computer::find($target->computer_id);
        } else {
            $this->computer = $target;
        }

        $this->onQueue('scans');
    }

    /**
     * Execute the job.
     */
    public function handle(SoftwareFilterService $filterService, SoftwareCatalogService $catalogService): void
    {
        try {
            // 1. Ensure ScanSession exists
            if (! $this->scanSession) {
                $this->scanSession = ScanSession::create([
                    'computer_id' => $this->computer->id,
                    'scan_uuid' => (string) Str::uuid(),
                    'started_at' => now(),
                    'status' => 'pending',
                    'trigger' => $this->computer->scan_requested ? 'on_demand' : 'scheduled',
                ]);
            }

            $this->scanSession->update(['status' => 'running']);

            // 2. Log processing start
            Log::info('Processing scan for computer: '.$this->computer->hostname);

            // 3. Filter software into categories
            $filterResult = $filterService->filter($this->softwareList);

            // 4. Sync discoveries to database (current state)
            $catalogService->syncDiscoveries(
                $this->computer,
                $filterResult->clean,
                $filterResult->flagged
            );

            // 5. Persist historical software results
            foreach ($filterResult->clean as $soft) {
                $catalog = SoftwareCatalog::where('normalized_name', $soft['name'])->first();

                ScanSoftwareResult::create([
                    'scan_session_id' => $this->scanSession->id,
                    'catalog_id' => $catalog?->id,
                    'raw_name' => $soft['name'],
                    'version' => $soft['version'] ?? null,
                    'vendor' => $soft['vendor'] ?? null,
                    'install_date' => $this->parseInstallDate($soft['install_date'] ?? null),
                ]);
            }

            // 6. Complete ScanSession
            $this->scanSession->update([
                'software_count' => count($filterResult->clean),
                'completed_at' => now(),
                'status' => 'completed',
            ]);

            // 7. Dispatch compliance report generation
            GenerateComplianceReportJob::dispatch($this->scanSession);

        } catch (\Throwable $e) {
            if ($this->scanSession) {
                $this->scanSession->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => now(),
                ]);
            }

            throw $e;
        }
    }

    /**
     * Parse install_date safely.
     */
    protected function parseInstallDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        if (preg_match('/^\d{8}$/', $date)) {
            try {
                return Carbon::createFromFormat('Ymd', $date)->toDateString();
            } catch (\Exception) {
                return null;
            }
        }

        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        if ($this->scanSession) {
            $this->scanSession->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }

        Log::error('ProcessScanResultJob failed', [
            'mac_address' => $this->computer->mac_address ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
