<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoUnidadMovement extends Model
{
    protected $fillable = [
        'producto_unidad_id',
        'producto_variante_id',
        'user_id',
        'movement_type',
        'from_state',
        'to_state',
        'from_location_id',
        'to_location_id',
        'reason',
        'notes',
        'reference_type',
        'reference_id',
        'metadata',
        'performed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'performed_at' => 'datetime',
    ];

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(ProductoUnidad::class, 'producto_unidad_id');
    }

    public function variante(): BelongsTo
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->user();
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }
}
