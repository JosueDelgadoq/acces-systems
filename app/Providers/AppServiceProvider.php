<?php

namespace App\Providers;

use App\Models\Claim;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use App\Observers\ReclamoObserver;

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
    if (str_contains(config('app.url'), 'ngrok')) {
        URL::forceScheme('https');
    }
        Claim::observe(ReclamoObserver::class);
}

}