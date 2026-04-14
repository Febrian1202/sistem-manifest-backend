<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearDashboardCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-dashboard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear only dashboard related cache tags';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Cache::tags(['dashboard'])->flush();
        $this->info('Dashboard cache cleared successfully.');
    }
}
