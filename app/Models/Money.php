<?php

namespace App\Models;

use App\Services\AccountingService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Money — cash documents from z_document
 * PPO = Прихід грошей
 * PRO = Витрата грошей
 */
class Money extends Model
{
    protected $table = 'z_document';
    public $timestamps = false;
    protected $guarded = [];

    // ── init: дані для index ──────────────────────────────────────────────────

    public static function init($fid, $pos = 0, array $filters = [])
    {
        $currentUserId = (string) (Auth::id() ?: session('userid', '0'));

        $baseQuery = DB::table('z_document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->leftJoin('conf as cashbox', function ($join) {
                $join->on('cashbox.id', '=', DB::raw("COALESCE(NULLIF(d.money, ''), NULLIF(d.oplata, ''))"));
            })
            ->leftJoin('conf as payment_type', 'payment_type.id', '=', 'd.reestr')
            ->where('d.firma', $fid)
            ->where('d.client2', $currentUserId)
            ->whereIn('d.type', ['PPO', 'PRO']);

        if (($filters['q'] ?? '') !== '') {
            $q = $filters['q'];
            $baseQuery->where(function ($query) use ($q) {
                $query->where('d.num', 'like', "%{$q}%")
                    ->orWhere('d.content', 'like', "%{$q}%")
                    ->orWhere('u.orgname', 'like', "%{$q}%")
                    ->orWhere('u.name', 'like', "%{$q}%")
                    ->orWhere('u.secondname', 'like', "%{$q}%")
                    ->orWhere('u.phone', 'like', "%{$q}%");
            });
        }

        if (($filters['type'] ?? '') !== '' && in_array($filters['type'], ['PPO', 'PRO'], true)) {
            $baseQuery->where('d.type', $filters['type']);
        }

        if (($filters['money'] ?? '') !== '') {
            $baseQuery->whereRaw("COALESCE(NULLIF(d.money, ''), NULLIF(d.oplata, '')) = ?", [$filters['money']]);
        }

        if (($filters['reestr'] ?? '') !== '') {
            $baseQuery->where('d.reestr', $filters['reestr']);
        }

        if (($filters['date_from'] ?? '') !== '') {
            $from = date('d-m-Y', strtotime($filters['date_from']));
            $baseQuery->whereRaw("STR_TO_DATE(d.data, '%d-%m-%Y') >= STR_TO_DATE(?, '%d-%m-%Y')", [$from]);
        }

        if (($filters['date_to'] ?? '') !== '') {
            $to = date('d-m-Y', strtotime($filters['date_to']));
            $baseQuery->whereRaw("STR_TO_DATE(d.data, '%d-%m-%Y') <= STR_TO_DATE(?, '%d-%m-%Y')", [$to]);
        }

        $documentsQuery = clone $baseQuery;
        $sumPPO = (clone $baseQuery)->where('d.type', 'PPO')->sum('d.summa');
        $sumPRO = (clone $baseQuery)->where('d.type', 'PRO')->sum('d.summa');
        $total = (clone $baseQuery)->count();

        $documents = $documentsQuery
            ->orderByDesc('d.id')
            ->offset($pos)
            ->limit(30)
            ->get([
                'd.id', 'd.num', 'd.type', 'd.data', 'd.time',
                'd.summa', 'd.content', 'd.money', 'd.oplata', 'd.reestr', 'd.provodka',
                'u.name', 'u.name2', 'u.secondname', 'u.orgname', 'u.phone', 'u.city', 'u.region', 'u.poshta',
                DB::raw("COALESCE(NULLIF(d.money, ''), NULLIF(d.oplata, '')) as effective_cashbox_id"),
                'cashbox.name as cashbox_name',
                'payment_type.name as payment_type_name',
            ]);

