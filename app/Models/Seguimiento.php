<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seguimiento extends Model
{
    use HasFactory;

    public const STATUS_PENDIENTE = 'pendiente';
    public const STATUS_COMPLETADO = 'completado';
    public const STATUS_CANCELADO = 'cancelado';

    public const STATUS_OPTIONS = [
        self::STATUS_PENDIENTE => 'Pendiente',
        self::STATUS_COMPLETADO => 'Completado',
        self::STATUS_CANCELADO => 'Cancelado',
    ];

    public const MEDIO_CONTACTO_OPTIONS = [
        'whatsapp' => 'WhatsApp',
        'llamada_anura_ip' => 'Llamada',
        'mail' => 'Mail',
        'visita' => 'Visita',
        'videollamada' => 'Videollamada',
    ];

    protected $fillable = [
        'lead_id',
        'fecha_contacto',
        'comercial_id',
        'medio_contacto',
        'resultado',
        'proxima_accion',
        'fecha_proxima_accion',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_contacto' => 'date',
        'fecha_proxima_accion' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function comercial(): BelongsTo
    {
        return $this->belongsTo(User::class, 'comercial_id');
    }

    public static function getStatusOptions(): array
    {
        return self::STATUS_OPTIONS;
    }

    public static function getMedioContactoOptions(): array
    {
        return self::MEDIO_CONTACTO_OPTIONS;
    }
}

