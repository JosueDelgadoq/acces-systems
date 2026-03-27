<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockController;
use App\Models\Pendiente;

// Home
Route::get('/', function () {
    return view('welcome');
});

// Redirección al admin (si querés usar Filament)
Route::redirect('/', '/admin');

// 🔥 RUTAS DE STOCK (AFUERA, independientes)
Route::post('/scan', [StockController::class, 'scan']);
Route::post('/movimiento', [StockController::class, 'movimiento']);

// Calendario
Route::get('/api/pendientes-calendar', function () {
    return Pendiente::all()->map(function ($p) {
        return [
            'id' => $p->id,
            'title' => $p->description . ' - ' . ($p->user->name ?? 'Sin asignar'),
            'start' => $p->due_date,
            'color' => match ($p->priority) {
                'alta' => '#ef4444',
                'media' => '#f59e0b',
                'baja' => '#22c55e',
                default => '#6b7280',
            },
        ];
    });
});