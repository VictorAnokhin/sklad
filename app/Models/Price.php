<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Price — price groups per product per firm
 *
 * @property int    $id
 * @property string $pnum     comp.id
 * @property string $cod      comp.cod
 * @property string $firma
 * @property string $tgroup   price group id (conf.id where type='tgroup')
 * @property string $idagent
 * @property float  $pay      current price
 * @property float  $pay1     retail price
 * @property float  $oldpay   old price for strikethrough display
 * @property int    $count    stock count
 * @property string $sklad    '1'|'0' available in warehouse
 */
class Price extends Model
{
    protected $table    = 'price';
    public    $timestamps = false;

    protected $fillable = [
        'pnum', 'cod', 'firma', 'tgroup', 'idagent',
        'pay', 'pay1', 'oldpay', 'count', 'sklad',
    ];

    public function comp() { return $this->belongsTo(Comp::class, 'pnum'); }
}
