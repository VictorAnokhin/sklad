<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ZBody — document line items (z_body table)
 * Migrated from: z_body operations in run-doc.php, doc-index.php
 *
 * @property int    $id
 * @property string $docnum   parent doc num
 * @property string $pid      product display id
 * @property string $pnum     product id (comp.id)
 * @property float  $pcount
 * @property float  $pprice
 * @property float  $psumma
 * @property string $type     parent doc type
 * @property string $firma
 * @property string $docid    parent doc id
 */
class ZBody extends Model
{
    protected $table    = 'z_body';
    public    $timestamps = false;

    protected $fillable = [
        'docnum', 'pid', 'pnum', 'pcount',
        'pprice', 'psumma', 'type', 'firma', 'docid',
    ];

    public function document() { return $this->belongsTo(Document::class, 'docid'); }
    public function product()  { return $this->belongsTo(Comp::class, 'pnum'); }

    // ── Upsert helper (replaces run=go in doc-index.php) ─────────────────────

    public static function addOrIncrement(
        string $typez, string $numz, string $pnum,
        string $fid, string $docid, string $pid,
        string $pprice, string $psumma
    ): void {
        $exists = static::where('type',   $typez)
                         ->where('docnum', $numz)
                         ->where('pnum',   $pnum)
                         ->where('firma',  $fid)
                         ->where('docid',  $docid)
                         ->exists();

        if (!$exists) {
            static::create([
                'docnum' => $numz, 'pid'    => $pid,
                'pnum'   => $pnum, 'pcount' => 1,
                'pprice' => $pprice, 'psumma' => $psumma,
                'type'   => $typez, 'firma'  => $fid, 'docid' => $docid,
            ]);
        } else {
            static::where('pnum',  $pnum)
                   ->where('firma', $fid)
                   ->where('docid', $docid)
                   ->increment('pcount');
        }
    }
}
