<?php

namespace App\Services;

use App\Enums\Inventory\InventoryImportRowStatus;
use App\Enums\Inventory\InventoryImportStatus;
use App\Enums\Inventory\InventoryTrackingMode;
use App\Imports\InventoryWorkbookImport;
use App\Models\ClientEquipo;
use App\Models\InventoryCategory;
use App\Models\InventoryImportBatch;
use App\Models\InventoryImportRow;
use App\Models\InventoryLocation;
use App\Models\Producto;
use App\Models\ProductoUnidad;
use App\Models\ProductoVariante;
use App\Models\User;
use App\Support\Inventory\InventorySerialNormalizer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class InventoryImportService
{
    private const HEADING_SYNONYMS = [
        'product_name' => ['producto', 'item', 'descripcion', 'description', 'equipo', 'articulo', 'artículo', 'nombre'],
        'brand' => ['marca', 'brand'],
        'model' => ['modelo', 'model'],
        'subtype' => ['subtipo', 'familia', 'clase'],
        'legacy_type' => ['tipo legacy', 'tipo_producto'],
        'variant_code' => ['codigo variante', 'código variante', 'variant code'],
        'variant_barcode' => ['codigo barra variante', 'código barra variante', 'barcode variante'],
        'side' => ['lado', 'mano'],
        'usage' => ['uso', 'aplicacion', 'aplicación'],
        'variant_state' => ['estado producto', 'estado variante', 'nuevo/usado'],
        'unit_status' => ['estado item', 'estado stock', 'situacion', 'situación', 'status'],
        'condition' => ['condicion', 'condición', 'condition'],
        'serial_number' => ['serie', 'serial', 'numero de serie', 'nro serie', 'n° serie', 'serial number'],
        'inventory_code' => ['codigo', 'codigo inventario', 'código inventario', 'barcode', 'codigo interno', 'código interno', 'codigo de barras', 'código de barras'],
        'location' => ['ubicacion', 'ubicación', 'deposito', 'depósito', 'almacen', 'almacén', 'zona', 'rack'],
        'notes' => ['observaciones', 'nota', 'detalle', 'comentarios'],
        'client_name' => ['cliente', 'client'],
        'client_equipo_reference' => ['equipo asociado', 'equipo cliente', 'instalacion', 'instalación', 'serie equipo'],
        'acquired_at' => ['fecha ingreso', 'fecha compra', 'adquirido', 'acquired_at'],
        'installed_at' => ['fecha instalacion', 'fecha instalación', 'installed_at'],
        'category_name' => ['categoria', 'categoría'],
        'sku' => ['sku'],
    ];

    public function __construct(
        protected InventoryCatalogService $catalog,
    ) {
    }

    public function preview(InventoryImportBatch $batch, ?User $actor = null): InventoryImportBatch
    {
        $batch->refresh();

        $workbook = $this->readWorkbook($batch);
        $rows = collect();
        $unsupportedSheets = [];

        foreach ($workbook as $sheetName => $sheetRows) {
            $profile = $this->resolveSheetProfile((string) $sheetName);

            if (! $profile['supported']) {
                $unsupportedSheets[] = $sheetName;
                continue;
            }

            foreach ($this->extractSheetRows((string) $sheetName, $sheetRows) as $row) {
                $normalized = $this->normalizePreviewPayload($profile, $row['payload']);
                $rows->push([
                    'source_sheet' => (string) $sheetName,
                    'source_row_number' => $row['row_number'],
                    'row_payload' => $row['payload'],
                    'row_hash' => InventorySerialNormalizer::rowHash($row['payload']),
                    'normalized_payload' => $normalized['payload'],
                    'validation_errors' => $normalized['errors'],
                    'conflict_details' => $normalized['conflicts'],
                    'status' => $this->resolvePreviewStatus($normalized['errors'], $normalized['conflicts']),
                ]);
            }
        }

        $rows = $this->applyDuplicateDetection($rows);
        $rows = $this->applyExistingInventoryConflicts($rows);

        DB::transaction(function () use ($batch, $actor, $rows, $unsupportedSheets): void {
            $lockedBatch = InventoryImportBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $lockedBatch->rows()->delete();

            foreach ($rows as $row) {
                $lockedBatch->rows()->create($row);
            }

            $summary = $this->buildSummary($lockedBatch->rows()->get(), [
                'unsupported_sheets' => $unsupportedSheets,
                'previewed_by_user_id' => $actor?->id,
            ]);

            $lockedBatch->forceFill([
                'status' => $summary['error_rows'] > 0 || $summary['conflict_rows'] > 0
                    ? InventoryImportStatus::PREVIEWED_WITH_ERRORS->value
                    : InventoryImportStatus::PREVIEWED->value,
                'summary' => $summary,
                'previewed_at' => now(),
                'failed_at' => null,
            ])->save();
        });

        return $batch->fresh(['rows', 'uploader']);
    }

    public function import(InventoryImportBatch $batch, ?User $actor = null): InventoryImportBatch
    {
        if (! $batch->rows()->exists()) {
            $batch = $this->preview($batch, $actor);
        }

        DB::transaction(function () use ($batch, $actor): void {
            $lockedBatch = InventoryImportBatch::query()
                ->with('rows')
                ->lockForUpdate()
                ->findOrFail($batch->id);

            foreach ($lockedBatch->rows as $row) {
                if (in_array($row->status, [
                    InventoryImportRowStatus::ERROR->value,
                    InventoryImportRowStatus::CONFLICT->value,
                ], true)) {
                    continue;
                }

                try {
                    $result = $this->importRow($lockedBatch, $row, $actor);

                    $row->forceFill([
                        'status' => InventoryImportRowStatus::IMPORTED->value,
                        'producto_unidad_id' => $result['unit_id'],
                        'resolution' => $result,
                        'validation_errors' => [],
                        'conflict_details' => [],
                    ])->save();
                } catch (Throwable $exception) {
                    $row->forceFill([
                        'status' => InventoryImportRowStatus::ERROR->value,
                        'validation_errors' => [$exception->getMessage()],
                    ])->save();
                }
            }

            $summary = $this->buildSummary($lockedBatch->rows()->get(), [
                'unsupported_sheets' => $lockedBatch->summary['unsupported_sheets'] ?? [],
                'imported_by_user_id' => $actor?->id,
            ]);

            $lockedBatch->forceFill([
                'status' => InventoryImportStatus::IMPORTED->value,
                'summary' => $summary,
                'imported_at' => now(),
                'failed_at' => null,
            ])->save();
        });

        return $batch->fresh(['rows', 'units']);
    }

    public function rollback(InventoryImportBatch $batch, ?User $actor = null): InventoryImportBatch
    {
        DB::transaction(function () use ($batch, $actor): void {
            $lockedBatch = InventoryImportBatch::query()
                ->with('rows.unit')
                ->lockForUpdate()
                ->findOrFail($batch->id);
            $createdCategoryIds = collect();
            $createdLocationIds = collect();
            $createdVariantIds = collect();
            $createdProductIds = collect();

            foreach ($lockedBatch->rows as $row) {
                if ($row->status !== InventoryImportRowStatus::IMPORTED->value) {
                    continue;
                }

                $resolution = $row->resolution ?? [];
                $unit = $row->unit;
                $createdCategoryIds = $this->rememberCreatedStructureIds($createdCategoryIds, $resolution, 'category');
                $createdLocationIds = $this->rememberCreatedStructureIds($createdLocationIds, $resolution, 'location');
                $createdVariantIds = $this->rememberCreatedStructureIds($createdVariantIds, $resolution, 'variant');
                $createdProductIds = $this->rememberCreatedStructureIds($createdProductIds, $resolution, 'product');

                if (($resolution['action'] ?? null) === 'created' && $unit) {
                    $unit->delete();
                }

                if (($resolution['action'] ?? null) === 'updated' && $unit && isset($resolution['previous'])) {
                    $unit->setMovementContext([
                        'user_id' => $actor?->id,
                        'movement_type' => 'rollback_importacion_excel',
                        'reason' => 'rollback_importacion_excel',
                        'notes' => 'Rollback de importación de inventario.',
                    ]);
                    $unit->fill($resolution['previous']);
                    $unit->save();
                }

                if (($resolution['client_equipo_id'] ?? null) && isset($resolution['previous_client_equipo_unit_id'])) {
                    ClientEquipo::query()
                        ->whereKey($resolution['client_equipo_id'])
                        ->update(['producto_unidad_id' => $resolution['previous_client_equipo_unit_id']]);
                }

                $row->forceFill([
                    'status' => InventoryImportRowStatus::ROLLED_BACK->value,
                ])->save();
            }

            $this->cleanupCreatedVariants($createdVariantIds);
            $this->cleanupCreatedProducts($createdProductIds);
            $this->cleanupCreatedLocations($createdLocationIds);
            $this->cleanupCreatedCategories($createdCategoryIds);

            $summary = $this->buildSummary($lockedBatch->rows()->get(), [
                'unsupported_sheets' => $lockedBatch->summary['unsupported_sheets'] ?? [],
                'rolled_back_by_user_id' => $actor?->id,
            ]);

            $lockedBatch->forceFill([
                'status' => InventoryImportStatus::ROLLED_BACK->value,
                'summary' => $summary,
                'rolled_back_at' => now(),
            ])->save();
        });

        return $batch->fresh(['rows']);
    }

    protected function importRow(InventoryImportBatch $batch, InventoryImportRow $row, ?User $actor = null): array
    {
        $payload = $row->normalized_payload ?? [];

        $category = $this->catalog->resolveCategory($payload);
        $categoryWasCreated = $category->wasRecentlyCreated;
        $product = $this->catalog->resolveProductForCategory($payload, $category);
        $productWasCreated = $product->wasRecentlyCreated;
        $variant = $this->catalog->resolveVariant($product, $payload);
        $variantWasCreated = $variant->wasRecentlyCreated;
        $location = $this->catalog->resolveLocation($payload['location_name'] ?? null);
        $locationWasCreated = $location?->wasRecentlyCreated ?? false;
        $client = $this->catalog->findClientByName($payload['client_name'] ?? null);
        $clientEquipo = $this->catalog->findClientEquipoByReference($payload['client_equipo_reference'] ?? null, $client);

        $existing = $this->findExistingUnit($payload);

        if ($existing && $existing->producto_variante_id !== $variant->id) {
            throw new \RuntimeException('La serie o código ya existe asociado a otra variante.');
        }

        $status = $payload['unit_status']
            ?? ($payload['installed_at'] || $client || $clientEquipo ? ProductoUnidad::STATUS_INSTALLED : ProductoUnidad::STATUS_AVAILABLE);

        $unitData = [
            'producto_variante_id' => $variant->id,
            'inventory_code' => $payload['inventory_code'] ?? null,
            'codigo_barra' => $payload['inventory_code'] ?? null,
            'serial_number' => $payload['serial_number'] ?? null,
            'condition' => $payload['condition'] ?? null,
            'estado' => $status,
            'location_id' => $location?->id,
            'notes' => $payload['notes'] ?? null,
            'client_id' => $client?->id,
            'client_equipo_id' => $clientEquipo?->id,
            'installed_at' => $payload['installed_at'] ?? null,
            'acquired_at' => $payload['acquired_at'] ?? null,
            'source_import_batch_id' => $batch->id,
            'source_sheet' => $row->source_sheet,
            'source_row_number' => $row->source_row_number,
            'source_row_hash' => $row->row_hash,
        ];

        $previousClientEquipoUnitId = $clientEquipo?->producto_unidad_id;

        if ($existing) {
            $previous = Arr::only($existing->toArray(), array_keys($unitData));
            $existing->setMovementContext([
                'user_id' => $actor?->id,
                'movement_type' => 'importacion_excel_actualizacion',
                'reason' => 'importacion_excel',
                'notes' => 'Actualización de unidad desde importación Excel.',
            ]);
            $existing->fill(array_filter($unitData, fn ($value) => $value !== null));
            $existing->save();
            $unit = $existing;
            $action = 'updated';
        } else {
            $unit = new ProductoUnidad($unitData);
            $unit->setMovementContext([
                'user_id' => $actor?->id,
                'movement_type' => 'importacion_excel_alta',
                'reason' => 'importacion_excel',
                'notes' => 'Alta de unidad desde importación Excel.',
            ]);
            $unit->save();
            $action = 'created';
            $previous = null;
        }

        if ($clientEquipo) {
            $clientEquipo->forceFill([
                'producto_unidad_id' => $unit->id,
                'serie' => $clientEquipo->serie ?: ($payload['serial_number'] ?? $clientEquipo->serie),
                'fecha_instalacion' => $payload['installed_at'] ?? $clientEquipo->fecha_instalacion,
            ])->save();
        }

        return [
            'action' => $action,
            'unit_id' => $unit->id,
            'category_id' => $category->id,
            'category_was_created' => $categoryWasCreated,
            'location_id' => $location?->id,
            'location_was_created' => $locationWasCreated,
            'variant_id' => $variant->id,
            'variant_was_created' => $variantWasCreated,
            'product_id' => $product->id,
            'product_was_created' => $productWasCreated,
            'client_equipo_id' => $clientEquipo?->id,
            'previous_client_equipo_unit_id' => $previousClientEquipoUnitId,
            'previous' => $previous,
        ];
    }

    protected function rememberCreatedStructureIds(Collection $ids, array $resolution, string $type): Collection
    {
        $id = $resolution[$type . '_id'] ?? null;
        $wasCreated = (bool) ($resolution[$type . '_was_created'] ?? false);

        if (! $wasCreated || ! filled($id)) {
            return $ids;
        }

        return $ids->push((int) $id);
    }

    protected function cleanupCreatedVariants(Collection $variantIds): void
    {
        foreach ($variantIds->unique()->values() as $variantId) {
            /** @var ProductoVariante|null $variant */
            $variant = ProductoVariante::query()
                ->withCount(['unidades', 'guiaItems', 'repuestos'])
                ->find($variantId);

            if (! $variant) {
                continue;
            }

            if ($variant->unidades_count > 0 || $variant->guia_items_count > 0 || $variant->repuestos_count > 0) {
                continue;
            }

            $variant->delete();
        }
    }

    protected function cleanupCreatedProducts(Collection $productIds): void
    {
        foreach ($productIds->unique()->values() as $productId) {
            /** @var Producto|null $product */
            $product = Producto::query()
                ->withCount('variantes')
                ->find($productId);

            if (! $product) {
                continue;
            }

            if ($product->variantes_count > 0) {
                continue;
            }

            $product->delete();
        }
    }

    protected function cleanupCreatedLocations(Collection $locationIds): void
    {
        foreach ($locationIds->unique()->values() as $locationId) {
            /** @var InventoryLocation|null $location */
            $location = InventoryLocation::query()
                ->withCount(['children', 'units', 'movementsFrom', 'movementsTo'])
                ->find($locationId);

            if (! $location) {
                continue;
            }

            if (
                $location->children_count > 0
                || $location->units_count > 0
                || $location->movements_from_count > 0
                || $location->movements_to_count > 0
            ) {
                continue;
            }

            $location->delete();
        }
    }

    protected function cleanupCreatedCategories(Collection $categoryIds): void
    {
        foreach ($categoryIds->unique()->values() as $categoryId) {
            /** @var InventoryCategory|null $category */
            $category = InventoryCategory::query()
                ->withCount('products')
                ->find($categoryId);

            if (! $category) {
                continue;
            }

            if ($category->products_count > 0) {
                continue;
            }

            $category->delete();
        }
    }

    protected function readWorkbook(InventoryImportBatch $batch): array
    {
        if (! Storage::disk($batch->file_disk)->exists($batch->file_path)) {
            throw new \RuntimeException('El archivo de importación no existe en el disco configurado.');
        }

        $path = Storage::disk($batch->file_disk)->path($batch->file_path);

        $sheets = Excel::toArray(
            new InventoryWorkbookImport(),
            $path,
        );

        $spreadsheet = IOFactory::load($path);
        $namedSheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $index => $worksheet) {
            $namedSheets[$worksheet->getTitle()] = $sheets[$index] ?? [];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $namedSheets;
    }

    protected function extractSheetRows(string $sheetName, array $rows): array
    {
        $headerIndex = $this->detectHeaderRowIndex($rows);
        $headers = $headerIndex !== null ? $this->normalizeHeaders($rows[$headerIndex]) : [];
        $dataStart = $headerIndex !== null ? $headerIndex + 1 : 0;
        $compiled = [];

        foreach ($rows as $index => $values) {
            if ($index < $dataStart) {
                continue;
            }

            $payload = $this->mapRowPayload($headers, $values);

            if ($this->rowIsEmpty($payload)) {
                continue;
            }

            $compiled[] = [
                'sheet' => $sheetName,
                'row_number' => $index + 1,
                'payload' => $payload,
            ];
        }

        return $compiled;
    }

    protected function detectHeaderRowIndex(array $rows): ?int
    {
        $limit = min(10, count($rows));

        for ($index = 0; $index < $limit; $index++) {
            $recognized = 0;

            foreach ($rows[$index] as $value) {
                if ($this->canonicalHeaderKey($value)) {
                    $recognized++;
                }
            }

            if ($recognized >= 2) {
                return $index;
            }
        }

        return null;
    }

    protected function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header, $index) {
            return $this->canonicalHeaderKey($header) ?: 'column_' . ($index + 1);
        }, $headers, array_keys($headers));
    }

    protected function mapRowPayload(array $headers, array $values): array
    {
        if ($headers === []) {
            return collect($values)
                ->mapWithKeys(fn ($value, $index): array => ['column_' . ($index + 1) => $value])
                ->all();
        }

        $payload = [];

        foreach ($headers as $index => $header) {
            $payload[$header] = $values[$index] ?? null;
        }

        return $payload;
    }

    protected function normalizePreviewPayload(array $profile, array $payload): array
    {
        $usedKeys = [];
        $productName = $this->takeFirst($payload, ['product_name'], $usedKeys);
        $brand = $this->takeFirst($payload, ['brand'], $usedKeys) ?: ($profile['default_brand'] ?? null);
        $model = $this->takeFirst($payload, ['model'], $usedKeys);
        $subtype = $this->normalizeSubtype(
            $this->takeFirst($payload, ['subtype'], $usedKeys),
            $profile,
            $productName,
        );
        $detectedCategory = $this->resolveCategoryProfile($profile, $subtype, $productName);
        $legacyType = $this->takeFirst($payload, ['legacy_type'], $usedKeys) ?: $detectedCategory['legacy_type'];
        $categoryName = $this->takeFirst($payload, ['category_name'], $usedKeys) ?: $detectedCategory['category_name'];
        $variantCode = $this->takeFirst($payload, ['variant_code'], $usedKeys);
        $variantBarcode = $this->takeFirst($payload, ['variant_barcode'], $usedKeys);
        $side = $this->normalizeSide($this->takeFirst($payload, ['side'], $usedKeys));
        $usage = $this->normalizeUsage($this->takeFirst($payload, ['usage'], $usedKeys));
        $variantState = $this->normalizeVariantState($this->takeFirst($payload, ['variant_state'], $usedKeys));
        $condition = $this->normalizeCondition($this->takeFirst($payload, ['condition'], $usedKeys) ?: $variantState);
        $unitStatus = $this->normalizeUnitStatus($this->takeFirst($payload, ['unit_status'], $usedKeys));
        $serialNumber = $this->normalizeScalar($this->takeFirst($payload, ['serial_number'], $usedKeys));
        $inventoryCode = $this->normalizeScalar($this->takeFirst($payload, ['inventory_code'], $usedKeys));
        $locationName = $this->normalizeScalar($this->takeFirst($payload, ['location'], $usedKeys));
        $notes = $this->normalizeScalar($this->takeFirst($payload, ['notes'], $usedKeys));
        $clientName = $this->normalizeScalar($this->takeFirst($payload, ['client_name'], $usedKeys));
        $clientEquipoReference = $this->normalizeScalar($this->takeFirst($payload, ['client_equipo_reference'], $usedKeys));
        $acquiredAt = $this->normalizeDate($this->takeFirst($payload, ['acquired_at'], $usedKeys));
        $installedAt = $this->normalizeDate($this->takeFirst($payload, ['installed_at'], $usedKeys));
        $sku = $this->normalizeScalar($this->takeFirst($payload, ['sku'], $usedKeys));

        if (blank($productName)) {
            $productName = $this->deriveProductName($profile, $payload, $subtype);
        }

        $normalized = [
            'category_slug' => $detectedCategory['category_slug'],
            'category_name' => $categoryName ?: $detectedCategory['category_name'],
            'product_name' => $productName,
            'legacy_type' => $legacyType,
            'tracking_mode' => filled($serialNumber)
                ? InventoryTrackingMode::SERIALIZED->value
                : InventoryTrackingMode::UNITIZED->value,
            'brand' => $brand,
            'sku' => $sku,
            'model' => $model,
            'subtype' => $subtype,
            'variant_code' => $variantCode,
            'variant_barcode' => $variantBarcode,
            'side' => $side,
            'usage' => $usage,
            'variant_state' => $variantState,
            'unit_status' => $unitStatus,
            'condition' => $condition,
            'serial_number' => $serialNumber,
            'serial_number_normalized' => InventorySerialNormalizer::normalize($serialNumber),
            'inventory_code' => $inventoryCode,
            'location_name' => $locationName,
            'notes' => $notes,
            'client_name' => $clientName,
            'client_equipo_reference' => $clientEquipoReference,
            'acquired_at' => $acquiredAt?->toDateTimeString(),
            'installed_at' => $installedAt?->toDateTimeString(),
            'attributes' => $this->remainingAttributes($payload, $usedKeys),
        ];

        $errors = $this->validatePreviewPayload($normalized);
        $conflicts = [];

        if ($clientName && ! $this->catalog->findClientByName($clientName)) {
            $errors[] = 'No se pudo resolver el cliente indicado.';
        }

        if ($clientEquipoReference && ! $this->catalog->findClientEquipoByReference($clientEquipoReference, $this->catalog->findClientByName($clientName))) {
            $errors[] = 'No se pudo resolver el equipo/instalación asociado.';
        }

        return [
            'payload' => $normalized,
            'errors' => array_values(array_unique($errors)),
            'conflicts' => $conflicts,
        ];
    }

    protected function validatePreviewPayload(array $payload): array
    {
        $errors = [];

        if (blank($payload['product_name'] ?? null)) {
            $errors[] = 'No se pudo determinar el producto.';
        }

        if (blank($payload['category_slug'] ?? null)) {
            $errors[] = 'No se pudo determinar la categoría.';
        }

        if (filled($payload['side'] ?? null) && ! array_key_exists($payload['side'], \App\Enums\Inventory\InventoryVariantSide::options())) {
            $errors[] = 'El lado indicado no es válido.';
        }

        if (filled($payload['usage'] ?? null) && ! array_key_exists($payload['usage'], \App\Enums\Inventory\InventoryVariantUsage::options())) {
            $errors[] = 'El uso indicado no es válido.';
        }

        if (filled($payload['condition'] ?? null) && ! array_key_exists($payload['condition'], ProductoUnidad::CONDITION_OPTIONS)) {
            $errors[] = 'La condición indicada no es válida.';
        }

        if (filled($payload['unit_status'] ?? null) && ! array_key_exists($payload['unit_status'], ProductoUnidad::STATUS_OPTIONS)) {
            $errors[] = 'El estado del ítem no es válido.';
        }

        if (filled($payload['variant_state'] ?? null) && ! in_array($payload['variant_state'], ['nuevo', 'usado'], true)) {
            $errors[] = 'El estado de la variante debe ser nuevo o usado.';
        }

        return $errors;
    }

    protected function applyDuplicateDetection(Collection $rows): Collection
    {
        foreach (['serial_number_normalized', 'inventory_code'] as $field) {
            $duplicates = $rows
                ->filter(fn (array $row): bool => filled(data_get($row, "normalized_payload.{$field}")))
                ->groupBy(fn (array $row) => data_get($row, "normalized_payload.{$field}"))
                ->filter(fn (Collection $group): bool => $group->count() > 1);

            foreach ($duplicates as $duplicateRows) {
                foreach ($duplicateRows as $index => $row) {
                    $current = $rows->get($index);
                    $current['conflict_details'] = array_values(array_filter([
                        ...($current['conflict_details'] ?? []),
                        'Duplicado dentro del mismo archivo por ' . $field . '.',
                    ]));
                    $current['status'] = InventoryImportRowStatus::CONFLICT->value;
                    $rows->put($index, $current);
                }
            }
        }

        return $rows;
    }

    protected function applyExistingInventoryConflicts(Collection $rows): Collection
    {
        foreach ($rows as $index => $row) {
            $payload = $row['normalized_payload'] ?? [];
            $existing = $this->findExistingUnit($payload);

            if (! $existing) {
                continue;
            }

            $existing->loadMissing('variante.producto');
            $existingName = Str::lower((string) $existing->variante?->producto?->nombre);
            $incomingName = Str::lower((string) ($payload['product_name'] ?? ''));

            if ($existingName !== '' && $incomingName !== '' && $existingName !== $incomingName) {
                $current = $rows->get($index);
                $current['conflict_details'] = array_values(array_filter([
                    ...($current['conflict_details'] ?? []),
                    'Ya existe una unidad con la misma serie o código interno en otro producto.',
                ]));
                $current['status'] = InventoryImportRowStatus::CONFLICT->value;
                $rows->put($index, $current);
                continue;
            }

            $current = $rows->get($index);
            $current['normalized_payload']['matched_unit_id'] = $existing->id;
            $rows->put($index, $current);
        }

        return $rows;
    }

    protected function buildSummary(EloquentCollection $rows, array $extra = []): array
    {
        return array_merge([
            'total_rows' => $rows->count(),
            'preview_rows' => $rows->where('status', InventoryImportRowStatus::PREVIEW->value)->count(),
            'imported_rows' => $rows->where('status', InventoryImportRowStatus::IMPORTED->value)->count(),
            'error_rows' => $rows->where('status', InventoryImportRowStatus::ERROR->value)->count(),
            'conflict_rows' => $rows->where('status', InventoryImportRowStatus::CONFLICT->value)->count(),
            'rolled_back_rows' => $rows->where('status', InventoryImportRowStatus::ROLLED_BACK->value)->count(),
            'sheets' => $rows
                ->groupBy('source_sheet')
                ->map(fn (Collection $sheetRows): array => [
                    'total_rows' => $sheetRows->count(),
                    'preview_rows' => $sheetRows->where('status', InventoryImportRowStatus::PREVIEW->value)->count(),
                    'imported_rows' => $sheetRows->where('status', InventoryImportRowStatus::IMPORTED->value)->count(),
                    'error_rows' => $sheetRows->where('status', InventoryImportRowStatus::ERROR->value)->count(),
                    'conflict_rows' => $sheetRows->where('status', InventoryImportRowStatus::CONFLICT->value)->count(),
                ])
                ->all(),
        ], $extra);
    }

    protected function findExistingUnit(array $payload): ?ProductoUnidad
    {
        $query = ProductoUnidad::query();
        $serial = $payload['serial_number_normalized'] ?? null;
        $inventoryCode = $payload['inventory_code'] ?? null;

        if (filled($serial)) {
            return $query->where('serial_number_normalized', $serial)->first();
        }

        if (filled($inventoryCode)) {
            return $query
                ->where('inventory_code', $inventoryCode)
                ->orWhere('codigo_barra', $inventoryCode)
                ->first();
        }

        return null;
    }

    protected function resolvePreviewStatus(array $errors, array $conflicts): string
    {
        if ($errors !== []) {
            return InventoryImportRowStatus::ERROR->value;
        }

        if ($conflicts !== []) {
            return InventoryImportRowStatus::CONFLICT->value;
        }

        return InventoryImportRowStatus::PREVIEW->value;
    }

    protected function resolveSheetProfile(string $sheetName): array
    {
        $normalized = Str::of($sheetName)->ascii()->lower()->squish()->value();

        return match (true) {
            Str::contains($normalized, 'oruga') => [
                'supported' => true,
                'category_slug' => 'oruga',
                'category_name' => 'Orugas',
                'legacy_type' => 'oruga',
                'default_product_name' => 'Oruga',
                'default_brand' => null,
            ],
            Str::contains($normalized, 'plataforma') || Str::contains($normalized, 'aleron') => [
                'supported' => true,
                'category_slug' => 'plataforma',
                'category_name' => 'Plataformas',
                'legacy_type' => 'plataforma',
                'default_product_name' => 'Plataforma',
                'default_brand' => null,
            ],
            Str::contains($normalized, 'motor') && Str::contains($normalized, 'suelto') => [
                'supported' => true,
                'category_slug' => 'motor',
                'category_name' => 'Motores',
                'legacy_type' => 'motor',
                'default_product_name' => 'Motor',
                'default_brand' => null,
            ],
            Str::contains($normalized, 'sillas y motores k2') || Str::contains($normalized, 'motores k2') || Str::contains($normalized, 'k2') => [
                'supported' => true,
                'category_slug' => 'silla',
                'category_name' => 'Sillas K2',
                'legacy_type' => 'silla',
                'default_product_name' => 'Silla K2',
                'default_brand' => 'K2',
            ],
            default => [
                'supported' => false,
                'category_slug' => null,
            ],
        };
    }

    protected function canonicalHeaderKey(mixed $value): ?string
    {
        if (! is_scalar($value) || trim((string) $value) === '') {
            return null;
        }

        $normalized = Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();

        foreach (self::HEADING_SYNONYMS as $canonical => $variants) {
            if (in_array($normalized, $variants, true)) {
                return $canonical;
            }
        }

        return null;
    }

    protected function takeFirst(array $payload, array $keys, array &$usedKeys): mixed
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $payload)) {
                continue;
            }

            $usedKeys[] = $key;

            return $payload[$key];
        }

        return null;
    }

    protected function remainingAttributes(array $payload, array $usedKeys): array
    {
        return collect($payload)
            ->except($usedKeys)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => is_scalar($value) ? trim((string) $value) : $value)
            ->all();
    }

    protected function rowIsEmpty(array $payload): bool
    {
        return collect($payload)
            ->filter(fn ($value) => filled($value))
            ->isEmpty();
    }

    protected function normalizeSubtype(?string $value, array $profile, ?string $productName): ?string
    {
        $candidate = Str::lower((string) ($value ?: ''));
        $productText = Str::lower((string) $productName);

        if (Str::contains($candidate . ' ' . $productText, 'aleron')) {
            return 'aleron';
        }

        if (Str::contains($candidate . ' ' . $productText, 'oruga')) {
            return 'oruga';
        }

        if (Str::contains($candidate . ' ' . $productText, 'motor')) {
            return Str::contains($productText, 'k2') ? 'motor_k2' : 'motor_suelto';
        }

        if (Str::contains($candidate . ' ' . $productText, 'silla')) {
            return 'silla';
        }

        if (($profile['category_slug'] ?? null) === 'plataforma') {
            return 'plataforma';
        }

        return filled($candidate) ? Str::slug($candidate, '_') : null;
    }

    protected function resolveCategoryProfile(array $profile, ?string $subtype, ?string $productName): array
    {
        $productText = Str::lower((string) $productName);

        return match (true) {
            $subtype === 'aleron' => [
                'category_slug' => 'aleron',
                'category_name' => 'Alerones',
                'legacy_type' => 'aleron',
            ],
            $subtype === 'oruga' => [
                'category_slug' => 'oruga',
                'category_name' => 'Orugas',
                'legacy_type' => 'oruga',
            ],
            $subtype === 'motor_k2', $subtype === 'motor_suelto', Str::contains($productText, 'motor') => [
                'category_slug' => 'motor',
                'category_name' => 'Motores',
                'legacy_type' => 'motor',
            ],
            $subtype === 'silla', Str::contains($productText, 'silla') => [
                'category_slug' => 'silla',
                'category_name' => 'Sillas',
                'legacy_type' => 'silla',
            ],
            default => [
                'category_slug' => $profile['category_slug'],
                'category_name' => $profile['category_name'] ?? Str::title((string) $profile['category_slug']),
                'legacy_type' => $profile['legacy_type'] ?? $profile['category_slug'],
            ],
        };
    }

    protected function deriveProductName(array $profile, array $payload, ?string $subtype): string
    {
        $firstValue = collect($payload)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => is_scalar($value) ? trim((string) $value) : null)
            ->filter()
            ->first();

        if (($profile['default_brand'] ?? null) === 'K2' && Str::contains(Str::lower((string) $firstValue), 'motor')) {
            return 'Motor K2';
        }

        if (($profile['default_brand'] ?? null) === 'K2') {
            return 'Silla K2';
        }

        if (filled($firstValue) && ! is_numeric($firstValue)) {
            return $firstValue;
        }

        return match ($subtype) {
            'aleron' => 'Alerón',
            'oruga' => 'Oruga',
            'motor_k2' => 'Motor K2',
            'motor_suelto' => 'Motor',
            default => (string) ($profile['default_product_name'] ?? 'Componente'),
        };
    }

    protected function normalizeSide(mixed $value): ?string
    {
        $normalized = Str::lower((string) $value);

        return match (true) {
            blank($value) => null,
            Str::contains($normalized, 'izq') || Str::contains($normalized, 'left') => 'izquierdo',
            Str::contains($normalized, 'der') || Str::contains($normalized, 'right') => 'derecho',
            Str::contains($normalized, 'ambi') || Str::contains($normalized, 'bilat') => 'bilateral',
            Str::contains($normalized, 'cent') => 'central',
            default => null,
        };
    }

    protected function normalizeUsage(mixed $value): ?string
    {
        $normalized = Str::lower((string) $value);

        return match (true) {
            blank($value) => null,
            Str::contains($normalized, 'inter') => 'interior',
            Str::contains($normalized, 'exter') => 'exterior',
            Str::contains($normalized, 'mixt') => 'mixto',
            default => null,
        };
    }

    protected function normalizeVariantState(mixed $value): ?string
    {
        $normalized = Str::lower((string) $value);

        return match (true) {
            blank($value) => null,
            Str::contains($normalized, 'nuev') => 'nuevo',
            Str::contains($normalized, 'usad') => 'usado',
            default => null,
        };
    }

    protected function normalizeCondition(mixed $value): ?string
    {
        $normalized = Str::lower((string) $value);

        return match (true) {
            blank($value) => null,
            Str::contains($normalized, 'nuev') => 'nuevo',
            Str::contains($normalized, 'reac') => 'reacondicionado',
            Str::contains($normalized, 'repu') => 'repuestos',
            Str::contains($normalized, 'dan') || Str::contains($normalized, 'dañ') => 'danado',
            Str::contains($normalized, 'usad') => 'usado',
            default => null,
        };
    }

    protected function normalizeUnitStatus(mixed $value): ?string
    {
        $normalized = Str::lower((string) $value);

        return match (true) {
            blank($value) => null,
            Str::contains($normalized, 'dispon') => ProductoUnidad::STATUS_AVAILABLE,
            Str::contains($normalized, 'reserv') => ProductoUnidad::STATUS_RESERVED,
            Str::contains($normalized, 'asign') => ProductoUnidad::STATUS_ASSIGNED,
            Str::contains($normalized, 'instal') => ProductoUnidad::STATUS_INSTALLED,
            Str::contains($normalized, 'vend') => ProductoUnidad::STATUS_SOLD,
            Str::contains($normalized, 'repar') => ProductoUnidad::STATUS_REPAIR,
            Str::contains($normalized, 'baja') || Str::contains($normalized, 'retir') => ProductoUnidad::STATUS_RETIRED,
            default => null,
        };
    }

    protected function normalizeScalar(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return trim((string) $value);
    }

    protected function normalizeDate(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }
}
