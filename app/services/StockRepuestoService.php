<?php

namespace App\Services;

use App\Models\Repuesto;
use App\Models\MovimientoRepuesto;

class StockRepuestoService
{
    public static function entrada($repuestoId, $cantidad, $motivo = null)
    {
        $repuesto = Repuesto::findOrFail($repuestoId);

        $repuesto->increment('stock_actual', $cantidad);

        MovimientoRepuesto::create([
            'repuesto_id' => $repuesto->id,
            'tipo' => 'entrada',
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'user_id' => auth()->id(),
        ]);
    }

    public static function salida($repuestoId, $cantidad, $motivo = null)
    {
        $repuesto = Repuesto::findOrFail($repuestoId);

        if ($repuesto->stock_actual < $cantidad) {
            throw new \Exception('Stock insuficiente');
        }

        $repuesto->decrement('stock_actual', $cantidad);

        MovimientoRepuesto::create([
            'repuesto_id' => $repuesto->id,
            'tipo' => 'salida',
            'cantidad' => $cantidad,
            'motivo' => $motivo,
            'user_id' => auth()->id(),
        ]);
    }
}