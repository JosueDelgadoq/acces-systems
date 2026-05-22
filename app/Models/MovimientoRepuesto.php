<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoRepuesto extends Model
{
    protected $table = 'movimientos_repuestos';

    protected $fillable = [
        'repuesto_id',
        'tipo',
        'cantidad',
        'motivo',
        'user_id',
    ];

    public function repuesto()
    {
        return $this->belongsTo(Repuesto::class);
    }
}