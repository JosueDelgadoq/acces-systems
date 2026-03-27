<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendienteView extends Model
{
protected $fillable = [
    'client_id',
    'user_id',
    'type',
    'description',
    'status',
    'priority', // 👈 AGREGAR
    'due_date',
    'completed_at',
];

    public $timestamps = false;

    // 👇 IMPORTANTE: no usa tabla real
    protected $table = null;

    public function getTable()
    {
        return 'pendientes_virtual'; // fake
    }
    protected $casts = [
    'completed_at' => 'datetime',
];
}