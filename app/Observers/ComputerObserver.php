<?php

namespace App\Observers;

use App\Models\Computer;
use Illuminate\Support\Facades\Cache;

class ComputerObserver
{
    public function created(Computer $computer): void
    {
        Cache::tags(['dashboard'])->flush();
    }

    public function updated(Computer $computer): void
    {
        Cache::tags(['dashboard'])->flush();
    }

    public function deleted(Computer $computer): void
    {
        Cache::tags(['dashboard'])->flush();
    }
}
