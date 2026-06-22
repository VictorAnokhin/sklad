<?php

namespace App\Models;

use App\Services\AccountingService;
use App\Support\HoldingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

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

        $total = (clone $baseQuery)->count();
        $sumPP = (clone $baseQuery)->sum('d.summa');
        $depositTotals = (clone $baseQuery)
            ->select('d.money', DB::raw('SUM(d.summa) as total_sum'), DB::raw('COUNT(*) as docs_count'))
            ->groupBy('d.money')
            ->orderByDesc('total_sum')
            ->get()
            ->map(function ($row) use ($depositMap) {
                $depositId = (string) ($row->money ?? '');

                return (object) [
                    'id' => $depositId,
                    'name' => $depositMap[$depositId] ?? ($depositId !== '' ? $depositId : '—'),
                    'total_sum' => (float) ($row->total_sum ?? 0),
                    'docs_count' => (int) ($row->docs_count ?? 0),
                ];
            });

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

        return compact('documents', 'sumPP', 'total', 'oplataMap', 'depositMap', 'depositTotals');
    }

    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public static function find(int $id, string $fid)
    {
        $columns = [
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
        ];

        if (Schema::hasColumn('z_document', 'currency_from')) {
            $columns[] = 'd.currency_from';
        }

        return DB::table('z_document as d')
            ->leftJoin('users as owner_user', 'owner_user.id', '=', 'd.client2')
            ->where('d.id', $id)
            ->where('d.firma', $fid)
            ->where('d.type', 'PP')
            ->first($columns);
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
            'currency_from' => 'UAH',
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
            ->get(array_filter(['id', 'name', 'value', Schema::hasColumn('conf', 'currency') ? 'currency' : null]));
    }

    public static function deposits(string $fid)
    {
        $hasDoc = Schema::hasColumn('conf', 'doc');

        return DB::table('conf')
            ->where('type', 'deposit')
            ->where(function ($query) use ($fid, $hasDoc): void {
                $query->where('firma', $fid);

                if ($hasDoc) {
                    $query->orWhere(function ($bankQuery) use ($fid): void {
                        $bankQuery
                            ->where('doc', 'bank')
                            ->whereIn('firma', array_map('intval', HoldingScope::projectIdsFor($fid)));
                    });
                }
            })
            ->orderBy('name')
            ->get(array_filter(['id', 'name', 'value', 'firma', Schema::hasColumn('conf', 'currency') ? 'currency' : null, Schema::hasColumn('conf', 'doc') ? 'doc' : null]))
            ->map(function ($deposit) {
                $deposit->currency = self::normalizeCurrency($deposit->currency ?? 'UAH');
                $deposit->deposit_type = (string) ($deposit->doc ?? '') === 'bank' ? 'bank' : 'personal';

                return $deposit;
            });
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
        if (Schema::hasColumn('z_document', 'currency_from')) {
            $payload['currency_from'] = self::normalizeCurrency($data['currency_from'] ?? 'UAH');
        }

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
        $currency = self::normalizeCurrency($doc->currency_from ?? self::depositCurrency($fid, $depositId));

        try {
            DB::transaction(function () use ($fid, $direction, $summa, $mode, $depositId, $ownerUserId, $currency, $id, $wasPosted, $doc): void {
                if ($mode === 'topup' && $depositId !== '' && $ownerUserId !== '' && $ownerUserId !== '0') {
                    if (!$wasPosted) {
                        Money::assertUserBalanceAvailable($fid, $ownerUserId, $currency, $summa);
                    }

                    Money::shiftUserBalance($fid, $ownerUserId, -1 * $summa * $direction, $currency);
                    self::shiftDepositValue($fid, $depositId, $summa * $direction);
                }

                if ($mode === 'withdraw' && $depositId !== '' && $ownerUserId !== '' && $ownerUserId !== '0') {
                    self::shiftDepositValue($fid, $depositId, -1 * $summa * $direction);
                    Money::shiftUserBalance($fid, $ownerUserId, $summa * $direction, $currency);
                }

                if ($mode === 'exchange') {
                    $fromCashboxId = trim((string) ($doc->oplata ?? ''));
                    $toCashboxId = trim((string) ($doc->oplata2 ?? ''));

                    if ($fromCashboxId !== '' && $toCashboxId !== '') {
                        self::shiftConfValue($fid, 'oplata', $fromCashboxId, -1 * $summa * $direction);
                        self::shiftConfValue($fid, 'oplata', $toCashboxId, $summa * $direction);
                    }
                }

                $ledger = app(AccountingService::class)->createDocumentTransaction(
                    'z_document:deposit_operation',
                    $id,
                    'PP',
                    $doc,
                    [],
                    $fid,
                    $wasPosted
                );
                FundPoolOperation::recordDepositDocument((int) $fid, $doc, $ledger?->id, $wasPosted);

                DB::table('z_document')
                    ->where('id', $id)
                    ->where('firma', $fid)
                    ->where('type', 'PP')
                    ->update(['provodka' => $wasPosted ? 0 : 1]);
            });
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            if (! str_starts_with($message, 'Недостатньо') && $message !== 'Користувача балансу не знайдено') {
                throw $exception;
            }

            return [
                'document' => $doc,
                'isPosted' => $wasPosted,
                'error' => $message,
            ];
        }

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

    private static function shiftDepositValue(string $fid, string $depositId, float $delta): void
    {
        $deposit = self::depositQuery($fid, $depositId)
            ->lockForUpdate()
            ->first(['id', 'firma', 'value']);

        if (!$deposit) {
            return;
        }

        DB::table('conf')
            ->where('id', $deposit->id)
            ->where('type', 'deposit')
            ->where('firma', $deposit->firma)
            ->update(['value' => (float) ($deposit->value ?? 0) + $delta]);
    }

    public static function depositCurrency(string $fid, string $depositId): string
    {
        if ($depositId === '') {
            return 'UAH';
        }

        if (str_starts_with($depositId, 'pool:')) {
            return self::poolCurrency($depositId);
        }

        if (!Schema::hasColumn('conf', 'currency')) {
            return 'UAH';
        }

        $currency = DB::table('conf')
            ->fromSub(self::depositQuery($fid, $depositId), 'deposit_scope')
            ->value('currency');

        return self::normalizeCurrency($currency ?? 'UAH');
    }

    private static function poolCurrency(string $assetKey): string
    {
        if (!Schema::hasTable('fund_pools')) {
            return 'USDC';
        }

        $poolId = (int) substr($assetKey, strlen('pool:'));
        if ($poolId <= 0) {
            return 'USDC';
        }

        $pool = DB::table('fund_pools')
            ->where('id', $poolId)
            ->first();
        if (!$pool) {
            return 'USDC';
        }

        $symbol = (string) ($pool->symbol ?? '');
        if ($symbol === '') {
            $coinTypeParts = explode('::', (string) ($pool->coin_type ?? ''));
            $symbol = (string) end($coinTypeParts);
        }

        return self::normalizeCurrency($symbol !== '' ? $symbol : 'USDC');
    }

    private static function depositQuery(string $fid, string $depositId)
    {
        $hasDoc = Schema::hasColumn('conf', 'doc');

        return DB::table('conf')
            ->where('id', $depositId)
            ->where('type', 'deposit')
            ->where(function ($query) use ($fid, $hasDoc): void {
                $query->where('firma', $fid);

                if ($hasDoc) {
                    $query->orWhere(function ($bankQuery) use ($fid): void {
                        $bankQuery
                            ->where('doc', 'bank')
                            ->whereIn('firma', array_map('intval', HoldingScope::projectIdsFor($fid)));
                    });
                }
            });
    }

    private static function normalizeCurrency(mixed $value): string
    {
        $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $value) ?? '');

        return $currency !== '' ? substr($currency, 0, 10) : 'UAH';
    }

    private static function formatBalanceAmount(float $amount): string
    {
        $formatted = number_format($amount, 8, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        if ($formatted === '' || $formatted === '-0') {
            return '0';
        }

        return $formatted;
    }
}
