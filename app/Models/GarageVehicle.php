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
        'garage_photo_1',
        'garage_photo_2',
        'garage_photo_3',
        'garage_photo_4',
        'garage_photo_5',
        'vehicle_price',
        'adv_link',
        'characteristics',
        'autoria_payload',
        'autoria_status',
        'checked_at',
    ];

    protected $casts = [
        'characteristics' => 'array',
        'autoria_payload' => 'array',
        'vehicle_price' => 'decimal:2',
        'checked_at' => 'datetime',
    ];
}
