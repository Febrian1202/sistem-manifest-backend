<?php

namespace App\Services;

use App\Models\Computer;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SoftwareCatalogService
{
    /**
     * Sync software discoveries for a computer.
     * Uses updateOrCreate for idempotency in queued jobs.
     */
    public function syncDiscoveries(Computer $computer, array $cleanSoftware, array $flaggedSoftware): void
    {
        DB::transaction(function () use ($computer, $cleanSoftware, $flaggedSoftware) {
            // Collect IDs of software currently installed to prune later if needed, 
            // or we can stick to the delete-first approach if we want to be simple.
            // However, Step 2 explicitly says "Use updateOrCreate".
            
            $currentDiscoveryIds = [];
            $flaggedNames = collect($flaggedSoftware)->pluck('name')->toArray();

            foreach ($cleanSoftware as $soft) {
                $name = $soft['name'];

                // 1. Find or Create Catalog entry
                $catalog = SoftwareCatalog::firstOrCreate(
                    ['normalized_name' => $name],
                    ['status' => 'Unreviewed', 'category' => 'Freeware']
                );

                // 2. Auto-blacklist if flagged and still unreviewed
                if (in_array($name, $flaggedNames) && $catalog->status === 'Unreviewed') {
                    $catalog->update(['status' => 'Blacklist']);
                }

                // 3. Sync Discovery (updateOrCreate for idempotency)
                // We use [computer_id, raw_name, version] as match keys per Fix 1 requirements.
                // We use '' as a sentinel for NULL version to ensure proper unique indexing.
                $discovery = SoftwareDiscovery::updateOrCreate(
                    [
                        'computer_id' => $computer->id,
                        'raw_name' => $name,
                        'version' => $soft['version'] ?? '',
                    ],
                    [
                        'catalog_id' => $catalog->id,
                        'vendor' => $soft['vendor'] ?? null,
                        'install_date' => $this->parseInstallDate($soft['install_date'] ?? null),
                    ]
                );

                $currentDiscoveryIds[] = $discovery->id;
            }

            // 4. Prune discoveries no longer present on the computer
            SoftwareDiscovery::where('computer_id', $computer->id)
                ->whereNotIn('id', $currentDiscoveryIds)
                ->delete();
        });
    }

    /**
     * Parse install_date from various formats (e.g. 'M/d/yyyy', 'yyyyMMdd') to a MySQL-compatible date.
     */
    private function parseInstallDate(?string $date): string
    {
        if (empty($date)) {
            return now()->toDateString();
        }

        // Handle Windows registry format 'yyyyMMdd' (e.g. '20250205')
        if (preg_match('/^\d{8}$/', $date)) {
            try {
                return Carbon::createFromFormat('Ymd', $date)->toDateString();
            } catch (\Exception $e) {
                return now()->toDateString();
            }
        }

        // Handle other formats like 'M/d/yyyy', 'yyyy-MM-dd', etc.
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Exception $e) {
            return now()->toDateString();
        }
    }
}
