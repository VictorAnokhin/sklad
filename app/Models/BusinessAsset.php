<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessAsset extends Model
{
    protected $fillable = [
        'fid',
        'asset_type_id',
        'type',
        'name',
        'currency',
        'initial_cost',
        'current_value',
        'accumulated_depreciation',
        'acquired_at',
        'disposed_at',
        'status',
        'description',
    ];

    protected $casts = [
        'fid' => 'integer',
        'asset_type_id' => 'integer',
        'initial_cost' => 'decimal:2',
        'current_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'acquired_at' => 'date',
        'disposed_at' => 'date',
    ];

    public function operations()
    {
        return $this->hasMany(AssetOperation::class);
    }

    public static function typeOptions(): array
    {
        return [
            'equipment' => 'Оборудование',
            'real_estate' => 'Недвижимость',
            'securities' => 'Ценные бумаги',
            'crypto' => 'Криптоактивы',
            'software_rd' => 'Разработка ПО / R&D',
        ];
    }

    public static function typeLabel(?string $type): string
    {
        return self::typeOptions()[$type ?: ''] ?? 'Оборудование';
    }
}
