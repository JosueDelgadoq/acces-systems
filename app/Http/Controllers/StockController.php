<?php

namespace App\Http\Controllers;

use App\Models\ProductoUnidad;
use App\Models\ProductoVariante;
use App\Services\ProductoUnidadInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockController extends Controller
{
    public function __construct(
        protected ProductoUnidadInventoryService $inventory,
    ) {
    }

    protected function authorizeInventoryUpdate(Request $request): void
    {
        abort_unless($request->user()?->can('inventario.update'), 403);
    }

    public function scan(Request $request): JsonResponse
    {
        $this->authorizeInventoryUpdate($request);

        $data = $request->validate([
            'codigo_barra' => ['required', 'string', 'max:255'],
        ]);

        $unit = $this->inventory->findUnitByBarcode($data['codigo_barra']);

        if ($unit) {
            return response()->json($this->inventory->buildScanPayload($unit));
        }

        $variant = ProductoVariante::query()
            ->with('producto')
            ->where('codigo_barra', $data['codigo_barra'])
            ->orWhere('variant_code', $data['codigo_barra'])
            ->first();

        if ($variant) {
            return response()->json([
                'id' => $variant->id,
                'scan_type' => 'variant',
                'producto_variante_id' => $variant->id,
                'variant_code' => $variant->variant_code,
                'producto' => $variant->producto?->nombre,
                'tipo' => $variant->producto?->tipo,
                'subtype' => $variant->subtype,
                'estado' => $variant->estado,
                'uso' => $variant->uso,
                'lado' => $variant->lado,
                'modelo' => $variant->modelo,
                'stock' => $variant->stock_actual,
                'stock_disponible' => $variant->stock_actual,
                'total_unidades' => $variant->unidades_totales,
                'message' => 'El codigo pertenece a una variante. Para movimientos operativos escanea una unidad serializada.',
            ]);
        }

        return response()->json([
            'error' => 'barcode_not_found',
            'message' => 'El codigo de barras no esta registrado en inventario.',
        ], 404);
    }

    public function movimiento(Request $request): JsonResponse
    {
        $this->authorizeInventoryUpdate($request);

        $data = $request->validate([
            'producto_unidad_id' => ['nullable', 'integer', 'exists:producto_unidades,id'],
            'producto_variante_id' => ['nullable', 'integer', 'exists:producto_variantes,id'],
            'tipo' => ['nullable', Rule::in(['entrada', 'salida'])],
            'cantidad' => ['nullable', 'integer', 'min:1'],
            'estado_destino' => ['nullable', Rule::in(array_keys(ProductoUnidad::STATUS_OPTIONS))],
            'motivo' => ['nullable', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        if (filled($data['producto_unidad_id'] ?? null)) {
            $unit = ProductoUnidad::query()->findOrFail($data['producto_unidad_id']);

            $targetState = $data['estado_destino'] ?? $this->mapLegacyTypeToState($data['tipo'] ?? null);

            if (! $targetState) {
                return response()->json([
                    'error' => 'missing_target_state',
                    'message' => 'Debes indicar el estado destino de la unidad.',
                ], 422);
            }

            $updated = $this->inventory->changeState(
                unit: $unit,
                targetState: $targetState,
                actor: $request->user(),
                reason: $data['motivo'] ?? null,
                notes: $data['observaciones'] ?? null,
            );

            return response()->json([
                'ok' => true,
                'message' => 'Estado de unidad actualizado.',
                'unit' => $this->inventory->buildScanPayload($updated),
            ]);
        }

        if (filled($data['producto_variante_id'] ?? null)) {
            if (($data['tipo'] ?? null) !== 'entrada') {
                return response()->json([
                    'error' => 'serial_units_required',
                    'message' => 'Las salidas ya no se registran por cantidad. Escanea la unidad y cambia su estado.',
                ], 422);
            }

            $quantity = (int) ($data['cantidad'] ?? 0);

            if ($quantity < 1) {
                return response()->json([
                    'error' => 'invalid_quantity',
                    'message' => 'Debes indicar una cantidad valida para generar unidades.',
                ], 422);
            }

            $variant = ProductoVariante::query()->findOrFail($data['producto_variante_id']);

            $created = $this->inventory->createUnits(
                variant: $variant,
                quantity: $quantity,
                actor: $request->user(),
                reason: $data['motivo'] ?? 'ingreso_stock',
                notes: $data['observaciones'] ?? null,
            );

            $variant->refresh();

            return response()->json([
                'ok' => true,
                'message' => 'Se generaron unidades serializadas para la variante.',
                'created_units' => $created->count(),
                'stock' => $variant->stock_actual,
                'total_unidades' => $variant->unidades_totales,
            ]);
        }

        return response()->json([
            'error' => 'missing_inventory_target',
            'message' => 'Debes indicar una unidad o una variante para registrar el movimiento.',
        ], 422);
    }

    protected function mapLegacyTypeToState(?string $type): ?string
    {
        return match ($type) {
            'entrada' => ProductoUnidad::STATUS_AVAILABLE,
            'salida' => ProductoUnidad::STATUS_INSTALLED,
            default => null,
        };
    }
}
