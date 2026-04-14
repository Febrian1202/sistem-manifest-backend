<?php

namespace App\Jobs;

use App\Models\Computer;
use App\Services\SoftwareCatalogService;
use App\Services\SoftwareFilterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Computer $computer,
        public array $softwareList,
    ) {
        $this->onQueue('scans');
    }

    /**
     * Execute the job.
     */
    public function handle(SoftwareFilterService $filterService, SoftwareCatalogService $catalogService): void
    {
        // 1. Log processing start
        Log::info('Processing scan for computer: ' . $this->computer->hostname);

        // 2. Filter software into categories
        $filterResult = $filterService->filter($this->softwareList);

        // 3. Sync discoveries to database
        $catalogService->syncDiscoveries(
            $this->computer,
            $filterResult->clean,
            $filterResult->flagged
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessScanResultJob failed', [
            'mac_address' => $this->hardwareData['mac_address'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);
    }
}
