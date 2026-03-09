<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'client_id',
        'title',
        'description',
        'status',
        'priority',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}