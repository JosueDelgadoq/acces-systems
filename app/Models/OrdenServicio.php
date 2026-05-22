<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenServicio extends Model
{
    protected $table = 'ordenes_servicio';

    protected $fillable = [
        'cliente_id',
        'tecnico_id',
        'tipo_servicio',
        'descripcion',
        'estado',
        'prioridad',
        'fecha_programada',
        'fecha_inicio',
        'fecha_fin',
        'latitud',
        'longitud'
    ];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'cliente_id');
    }

    public function tecnico()
    {
        return $this->belongsTo(Tecnico::class);
    }

    public function historial()
    {
        return $this->hasMany(OrdenHistorial::class, 'orden_id');
    }

    public function fotos()
    {
        return $this->hasMany(FotoServicio::class, 'orden_id');
    }
    public function scopePendientes($query)
{
    return $query->where('estado', 'pendiente');
}

public function scopeActivas($query)
{
    return $query->whereIn('estado', [
        'asignado',
        'en_camino',
        'en_sitio'
    ]);
}
}
