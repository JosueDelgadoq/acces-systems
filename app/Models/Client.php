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
}