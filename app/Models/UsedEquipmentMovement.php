<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsedEquipmentMovement extends Model
{
    protected $fillable = [
        'used_equipment_id',
        'user_id',
        'movement_type',
        'description',
        'from_status',
        'to_status',
        'from_location',
        'to_location',
        'parts_before',
        'parts_after',
        'meta',
    ];

    protected $casts = [
        'parts_before' => 'array',
        'parts_after' => 'array',
        'meta' => 'array',
    ];

    public function equipment()
    {
        return $this->belongsTo(UsedEquipment::class, 'used_equipment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
