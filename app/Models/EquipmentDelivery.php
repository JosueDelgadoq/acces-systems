<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentDelivery extends Model
{
    protected $fillable = [
        'client_id',
        'equipment',
        'sale_date',
        'delivery_date',
        'installation_date',
        'delivery_remito',
        'installation_remito',
        'signed_remito',
        'status',
        'observations',
    ];

    protected $casts = [
        'signed_remito' => 'boolean',
        'sale_date' => 'date',
        'delivery_date' => 'date',
        'installation_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

