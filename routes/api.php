<?php

use App\Http\Controllers\Api\SamuWebhookController;
use App\Http\Controllers\TrackingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas usadas por la app Android de técnicos.
|
*/

/*
|--------------------------------------------------------------------------
| Login App Android
|--------------------------------------------------------------------------
*/

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (! Auth::attempt($credentials)) {
        return response()->json([
            'success' => false,
            'message' => 'Credenciales inválidas',
        ], 401);
    }

    $user = Auth::user();

    return response()->json([
        'success' => true,
        'token' => $user->createToken('mobile')->plainTextToken,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'technician_status' => $user->technician_status,
            'tracking_enabled' => $user->tracking_enabled,
        ],
    ]);
});

Route::post('/webhooks/samu', SamuWebhookController::class);

/*
|--------------------------------------------------------------------------
| Rutas protegidas App Android
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Tracking GPS
    |--------------------------------------------------------------------------
    */

    Route::post('/visits/{visit}/accept', [TrackingController::class, 'acceptVisit']);

    Route::post('/tracking/update', [TrackingController::class, 'update'])
        ->middleware('throttle:tracking-updates');

    Route::post('/tracking/status', [TrackingController::class, 'status'])
        ->middleware('throttle:tracking-status');

    Route::get('/tracking/history/{user}', [TrackingController::class, 'history'])
        ->middleware('throttle:tracking-history');

    /*
    |--------------------------------------------------------------------------
    | Trabajos / visitas del técnico
    |--------------------------------------------------------------------------
    */

    Route::get('/my-visits', [TrackingController::class, 'myVisits'])
        ->middleware('throttle:60,1');

    Route::get('/visits/{visit}/events', [TrackingController::class, 'visitEvents'])
        ->middleware('throttle:tracking-history');

    Route::post('/visits/{visit}/start', [TrackingController::class, 'startVisit'])
        ->middleware('throttle:30,1');

    Route::post('/visits/{visit}/finish', [TrackingController::class, 'finishVisit'])
        ->middleware('throttle:30,1');

    /*
    |--------------------------------------------------------------------------
    | Sesión
    |--------------------------------------------------------------------------
    */

    Route::post('/logout', function (Request $request) {
        $request->user()?->update([
            'technician_status' => 'offline',
            'last_seen_at' => now(),
        ]);

        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada',
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Live Tracking para mapa del ERP
|--------------------------------------------------------------------------
|
| Esta ruta queda pública para que tu Blade del mapa pueda consultar técnicos
| en vivo sin token. Si después querés más seguridad, la movemos a web.php
| con middleware auth.
|
*/

Route::get('/tracking/live', [TrackingController::class, 'live'])
    ->middleware('throttle:tracking-live');
