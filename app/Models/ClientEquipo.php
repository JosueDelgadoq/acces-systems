<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientEquipo extends Model
{
    protected $fillable = [
    'client_id',
    'equipo_id',
    'serie',
    'ubicacion',
    'estado',
    'fecha_instalacion',
];
public function client()
{
    return $this->belongsTo(Client::class);
    
}

public function equipo()
{
    return $this->belongsTo(Equipo::class);
}
}
