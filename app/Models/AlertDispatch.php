<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertDispatch extends Model
{
    protected $fillable = [
        'alert_key',
        'channel',
        'target',
        'entity_type',
        'entity_id',
        'dedupe_key',
        'meta',
        'sent_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_at' => 'datetime',
    ];
}
