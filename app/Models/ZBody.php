<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ZBody extends Model
{
    protected $table = 'z_body';
    public $timestamps = false;
    protected $guarded = [];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public function doc()
    {
        return $this->belongsTo(ZDocument::class, 'docid');
    }

    public static function addOrIncrement($typez, $numz, $pnum, $fid, $docid, $pid, $pprice, $psumma): void
    {
        $existing = self::where('docid', $docid)
            ->where('pnum', $pnum)
            ->where('firma', $fid)
            ->first();

        if ($existing) {
            $nextCount = (float) ($existing->pcount ?? 0) + 1;
            $nextPrice = (float) $pprice;
            $update = [
                'pcount' => $nextCount,
                'pprice' => $nextPrice,
                'psumma' => $nextCount * $nextPrice,
            ];

            if ((string) $typez === 'RN') {
                $update['zvalue'] = (string) ($existing->zvalue ?? '') !== ''
                    ? (string) $existing->zvalue
                    : self::resolveUnitCost($pnum, $fid);
            }

            $existing->update($update);
            return;
        }

        $payload = [
            'docnum' => $numz,
            'pid' => $pid,
            'pnum' => $pnum,
            'pcount' => 1,
            'pprice' => (float) $pprice,
            'psumma' => (float) $psumma,
            'type' => $typez,
            'firma' => $fid,
            'docid' => $docid,
            'zvalue' => '',
        ];

        if ((string) $typez === 'RN') {
            $payload['zvalue'] = self::resolveUnitCost($pnum, $fid);
        }

        self::create($payload);
    }

    public static function resolveUnitCost($pnum, $fid): string
    {
        $costColumn = Schema::hasColumn('price', 'pay0') ? 'pay0' : 'pay';
        $cost = DB::table('price')
            ->where('pnum', $pnum)
            ->where('firma', $fid)
            ->value($costColumn);

        return number_format((float) ($cost ?? 0), 2, '.', '');
    }

}
