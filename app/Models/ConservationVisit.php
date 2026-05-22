<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConservationVisit extends Model
{
    protected $fillable = [
        'conservation_id',
        'date',
        'service_number',
        'contract_cycle_number',
        'contract_total_services',
        'technician_id',
        'status',
        'notes',
        'remito',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function conservation(): BelongsTo
    {
        return $this->belongsTo(Conservation::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
