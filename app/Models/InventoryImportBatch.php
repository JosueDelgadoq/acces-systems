<?php

namespace App\Models;

use App\Enums\Inventory\InventoryImportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryImportBatch extends Model
{
    protected $table = 'inventory_import_batches';

    protected $fillable = [
        'file_name',
        'file_path',
        'file_disk',
        'status',
        'uploaded_by_user_id',
        'notes',
        'summary',
        'previewed_at',
        'imported_at',
        'rolled_back_at',
        'failed_at',
    ];

    protected $casts = [
        'summary' => 'array',
        'previewed_at' => 'datetime',
        'imported_at' => 'datetime',
        'rolled_back_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->file_disk ??= 'local';
            $model->status ??= InventoryImportStatus::DRAFT->value;
        });
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(InventoryImportRow::class, 'inventory_import_batch_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductoUnidad::class, 'source_import_batch_id');
    }

    public function getStatusLabelAttribute(): string
    {
        $status = InventoryImportStatus::tryFrom((string) $this->status);

        return $status?->label() ?? (string) str($this->status)->replace('_', ' ')->title();
    }
}
