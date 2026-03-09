<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Conf — universal classifier/config table
 * Types used in project:
 *   status, reteil, oplata, sklads, reestr, tgroup, typeproduct, money
 *
 * @property int    $id
 * @property string $type
 * @property string $name     base64-encoded
 * @property string $color    hex color for status badges
 * @property string $status   min idstatus to see
 * @property string $firma
 * @property string $constanta '1' = visible to all firms
 * @property string $vision    '1' = active/visible
 * @property string $hide      '1' = hidden in ZOUT filter by default
 */
class Conf extends Model
{
    protected $table    = 'conf';
    public    $timestamps = false;

    protected $fillable = [
        'type', 'name', 'color', 'status', 'firma',
        'constanta', 'vision', 'hide',
    ];

    public function getNameDecodedAttribute(): string
    {
        return convert_from_base($this->name);
    }

    public function scopeOfType($q, string $type) { return $q->where('type', $type); }
    public function scopeVisible($q)               { return $q->where('vision', '1'); }
    public function scopeForFirm($q, string $fid)
    {
        return $q->where(function ($s) use ($fid) {
            $s->where('firma', $fid)->orWhere('constanta', '1');
        });
    }
}
