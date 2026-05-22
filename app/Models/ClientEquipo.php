<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientEquipo extends Model
{
    protected $fillable = [
        'client_id',
        'equipo_id',
        'producto_unidad_id',
        'serie',
        'ubicacion',
        'estado',
        'fecha_instalacion',
    ];

    protected $casts = [
        'fecha_instalacion' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function productoUnidad(): BelongsTo
    {
        return $this->belongsTo(ProductoUnidad::class, 'producto_unidad_id');
    }
}
