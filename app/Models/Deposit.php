<?php

namespace App\Models;

use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Deposit extends Model
{
    protected $table = 'z_document';
    public $timestamps = false;
    protected $guarded = [];

    public static function init(string $fid, int $pos = 0): array
    {
        $baseQuery = DB::table('z_document as d')
            ->where('d.firma', $fid)
            ->where('d.type', 'PP');

        $total = (clone $baseQuery)->count();
        $sumPP = (clone $baseQuery)->sum('d.summa');

        $documents = (clone $baseQuery)
            ->orderByDesc('d.id')
            ->offset($pos)
            ->limit(30)
            ->get([
                'd.id',
                'd.num',
                'd.type',
                'd.data',
                'd.time',
                'd.summa',
                'd.content',
                'd.docum',
                'd.oplata',
                'd.oplata2',
                'd.money',
                'd.provodka',
            ]);

        $oplataMap = DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->orderBy('name')
            ->pluck('name', 'id');

        $depositMap = DB::table('conf')
            ->where('type', 'deposit')
            ->where('firma', $fid)
            ->orderBy('name')
            ->pluck('name', 'id');

        return compact('documents', 'sumPP', 'total', 'oplataMap', 'depositMap');
    }

    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public static function find(int $id, string $fid)
    {
        return DB::table('z_document as d')
            ->where('d.id', $id)
            ->where('d.firma', $fid)
            ->where('d.type', 'PP')
            ->first([
                'd.id',
                'd.num',
                'd.type',
                'd.data',
                'd.time',
                'd.summa',
                'd.content',
                'd.docum',
                'd.oplata',
                'd.oplata2',
                'd.money',
                'd.provodka',
            ]);
    }

    public static function emptyDocument(): object
    {
        return (object) [
            'id' => 0,
            'num' => '',
            'type' => 'PP',
            'data' => date('d-m-Y'),
            'time' => '',
            'summa' => 0,
            'content' => '',
            'docum' => 'topup',
            'oplata' => '',
            'oplata2' => '',
            'money' => '',
            'provodka' => 0,
        ];
    }

    public static function oplatas(string $fid)
    {
        return DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get(['id', 'name', 'value']);
    }

    public static function deposits(string $fid)
    {
        return DB::table('conf')
            ->where('type', 'deposit')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get(['id', 'name', 'value']);
    }

    public static function saveDocument(int $id, string $fid, array $data): int
    {
        $payload = [
            'type' => 'PP',
            'firma' => $fid,
            'summa' => (float) ($data['summa'] ?? 0),
            'content' => (string) ($data['content'] ?? ''),
            'data' => curdate((string) ($data['data'] ?? date('d-m-Y'))),
            'docum' => (string) ($data['docum'] ?? 'topup'),
            'oplata' => (string) ($data['oplata'] ?? ''),
            'oplata2' => (string) ($data['oplata2'] ?? ''),
            'money' => (string) ($data['money'] ?? ''),
            'client1' => '0',
        ];

        if ($id === 0) {
            $maxNum = DB::table('z_document')
                ->where('firma', $fid)
                ->where('type', 'PP')
                ->max(DB::raw('CAST(num AS UNSIGNED)'));

            $payload['num'] = $maxNum ? (int) $maxNum + 1 : 1;
            $payload['time'] = date('H:i:s');

            return (int) DB::table('z_document')->insertGetId($payload);
        }

        DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->where('type', 'PP')
            ->update($payload);

        return $id;
    }

    public static function deleteDocument(int $id, string $fid): void
    {
        DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->where('type', 'PP')
            ->delete();
    }

    public static function provodka(int $id, string $fid): array
    {
        $doc = DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->where('type', 'PP')
            ->first();

        if (!$doc) {
            return [
                'document' => null,
                'isPosted' => false,
            ];
        }

        $confColumns = Schema::getColumnListing('conf');
        if (!in_array('value', $confColumns, true)) {
            return [
                'document' => $doc,
                'isPosted' => (int) ($doc->provodka ?? 0) === 1,
            ];
        }

        $wasPosted = (int) ($doc->provodka ?? 0) === 1;
        $direction = $wasPosted ? -1 : 1;
        $summa = (float) ($doc->summa ?? 0);
        $mode = (string) ($doc->docum ?? 'topup');
        $oplataId = (string) ($doc->oplata ?? '');
        $oplata2Id = (string) ($doc->oplata2 ?? '');
        $depositId = (string) ($doc->money ?? '');

        DB::transaction(function () use ($fid, $direction, $summa, $mode, $oplataId, $oplata2Id, $depositId, $id, $wasPosted, $doc): void {
            if ($mode === 'topup' && $oplataId !== '' && $depositId !== '') {
                self::shiftConfValue($fid, 'oplata', $oplataId, -1 * $summa * $direction);
                self::shiftConfValue($fid, 'deposit', $depositId, $summa * $direction);
            }

            if ($mode === 'withdraw' && $depositId !== '' && $oplata2Id !== '') {
                self::shiftConfValue($fid, 'deposit', $depositId, -1 * $summa * $direction);
                self::shiftConfValue($fid, 'oplata', $oplata2Id, $summa * $direction);
            }

            if ($mode === 'exchange' && $oplataId !== '' && $oplata2Id !== '') {
                self::shiftConfValue($fid, 'oplata', $oplataId, -1 * $summa * $direction);
                self::shiftConfValue($fid, 'oplata', $oplata2Id, $summa * $direction);
            }

            app(AccountingService::class)->createDocumentTransaction(
                'z_document:deposit_operation',
                $id,
                'PP',
                $doc,
                [],
                $fid,
                $wasPosted
            );

            DB::table('z_document')
                ->where('id', $id)
                ->where('firma', $fid)
                ->where('type', 'PP')
                ->update(['provodka' => $wasPosted ? 0 : 1]);
        });

        $fresh = DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->where('type', 'PP')
            ->first();

        return [
            'document' => $fresh,
            'isPosted' => !$wasPosted,
        ];
    }

    private static function shiftConfValue(string $fid, string $type, string $id, float $delta): void
    {
        $currentValue = (float) DB::table('conf')
            ->where('id', $id)
            ->where('type', $type)
            ->where('firma', $fid)
            ->value('value');

        DB::table('conf')
            ->where('id', $id)
            ->where('type', $type)
            ->where('firma', $fid)
            ->update(['value' => $currentValue + $delta]);
    }
}
