<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Http\Request;
use App\Models\Pendiente;
use App\Jobs\CheckDueDatesJob;
use Illuminate\Support\Facades\Route;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas programadas (Schedule)
Schedule::job(new CheckDueDatesJob)->dailyAt('08:00');

Route::post('/pendientes-update-date', function (Request $request) {
    $pendiente = Pendiente::findOrFail($request->id);
    $pendiente->due_date = $request->date;
    $pendiente->save();

    return response()->json(['success' => true]);
});