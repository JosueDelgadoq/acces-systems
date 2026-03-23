<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postventa extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'producto_instalado',
        'fecha_instalacion',
        'garantia_activa',
        'servicio_mantenimiento',
        'proximo_servicio',
        'nivel_satisfaccion',
        'resena_obtenida',
    ];

    protected $casts = [
        'fecha_instalacion' => 'date',
        'proximo_servicio' => 'date',
        'garantia_activa' => 'boolean',
        'servicio_mantenimiento' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}

