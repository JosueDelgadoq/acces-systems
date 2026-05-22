<?php

namespace App\Models;

use App\Enums\Inventory\InventoryLocationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLocation extends Model
{
    protected $table = 'inventory_locations';

    protected $fillable = [
        'name',
        'code',
        'type',
        'parent_id',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->type ??= InventoryLocationType::WAREHOUSE->value;
            $model->is_active ??= true;
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductoUnidad::class, 'location_id');
    }

    public function movementsFrom(): HasMany
    {
        return $this->hasMany(ProductoUnidadMovement::class, 'from_location_id');
    }

    public function movementsTo(): HasMany
    {
        return $this->hasMany(ProductoUnidadMovement::class, 'to_location_id');
    }

    public function getTypeLabelAttribute(): string
    {
        $type = InventoryLocationType::tryFrom((string) $this->type);

        return $type?->label() ?? (string) str($this->type)->replace('_', ' ')->title();
    }
}
