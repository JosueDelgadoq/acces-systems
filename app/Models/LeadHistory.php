<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadHistory extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'event_key',
        'title',
        'description',
        'status_from',
        'status_to',
        'meta',
        'changed_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'changed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
