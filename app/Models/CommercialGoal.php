<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'goal_month',
        'target_leads',
        'target_contacts',
        'target_quotes',
        'target_sales',
        'target_revenue',
        'notes',
    ];

    protected $casts = [
        'goal_month' => 'date',
        'target_revenue' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
