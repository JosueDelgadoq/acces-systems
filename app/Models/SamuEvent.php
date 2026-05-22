<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class SamuEvent extends Model
{
    public const SOURCE_SAMU = 'samu';

    public const CLASSIFICATION_COMMERCIAL_LEAD = 'commercial_lead';
    public const CLASSIFICATION_COMMERCIAL_FOLLOW_UP = 'commercial_follow_up';
    public const CLASSIFICATION_POST_SALE_CLAIM = 'post_sale_claim';
    public const CLASSIFICATION_POST_SALE_SUPPORT = 'post_sale_support';
    public const CLASSIFICATION_POST_SALE_MAINTENANCE = 'post_sale_maintenance';
    public const CLASSIFICATION_POST_SALE_DIAGNOSIS = 'post_sale_diagnosis';
    public const CLASSIFICATION_POST_SALE_INSTALLATION = 'post_sale_installation';
    public const CLASSIFICATION_POST_SALE_DISPATCH = 'post_sale_dispatch';
    public const CLASSIFICATION_HABILITATION_FOLLOW_UP = 'habilitation_follow_up';
    public const CLASSIFICATION_MIXED = 'mixed';
    public const CLASSIFICATION_UNCLASSIFIED = 'unclassified';

    public const INTEREST_LEVEL_OPTIONS = [
        'alto' => 'Alto',
        'medio' => 'Medio',
        'bajo' => 'Bajo',
    ];

    public const CLASSIFICATION_OPTIONS = [
        self::CLASSIFICATION_COMMERCIAL_LEAD => 'Lead comercial',
        self::CLASSIFICATION_COMMERCIAL_FOLLOW_UP => 'Seguimiento comercial',
        self::CLASSIFICATION_POST_SALE_CLAIM => 'Reclamo de postventa',
        self::CLASSIFICATION_POST_SALE_SUPPORT => 'Soporte de postventa',
        self::CLASSIFICATION_POST_SALE_MAINTENANCE => 'Mantenimiento de postventa',
        self::CLASSIFICATION_POST_SALE_DIAGNOSIS => 'Diagnostico tecnico',
        self::CLASSIFICATION_POST_SALE_INSTALLATION => 'Instalacion postventa',
        self::CLASSIFICATION_POST_SALE_DISPATCH => 'Despacho / logistica',
        self::CLASSIFICATION_HABILITATION_FOLLOW_UP => 'Seguimiento de habilitacion',
        self::CLASSIFICATION_MIXED => 'Mixto comercial + postventa',
        self::CLASSIFICATION_UNCLASSIFIED => 'Sin clasificar',
    ];

    public const UNLINKABLE_RELATION_FIELDS = [
        'lead_id' => 'Lead',
        'client_id' => 'Cliente',
        'claim_id' => 'Claim',
        'pendiente_id' => 'Pendiente',
        'habilitation_id' => 'Habilitacion',
    ];

    public const LOW_SIGNAL_TEXT_MARKERS = [
        'duracion de la llamada no fue suficiente para elaborar un analisis detallado',
        'duracion de la llamada no fue suficiente',
        'no fue suficiente para elaborar un analisis detallado',
        'interaccion fue demasiado corta',
        'no aporto informacion suficiente',
    ];

    public const LOW_SIGNAL_SQL_PATTERNS = [
        'la duraci_n de la llamada no fue suficiente para elaborar un an_lisis detallado',
        'la duracion de la llamada no fue suficiente para elaborar un analisis detallado',
        'duraci_n de la llamada no fue suficiente',
        'duracion de la llamada no fue suficiente',
        'no fue suficiente para elaborar un an_lisis detallado',
        'no fue suficiente para elaborar un analisis detallado',
        'la interaccion fue demasiado corta',
        'no aporto informacion suficiente',
    ];

    protected $fillable = [
        'external_id',
        'source',
        'event_type',
        'lead_id',
        'client_id',
        'claim_id',
        'pendiente_id',
        'habilitation_id',
        'payload',
        'summary',
        'transcript',
        'interest_level',
        'next_step',
        'pipeline_stage',
        'classification',
        'manual_classification',
        'manual_review_notes',
        'manual_reviewed_by',
        'manual_reviewed_at',
        'classification_reason',
        'objections',
        'tasks_detected',
        'processed',
        'processed_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'objections' => 'array',
        'tasks_detected' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
        'manual_reviewed_at' => 'datetime',
    ];

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query
            ->withoutLowSignal()
            ->where(function (Builder $inner): void {
                $inner
                    ->where('processed', false)
                    ->orWhereNotNull('error_message')
                    ->orWhereNotNull('manual_classification')
                    ->orWhereNull('classification')
                    ->orWhere('classification', self::CLASSIFICATION_UNCLASSIFIED);
            });
    }

    public function scopeUnclassifiedReview(Builder $query): Builder
    {
        return $query
            ->withoutLowSignal()
            ->whereNull('manual_classification')
            ->where(function (Builder $inner): void {
                $inner
                    ->where('processed', true)
                    ->orWhereNotNull('error_message');
            })
            ->where(function (Builder $inner): void {
                $inner
                    ->whereNull('classification')
                    ->orWhere('classification', self::CLASSIFICATION_UNCLASSIFIED);
            });
    }

    public function scopeWithManualOverride(Builder $query): Builder
    {
        return $query
            ->whereNotNull('manual_classification')
            ->where('manual_classification', '!=', self::CLASSIFICATION_UNCLASSIFIED);
    }

    public function scopeDiscarded(Builder $query): Builder
    {
        return $query->where('manual_classification', self::CLASSIFICATION_UNCLASSIFIED);
    }

    public function scopeWithErrors(Builder $query): Builder
    {
        return $query->whereNotNull('error_message');
    }

    public function scopeLowSignal(Builder $query): Builder
    {
        static::applyLowSignalConstraint($query);

        return $query;
    }

    public function scopeWithoutLowSignal(Builder $query): Builder
    {
        static::applyWithoutLowSignalConstraint($query);

        return $query;
    }

    public function hasManualOverride(): bool
    {
        return filled($this->manual_classification);
    }

    public function isDiscarded(): bool
    {
        return $this->manual_classification === self::CLASSIFICATION_UNCLASSIFIED;
    }

    public function isUnclassifiedReview(): bool
    {
        return blank($this->manual_classification)
            && ($this->processed || filled($this->error_message))
            && in_array($this->classification, [null, self::CLASSIFICATION_UNCLASSIFIED], true);
    }

    public function isLowSignal(): bool
    {
        $haystack = Str::of(collect([
            $this->summary,
            $this->classification_reason,
        ])->filter()->implode(' '))
            ->ascii()
            ->lower()
            ->value();

        return Str::contains($haystack, self::LOW_SIGNAL_TEXT_MARKERS);
    }

    /**
     * @return array{label:string,color:string}
     */
    public function getReviewStatusMeta(): array
    {
        return match (true) {
            filled($this->error_message) => ['label' => 'Con error', 'color' => 'danger'],
            $this->isDiscarded() => ['label' => 'Descartado', 'color' => 'gray'],
            $this->hasManualOverride() => ['label' => 'Override manual', 'color' => 'warning'],
            $this->isLowSignal() => ['label' => 'Baja senal', 'color' => 'gray'],
            ! $this->processed => ['label' => 'Pendiente de cola', 'color' => 'primary'],
            $this->isUnclassifiedReview() => ['label' => 'Sin catalogar', 'color' => 'info'],
            default => ['label' => 'OK', 'color' => 'success'],
        };
    }

    public static function getUnlinkableRelationOptions(): array
    {
        return self::UNLINKABLE_RELATION_FIELDS;
    }

    public static function getInterestLevelLabel(?string $level): string
    {
        return self::INTEREST_LEVEL_OPTIONS[$level]
            ?? (string) str($level)->replace('_', ' ')->title();
    }

    public static function getClassificationLabel(?string $classification): string
    {
        return self::CLASSIFICATION_OPTIONS[$classification]
            ?? (string) str($classification)->replace('_', ' ')->title();
    }

    protected static function applyLowSignalConstraint(Builder $query): void
    {
        $patterns = self::LOW_SIGNAL_SQL_PATTERNS;

        $query->where(function (Builder $outer) use ($patterns): void {
            foreach (['summary', 'classification_reason'] as $columnIndex => $column) {
                $outer->{$columnIndex === 0 ? 'where' : 'orWhere'}(function (Builder $columnQuery) use ($column, $patterns): void {
                    foreach ($patterns as $patternIndex => $pattern) {
                        $columnQuery->{$patternIndex === 0 ? 'whereRaw' : 'orWhereRaw'}(
                            "LOWER(COALESCE({$column}, '')) like ?",
                            ['%' . mb_strtolower($pattern, 'UTF-8') . '%'],
                        );
                    }
                });
            }
        });
    }

    protected static function applyWithoutLowSignalConstraint(Builder $query): void
    {
        $patterns = self::LOW_SIGNAL_SQL_PATTERNS;

        $query->where(function (Builder $outer) use ($patterns): void {
            foreach (['summary', 'classification_reason'] as $column) {
                foreach ($patterns as $pattern) {
                    $outer->whereRaw(
                        "LOWER(COALESCE({$column}, '')) not like ?",
                        ['%' . mb_strtolower($pattern, 'UTF-8') . '%'],
                    );
                }
            }
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }

    public function pendiente(): BelongsTo
    {
        return $this->belongsTo(Pendiente::class);
    }

    public function habilitation(): BelongsTo
    {
        return $this->belongsTo(Habilitation::class);
    }

    public function manualReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_reviewed_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SamuEventNote::class)
            ->latest('id');
    }

    public function latestNote(): HasOne
    {
        return $this->hasOne(SamuEventNote::class)
            ->latestOfMany();
    }
}
