<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'notes',
        'address',
        'city',
        'state',
        'postal_code',
        'address_notes',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function conservations()
    {
        return $this->hasMany(Conservation::class);
    }

    public function habilitations()
    {
        return $this->hasMany(Habilitation::class);
    }

    public function technicalBudgets()
    {
        return $this->hasMany(TechnicalBudget::class);
    }

    public function equipmentDeliveries()
    {
        return $this->hasMany(EquipmentDelivery::class);
    }

    public function billingControls()
    {
        return $this->hasMany(BillingControl::class);
    }

    /**
     * Get full address as a single string
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
        ]);

        return implode(', ', $parts) ?: 'Sin dirección';
    }
}
