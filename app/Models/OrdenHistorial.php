<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenHistorial extends Model
{
    protected $table = 'ordenes_historial';

    protected $fillable = [
        'orden_id',
        'estado',
        'comentario',
        'user_id'
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}