<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Conservation extends Model
{
    protected $fillable = [
        'client_id',
        'start_date',
        'next_service_date',
        'expiration_date',
        'frequency',
        'notes',
    ];

    protected static function booted()
    {
        static::creating(function ($conservation) {

            if ($conservation->start_date && $conservation->frequency) {

                $date = Carbon::parse($conservation->start_date);

                $conservation->next_service_date = match ($conservation->frequency) {
                    'mensual' => $date->addMonth(),
                    'trimestral' => $date->addMonths(3),
                    'semestral' => $date->addMonths(6),
                    'anual' => $date->addYear(),
                };
            }

        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}