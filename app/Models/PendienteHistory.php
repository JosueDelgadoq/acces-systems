<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendienteHistory extends Model
{
    protected $fillable = [
        'pendiente_id',
        'client_id',
        'user_id',
        'status_from',
        'status_to',
        'observation',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function pendiente(): BelongsTo
    {
        return $this->belongsTo(Pendiente::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
