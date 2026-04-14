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
        public array $hardwareData,
        public array $softwareList,
    ) {
        $this->onQueue('scans');
    }

    /**
     * Execute the job.
     */
    public function handle(SoftwareFilterService $filterService, SoftwareCatalogService $catalogService): void
    {
        // 1. Find the computer (it was created/synced synchronously in Controller)
        $computer = Computer::where('hostname', $this->hardwareData['computer_name'] ?? '')->first();

        if (!$computer) {
            throw new \Exception("Computer record not found for hostname: " . ($this->hardwareData['computer_name'] ?? 'unknown'));
        }

        // 2. Filter software into categories
        $filterResult = $filterService->filter($this->softwareList);

        // 3. Sync discoveries to database
        $catalogService->syncDiscoveries(
            $computer,
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
