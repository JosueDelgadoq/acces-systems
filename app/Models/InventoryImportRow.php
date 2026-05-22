<?php

namespace App\Models;

use App\Enums\Inventory\InventoryImportRowStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryImportRow extends Model
{
    protected $table = 'inventory_import_rows';

    protected $fillable = [
        'inventory_import_batch_id',
        'producto_unidad_id',
        'source_sheet',
        'source_row_number',
        'row_hash',
        'status',
        'row_payload',
        'normalized_payload',
        'validation_errors',
        'conflict_details',
        'resolution',
    ];

    protected $casts = [
        'row_payload' => 'array',
        'normalized_payload' => 'array',
        'validation_errors' => 'array',
        'conflict_details' => 'array',
        'resolution' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->status ??= InventoryImportRowStatus::PENDING->value;
        });
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryImportBatch::class, 'inventory_import_batch_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductoUnidad::class, 'producto_unidad_id');
    }

    public function getStatusLabelAttribute(): string
    {
        $status = InventoryImportRowStatus::tryFrom((string) $this->status);

        return $status?->label() ?? (string) str($this->status)->replace('_', ' ')->title();
    }
}
