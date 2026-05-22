<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SamuEventNote extends Model
{
    protected $fillable = [
        'samu_event_id',
        'user_id',
        'note',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(SamuEvent::class, 'samu_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
