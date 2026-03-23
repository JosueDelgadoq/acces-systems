<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pendiente extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'type',
        'description',
        'due_date',
        'status',
        'notes',
    ];

    // RELACIÓN CON CLIENTE
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // RELACIÓN CON USUARIO
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}