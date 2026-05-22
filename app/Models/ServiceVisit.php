<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceVisit extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';

    public const TYPE_INSTALLATION = 'installation';
    public const TYPE_TECHNICAL_SERVICE = 'technical_service';
    public const TYPE_DIAGNOSIS = 'diagnosis';

    public const STATUS_OPTIONS = [
        self::STATUS_PENDING => 'Asignada',
        self::STATUS_ACCEPTED => 'Aceptada',
        self::STATUS_IN_PROGRESS => 'En sitio',
        self::STATUS_COMPLETED => 'Finalizada',
    ];

    public const TYPE_OPTIONS = [
        self::TYPE_INSTALLATION => 'Instalacion',
        self::TYPE_TECHNICAL_SERVICE => 'Servicio tecnico',
        self::TYPE_DIAGNOSIS => 'Diagnostico',
    ];

    public const PENDIENTE_TYPE_MAP = [
        'instalacion' => self::TYPE_INSTALLATION,
        'diagnostico' => self::TYPE_DIAGNOSIS,
        'presupuesto' => self::TYPE_DIAGNOSIS,
        'reclamo' => self::TYPE_TECHNICAL_SERVICE,
        'desinstalacion' => self::TYPE_TECHNICAL_SERVICE,
        'soporte' => self::TYPE_TECHNICAL_SERVICE,
        'mantenimiento' => self::TYPE_TECHNICAL_SERVICE,
        'reparacion' => self::TYPE_TECHNICAL_SERVICE,
    ];

    protected $fillable = [
        'claim_id',
        'pendiente_id',
        'technician_id',
        'status',
        'visit_type',
        'arrival_time',
        'departure_time',
        'arrival_photo',
        'departure_photo',
        'report',
        'lat_arrival',
        'lng_arrival',
        'lat_departure',
        'lng_departure',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime',
        'lat_arrival' => 'float',
        'lng_arrival' => 'float',
        'lat_departure' => 'float',
        'lng_departure' => 'float',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $visit): void {
            $visit->visit_type = self::resolveVisitType(
                $visit->resolvePendienteType(),
                filled($visit->claim_id),
            );
        });
    }

    public static function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    public static function getTypeOptions(): array
    {
        return self::TYPE_OPTIONS;
    }

    public static function getStatusLabel(?string $status): string
    {
        return self::STATUS_OPTIONS[$status]
            ?? (string) str($status)->replace('_', ' ')->title();
    }

    public static function getTypeLabel(?string $type): string
    {
        return self::TYPE_OPTIONS[$type]
            ?? 'Servicio tecnico';
    }

    public static function requiresOperationalVisit(?string $pendienteType): bool
    {
        return filled($pendienteType) && array_key_exists($pendienteType, self::PENDIENTE_TYPE_MAP);
    }

    public static function resolveVisitType(?string $pendienteType, bool $hasLegacyClaim = false): string
    {
        if ($pendienteType && array_key_exists($pendienteType, self::PENDIENTE_TYPE_MAP)) {
            return self::PENDIENTE_TYPE_MAP[$pendienteType];
        }

        return self::TYPE_TECHNICAL_SERVICE;
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function pendiente(): BelongsTo
    {
        return $this->belongsTo(Pendiente::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ServiceVisitEvent::class);
    }

    public function unitAssignments(): HasMany
    {
        return $this->hasMany(ProductoUnidadAssignment::class)
            ->latest('id');
    }

    public function activeUnitAssignments(): HasMany
    {
        return $this->hasMany(ProductoUnidadAssignment::class)
            ->whereIn('status', ProductoUnidadAssignment::ACTIVE_STATUSES)
            ->latest('id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    public function scopeAssignedToTechnician(Builder $query, User|int $technician): Builder
    {
        $technicianId = $technician instanceof User ? $technician->id : $technician;

        return $query->where('technician_id', $technicianId);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_IN_PROGRESS,
        ], true);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeAccepted(): bool
    {
        return $this->isPending();
    }

    public function canBeStarted(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACCEPTED,
        ], true);
    }

    public function canBeFinished(): bool
    {
        return $this->isInProgress();
    }

    protected function resolvePendienteType(): ?string
    {
        if ($this->relationLoaded('pendiente')) {
            return $this->pendiente?->type;
        }

        if (blank($this->pendiente_id)) {
            return null;
        }

        return Pendiente::query()
            ->whereKey($this->pendiente_id)
            ->value('type');
    }
}
