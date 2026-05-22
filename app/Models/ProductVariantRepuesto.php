<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantRepuesto extends Model
{
    protected $table = 'producto_variante_repuestos';

    protected $fillable = [
        'producto_variante_id',
        'repuesto_id',
        'cantidad',
        'obligatorio',
    ];

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'producto_variante_id');
    }

    public function repuesto()
    {
        return $this->belongsTo(Repuesto::class);
    }
}