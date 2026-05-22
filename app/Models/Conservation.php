<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conservation extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RENEWAL_REQUIRED = 'renewal_required';

    protected $fillable = [
        'client_id',
        'start_date',
        'next_service_date',
        'expiration_date',
        'frequency',
        'notes',
        'current_service_number',
        'total_services',
        'contract_cycle_number',
        'contract_status',
        'last_service_date',
        'completed_this_month',
        'completed_at',
        'renewal_required_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'next_service_date' => 'date',
        'expiration_date' => 'date',
        'last_service_date' => 'date',
        'completed_this_month' => 'boolean',
        'completed_at' => 'date',
        'renewal_required_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conservation $conservation): void {
            $conservation->contract_status ??= self::STATUS_ACTIVE;
            $conservation->contract_cycle_number ??= 1;
            $conservation->current_service_number ??= 1;
            $conservation->total_services ??= 12;

            if (! $conservation->next_service_date && $conservation->start_date && $conservation->frequency) {
                $conservation->next_service_date = static::calculateNextServiceDate(
                    $conservation->start_date,
                    $conservation->frequency,
                );
            }
        });

        static::saving(function (Conservation $conservation): void {
            $conservation->contract_status ??= self::STATUS_ACTIVE;
            $conservation->contract_cycle_number ??= 1;

            $totalServices = max(1, (int) ($conservation->total_services ?? 12));
            $currentServiceNumber = max(1, (int) ($conservation->current_service_number ?? 1));

            $conservation->total_services = $totalServices;

            if ($conservation->contract_status === self::STATUS_RENEWAL_REQUIRED) {
                $conservation->current_service_number = $totalServices;
            } else {
                $conservation->current_service_number = min($currentServiceNumber, $totalServices);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(ConservationVisit::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(ConservationRenewal::class)->latest('renewed_at');
    }

    public function scopeActiveContracts(Builder $query): Builder
    {
        return $query
            ->where('contract_status', self::STATUS_ACTIVE)
            ->where(function (Builder $inner): void {
                $inner
                    ->whereNull('expiration_date')
                    ->orWhereDate('expiration_date', '>=', today());
            });
    }

    public function scopeDueForRenewal(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner
                ->where('contract_status', self::STATUS_RENEWAL_REQUIRED)
                ->orWhereDate('expiration_date', '<', today());
        });
    }

    public function scopeSchedulable(Builder $query): Builder
    {
        return $query
            ->activeContracts()
            ->whereNotNull('next_service_date');
    }

    public static function calculateNextServiceDate(
        CarbonInterface|string $baseDate,
        string $frequency,
    ): Carbon {
        $date = $baseDate instanceof CarbonInterface
            ? $baseDate->copy()
            : Carbon::parse($baseDate);

        return match ($frequency) {
            'mensual' => $date->addMonth(),
            'trimestral' => $date->addMonths(3),
            'semestral' => $date->addMonths(6),
            'anual' => $date->addYear(),
            default => $date->addMonth(),
        };
    }

    public function isExpired(): bool
    {
        return $this->expiration_date instanceof CarbonInterface
            && $this->expiration_date->isBefore(today());
    }

    public function isRenewalRequired(): bool
    {
        return $this->contract_status === self::STATUS_RENEWAL_REQUIRED;
    }

    public function canBeRenewed(): bool
    {
        return $this->isRenewalRequired() || $this->isExpired();
    }

    public function getServiceProgressAttribute(): string
    {
        $current = str_pad((string) ($this->current_service_number ?? 1), 2, '0', STR_PAD_LEFT);
        $total = str_pad((string) ($this->total_services ?? 12), 2, '0', STR_PAD_LEFT);

        return "{$current}/{$total}";
    }

    public function getCompletedServicesCountAttribute(): int
    {
        $total = max(1, (int) ($this->total_services ?? 12));
        $current = max(1, (int) ($this->current_service_number ?? 1));

        if ($this->isRenewalRequired()) {
            return $total;
        }

        return max(0, min($total, $current - 1));
    }

    public function getRemainingServicesAttribute(): int
    {
        $total = max(1, (int) ($this->total_services ?? 12));

        return max(0, $total - $this->completed_services_count);
    }

    public function getContractStatusLabelAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Vencido';
        }

        return match ($this->contract_status) {
            self::STATUS_RENEWAL_REQUIRED => 'Renovar contrato',
            default => 'Activo',
        };
    }
}
