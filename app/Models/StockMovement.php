<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'producto_variante_id',
        'cantidad',
        'tipo',
        'origen',
        'motivo'
    ];

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class);
    }
}