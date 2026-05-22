<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\ProductVariantRepuesto;
use App\Models\Repuesto;
use Illuminate\Support\Facades\Schema;

class EquipoService
{
    public function diagnosticar(int $equipoId): array
    {
        if (! Schema::hasColumn('equipos', 'producto_variante_id')) {
            return [
                'faltantes' => [],
                'ok' => [],
            ];
        }

        $equipo = Equipo::query()->with('repuestos')->find($equipoId);

        if (! $equipo || blank($equipo->producto_variante_id)) {
            return [
                'faltantes' => [],
                'ok' => [],
            ];
        }

        $esperados = ProductVariantRepuesto::query()
            ->where('producto_variante_id', $equipo->producto_variante_id)
            ->get();

        $actuales = $equipo->repuestos->keyBy('repuesto_id');
        $faltantes = [];
        $ok = [];

        foreach ($esperados as $item) {
            $actual = $actuales[$item->repuesto_id] ?? null;

            if (! $actual) {
                $faltantes[] = [
                    'repuesto_id' => $item->repuesto_id,
                    'cantidad_faltante' => $item->cantidad,
                ];
                continue;
            }

            if ($actual->cantidad < $item->cantidad) {
                $faltantes[] = [
                    'repuesto_id' => $item->repuesto_id,
                    'cantidad_faltante' => $item->cantidad - $actual->cantidad,
                ];
            } else {
                $ok[] = $item->repuesto_id;
            }
        }

        return [
            'faltantes' => $faltantes,
            'ok' => $ok,
        ];
    }

    public function sugerirRepuestos(array $faltantes): array
    {
        $sugerencias = [];

        foreach ($faltantes as $faltante) {
            $stock = Repuesto::query()
                ->whereKey($faltante['repuesto_id'])
                ->first();

            $sugerencias[] = [
                'repuesto_id' => $faltante['repuesto_id'],
                'necesario' => $faltante['cantidad_faltante'],
                'disponible' => $stock?->stock_actual ?? 0,
            ];
        }

        return $sugerencias;
    }
}
