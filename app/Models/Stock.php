<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'producto_variante_id',
        'cantidad'
    ];

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }
}