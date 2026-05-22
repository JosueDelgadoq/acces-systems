<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductoUnidadAssignment extends Model
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_INSTALLED = 'installed';
    public const STATUS_RELEASED = 'released';

    public const ACTIVE_STATUSES = [
        self::STATUS_RESERVED,
        self::STATUS_ASSIGNED,
    ];

    public const STATUS_OPTIONS = [
        self::STATUS_RESERVED => 'Reservado',
        self::STATUS_ASSIGNED => 'Asignado',
        self::STATUS_INSTALLED => 'Instalado',
        self::STATUS_RELEASED => 'Liberado',
    ];

    protected $fillable = [
        'producto_unidad_id',
        'pendiente_id',
        'service_visit_id',
        'technician_id',
        'created_by_user_id',
        'released_by_user_id',
        'status',
        'notes',
        'resolution_notes',
        'holder_type',
        'holder_id',
        'context_type',
        'context_id',
        'client_id',
        'client_equipo_id',
        'reserved_at',
        'assigned_at',
        'installed_at',
        'resolved_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'assigned_at' => 'datetime',
        'installed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductoUnidad::class, 'producto_unidad_id');
    }

    public function pendiente(): BelongsTo
    {
        return $this->belongsTo(Pendiente::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(ServiceVisit::class, 'service_visit_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function clientEquipo(): BelongsTo
    {
        return $this->belongsTo(ClientEquipo::class, 'client_equipo_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_user_id');
    }

    public function holder(): MorphTo
    {
        return $this->morphTo();
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeForPendiente(Builder $query, Pendiente|int $pendiente): Builder
    {
        $pendienteId = $pendiente instanceof Pendiente ? $pendiente->id : $pendiente;

        return $query->where('pendiente_id', $pendienteId);
    }

    public function scopeForVisit(Builder $query, ServiceVisit|int $visit): Builder
    {
        $visitId = $visit instanceof ServiceVisit ? $visit->id : $visit;

        return $query->where('service_visit_id', $visitId);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_OPTIONS[$this->status] ?? (string) str($this->status)->replace('_', ' ')->title();
    }
}
