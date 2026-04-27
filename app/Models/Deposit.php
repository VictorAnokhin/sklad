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

    public static function init(string $fid, int $pos = 0, array $filters = []): array
    {
        $baseQuery = DB::table('z_document as d')
            ->where('d.firma', $fid)
            ->where('d.type', 'PP')
            ->where('d.docum', '!=', 'exchange');

        if (($filters['q'] ?? '') !== '') {
            $q = $filters['q'];
            $baseQuery->where(function ($query) use ($q) {
                $query->where('d.num', 'like', "%{$q}%")
                    ->orWhere('d.content', 'like', "%{$q}%");
            });
        }

        if (($filters['mode'] ?? '') !== '' && in_array($filters['mode'], ['topup', 'withdraw'], true)) {
            $baseQuery->where('d.docum', $filters['mode']);
        }

        if (($filters['date_from'] ?? '') !== '') {
            $from = date('d-m-Y', strtotime($filters['date_from']));
            $baseQuery->whereRaw("STR_TO_DATE(d.data, '%d-%m-%Y') >= STR_TO_DATE(?, '%d-%m-%Y')", [$from]);
        }

        if (($filters['date_to'] ?? '') !== '') {
            $to = date('d-m-Y', strtotime($filters['date_to']));
            $baseQuery->whereRaw("STR_TO_DATE(d.data, '%d-%m-%Y') <= STR_TO_DATE(?, '%d-%m-%Y')", [$to]);
        }

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
            ->leftJoin('users as owner_user', 'owner_user.id', '=', 'd.client2')
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
                'd.client2',
                'd.provodka',
                'owner_user.balance as owner_balance',
                'owner_user.name as owner_name',
                'owner_user.secondname as owner_secondname',
                'owner_user.fathername as owner_fathername',
                'owner_user.orgname as owner_orgname',
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
            'client2' => '',
            'provodka' => 0,
            'owner_balance' => 0,
            'owner_name' => '',
            'owner_secondname' => '',
            'owner_fathername' => '',
            'owner_orgname' => '',
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
            'client2' => (string) ($data['client2'] ?? '0'),
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

        $wasPosted = (int) ($doc->provodka ?? 0) === 1;
        $direction = $wasPosted ? -1 : 1;
        $summa = (float) ($doc->summa ?? 0);
        $mode = (string) ($doc->docum ?? 'topup');
        $depositId = (string) ($doc->money ?? '');
        $ownerUserId = trim((string) ($doc->client2 ?? ''));

        DB::transaction(function () use ($fid, $direction, $summa, $mode, $depositId, $ownerUserId, $id, $wasPosted, $doc): void {
            if ($mode === 'topup' && $depositId !== '' && $ownerUserId !== '' && $ownerUserId !== '0') {
                self::shiftUserBalance($fid, $ownerUserId, -1 * $summa * $direction);
                self::shiftConfValue($fid, 'deposit', $depositId, $summa * $direction);
            }

            if ($mode === 'withdraw' && $depositId !== '' && $ownerUserId !== '' && $ownerUserId !== '0') {
                self::shiftConfValue($fid, 'deposit', $depositId, -1 * $summa * $direction);
                self::shiftUserBalance($fid, $ownerUserId, $summa * $direction);
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

    private static function shiftUserBalance(string $fid, string $userId, float $delta): void
    {
        $currentBalance = (float) DB::table('users')
            ->where('id', $userId)
            ->where('firma', $fid)
            ->value('balance');

        DB::table('users')
            ->where('id', $userId)
            ->where('firma', $fid)
            ->update(['balance' => $currentBalance + $delta]);
    }
}
