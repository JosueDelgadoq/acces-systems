<?php

namespace App\Models;

use App\Enums\Inventory\InventoryTrackingMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'tipo',
        'category_id',
        'sku',
        'tracking_mode',
        'brand',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->tracking_mode ??= InventoryTrackingMode::UNITIZED->value;
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    public function variantes(): HasMany
    {
        return $this->hasMany(ProductoVariante::class);
    }

    public function getTrackingModeLabelAttribute(): string
    {
        $mode = InventoryTrackingMode::tryFrom((string) $this->tracking_mode);

        return $mode?->label() ?? (string) str($this->tracking_mode)->replace('_', ' ')->title();
    }
}
