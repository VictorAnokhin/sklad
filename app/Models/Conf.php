<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Conf extends Model
{
    protected $table = 'conf';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = [
        'last_balance' => 'decimal:18',
        'last_price' => 'decimal:8',
        'last_updated_at' => 'datetime',
    ];


    public function firmaObj()
    {
        return $this->belongsTo(Firma::class, 'firma');
    }

    public function legacyUser()
    {
        return $this->belongsTo(LegacyUser::class, 'userid');
    }

    // ── Тип клієнта (conf.type = tgroup) — один запит для Settings і форми товару ──

    /**
     * Ті самі записи, що змінна $tgroups у SettingsController@index («Тип клієнта»).
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public static function tgroupsForFirma(string|int $fid)
    {
        return DB::table('conf')
            ->where('type', 'tgroup')
            ->where('firma', $fid)
            ->orderBy('name')
            ->get();
    }

    /** Синонім {@see tgroupsForFirma} (використовується в Goods::showGoods). */
    public static function getPriceGroups(string|int $fid)
    {
        return self::tgroupsForFirma($fid);
    }

    // ── getFilterTags: теги-фільтри для форми товару ──────────────────────────

    public static function getFilterTags($fid)
    {
        return self::where('type', 'filter')
            ->where(fn($q) => $q->where('firma', $fid)->orWhere('constanta', '1'))
            ->orderBy('name')
            ->get();
    }

    public static function decoratePaymentType(object $item): object
    {
        $item->doc = Schema::hasColumn('conf', 'doc') ? self::normalizePaymentDocFlags($item->doc ?? '') : '';
        $item->doc_label = self::paymentDocLabel($item->doc);
        $item->debit_account_id = Schema::hasColumn('conf', 'debit_account_id') ? (int) ($item->debit_account_id ?? 0) : 0;
        $item->credit_account_id = Schema::hasColumn('conf', 'credit_account_id') ? (int) ($item->credit_account_id ?? 0) : 0;
        $item->cost_type = (string) ($item->constanta ?? '1') === '1' ? 'variable' : 'fixed';
        $item->cost_type_label = $item->cost_type === 'variable' ? 'Переменные' : 'Постоянные';
        $item->cash_flow_activity = self::normalizeCashFlowActivity($item->vision ?? '');
        $item->cash_flow_activity_label = self::cashFlowActivityLabel($item->cash_flow_activity);

        return $item;
    }

    public static function normalizeCashFlowActivity(string|null $value): string
    {
        $activity = trim((string) $value);

        return in_array($activity, ['operating', 'investing', 'financing'], true) ? $activity : 'operating';
    }

    public static function cashFlowActivityLabel(string|null $value): string
    {
        return match (self::normalizeCashFlowActivity($value)) {
            'investing' => 'Инвестиционная',
            'financing' => 'Финансовая',
            default => 'Операционная',
        };
    }

    public static function normalizeWeb3ChainIdToHex(string|int|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && in_array(strtolower(trim($value)), ['solana', 'sui'], true)) {
            return strtolower(trim($value));
        }

        if (is_int($value)) {
            return '0x' . dechex($value);
        }

        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, '0x')) {
            $hex = substr($raw, 2);
            if ($hex === '' || !ctype_xdigit($hex)) {
                return null;
            }
            return '0x' . dechex((int) hexdec($hex));
        }

        if (ctype_digit($raw)) {
            return '0x' . dechex((int) $raw);
        }

        if (ctype_xdigit($raw)) {
            return '0x' . dechex((int) hexdec($raw));
        }

        return null;
    }

    public static function normalizeWeb3ChainIdToDecimalString(string|int|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && in_array(strtolower(trim($value)), ['solana', 'sui'], true)) {
            return strtolower(trim($value));
        }

        if (is_int($value)) {
            return (string) $value;
        }

        $raw = strtolower(trim((string) $value));
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, '0x')) {
            $hex = substr($raw, 2);
            if ($hex === '' || !ctype_xdigit($hex)) {
                return null;
            }
            return (string) (int) hexdec($hex);
        }

        if (ctype_digit($raw)) {
            return ltrim($raw, '0') === '' ? '0' : ltrim($raw, '0');
        }

        if (ctype_xdigit($raw)) {
            return (string) (int) hexdec($raw);
        }

        return null;
    }

    public static function paymentTypeAccountBinding(?string $paymentTypeId): array
    {
        $paymentTypeId = trim((string) $paymentTypeId);
        if (
            $paymentTypeId === ''
            || !Schema::hasColumn('conf', 'debit_account_id')
            || !Schema::hasColumn('conf', 'credit_account_id')
        ) {
            return ['debit_account_id' => null, 'credit_account_id' => null];
        }

        $item = self::query()
            ->where('type', 'reestr')
            ->where('id', $paymentTypeId)
            ->first(['debit_account_id', 'credit_account_id']);

        return [
            'debit_account_id' => $item?->debit_account_id ? (int) $item->debit_account_id : null,
            'credit_account_id' => $item?->credit_account_id ? (int) $item->credit_account_id : null,
        ];
    }

    public static function paymentDocLabel(?string $doc): string
    {
        $flags = self::paymentDocFlags($doc);

        if ($flags === []) {
            return 'Все документы';
        }

        $labels = [];
        foreach ($flags as $flag) {
            $labels[] = match ($flag) {
                'PO' => 'Получение денег',
                'RO' => 'Выдача денег',
                'PPO' => 'Приход денег',
                'DEPOSIT' => 'Депозиты',
                'ZP' => 'Выдача зарплаты',
                'PRO' => 'Личные средства',
                default => $flag,
            };
        }

        return implode(', ', $labels);
    }

    public static function paymentTypesForDocument($fid, ?string $docType)
    {
        $docType = strtoupper(trim((string) $docType));
        $query = self::query()
            ->where('type', 'reestr')
            ->orderBy('name');

        if (Schema::hasColumn('conf', 'doc') && $docType !== '') {
            $docAliases = match ($docType) {
                'PPO' => ['PPO', 'PO'],
                'PRO' => ['PRO', 'RO'],
                'PO', 'RO', 'DEPOSIT', 'ZP' => [$docType],
                default => [],
            };

            if ($docAliases !== []) {
                $query->where(function ($builder) use ($docAliases) {
                    $builder->whereNull('doc')
                        ->orWhere('doc', '')
                        ->orWhereIn('doc', $docAliases);

                    foreach ($docAliases as $alias) {
                        $builder->orWhereRaw('FIND_IN_SET(?, REPLACE(doc, \' \', \'\')) > 0', [$alias]);
                    }
                });
            }
        }

        return $query->get()->map(fn ($item) => self::decoratePaymentType($item));
    }

    public static function paymentDocFlags(string|array|null $doc): array
    {
        $rawFlags = is_array($doc) ? $doc : explode(',', (string) $doc);
        $allowed = ['PO', 'RO', 'PPO', 'DEPOSIT', 'ZP', 'PRO'];
        $flags = [];

        foreach ($rawFlags as $flag) {
            $normalized = strtoupper(trim((string) $flag));
            if ($normalized !== '' && in_array($normalized, $allowed, true)) {
                $flags[] = $normalized;
            }
        }

        return array_values(array_unique($flags));
    }

    public static function normalizePaymentDocFlags(string|array|null $doc): string
    {
        $flags = self::paymentDocFlags($doc);
        $order = ['PO', 'RO', 'PPO', 'DEPOSIT', 'ZP', 'PRO'];

        usort($flags, static function (string $left, string $right) use ($order): int {
            return array_search($left, $order, true) <=> array_search($right, $order, true);
        });

        return implode(',', $flags);
    }
}
