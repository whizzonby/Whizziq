<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class FilamentOnboardingViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register the filament-onboarding view namespace
        View::addNamespace('filament-onboarding', [
            resource_path('views/vendor/filament-onboarding'),
            base_path('vendor/saasykit/filament-onboarding/resources/views')
        ]);
    }
}
