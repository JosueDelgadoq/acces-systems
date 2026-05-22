<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UbicacionTecnico extends Model
{
    protected $table = 'ubicaciones_tecnicos';

    public $timestamps = false;

    protected $fillable = [
        'tecnico_id',
        'latitud',
        'longitud',
        'precision',
        'timestamp'
    ];

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }
}