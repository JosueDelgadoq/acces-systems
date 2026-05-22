<?php

namespace App\Services;

use App\Models\ProductoUnidad;
use App\Models\ProductoVariante;
use App\Models\User;
use App\Support\Inventory\InventorySerialNormalizer;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class ProductoUnidadInventoryService
{
    public function findUnitByBarcode(string $barcode): ?ProductoUnidad
    {
        $trimmed = trim($barcode);
        $normalizedSerial = InventorySerialNormalizer::normalize($trimmed);

        return ProductoUnidad::query()
            ->with(['variante.producto', 'location', 'client', 'clientEquipo'])
            ->where('codigo_barra', $trimmed)
            ->orWhere('inventory_code', $trimmed)
            ->when(
                filled($normalizedSerial),
                fn ($query) => $query->orWhere('serial_number_normalized', $normalizedSerial),
            )
            ->first();
    }

    public function buildScanPayload(ProductoUnidad $unit): array
    {
        $unit->loadMissing(['variante.producto', 'location', 'client', 'clientEquipo']);

        $variant = $unit->variante;

        return [
            'id' => $unit->id,
            'scan_type' => 'unit',
            'producto_unidad_id' => $unit->id,
            'producto_variante_id' => $unit->producto_variante_id,
            'inventory_code' => $unit->inventory_code,
            'codigo_barra' => $unit->codigo_barra,
            'serial_number' => $unit->serial_number,
            'producto' => $variant?->producto?->nombre,
            'tipo' => $variant?->producto?->tipo,
            'estado' => $unit->estado,
            'estado_label' => $unit->status_label,
            'condition' => $unit->condition,
            'condition_label' => $unit->condition_label,
            'uso' => $variant?->uso,
            'lado' => $variant?->lado,
            'modelo' => $variant?->modelo,
            'subtype' => $variant?->subtype,
            'location' => $unit->location?->name,
            'client' => $unit->client?->company ?: $unit->client?->name,
            'client_equipo' => $unit->clientEquipo?->serie,
            'stock' => $variant?->stock_actual ?? 0,
            'stock_disponible' => $variant?->stock_actual ?? 0,
            'total_unidades' => $variant?->unidades()->count() ?? 0,
            'movement_count' => $unit->movements()->count(),
        ];
    }

    public function createUnits(
        ProductoVariante $variant,
        int $quantity,
        ?User $actor = null,
        ?string $reason = null,
        ?string $notes = null,
    ): EloquentCollection {
        return DB::transaction(function () use ($variant, $quantity, $actor, $reason, $notes): EloquentCollection {
            $units = new EloquentCollection();

            for ($index = 0; $index < $quantity; $index++) {
                $unit = new ProductoUnidad([
                    'producto_variante_id' => $variant->id,
                    'estado' => ProductoUnidad::STATUS_AVAILABLE,
                ]);

                $unit->setMovementContext([
                    'user_id' => $actor?->id,
                    'movement_type' => 'alta',
                    'reason' => $reason ?: 'ingreso_stock',
                    'notes' => $notes,
                ]);

                $unit->save();
                $units->push($unit);
            }

            return $units;
        });
    }

    public function changeState(
        ProductoUnidad $unit,
        string $targetState,
        ?User $actor = null,
        ?string $reason = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): ProductoUnidad {
        return DB::transaction(function () use (
            $unit,
            $targetState,
            $actor,
            $reason,
            $notes,
            $referenceType,
            $referenceId,
        ): ProductoUnidad {
            $locked = ProductoUnidad::query()
                ->whereKey($unit->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $locked->loadMissing(['variante.producto']);

            if ($locked->estado === $targetState) {
                return $locked;
            }

            $locked->setMovementContext([
                'user_id' => $actor?->id,
                'movement_type' => $this->resolveMovementType($locked->estado, $targetState),
                'reason' => $reason,
                'notes' => $notes,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
            ]);

            $locked->estado = $targetState;
            $locked->save();

            return $locked->fresh(['variante.producto']);
        });
    }

    protected function resolveMovementType(?string $fromState, string $toState): string
    {
        return match ($toState) {
            ProductoUnidad::STATUS_AVAILABLE => $fromState === ProductoUnidad::STATUS_REPAIR
                ? 'reingreso_reparacion'
                : 'ingreso',
            ProductoUnidad::STATUS_RESERVED => 'reserva',
            ProductoUnidad::STATUS_ASSIGNED => 'asignacion',
            ProductoUnidad::STATUS_INSTALLED => 'instalacion',
            ProductoUnidad::STATUS_SOLD => 'venta',
            ProductoUnidad::STATUS_REPAIR => 'envio_reparacion',
            ProductoUnidad::STATUS_RETIRED => 'baja',
            default => 'cambio_estado',
        };
    }
}
