<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
public function clients()
{
    return $this->belongsToMany(Client::class);
}
}
