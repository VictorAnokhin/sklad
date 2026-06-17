<?php

namespace App\Models;

use App\Services\AccountingService;
use App\Support\HoldingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

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
            ->whereIn('d.type', ['PPO', 'PRO', 'PPP']);

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

        if (($filters['type'] ?? '') !== '' && in_array($filters['type'], ['PPO', 'PRO', 'PPP'], true)) {
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

        $columns = [
            'd.id', 'd.num', 'd.type', 'd.data', 'd.time',
            'd.summa', 'd.content', 'd.money', 'd.oplata', 'd.reestr', 'd.provodka',
            'u.name', 'u.name2', 'u.secondname', 'u.orgname', 'u.phone', 'u.city', 'u.region', 'u.poshta', 'u.idstatus',
            DB::raw("COALESCE(NULLIF(d.money, ''), NULLIF(d.oplata, '')) as effective_cashbox_id"),
            'cashbox.name as cashbox_name',
            'payment_type.name as payment_type_name',
        ];
        foreach (['summa2', 'currency_from', 'currency_to', 'exchange_rate'] as $column) {
            if (Schema::hasColumn('z_document', $column)) {
                $columns[] = "d.{$column}";
            }
        }

        $documents = $documentsQuery
            ->orderByDesc('d.id')
            ->offset($pos)
            ->limit(30)
            ->get($columns);

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
                'd.summa2',
                'd.currency_from',
                'd.currency_to',
                'd.exchange_rate',
                'd.commission_amount',
                'd.commission_currency',
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
        $columns = [
            'd.id', 'd.num', 'd.type', 'd.data', 'd.time',
            'd.summa', 'd.content', 'd.money', 'd.oplata', 'd.reestr', 'd.provodka', 'd.client1', 'd.client2',
            DB::raw("COALESCE(NULLIF(d.money, ''), NULLIF(d.oplata, '')) as effective_cashbox_id"),
            'u.name', 'u.name2', 'u.secondname', 'u.orgname', 'u.phone', 'u.city', 'u.region', 'u.poshta', 'u.idstatus',
            'u.balance as client_balance',
            'owner_user.name as owner_name',
            'owner_user.secondname as owner_secondname',
            'owner_user.fathername as owner_fathername',
            'owner_user.orgname as owner_orgname',
            'owner_user.balance as owner_balance',
        ];

        foreach (['summa2', 'currency_from', 'currency_to', 'exchange_rate'] as $column) {
            if (Schema::hasColumn('z_document', $column)) {
                $columns[] = "d.{$column}";
            }
        }

        return DB::table('z_document as d')
            ->leftJoin('users as u', 'u.id', '=', 'd.client1')
            ->leftJoin('users as owner_user', 'owner_user.id', '=', 'd.client2')
            ->where('d.id', $id)
            ->where('d.firma', $fid)
            ->whereIn('d.type', ['PPO', 'PRO', 'PPP'])
            ->first($columns);
    }

    // ── emptyDocument: порожній об'єкт для нового документа ──────────────────

    public static function emptyDocument($type = 'PPO')
    {
        return (object)[
            'id'         => 0,
            'num'        => '',
            'type'       => in_array($type, ['PPO', 'PRO', 'PPP'], true) ? $type : 'PPO',
            'data'       => date('d-m-Y'),
            'time'       => '',
            'summa'      => 0,
            'summa2'     => 0,
            'exchange_rate' => 1,
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
            'currency_from' => 'UAH',
            'currency_to' => 'USD',
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
            'summa2' => 0,
            'currency_from' => 'UAH',
            'currency_to' => 'UAH',
            'exchange_rate' => 1,
            'commission_amount' => 0,
            'commission_currency' => 'UAH',
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
            ->get(array_filter([
                'id',
                'name',
                'value',
                Schema::hasColumn('conf', 'currency') ? 'currency' : null,
            ]))
            ->map(function ($cashbox) {
                $cashbox->balance = (float) ($cashbox->value ?? 0);
                $cashbox->currency = self::currencyForCashboxRow($cashbox) ?? 'UAH';

                return $cashbox;
            });
    }

    // ── save: insert або update ───────────────────────────────────────────────

    public static function saveDocument($id, $fid, $data): int
    {
        $data['firma'] = $fid;
        $data['money'] = (string)($data['money'] ?? '');
        $data['docum'] = (string)($data['docum'] ?? '');
        $data['oplata'] = (string)($data['oplata'] ?? $data['money']);
        $data['reestr'] = (string)($data['reestr'] ?? '');
        $data['content'] = (string)($data['content'] ?? '');
        $data['client1'] = (string)($data['client1'] ?? '0');
        $data['client2'] = (string)($data['client2'] ?? '0');
        $data['summa'] = (float)($data['summa'] ?? 0);
        $data['data'] = curdate((string) ($data['data'] ?? date('d-m-Y')));
        if (Schema::hasColumn('z_document', 'summa2')) {
            $data['summa2'] = (float) ($data['summa2'] ?? 0);
        } else {
            unset($data['summa2']);
        }
        if (Schema::hasColumn('z_document', 'exchange_rate')) {
            $data['exchange_rate'] = round((float) ($data['exchange_rate'] ?? 1), 8);
        } else {
            unset($data['exchange_rate']);
        }
        if (Schema::hasColumn('z_document', 'currency_from')) {
            $data['currency_from'] = self::normalizeCurrency($data['currency_from'] ?? 'UAH');
        } else {
            unset($data['currency_from']);
        }
        if (Schema::hasColumn('z_document', 'currency_to')) {
            $data['currency_to'] = self::normalizeCurrency($data['currency_to'] ?? $data['currency_from'] ?? 'UAH');
        } else {
            unset($data['currency_to']);
        }

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
            ->whereIn('type', ['PPO', 'PRO', 'PPP'])
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
                'd.summa2',
                'd.currency_from',
                'd.currency_to',
                'd.exchange_rate',
                'd.commission_amount',
                'd.commission_currency',
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
        $cashboxCurrencies = self::resolveTransferCashboxCurrencies(
            $fid,
            (string) ($data['oplata'] ?? ''),
            (string) ($data['oplata2'] ?? '')
        );
        $data['currency_from'] = $cashboxCurrencies['from'] ?? ($data['currency_from'] ?? 'UAH');
        $data['currency_to'] = $cashboxCurrencies['to'] ?? ($data['currency_to'] ?? $data['currency_from']);

        $payload = self::normalizeTransferPayload($data);
        $payload = array_merge($payload, [
            'type' => 'PP',
            'firma' => $fid,
            'docum' => 'exchange',
            'money' => '',
            'client1' => '0',
            'client2' => (string) ($data['client2'] ?? '0'),
        ]);

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
            ->where('docum', 'exchange')
            ->update($payload);

        return $id;
    }

    private static function normalizeTransferPayload(array $data): array
    {
        $amountFrom = self::parseDecimal($data['summa'] ?? 0);
        $amountTo = self::parseDecimal($data['summa2'] ?? 0);
        $rate = self::parseDecimal($data['exchange_rate'] ?? 0);

        $providedAmountFrom = array_key_exists('summa', $data) && trim((string) $data['summa']) !== '';
        $providedAmountTo = array_key_exists('summa2', $data) && trim((string) $data['summa2']) !== '';
        $providedRate = array_key_exists('exchange_rate', $data) && trim((string) $data['exchange_rate']) !== '';

        if ($providedAmountFrom && $providedRate && !$providedAmountTo) {
            $amountTo = $amountFrom * $rate;
        } elseif ($providedAmountFrom && $providedAmountTo && !$providedRate && $amountFrom > 0) {
            $rate = $amountTo / $amountFrom;
        } elseif ($providedAmountTo && $providedRate && !$providedAmountFrom && $rate > 0) {
            $amountFrom = $amountTo / $rate;
        }

        if ($amountTo <= 0 && $amountFrom > 0) {
            $amountTo = $amountFrom;
        }

        if ($rate <= 0 && $amountFrom > 0) {
            $rate = $amountTo / $amountFrom;
        }

        $currencyFrom = self::normalizeCurrency($data['currency_from'] ?? 'UAH');
        $currencyTo = self::normalizeCurrency($data['currency_to'] ?? $currencyFrom);

        return [
            'summa' => round($amountFrom, 2),
            'summa2' => round($amountTo, 2),
            'content' => (string) ($data['content'] ?? ''),
            'data' => curdate((string) ($data['data'] ?? date('d-m-Y'))),
            'oplata' => (string) ($data['oplata'] ?? ''),
            'oplata2' => (string) ($data['oplata2'] ?? ''),
            'currency_from' => $currencyFrom,
            'currency_to' => $currencyTo,
            'exchange_rate' => round($rate > 0 ? $rate : 1, 8),
            'commission_amount' => round(max(0, self::parseDecimal($data['commission_amount'] ?? 0)), 2),
            'commission_currency' => $currencyFrom,
        ];
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
            ->whereIn('type', ['PPO', 'PRO', 'PPP'])
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
        if ((string) ($doc->type ?? '') === 'PPP') {
            return self::provodkaBalanceExchange($doc, $id, $fid, $wasPosted, $direction);
        }

        $targetClientId = trim((string) ($doc->client1 ?? ''));
        $ownerUserId = trim((string) ($doc->client2 ?? ''));
        $ownerDelta = ($doc->type === 'PPO' ? 1 : -1) * $summa * $direction;
        $targetDelta = -1 * $ownerDelta;
        $currency = self::normalizeCurrency($doc->currency_from ?? 'UAH');

        DB::transaction(function () use ($ownerUserId, $targetClientId, $fid, $ownerDelta, $targetDelta, $currency, $id, $wasPosted, $doc) {
            if ($ownerUserId !== '' && $ownerUserId !== '0') {
                self::ensureUserBalanceCache($fid, $ownerUserId, $currency);
                self::shiftUserBalance($fid, $ownerUserId, $ownerDelta, $currency);
            }

            if ($targetClientId !== '' && $targetClientId !== '0') {
                self::ensureUserBalanceCache($fid, $targetClientId, $currency);
                self::shiftUserBalance($fid, $targetClientId, $targetDelta, $currency);
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
                ->whereIn('type', ['PPO', 'PRO', 'PPP'])
                ->update(['provodka' => $wasPosted ? 0 : 1]);
        });

        $fresh = DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->whereIn('type', ['PPO', 'PRO', 'PPP'])
            ->first();

        return [
            'document' => $fresh,
            'isPosted' => !$wasPosted,
        ];
    }

    private static function provodkaBalanceExchange(object $doc, int $id, string $fid, bool $wasPosted, int $direction): array
    {
        $ownerUserId = trim((string) ($doc->client2 ?? ''));
        $amountFrom = round((float) ($doc->summa ?? 0), 2);
        $amountTo = round((float) (($doc->summa2 ?? 0) > 0 ? $doc->summa2 : $doc->summa), 2);
        $currencyFrom = self::normalizeCurrency($doc->currency_from ?? 'UAH');
        $currencyTo = self::normalizeCurrency($doc->currency_to ?? $currencyFrom);

        try {
            DB::transaction(function () use ($ownerUserId, $fid, $amountFrom, $amountTo, $currencyFrom, $currencyTo, $direction, $id, $wasPosted, $doc): void {
                if ($ownerUserId !== '' && $ownerUserId !== '0') {
                    if (!$wasPosted) {
                        self::assertUserBalanceAvailable($fid, $ownerUserId, $currencyFrom, $amountFrom);
                    }

                    self::shiftUserBalance($fid, $ownerUserId, -1 * $amountFrom * $direction, $currencyFrom);
                    self::shiftUserBalance($fid, $ownerUserId, $amountTo * $direction, $currencyTo);
                }

                app(AccountingService::class)->createDocumentTransaction(
                    'z_document:balance_exchange',
                    $id,
                    'PPP',
                    $doc,
                    [],
                    $fid,
                    $wasPosted
                );

                DB::table('z_document')
                    ->where('id', $id)
                    ->where('firma', $fid)
                    ->where('type', 'PPP')
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
            ->where('type', 'PPP')
            ->first();

        return [
            'document' => $fresh,
            'isPosted' => !$wasPosted,
        ];
    }

    public static function provodkaTransfer(int $id, string $fid): array
    {
        $doc = DB::table('z_document')
            ->where('id', $id)
            ->where('firma', $fid)
            ->where('type', 'PP')
            ->where('docum', 'exchange')
            ->first();

        if (!$doc) {
            return [
                'document' => null,
                'isPosted' => false,
            ];
        }

        $wasPosted = (int) ($doc->provodka ?? 0) === 1;
        $direction = $wasPosted ? -1 : 1;
        $amountFrom = round((float) ($doc->summa ?? 0), 2);
        $amountTo = round((float) ($doc->summa2 ?? 0), 2);
        $commissionAmount = round((float) ($doc->commission_amount ?? 0), 2);
        $fromCashboxId = trim((string) ($doc->oplata ?? ''));
        $toCashboxId = trim((string) ($doc->oplata2 ?? ''));

        if ($amountTo <= 0) {
            $amountTo = $amountFrom;
        }

        try {
            DB::transaction(function () use ($fid, $fromCashboxId, $toCashboxId, $amountFrom, $amountTo, $commissionAmount, $direction, $id, $wasPosted, $doc): void {
                if ($fromCashboxId !== '' && $toCashboxId !== '') {
                    if (!$wasPosted) {
                        self::assertCashboxBalanceAvailable($fid, 'oplata', $fromCashboxId, $amountFrom + $commissionAmount);
                    }

                    self::shiftConfValue($fid, 'oplata', $fromCashboxId, -1 * ($amountFrom + $commissionAmount) * $direction);
                    self::shiftConfValue($fid, 'oplata', $toCashboxId, $amountTo * $direction);
                }

                app(AccountingService::class)->createDocumentTransaction(
                    'z_document:money_transfer',
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
                    ->where('docum', 'exchange')
                    ->update(['provodka' => $wasPosted ? 0 : 1]);
            });
        } catch (RuntimeException $exception) {
            $message = $exception->getMessage();
            if (! str_starts_with($message, 'Недостатньо')) {
                throw $exception;
            }

            return [
                'document' => $doc,
                'isPosted' => $wasPosted,
                'error' => $message,
            ];
        }

        $fresh = self::findTransfer($id, $fid);

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

    private static function assertCashboxBalanceAvailable(string $fid, string $type, string $id, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $cashbox = DB::table('conf')
            ->where('id', $id)
            ->where('type', $type)
            ->where('firma', $fid)
            ->lockForUpdate()
            ->first(['name', 'value']);

        $available = (float) ($cashbox->value ?? 0);
        if (!$cashbox || $available + 0.000001 < $amount) {
            $name = trim((string) ($cashbox->name ?? $id));
            throw new RuntimeException(sprintf(
                'Недостатньо коштів у касі %s. Доступно %s, потрібно %s',
                $name !== '' ? $name : $id,
                self::formatBalanceAmount($available),
                self::formatBalanceAmount($amount)
            ));
        }
    }

    private static function parseDecimal(mixed $value): float
    {
        $normalized = str_replace([' ', ','], ['', '.'], trim((string) $value));

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private static function resolveTransferCashboxCurrencies(string $fid, string $fromCashboxId, string $toCashboxId): array
    {
        $ids = array_values(array_filter([$fromCashboxId, $toCashboxId], fn (string $id): bool => trim($id) !== ''));
        if ($ids === []) {
            return [];
        }

        $columns = array_filter([
            'id',
            'name',
            Schema::hasColumn('conf', 'currency') ? 'currency' : null,
        ]);

        $cashboxes = DB::table('conf')
            ->where('type', 'oplata')
            ->where('firma', $fid)
            ->whereIn('id', $ids)
            ->get($columns)
            ->keyBy(fn ($row) => (string) $row->id);

        return array_filter([
            'from' => self::currencyForCashboxRow($cashboxes->get($fromCashboxId)),
            'to' => self::currencyForCashboxRow($cashboxes->get($toCashboxId)),
        ]);
    }

    private static function currencyForCashboxRow(?object $cashbox): ?string
    {
        if (!$cashbox) {
            return null;
        }

        $configuredCurrency = Schema::hasColumn('conf', 'currency')
            ? self::normalizeCurrency($cashbox->currency ?? '', '')
            : '';

        return $configuredCurrency !== ''
            ? $configuredCurrency
            : self::currencyFromCashboxName((string) ($cashbox->name ?? ''));
    }

    private static function normalizeCurrency(mixed $value, string $default = 'UAH'): string
    {
        $currency = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $value) ?? '');

        return $currency !== '' ? substr($currency, 0, 10) : $default;
    }

    private static function currencyFromCashboxName(string $name): string
    {
        $upperName = strtoupper($name);
        foreach (['UAH', 'USD', 'EUR', 'GBP', 'PLN', 'USDT', 'USDC', 'BTC', 'ETH'] as $currency) {
            if (preg_match('/(^|[^A-Z0-9])' . preg_quote($currency, '/') . '([^A-Z0-9]|$)/', $upperName) === 1) {
                return $currency;
            }
        }

        return 'UAH';
    }

    public static function userBalances(mixed $value): array
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return [[
                'amount' => self::formatBalanceAmount(0),
                'currency' => 'UAH',
                'is_default' => true,
            ]];
        }

        if (!str_contains($raw, ':') && is_numeric($raw)) {
            return [[
                'amount' => self::formatBalanceAmount((float) $raw),
                'currency' => 'UAH',
                'is_default' => true,
            ]];
        }

        $balances = [];
        foreach (explode(';', $raw) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || !str_contains($segment, ':')) {
                continue;
            }

            [$amount, $currency] = array_map('trim', explode(':', $segment, 2));
            $amount = str_replace(',', '.', $amount);
            if ($amount === '' || !is_numeric($amount)) {
                continue;
            }

            $balances[] = [
                'amount' => self::formatBalanceAmount((float) $amount),
                'currency' => self::normalizeCurrency($currency),
                'is_default' => count($balances) === 0,
            ];
        }

        return $balances;
    }

    public static function cachedUserBalances(string $userId, string $fid, mixed $fallbackBalance = ''): array
    {
        if (trim($userId) === '' || ! Schema::hasTable('users_cashe') || ! Schema::hasColumn('users_cashe', 'balance')) {
            return self::userBalances($fallbackBalance);
        }

        $query = DB::table('users_cashe')->where('userid', $userId);

        if (Schema::hasColumn('users_cashe', 'firma')) {
            $firmaScope = HoldingScope::projectIdsFor($fid);
            if ($firmaScope !== []) {
                $query->whereIn('firma', array_map('intval', $firmaScope));
            }
        }

        $columns = ['balance'];
        $columns[] = Schema::hasColumn('users_cashe', 'valuta')
            ? 'valuta'
            : DB::raw("'UAH' as valuta");

        $rows = $query
            ->orderByDesc('balance')
            ->get($columns);

        if ($rows->isEmpty()) {
            return self::userBalances($fallbackBalance);
        }

        return $rows
            ->map(function ($row, int $index) {
                return [
                    'amount' => self::formatBalanceAmount((float) ($row->balance ?? 0)),
                    'currency' => self::normalizeCurrency($row->valuta ?? 'UAH'),
                    'is_default' => $index === 0,
                ];
            })
            ->values()
            ->all();
    }

    private static function shiftUserBalance(string $fid, string $userId, float $delta, string $currency): void
    {
        if ($delta == 0.0 || ! self::canUseUserBalanceCache()) {
            return;
        }

        $cacheColumns = Schema::getColumnListing('users_cashe');
        $hasValuta = in_array('valuta', $cacheColumns, true);
        $currency = self::normalizeCurrency($currency);
        $firmaScope = HoldingScope::projectIdsFor($fid);
        $user = DB::table('users')
            ->where('id', $userId)
            ->when($firmaScope !== [], fn ($query) => $query->whereIn('firma', $firmaScope))
            ->first(['id', 'firma']);

        if (!$user) {
            return;
        }

        $criteria = ['userid' => (string) $user->id];
        if (in_array('firma', $cacheColumns, true)) {
            $criteria['firma'] = (int) ($user->firma ?? $fid);
        }
        if ($hasValuta) {
            $criteria['valuta'] = $currency;
        }

        $existing = DB::table('users_cashe')
            ->where($criteria)
            ->lockForUpdate()
            ->first(['id', 'balance']);

        $values = ['balance' => round((float) ($existing->balance ?? 0) + $delta, 2)];
        if (in_array('firma', $cacheColumns, true)) {
            $values['firma'] = (int) ($user->firma ?? $fid);
        }
        if (in_array('user_id', $cacheColumns, true)) {
            $values['user_id'] = (int) $user->id;
        }
        if ($hasValuta) {
            $values['valuta'] = $currency;
        }

        DB::table('users_cashe')->updateOrInsert($criteria, $values);
    }

    private static function assertUserBalanceAvailable(string $fid, string $userId, string $currency, float $amount): void
    {
        if ($amount <= 0 || ! self::canUseUserBalanceCache()) {
            return;
        }

        $cacheColumns = Schema::getColumnListing('users_cashe');
        $hasValuta = in_array('valuta', $cacheColumns, true);
        $currency = self::normalizeCurrency($currency);
        $firmaScope = HoldingScope::projectIdsFor($fid);
        $user = DB::table('users')
            ->where('id', $userId)
            ->when($firmaScope !== [], fn ($query) => $query->whereIn('firma', $firmaScope))
            ->first(['id', 'firma']);

        if (!$user) {
            throw new RuntimeException('Користувача балансу не знайдено');
        }

        $criteria = ['userid' => (string) $user->id];
        if (in_array('firma', $cacheColumns, true)) {
            $criteria['firma'] = (int) ($user->firma ?? $fid);
        }
        if ($hasValuta) {
            $criteria['valuta'] = $currency;
        }

        $existing = DB::table('users_cashe')
            ->where($criteria)
            ->lockForUpdate()
            ->first(['id', 'balance']);
        $available = (float) ($existing->balance ?? 0);

        if (!$existing || $available + 0.000001 < $amount) {
            throw new RuntimeException(sprintf(
                'Недостатньо коштів на балансі %s. Доступно %s, потрібно %s',
                $currency,
                self::formatBalanceAmount($available),
                self::formatBalanceAmount($amount)
            ));
        }
    }

    private static function ensureUserBalanceCache(string $fid, string $userId, string $currency): void
    {
        if (! self::canUseUserBalanceCache()) {
            return;
        }

        $cacheColumns = Schema::getColumnListing('users_cashe');
        $hasValuta = in_array('valuta', $cacheColumns, true);
        $currency = self::normalizeCurrency($currency);
        $firmaScope = HoldingScope::projectIdsFor($fid);
        $user = DB::table('users')
            ->where('id', $userId)
            ->when($firmaScope !== [], fn ($query) => $query->whereIn('firma', $firmaScope))
            ->first(['id', 'firma']);

        if (!$user) {
            return;
        }

        $criteria = ['userid' => (string) $user->id];
        if (in_array('firma', $cacheColumns, true)) {
            $criteria['firma'] = (int) ($user->firma ?? $fid);
        }
        if ($hasValuta) {
            $criteria['valuta'] = $currency;
        }

        $exists = DB::table('users_cashe')
            ->where($criteria)
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            return;
        }

        $values = array_merge($criteria, ['balance' => 0]);
        if (in_array('firma', $cacheColumns, true)) {
            $values['firma'] = (int) ($user->firma ?? $fid);
        }
        if (in_array('user_id', $cacheColumns, true)) {
            $values['user_id'] = (int) $user->id;
        }
        if ($hasValuta) {
            $values['valuta'] = $currency;
        }

        DB::table('users_cashe')->insert($values);
    }

    private static function canUseUserBalanceCache(): bool
    {
        if (! Schema::hasTable('users_cashe')) {
            return false;
        }

        $cacheColumns = Schema::getColumnListing('users_cashe');

        return in_array('userid', $cacheColumns, true) && in_array('balance', $cacheColumns, true);
    }

    private static function serializeUserBalances(array $balances): ?string
    {
        $segments = [];
        foreach ($balances as $balance) {
            $amount = self::formatBalanceAmount((float) ($balance['amount'] ?? 0));
            $currency = self::normalizeCurrency($balance['currency'] ?? 'UAH');
            $segments[] = "{$amount}:{$currency};";
        }

        return $segments === [] ? null : implode('', $segments);
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
