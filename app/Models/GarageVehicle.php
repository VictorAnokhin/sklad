<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GarageVehicle extends Model
{
    protected $fillable = [
        'user_id',
        'fid',
        'email',
        'vehicle_number',
        'vin',
        'input_value',
        'input_type',
        'title',
        'photo_url',
        'adv_link',
        'characteristics',
        'autoria_payload',
        'autoria_status',
        'checked_at',
    ];

    protected $casts = [
        'characteristics' => 'array',
        'autoria_payload' => 'array',
        'checked_at' => 'datetime',
    ];
}
