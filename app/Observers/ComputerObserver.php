<?php

namespace App\Observers;

use App\Models\Computer;
use Illuminate\Support\Facades\Cache;

class ComputerObserver
{
    public function created(Computer $computer): void
    {
        Cache::forget('dashboard.stats.'.now()->format('Y-m'));
        Cache::forget('dashboard.charts');
        Cache::forget('compliance.global_stats');
    }

    public function updated(Computer $computer): void
    {
        Cache::forget('dashboard.stats.'.now()->format('Y-m'));
        Cache::forget('dashboard.charts');
        Cache::forget('compliance.global_stats');
    }

    public function deleted(Computer $computer): void
    {
        Cache::forget('dashboard.stats.'.now()->format('Y-m'));
        Cache::forget('dashboard.charts');
        Cache::forget('compliance.global_stats');
    }
}
