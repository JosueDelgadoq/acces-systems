<?php

namespace App\Providers;

use App\Services\PendienteSchemaService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PendienteSchemaService::class);
    }

    public function boot(): void
    {
        App::setLocale('es');

        RateLimiter::for('tracking-updates', function (Request $request) {
            return [
                Limit::perMinute(90)->by((string) (optional($request->user())->id ?: $request->ip())),
            ];
        });

        RateLimiter::for('tracking-status', function (Request $request) {
            return [
                Limit::perMinute(30)->by((string) (optional($request->user())->id ?: $request->ip())),
            ];
        });

        RateLimiter::for('tracking-live', function (Request $request) {
            return [
                Limit::perMinute(120)->by((string) (optional($request->user())->id ?: $request->ip())),
            ];
        });

        RateLimiter::for('tracking-history', function (Request $request) {
            return [
                Limit::perMinute(60)->by((string) (optional($request->user())->id ?: $request->ip())),
            ];
        });

        if (app()->runningInConsole() && app()->environment('local')) {
            URL::forceRootUrl(config('app.url'));

            if (str_starts_with((string) config('app.url'), 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
