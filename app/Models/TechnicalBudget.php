<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalBudget extends Model
{
    protected $fillable = [
        'client_id',
        'title',
        'description',
        'status',
        'amount',
        'sent_at',
        'approval_date',
        'scheduled_visit',
        'service_remito',
        'observations',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'approval_date' => 'date',
        'scheduled_visit' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

