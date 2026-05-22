<?php

use App\Http\Controllers\ClientMapController;
use App\Http\Controllers\CommercialMetricsExportController;
use App\Http\Controllers\MapaTecnicosController;
use App\Http\Controllers\PendienteCalendarController;
use App\Http\Controllers\ServiceVisitController;
use App\Http\Controllers\ServiceVisitMapController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\UsedEquipmentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::get('/used-equipment/{usedEquipment}', [UsedEquipmentController::class, 'show'])
    ->name('used-equipment.show');

Route::get('/used-equipment/{usedEquipment}/label', [UsedEquipmentController::class, 'label'])
    ->name('used-equipment.label');

Route::middleware('auth')->group(function () {
    Route::get('/admin/reportes/comercial/xlsx', [CommercialMetricsExportController::class, 'xlsx'])
        ->name('commercial-metrics.xlsx');

    Route::get('/admin/reportes/comercial/pdf', [CommercialMetricsExportController::class, 'pdf'])
        ->name('commercial-metrics.pdf');

    /*
    |--------------------------------------------------------------------------
    | MAPAS
    |--------------------------------------------------------------------------
    */

    Route::get('/mapa/clientes', [ClientMapController::class, 'index']);

    Route::get('/mapa/tecnicos-live', [MapaTecnicosController::class, 'index'])
        ->middleware('throttle:tracking-live');

    Route::get('/mapa/visitas', [ServiceVisitMapController::class, 'index'])
        ->middleware('throttle:tracking-live');

    Route::redirect('/mapa', '/admin/mapa-clientes');

    /*
    |--------------------------------------------------------------------------
    | TRACKING WEB
    |--------------------------------------------------------------------------
    */

    Route::get('/tracking/live', [TrackingController::class, 'live'])
        ->middleware('throttle:tracking-live');

    Route::get('/tracking/history/{user}', [TrackingController::class, 'history'])
        ->middleware('throttle:tracking-history');

    Route::get('/tracking/visits/{visit}/events', [TrackingController::class, 'visitEvents'])
        ->middleware('throttle:tracking-history')
        ->name('tracking.visits.events');

    Route::post('/tracking/update', [TrackingController::class, 'update'])
        ->middleware('throttle:tracking-updates');

    Route::post('/tracking/status', [TrackingController::class, 'status'])
        ->middleware('throttle:tracking-status');

    Route::get('/tracking/mobile', [TrackingController::class, 'mobile'])
        ->name('tracking.mobile');

    /*
    |--------------------------------------------------------------------------
    | SERVICE VISITS
    |--------------------------------------------------------------------------
    */

    Route::post('/service-visit/upload-arrival', [ServiceVisitController::class, 'uploadArrival']);

    Route::post('/service-visit/upload-departure', [ServiceVisitController::class, 'uploadDeparture']);

    Route::post('/visits/{id}/start', [ServiceVisitController::class, 'start']);

    Route::post('/visits/{id}/finish', [ServiceVisitController::class, 'finish']);

    /*
    |--------------------------------------------------------------------------
    | STOCK
    |--------------------------------------------------------------------------
    */

    Route::post('/scan', [StockController::class, 'scan']);

    Route::post('/movimiento', [StockController::class, 'movimiento']);

    /*
    |--------------------------------------------------------------------------
    | CALENDARIO PENDIENTES
    |--------------------------------------------------------------------------
    */

    Route::get('/api/pendientes-calendar', [PendienteCalendarController::class, 'feed'])
        ->middleware('throttle:60,1');

    Route::post('/api/pendientes-update-date', [PendienteCalendarController::class, 'updateDate'])
        ->middleware('throttle:30,1');
});
