<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Document model — covers two physical tables:
 *   'document'   → ZIN (purchase orders), ZOUT (sales orders)
 *   'z_document' → PO, RO, PN, RN, WO1, CH, SP, VN, AO, RA, PP, RPO, ZD
 *
 * Migrated from: class document in library/lib.inc
 *
 * @property int    $id
 * @property string $num
 * @property string $type
 * @property string $firma
 * @property string $client1
 * @property string $client2
 * @property float  $summa
 * @property float  $summa2
 * @property float  $summa3
 * @property float  $discount
 * @property string $status
 * @property string $data      d-m-Y format
 * @property string $data2
 * @property string $time
 * @property int    $dt        unix timestamp
 * @property string $manager
 * @property string $user
 * @property string $content   base64-encoded note
 * @property string $ttn       Nova Poshta tracking
 * @property string $oplata    cash register id
 * @property string $sklads    warehouse id
 * @property string $reteil    project/supplier id
 * @property string $reestr    register id
 * @property string $numz      parent doc number
 * @property string $typez     parent doc type
 * @property string $docid     parent doc id
 * @property int    $provodka  1 = processed/posted
 * @property int    $dostup    min idstatus to see
 */
class Document extends Model
{
    protected $table    = 'document';
    public    $timestamps = false;

    // Types that live in the main 'document' table
    public const MAIN_TYPES = ['ZIN', 'ZOUT'];

    protected $fillable = [
        'num', 'type', 'firma', 'client1', 'client2',
        'summa', 'summa2', 'summa3', 'discount',
        'status', 'data', 'data2', 'time', 'dt',
        'manager', 'user', 'content', 'ttn',
        'oplata', 'oplata2', 'sklads', 'reteil', 'reestr',
        'numz', 'typez', 'docid', 'docum',
        'provodka', 'dostup', 'work', 'money', 'bonus',
        'numdoc', 'numorder', 'close', 'sms_flag',
        'typeproduct', 'schet',
    ];

    // ── Table routing ─────────────────────────────────────────────────────────

    public static function tableForType(string $type): string
    {
        return in_array($type, self::MAIN_TYPES, true) ? 'document' : 'z_document';
    }

    /** Return a model instance pointing at the correct table for $type */
    public static function forType(string $type): static
    {
        $m = new static();
        $m->setTable(static::tableForType($type));
        return $m;
    }

    // ── Next document number ──────────────────────────────────────────────────

    public static function nextNum(string $type, string $fid, string $year): string
    {
        $table = static::tableForType($type);
        $last  = DB::table($table)
            ->where('client1', '<>', '0')
            ->where('type', $type)
            ->where('firma', $fid)
            ->where('data', 'like', "%{$year}%")
            ->orderByDesc('num')
            ->value('num');
        return $last !== null ? (string)((int)$last + 1) : '1';
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function client()    { return $this->belongsTo(User::class, 'client1'); }
    public function lineItems() { return $this->hasMany(ZBody::class, 'docid'); }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeForFirm(Builder $q, string $fid): Builder
    {
        return $q->where('firma', $fid);
    }

    public function scopeInYear(Builder $q, string $year): Builder
    {
        return $q->where('data', 'like', "%{$year}%");
    }

    /**
     * Access control: user sees docs by their level, name, warehouse, or cashbox.
     * Mirrors legacy: WHERE dostup <= ? OR manager = ? OR sklads = ? OR oplata = ?
     */
    public function scopeAccessible(Builder $q, array $sess): Builder
    {
        return $q->where(function ($s) use ($sess) {
            $s->where('dostup', '<=', $sess['idstatus'])
              ->orWhere('manager', $sess['login'])
              ->orWhere('sklads',  $sess['idsklad']  ?? '')
              ->orWhere('oplata',  $sess['idkassa']  ?? '');
        });
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getContentDecodedAttribute(): string
    {
        return convert_from_base($this->content);
    }

    public function getManagerDecodedAttribute(): string
    {
        return convert_from_base($this->manager);
    }
}
