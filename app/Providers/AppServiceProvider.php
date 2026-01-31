<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        //
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
