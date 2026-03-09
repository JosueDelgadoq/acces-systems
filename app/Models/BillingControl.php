<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingControl extends Model
{
    protected $fillable = [
        'client_id',
        'service_description',
        'amount',
        'invoice_number',
        'invoice_date',
        'invoiced',
        'paid',
        'payment_date',
        'observations',
    ];

    protected $casts = [
        'invoiced' => 'boolean',
        'paid' => 'boolean',
        'amount' => 'decimal:2',
        'invoice_date' => 'date',
        'payment_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

