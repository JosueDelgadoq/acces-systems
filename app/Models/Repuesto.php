<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repuesto extends Model
{
protected $fillable = [
    'tipo',
    'nombre',
    'codigo',
    'descripcion',
    'stock_actual',
    'stock_minimo',
    'precio_compra',
    'precio_venta',
];

    public function movimientos()
    {
        return $this->hasMany(MovimientoRepuesto::class);
    }
}