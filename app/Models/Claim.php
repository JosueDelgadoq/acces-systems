<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Models\User;

class Claim extends Model
{
    protected $fillable = [
        'client_id',
        'title',
        'description',
        'status',
        'technician_id',
        'scheduled_visit',
        'resolution',
        'closed_at',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}