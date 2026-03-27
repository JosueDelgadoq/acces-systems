<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = ['nombre', 'tipo'];

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class);
    }
}