<?php

namespace App\Providers;

use App\View\Widgets\StatCard;
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
        Blade::anonymousComponentPath(
            resource_path('views/widgets'),
            'widget'
        );

        Blade::component('stat-card', StatCard::class);
    }
}
