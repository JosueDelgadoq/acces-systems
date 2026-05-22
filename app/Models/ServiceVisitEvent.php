<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceVisitEvent extends Model
{
    public const TYPE_ACCEPTED = 'accepted';
    public const TYPE_STARTED = 'started';
    public const TYPE_FINISHED = 'finished';
    public const TYPE_TRAVELING = 'traveling';

    public const TYPE_OPTIONS = [
        self::TYPE_ACCEPTED => 'Aceptada',
        self::TYPE_STARTED => 'Iniciada',
        self::TYPE_FINISHED => 'Finalizada',
        self::TYPE_TRAVELING => 'En traslado',
    ];

    protected $fillable = [
        'service_visit_id',
        'pendiente_id',
        'user_id',
        'event_type',
        'description',
        'lat',
        'lng',
        'metadata',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'metadata' => 'array',
    ];

    public static function getTypeLabel(?string $type): string
    {
        return self::TYPE_OPTIONS[$type]
            ?? (string) str($type)->replace('_', ' ')->title();
    }

    public function serviceVisit(): BelongsTo
    {
        return $this->belongsTo(ServiceVisit::class);
    }

    public function pendiente(): BelongsTo
    {
        return $this->belongsTo(Pendiente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
