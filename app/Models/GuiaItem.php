<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GuiaItem extends Model
{
    protected $fillable = [
        'producto_variante_id',
        'longitud',
        'tiene_angulo',
        'tiene_patas',
        'rebatible',
        'estado',
        'ubicacion_id',
        'observaciones',
    ];

    protected $casts = [
        'tiene_angulo' => 'boolean',
        'tiene_patas' => 'boolean',
        'rebatible' => 'boolean',
    ];

    public function productoVariante()
    {
        return $this->belongsTo(ProductoVariante::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $variante = $this->productoVariante;

        if (!$variante) {
            return (string) $this->longitud;
        }

        return $variante->display_name . ' | ' . $this->longitud . ' m';
    }
}
