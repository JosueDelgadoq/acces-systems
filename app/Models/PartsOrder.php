<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartsOrder extends Model
{
    protected $fillable = [
        'technician_id',
        'part_name',
        'supplier',
        'estimated_cost',
        'status',
        'purchase_date',
        'arrival_date',
        'invoice',
        'observations',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'purchase_date' => 'date',
        'arrival_date' => 'date',
    ];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}

