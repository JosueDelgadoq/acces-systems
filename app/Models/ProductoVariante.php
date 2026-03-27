<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoVariante extends Model
{
    protected $fillable = [
        'producto_id',
        'codigo_barra',
        'estado',
        'uso',
        'lado',
        'modelo'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function stock()
    {
        return $this->hasOne(Stock::class);
    }
}