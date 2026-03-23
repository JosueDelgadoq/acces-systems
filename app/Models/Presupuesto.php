<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Presupuesto extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'producto_1',
        'precio_1',
        'producto_2', 
        'precio_2',
        'producto_3',
        'precio_3',
        'producto_4',
        'precio_4',
        'presupuesto_definitivo',
        'fecha_envio',
        'created_by',
    ];

    protected $casts = [
        'precio_1' => 'decimal:2',
        'precio_2' => 'decimal:2',
        'precio_3' => 'decimal:2',
        'precio_4' => 'decimal:2',
        'presupuesto_definitivo' => 'decimal:2',
        'fecha_envio' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTotalAttribute()
    {
        return ($this->precio_1 ?? 0) + ($this->precio_2 ?? 0) + ($this->precio_3 ?? 0) + ($this->precio_4 ?? 0);
    }
}

