<?php

use App\Jobs\CheckDueDatesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas programadas (Schedule)
Schedule::job(new CheckDueDatesJob)
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('alerts:notify-stale-leads')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('alerts:process')
    ->hourly()
    ->withoutOverlapping();
