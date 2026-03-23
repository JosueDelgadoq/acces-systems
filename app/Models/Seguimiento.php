<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Seguimiento extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'fecha_contacto',
        'comercial_id',
        'medio_contacto',
        'resultado',
        'proxima_accion',
        'fecha_proxima_accion',
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
}

