<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Habilitation extends Model
{
    protected $fillable = [
        'client_id',
        'equipment',
        'status',
        'doc_completa',
        'fecha_envio_gestor',
        'fecha_presentacion',
        'proxima_gestion',
        'observaciones',
    ];

    protected $casts = [
        'doc_completa' => 'boolean',
        'fecha_envio_gestor' => 'date',
        'fecha_presentacion' => 'date',
        'proxima_gestion' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

