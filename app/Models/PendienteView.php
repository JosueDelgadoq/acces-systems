<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendienteView extends Model
{
    protected $fillable = [
        'id',
        'type',
        'client',
        'description',
        'due_date',
        'priority',
    ];

    public $timestamps = false;

    // 👇 IMPORTANTE: no usa tabla real
    protected $table = null;

    public function getTable()
    {
        return 'pendientes_virtual'; // fake
    }
}