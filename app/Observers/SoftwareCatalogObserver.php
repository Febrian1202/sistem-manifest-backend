<?php

namespace App\Observers;

use App\Models\SoftwareCatalog;
use Illuminate\Support\Facades\Cache;

class SoftwareCatalogObserver
{
    public function created(SoftwareCatalog $catalog): void
    {
        Cache::forget('dashboard.stats.'.now()->format('Y-m'));
        Cache::forget('dashboard.charts');
        Cache::forget('compliance.global_stats');
    }

    public function updated(SoftwareCatalog $catalog): void
    {
        // Many dashboard stats depend on software status (e.g. Blacklist)
        Cache::forget('dashboard.stats.'.now()->format('Y-m'));
        Cache::forget('dashboard.charts');
        Cache::forget('compliance.global_stats');
    }

    public function deleted(SoftwareCatalog $catalog): void
    {
        Cache::forget('dashboard.stats.'.now()->format('Y-m'));
        Cache::forget('dashboard.charts');
        Cache::forget('compliance.global_stats');
    }
}
