<?php

namespace App\Services;

use App\Enums\Inventory\InventoryLocationType;
use App\Models\Client;
use App\Models\ClientEquipo;
use App\Models\InventoryCategory;
use App\Models\InventoryLocation;
use App\Models\Producto;
use App\Models\ProductoVariante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class InventoryCatalogService
{
    public function findClientByName(?string $name): ?Client
    {
        if (blank($name)) {
            return null;
        }

        $term = trim((string) $name);
        $lower = Str::lower($term);

        return Client::query()
            ->whereRaw('LOWER(name) = ?', [$lower])
            ->orWhereRaw('LOWER(company) = ?', [$lower])
            ->first()
            ?? Client::query()
                ->where('name', 'like', '%' . $term . '%')
                ->orWhere('company', 'like', '%' . $term . '%')
                ->first();
    }

    public function findClientEquipoByReference(?string $reference, ?Client $client = null): ?ClientEquipo
    {
        if (blank($reference)) {
            return null;
        }

        $term = trim((string) $reference);
        $baseQuery = ClientEquipo::query()
            ->when($client, fn (Builder $query) => $query->where('client_id', $client->id));

        if (is_numeric($term)) {
            $record = (clone $baseQuery)->whereKey((int) $term)->first();

            if ($record) {
                return $record;
            }
        }

        return (clone $baseQuery)
            ->where('serie', $term)
            ->orWhere('ubicacion', 'like', '%' . $term . '%')
            ->first();
    }

    public function resolveCategory(array $payload): InventoryCategory
    {
        $slug = Str::slug((string) ($payload['category_slug'] ?? 'componente'));
        $name = $payload['category_name'] ?? Str::of($slug)->replace('-', ' ')->title()->toString();

        return InventoryCategory::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'code' => Str::upper(Str::replace('-', '_', $slug)),
                'is_active' => true,
            ],
        );
    }

    public function resolveLocation(?string $name, ?string $type = null): ?InventoryLocation
    {
        if (blank($name)) {
            return null;
        }

        $normalizedName = trim((string) $name);
        $code = Str::upper((string) Str::of($normalizedName)->ascii()->slug('-'));

        return InventoryLocation::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $normalizedName,
                'type' => $type ?: InventoryLocationType::WAREHOUSE->value,
                'is_active' => true,
            ],
        );
    }

    public function resolveProduct(array $payload): Producto
    {
        return $this->resolveProductForCategory($payload, $this->resolveCategory($payload));
    }

    public function resolveProductForCategory(array $payload, InventoryCategory $category): Producto
    {
        $trackingMode = $payload['tracking_mode'] ?? 'unitized';
        $legacyType = $payload['legacy_type'] ?? $category->slug;

        /** @var Producto|null $product */
        $product = Producto::query()
            ->where('category_id', $category->id)
            ->where('nombre', $payload['product_name'])
            ->first();

        if (! $product) {
            $product = Producto::query()->create([
                'nombre' => $payload['product_name'],
                'tipo' => $legacyType,
                'category_id' => $category->id,
                'sku' => $payload['sku'] ?? null,
                'tracking_mode' => $trackingMode,
                'brand' => $payload['brand'] ?? null,
            ]);

            return $product;
        }

        $product->fill(array_filter([
            'tipo' => $legacyType,
            'sku' => $product->sku ?: ($payload['sku'] ?? null),
            'tracking_mode' => $product->tracking_mode ?: $trackingMode,
            'brand' => $product->brand ?: ($payload['brand'] ?? null),
            'category_id' => $product->category_id ?: $category->id,
        ], fn ($value) => filled($value)));

        if ($product->isDirty()) {
            $product->save();
        }

        return $product;
    }

    public function resolveVariant(Producto $product, array $payload): ProductoVariante
    {
        $match = [
            'producto_id' => $product->id,
            'modelo' => $payload['model'] ?? null,
            'lado' => $payload['side'] ?? null,
            'uso' => $payload['usage'] ?? null,
            'estado' => $payload['variant_state'] ?? null,
            'subtype' => $payload['subtype'] ?? null,
        ];

        $query = ProductoVariante::query()->where('producto_id', $product->id);

        foreach (Arr::except($match, ['producto_id']) as $field => $value) {
            $query->when(
                filled($value),
                fn (Builder $builder) => $builder->where($field, $value),
                fn (Builder $builder) => $builder->whereNull($field),
            );
        }

        /** @var ProductoVariante|null $variant */
        $variant = $query->first();

        $attributes = array_filter([
            'producto_id' => $product->id,
            'codigo_barra' => $payload['variant_barcode'] ?? null,
            'estado' => $payload['variant_state'] ?? null,
            'uso' => $payload['usage'] ?? null,
            'lado' => $payload['side'] ?? null,
            'modelo' => $payload['model'] ?? null,
            'variant_code' => $payload['variant_code'] ?? null,
            'subtype' => $payload['subtype'] ?? null,
            'attributes_json' => $payload['attributes'] ?? null,
        ], fn ($value) => $value !== null);

        if (! $variant) {
            return ProductoVariante::query()->create($attributes);
        }

        $variant->fill(array_filter([
            'codigo_barra' => $variant->codigo_barra ?: ($payload['variant_barcode'] ?? null),
            'variant_code' => $variant->variant_code ?: ($payload['variant_code'] ?? null),
            'subtype' => $variant->subtype ?: ($payload['subtype'] ?? null),
            'attributes_json' => $this->mergeVariantAttributes($variant->attributes_json, $payload['attributes'] ?? []),
        ], fn ($value) => $value !== null));

        if ($variant->isDirty()) {
            $variant->save();
        }

        return $variant;
    }

    protected function mergeVariantAttributes(?array $current, array $incoming): ?array
    {
        $merged = array_filter(array_replace($current ?? [], $incoming), fn ($value) => filled($value));

        return $merged !== [] ? $merged : null;
    }
}
