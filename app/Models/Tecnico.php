<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tecnico extends Model
{
    protected $fillable = [
        'user_id',
        'telefono',
        'zona_base',
        'activo'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ordenes()
    {
        return $this->hasMany(OrdenServicio::class);
    }

    public function ubicaciones()
    {
        return $this->hasMany(UbicacionTecnico::class);
    }
}