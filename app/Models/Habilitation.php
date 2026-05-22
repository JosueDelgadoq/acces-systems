<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function samuEvents(): HasMany
    {
        return $this->hasMany(SamuEvent::class);
    }
}

