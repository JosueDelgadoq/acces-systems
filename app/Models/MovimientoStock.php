<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoStock extends Model
{
    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_variante_id',
        'tipo',
        'cantidad',
        'usuario_id',
        'nota',
    ];

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
