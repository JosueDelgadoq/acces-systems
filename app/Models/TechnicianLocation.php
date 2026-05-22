<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianLocation extends Model
{
    protected $fillable = [
        'user_id',
        'lat',
        'lng',
        'accuracy',
        'speed',
        'battery_level',
        'is_moving',
        'tracked_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'battery_level' => 'integer',
        'is_moving' => 'boolean',
        'tracked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
