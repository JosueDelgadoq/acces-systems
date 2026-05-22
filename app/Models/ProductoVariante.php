<?php

namespace App\Models;

use App\Enums\Inventory\InventoryVariantSide;
use App\Enums\Inventory\InventoryVariantUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductoVariante extends Model
{
    protected $fillable = [
        'producto_id',
        'codigo_barra',
        'estado',
        'uso',
        'lado',
        'modelo',
        'variant_code',
        'subtype',
        'attributes_json',
    ];

    protected $casts = [
        'attributes_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (self $model): void {
            if (filled($model->variant_code)) {
                return;
            }

            $model->forceFill([
                'variant_code' => $model->generateVariantCode(),
            ])->saveQuietly();
        });
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function repuestos(): HasMany
    {
        return $this->hasMany(ProductVariantRepuesto::class, 'producto_variante_id');
    }

    public function guiaItems(): HasMany
    {
        return $this->hasMany(GuiaItem::class, 'producto_variante_id');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class, 'producto_variante_id');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(ProductoUnidadMovement::class, 'producto_variante_id');
    }

    public function unidades(): HasMany
    {
        return $this->hasMany(ProductoUnidad::class);
    }

    public function getStockActualAttribute(): int
    {
        return $this->unidades()->where('estado', ProductoUnidad::STATUS_AVAILABLE)->count();
    }

    public function getUnidadesTotalesAttribute(): int
    {
        return $this->unidades()->count();
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->producto?->nombre,
            $this->subtype,
            $this->modelo,
            $this->lado,
            $this->uso,
            $this->estado,
        ]);

        return implode(' | ', $parts);
    }

    public function getInventoryLabelAttribute(): string
    {
        return $this->display_name ?: ($this->variant_code ?: ('Variante #' . $this->id));
    }

    public static function sideOptions(): array
    {
        return InventoryVariantSide::options();
    }

    public static function usageOptions(): array
    {
        return InventoryVariantUsage::options();
    }

    protected function generateVariantCode(): string
    {
        return 'PV-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }
}
