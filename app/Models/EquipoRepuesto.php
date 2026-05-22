<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoRepuesto extends Model
{
    protected $table = 'equipo_repuestos';

    protected $fillable = [
        'equipo_id',
        'repuesto_id',
        'cantidad',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function repuesto()
    {
        return $this->belongsTo(Repuesto::class);
    }
}