<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('pail:watch', function () {
    if (function_exists('pcntl_fork')) {
        $this->call('pail', ['--timeout' => 0]);

        return 0;
    }

    $logFile = storage_path('logs/laravel.log');
    if (! file_exists($logFile)) {
        @touch($logFile);
    }

    $this->info('Log monitor active (tailing storage/logs/laravel.log)');
    $handle = fopen($logFile, 'r');
    if ($handle) {
        fseek($handle, 0, SEEK_END);
        while (true) {
            $line = fgets($handle);
            if ($line !== false) {
                $this->line(rtrim($line));
            } else {
                usleep(500000);
            }
        }
        fclose($handle);
    }

    return 0;
})->purpose('Tail application logs cross-platform');

Schedule::command('activitylog:clean --force')->daily();
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run --only-db')->daily()->at('01:30');
