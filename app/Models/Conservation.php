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
        'current_service_number',
        'total_services',
        'last_service_date',
        'completed_this_month',
        'completed_at',
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

            // Set default values if not provided
            if ($conservation->current_service_number === null) {
                $conservation->current_service_number = 1;
            }
            if ($conservation->total_services === null) {
                $conservation->total_services = 12;
            }

        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the service progress in format like "3/12", "01/06"
     */
    public function getServiceProgressAttribute(): string
    {
        $current = str_pad((string) ($this->current_service_number ?? 1), 2, '0', STR_PAD_LEFT);
        $total = str_pad((string) ($this->total_services ?? 12), 2, '0', STR_PAD_LEFT);
        return "{$current}/{$total}";
    }

    /**
     * Get remaining services
     */
    public function getRemainingServicesAttribute(): int
    {
        return max(0, ($this->total_services ?? 12) - ($this->current_service_number ?? 1));
    }
}

