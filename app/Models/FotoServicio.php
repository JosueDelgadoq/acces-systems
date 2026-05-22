<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FotoServicio extends Model
{
    protected $table = 'fotos_servicio';

    protected $fillable = [
        'orden_id',
        'ruta',
        'tipo'
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_id');
    }
}