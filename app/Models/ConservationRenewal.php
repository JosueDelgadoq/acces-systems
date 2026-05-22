<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConservationRenewal extends Model
{
    protected $fillable = [
        'conservation_id',
        'renewed_by',
        'renewed_at',
        'previous_cycle_number',
        'new_cycle_number',
        'previous_start_date',
        'previous_expiration_date',
        'previous_frequency',
        'previous_total_services',
        'previous_completed_services',
        'previous_last_service_date',
        'new_start_date',
        'new_expiration_date',
        'new_frequency',
        'new_total_services',
        'notes',
    ];

    protected $casts = [
        'renewed_at' => 'date',
        'previous_start_date' => 'date',
        'previous_expiration_date' => 'date',
        'previous_last_service_date' => 'date',
        'new_start_date' => 'date',
        'new_expiration_date' => 'date',
    ];

    public function conservation(): BelongsTo
    {
        return $this->belongsTo(Conservation::class);
    }

    public function renewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}