        $kassasMap = DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $reestrMap = DB::table('conf')
            ->where('type', 'reestr')
            ->where('firma', $fid)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        return compact('documents', 'sumPPO', 'sumPRO', 'total', 'kassasMap', 'reestrMap');
    }

    public static function initTransfers($fid, $pos = 0, array $filters = []): array
    {
        $baseQuery = DB::table('z_document as d')
            ->leftJoin('conf as cashbox_from', function ($join) {
                $join->on('cashbox_from.id', '=', 'd.oplata')
                    ->where('cashbox_from.type', '=', 'oplata');
            })
            ->leftJoin('conf as cashbox_to', function ($join) {
                $join->on('cashbox_to.id', '=', 'd.oplata2')
                    ->where('cashbox_to.type', '=', 'oplata');
            })
            ->where('d.firma', $fid)
            ->where('d.type', 'PP')
            ->where('d.docum', 'exchange');

        if (($filters['q'] ?? '') !== '') {
            $q = $filters['q'];
            $baseQuery->where(function ($query) use ($q) {
                $query->where('d.num', 'like', "%{$q}%")
                    ->orWhere('d.content', 'like', "%{$q}%")
                    ->orWhere('cashbox_from.name', 'like', "%{$q}%")
                    ->orWhere('cashbox_to.name', 'like', "%{$q}%");
            });
        }

        if (($filters['money'] ?? '') !== '') {
            $baseQuery->where(function ($query) use ($filters) {
                $query->where('d.oplata', $filters['money'])
                    ->orWhere('d.oplata2', $filters['money']);
            });
        }

        if (($filters['date_from'] ?? '') !== '') {
            $from = date('d-m-Y', strtotime($filters['date_from']));
            $baseQuery->whereRaw("STR_TO_DATE(d.data, '%d-%m-%Y') >= STR_TO_DATE(?, '%d-%m-%Y')", [$from]);
        }

        if (($filters['date_to'] ?? '') !== '') {
            $to = date('d-m-Y', strtotime($filters['date_to']));
            $baseQuery->whereRaw("STR_TO_DATE(d.data, '%d-%m-%Y') <= STR_TO_DATE(?, '%d-%m-%Y')", [$to]);
        }

        $documentsQuery = clone $baseQuery;
        $sumTransfers = (float) (clone $baseQuery)->sum('d.summa');
        $total = (clone $baseQuery)->count();

        $documents = $documentsQuery
            ->orderByDesc('d.id')
            ->offset($pos)
            ->limit(30)
            ->get([
                'd.id',
                'd.num',
                'd.type',
                'd.docum',
                'd.data',
                'd.time',
                'd.summa',
                'd.content',
                'd.oplata',
                'd.oplata2',
                'd.provodka',
                'cashbox_from.name as from_cashbox_name',
                'cashbox_to.name as to_cashbox_name',
            ]);

        $kassasMap = DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        return compact('documents', 'sumTransfers', 'total', 'kassasMap');
    }

    // ── find: один документ + дані клієнта ───────────────────────────────────

    public static function find($id, $fid)
    {
        return DB::table('z_document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->leftJoin('users as owner_user', 'owner_user.id', '=', 'd.client2')
            ->where('d.id', $id)
            ->where('d.firma', $fid)
            ->whereIn('d.type', ['PPO', 'PRO'])
            ->first([
                'd.id', 'd.num', 'd.type', 'd.data', 'd.time',
                'd.summa', 'd.content', 'd.money', 'd.oplata', 'd.reestr', 'd.provodka', 'd.client1', 'd.client2',
                DB::raw("COALESCE(NULLIF(d.money, ''), NULLIF(d.oplata, '')) as effective_cashbox_id"),
                'u.name', 'u.name2', 'u.secondname', 'u.orgname', 'u.phone', 'u.city', 'u.region', 'u.poshta',
                'u.balance as client_balance',
                'owner_user.name as owner_name',
                'owner_user.secondname as owner_secondname',
                'owner_user.fathername as owner_fathername',
                'owner_user.orgname as owner_orgname',
                'owner_user.balance as owner_balance',
            ]);
    }

    // ── emptyDocument: порожній об'єкт для нового документа ──────────────────

    public static function emptyDocument($type = 'PPO')
    {
        return (object)[
            'id'         => 0,
            'num'        => '',
            'type'       => in_array($type, ['PPO', 'PRO'], true) ? $type : 'PPO',
            'data'       => date('d-m-Y'),
            'time'       => '',
            'summa'      => 0,
            'content'    => '',
            'money'      => 0,
            'oplata'     => '',
            'effective_cashbox_id' => '',
            'reestr'     => '',
            'provodka'   => 0,
            'client1'    => '',
            'client2'    => '',
            'name'       => '',
            'name2'      => '',
            'secondname' => '',
            'orgname'    => '',
            'phone'      => '',
            'city'       => '',
            'client_balance'    => 0,
            'owner_balance'    => 0,
        ];
    }

    public static function emptyTransferDocument(): object
    {
        return (object) [
            'id' => 0,
            'num' => '',
            'type' => 'PP',
            'docum' => 'exchange',
            'data' => date('d-m-Y'),
            'time' => '',
            'summa' => 0,
            'content' => '',
            'oplata' => '',
            'oplata2' => '',
            'provodka' => 0,
        ];
    }

    // ── kassas: список кас з conf ─────────────────────────────────────────────

    public static function kassas($fid, ?string $selectedId = null)
    {
        return DB::table('conf')
            ->where(function ($query) use ($fid, $selectedId) {
                $query->where(function ($cashboxQuery) use ($fid) {
                    $cashboxQuery->where('type', 'oplata')
                        ->where('firma', $fid);
                });

                if (trim((string) $selectedId) !== '') {
                    $query->orWhere('id', $selectedId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ── save: insert або update ───────────────────────────────────────────────

    public static function saveDocument($id, $fid, $data): int
    {
        $data['firma'] = $fid;
        $data['money'] = (string)($data['money'] ?? '');
        $data['oplata'] = (string)($data['oplata'] ?? $data['money']);
        $data['reestr'] = (string)($data['reestr'] ?? '');
        $data['content'] = (string)($data['content'] ?? '');
        $data['client1'] = (string)($data['client1'] ?? '0');
        $data['client2'] = (string)($data['client2'] ?? '0');
        $data['summa'] = (float)($data['summa'] ?? 0);
        $data['data'] = curdate((string) ($data['data'] ?? date('d-m-Y')));

        if ($id === 0) {
            $maxNum = DB::table('z_document')
                ->where('firma', $fid)
                ->where('type', $data['type'])
                ->max(DB::raw('CAST(num AS UNSIGNED)'));

            $data['num']  = $maxNum ? (int)$maxNum + 1 : 1;
            $data['time'] = date('H:i:s');

            return (int) DB::table('z_document')->insertGetId($data);
        } else {
            DB::table('z_document')
                ->where('id', $id)
                ->where('firma', $fid)
                ->update($data);

            return (int) $id;
        }
    }

    // ── delete ────────────────────────────────────────────────────────────────

    public static function deleteDocument($id, $fid)
    {
        DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->whereIn('type', ['PPO', 'PRO'])
            ->delete();
    }

    public static function findTransfer(int $id, string $fid)
    {
        return DB::table('z_document as d')
            ->leftJoin('conf as cashbox_from', function ($join) {
                $join->on('cashbox_from.id', '=', 'd.oplata')
                    ->where('cashbox_from.type', '=', 'oplata');
            })
            ->leftJoin('conf as cashbox_to', function ($join) {
                $join->on('cashbox_to.id', '=', 'd.oplata2')
                    ->where('cashbox_to.type', '=', 'oplata');
            })
            ->where('d.id', $id)
            ->where('d.firma', $fid)
            ->where('d.type', 'PP')
            ->where('d.docum', 'exchange')
            ->first([
                'd.id',
                'd.num',
                'd.type',
                'd.docum',
                'd.data',
                'd.time',
                'd.summa',
                'd.content',
                'd.oplata',
                'd.oplata2',
                'd.provodka',
                'cashbox_from.name as from_cashbox_name',
                'cashbox_to.name as to_cashbox_name',
            ]);
    }

    public static function saveTransferDocument(int $id, string $fid, array $data): int
    {
        return Deposit::saveDocument($id, $fid, [
            'summa' => (float) ($data['summa'] ?? 0),
            'content' => (string) ($data['content'] ?? ''),
            'data' => (string) ($data['data'] ?? date('d-m-Y')),
            'docum' => 'exchange',
            'oplata' => (string) ($data['oplata'] ?? ''),
            'oplata2' => (string) ($data['oplata2'] ?? ''),
            'money' => '',
        ]);
    }

    public static function deleteTransferDocument(int $id, string $fid): void
    {
        DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->where('type', 'PP')
            ->where('docum', 'exchange')
            ->delete();
    }

    // ── provodka ─────────────────────────────────────────────────────────────

    public static function provodka(int $id, string $fid): array
    {
        $doc = DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->whereIn('type', ['PPO', 'PRO'])
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
        $targetClientId = trim((string) ($doc->client1 ?? ''));
        $ownerUserId = trim((string) ($doc->client2 ?? ''));
        $ownerDelta = ($doc->type === 'PPO' ? 1 : -1) * $summa * $direction;
        $targetDelta = -1 * $ownerDelta;

        DB::transaction(function () use ($ownerUserId, $targetClientId, $fid, $ownerDelta, $targetDelta, $id, $wasPosted, $doc) {
            if ($ownerUserId !== '' && $ownerUserId !== '0') {
                $ownerBalance = (float) DB::table('users')
                    ->where('id', $ownerUserId)
                    ->where('firma', $fid)
                    ->value('balance');

                DB::table('users')
                    ->where('id', $ownerUserId)
                    ->where('firma', $fid)
                    ->update(['balance' => $ownerBalance + $ownerDelta]);
            }

            if ($targetClientId !== '' && $targetClientId !== '0') {
                $targetBalance = (float) DB::table('users')
                    ->where('id', $targetClientId)
                    ->where('firma', $fid)
                    ->value('balance');

                DB::table('users')
                    ->where('id', $targetClientId)
                    ->where('firma', $fid)
                    ->update(['balance' => $targetBalance + $targetDelta]);
            }

            app(AccountingService::class)->createDocumentTransaction(
                'z_document:money_order',
                $id,
                (string) ($doc->type ?? ''),
                $doc,
                [],
                $fid,
                $wasPosted
            );

            DB::table('z_document')
                ->where('id', $id)
                ->where('firma', $fid)
                ->whereIn('type', ['PPO', 'PRO'])
                ->update(['provodka' => $wasPosted ? 0 : 1]);
        });

        $fresh = DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->whereIn('type', ['PPO', 'PRO'])
            ->first();

        return [
            'document' => $fresh,
            'isPosted' => !$wasPosted,
        ];
    }

    public static function provodkaTransfer(int $id, string $fid): array
    {
        $doc = self::findTransfer($id, $fid);
        if (!$doc) {
            return [
                'document' => null,
                'isPosted' => false,
            ];
        }

        return Deposit::provodka($id, $fid);
    }
}
