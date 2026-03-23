<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'producto_instalado',
        'monto_total',
        'estado',
        'fecha_cierre',
        'created_by',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'fecha_cierre' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

