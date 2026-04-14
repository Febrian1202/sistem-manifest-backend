<?php

namespace App\Observers;

use App\Models\LicenseInventory;
use Illuminate\Support\Facades\Cache;

class LicenseInventoryObserver
{
    public function created(LicenseInventory $license): void
    {
        // License changes affect compliance stats shown on dashboard
        Cache::tags(['dashboard'])->flush();
    }

    public function updated(LicenseInventory $license): void
    {
        Cache::tags(['dashboard'])->flush();
    }

    public function deleted(LicenseInventory $license): void
    {
        Cache::tags(['dashboard'])->flush();
    }
}
