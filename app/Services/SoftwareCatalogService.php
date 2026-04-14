<?php

namespace App\Services;

use App\Models\Computer;
use App\Models\SoftwareCatalog;
use App\Models\SoftwareDiscovery;
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
                $discovery = SoftwareDiscovery::updateOrCreate(
                    [
                        'computer_id' => $computer->id,
                        'catalog_id' => $catalog->id,
                    ],
                    [
                        'raw_name' => $name,
                        'version' => $soft['version'] ?? null,
                        'vendor' => $soft['vendor'] ?? null,
                        'install_date' => $soft['install_date'] ?? now(),
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
}
