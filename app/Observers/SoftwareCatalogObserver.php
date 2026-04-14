<?php

namespace App\Observers;

use App\Models\SoftwareCatalog;
use Illuminate\Support\Facades\Cache;

class SoftwareCatalogObserver
{
    public function created(SoftwareCatalog $catalog): void
    {
        Cache::tags(['dashboard'])->flush();
    }

    public function updated(SoftwareCatalog $catalog): void
    {
        // Many dashboard stats depend on software status (e.g. Blacklisted)
        Cache::tags(['dashboard'])->flush();
    }

    public function deleted(SoftwareCatalog $catalog): void
    {
        Cache::tags(['dashboard'])->flush();
    }
}
