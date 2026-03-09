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
}