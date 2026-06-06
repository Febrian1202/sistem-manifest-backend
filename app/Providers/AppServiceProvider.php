<?php

namespace App\Providers;

use App\Models\Computer;
use App\Models\LicenseInventory;
use App\Models\SoftwareCatalog;
use App\Observers\ComputerObserver;
use App\Observers\LicenseInventoryObserver;
use App\Observers\SoftwareCatalogObserver;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Registration of Model Observers for Cache Invalidation
        Computer::observe(ComputerObserver::class);
        SoftwareCatalog::observe(SoftwareCatalogObserver::class);
        LicenseInventory::observe(LicenseInventoryObserver::class);

        Blade::component('components.ui.dropdown', 'dropdown');
        Blade::component('components.ui.dropdown-item', 'dropdown-item');
        Blade::component('components.ui.dropdown-label', 'dropdown-label');
        Blade::component('components.ui.dropdown-separator', 'dropdown-separator');
        Blade::component('components.ui.button', 'button');
        Blade::component('components.dashboard.stat-card', 'stat-card');
        Blade::component('components.form.input', 'input');
        Blade::component('components.form.label', 'label');
    }
}
