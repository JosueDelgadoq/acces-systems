<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'model',
        'type',
        'description',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function stock()
    {
        return $this->hasOne(Stock::class);
    }
}