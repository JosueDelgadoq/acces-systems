<?php

namespace App\Http\Controllers;

use App\Models\ProductoVariante;
use App\Models\Stock;
use App\Models\MovimientoStock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function scan(Request $request)
    {
        $codigo = $request->codigo_barra;

        $variante = ProductoVariante::with('producto', 'stock')
            ->where('codigo_barra', $codigo)
            ->first();

        if (!$variante) {
            return response()->json(['error' => 'No encontrado'], 404);
        }

        return response()->json([
            'producto' => $variante->producto->nombre,
            'tipo' => $variante->producto->tipo,
            'estado' => $variante->estado,
            'uso' => $variante->uso,
            'lado' => $variante->lado,
            'modelo' => $variante->modelo,
            'stock' => $variante->stock->cantidad ?? 0
        ]);
    }

    public function movimiento(Request $request)
    {
        $variante = ProductoVariante::findOrFail($request->producto_variante_id);

        MovimientoStock::create([
            'producto_variante_id' => $variante->id,
            'tipo' => $request->tipo,
            'cantidad' => $request->cantidad,
            'usuario_id' => auth()->id(),
        ]);

        $stock = Stock::firstOrCreate(
            ['producto_variante_id' => $variante->id],
            ['cantidad' => 0]
        );

        if ($request->tipo == 'entrada') {
            $stock->cantidad += $request->cantidad;
        } else {
            $stock->cantidad -= $request->cantidad;
        }

        $stock->save();

        return response()->json(['ok' => true]);
    }
}