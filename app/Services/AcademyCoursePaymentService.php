<?php

namespace App\Services;

use App\Models\Document;
use App\Models\EducationTopic;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AcademyCoursePaymentService
{
    private const FID = '36';

    public function ensureOrder(Authenticatable $user, EducationTopic $course): array
    {
        return DB::transaction(function () use ($user, $course) {
            DB::table('project')->where('id', self::FID)->lockForUpdate()->first();

            $order = $this->findCourseOrder((int) $user->getAuthIdentifier(), (int) $course->id, true);
            if (!$order) {
                $now = now();
                $year = $now->format('Y');
                $orderNum = Document::nextNum('ZOUT', self::FID, $year);
                $amount = round((float) $this->normalizeAv8Amount((string) $course->cost_av8), 2);

                $orderId = DB::table('document')->insertGetId([
                    'num' => (string) $orderNum,
                    'type' => 'ZOUT',
                    'firma' => self::FID,
                    'client1' => (string) $user->getAuthIdentifier(),
                    'client2' => '0',
                    'summa' => $amount,
                    'data' => $now->format('d-m-Y'),
                    'data2' => $now->format('d-m-Y'),
                    'time' => $now->format('H:i:s'),
                    'dt' => $now->timestamp,
                    'manager' => 'academy_api',
                    'user' => 'academy_api',
                    'content' => sprintf('Academy: заявка на курс #%d «%s»', $course->id, trim((string) $course->title)),
                    'numz' => (string) $orderNum,
                    'typez' => 'ZOUT',
                    'docum' => 'academy_course',
                    'provodka' => 0,
                    'dostup' => 1,
                    'money' => 'AV8',
                    'numdoc' => (string) $course->id,
                    'close' => 0,
                    'typeproduct' => 'course',
                ]);
                $order = DB::table('document')->where('id', $orderId)->first();
            }

            $this->ensureCourseLine($order, $course);

            return $this->orderResponse($order, (int) $course->id);
        });
    }

    public function record(Authenticatable $user, EducationTopic $course, string $digest, string $walletAddress): array
    {
        $walletAddress = $this->normalizeSuiAddress($walletAddress);
        $configuredReceiver = trim((string) config('services.av8_capital.payment_receiver_address'))
            ?: '0xb1a698b321dd94ba0ad955888d4f9a94262f9ddeb07964d228fcd788f08c5062';
        $receiverAddress = $this->normalizeSuiAddress($configuredReceiver);
        $packageId = trim((string) config('services.av8_capital.coin_package_id'))
            ?: '0x799f69b5be95ddc5d1107912a74f6835bef6a80a6ede155cb843753940a92934';
        $coinType = strtolower(sprintf(
            '%s::fund_share::FUND_SHARE',
            rtrim($packageId, ':')
        ));
        $amountAv8 = $this->normalizeAv8Amount((string) $course->cost_av8);

        if ($amountAv8 === '0.000000') {
            throw ValidationException::withMessages(['course_id' => 'Этот курс не требует оплаты.']);
        }
        if ($receiverAddress === '' || ! str_starts_with($coinType, '0x')) {
            throw ValidationException::withMessages(['payment' => 'Параметры оплаты AV8 не настроены на сервере.']);
        }

        $this->ensureOrder($user, $course);
        $cashboxId = $this->av8CashboxId();
        $this->assertWalletBelongsToUser((int) $user->getAuthIdentifier(), $walletAddress);
        $this->verifySuiPayment($digest, $walletAddress, $receiverAddress, $coinType, $this->av8BaseUnits($amountAv8));

        $paymentResult = DB::transaction(function () use ($user, $course, $digest, $walletAddress, $amountAv8, $cashboxId) {
            DB::table('project')->where('id', self::FID)->lockForUpdate()->first();

            $digestOrder = DB::table('document')
                ->where('firma', self::FID)
                ->where('type', 'ZOUT')
                ->where('numorder', $digest)
                ->first();

            if ($digestOrder) {
                if (
                    (string) $digestOrder->client1 !== (string) $user->getAuthIdentifier()
                    || !$this->orderContainsCourse($digestOrder, (int) $course->id)
                ) {
                    throw ValidationException::withMessages([
                        'digest' => 'Эта транзакция уже зарегистрирована для другой покупки.',
                    ]);
                }

                $payment = DB::table('z_document')
                    ->where('firma', self::FID)
                    ->where('type', 'PO')
                    ->where('docid', $digestOrder->id)
                    ->first();

                if ($payment) {
                    return ['order' => $digestOrder, 'payment' => $payment, 'already_recorded' => true];
                }
            }

            $now = now();
            $date = $now->format('d-m-Y');
            $year = $now->format('Y');
            $poNum = Document::nextNum('PO', self::FID, $year);
            $amount = round((float) $amountAv8, 2);
            $content = sprintf(
                'Academy: покупка курса #%d «%s»; сумма %s AV8; wallet %s; Sui tx %s',
                $course->id,
                trim((string) $course->title),
                $amountAv8,
                $walletAddress,
                $digest
            );

            $order = $digestOrder
                ?: $this->findCourseOrder((int) $user->getAuthIdentifier(), (int) $course->id, true);
            if (!$order) {
                throw ValidationException::withMessages(['course_id' => 'Заявка на курс не найдена. Откройте курс и повторите оплату.']);
            }
            $this->ensureCourseLine($order, $course);
            DB::table('document')->where('id', $order->id)->update([
                'summa' => $amount,
                'content' => $content,
                'numorder' => $digest,
                'money' => 'AV8',
                'close' => 0,
            ]);
            $order = DB::table('document')->where('id', $order->id)->first();

            $existingPayment = DB::table('z_document')
                ->where('firma', self::FID)
                ->where('type', 'PO')
                ->where('docid', $order->id)
                ->first();
            if ($existingPayment) {
                return ['order' => $order, 'payment' => $existingPayment, 'already_recorded' => true];
            }

            $poId = DB::table('z_document')->insertGetId([
                'num' => (string) $poNum,
                'type' => 'PO',
                'firma' => self::FID,
                'client1' => (string) $user->getAuthIdentifier(),
                'client2' => '0',
                'summa' => $amount,
                'data' => $date,
                'data2' => $date,
                'time' => $now->format('H:i:s'),
                'dt' => $now->timestamp,
                'manager' => 'academy_api',
                'user' => 'academy_api',
                'content' => $content,
                'oplata' => $cashboxId,
                'numz' => (string) $order->num,
                'typez' => 'ZOUT',
                'docid' => (string) $order->id,
                'docum' => 'sui_av8',
                'provodka' => 0,
                'dostup' => 1,
                'money' => 'AV8',
                'numdoc' => (string) $course->id,
                'numorder' => $digest,
                'close' => 0,
                'typeproduct' => 'course',
            ]);

            return [
                'order' => $order,
                'payment' => DB::table('z_document')->find($poId),
                'already_recorded' => false,
            ];
        });

        $payment = $paymentResult['payment'];
        if ($payment && (int) ($payment->provodka ?? 0) !== 1) {
            $posting = Document::provodka((string) $payment->id, 'PO', self::FID);
            if (!($posting['isPosted'] ?? false)) {
                throw ValidationException::withMessages(['payment' => 'Не удалось провести документ оплаты PO.']);
            }
            $payment = $posting['document'];
        }

        return $this->response(
            DB::table('document')->where('id', $paymentResult['order']->id)->first(),
            $payment,
            (bool) $paymentResult['already_recorded']
        );
    }

    private function findCourseOrder(int $userId, int $courseId, bool $lock = false): ?object
    {
        $query = DB::table('document')
            ->where('firma', self::FID)
            ->where('type', 'ZOUT')
            ->where('client1', (string) $userId)
            ->where(function ($courseOrder) use ($courseId) {
                $courseOrder
                    ->where(function ($typedOrder) use ($courseId) {
                        $typedOrder->where('typeproduct', 'course')
                            ->where('numdoc', (string) $courseId);
                    })
                    ->orWhereExists(function ($line) use ($courseId) {
                        $line->selectRaw('1')
                            ->from('z_body')
                            ->whereColumn('z_body.docid', 'document.id')
                            ->whereColumn('z_body.firma', 'document.firma')
                            ->where('z_body.pnum', (string) $courseId);
                    });
            })
            ->orderByDesc('id');

        return ($lock ? $query->lockForUpdate() : $query)->first();
    }

    private function ensureCourseLine(object $order, EducationTopic $course): void
    {
        $values = [
            'docnum' => (string) $order->num,
            'pid' => (string) $course->id,
            'pcount' => 1,
            'pprice' => round((float) $course->cost_av8, 2),
            'psumma' => round((float) $course->cost_av8, 2),
            'type' => 'ZOUT',
            'pcod' => 'course',
            'pname' => trim((string) $course->title),
            'zvalue' => '',
        ];
        $values = array_intersect_key($values, array_flip(Schema::getColumnListing('z_body')));

        DB::table('z_body')->updateOrInsert(
            [
                'docid' => (string) $order->id,
                'firma' => self::FID,
                'pnum' => (string) $course->id,
            ],
            $values
        );
    }

    private function orderContainsCourse(object $order, int $courseId): bool
    {
        if (
            strtolower(trim((string) ($order->typeproduct ?? ''))) === 'course'
            && (string) ($order->numdoc ?? '') === (string) $courseId
        ) {
            return true;
        }

        return DB::table('z_body')
            ->where('docid', (string) $order->id)
            ->where('firma', self::FID)
            ->where('pnum', (string) $courseId)
            ->exists();
    }

    private function av8CashboxId(): string
    {
        $query = DB::table('conf')
            ->where('firma', self::FID)
            ->where('type', 'oplata');
        if (Schema::hasColumn('conf', 'currency')) {
            $query->whereRaw('UPPER(currency) = ?', ['AV8']);
        } else {
            $query->where('name', 'like', '%AV8%');
        }
        $id = $query->orderByDesc('first')->orderBy('id')->value('id');
        if (!$id) {
            throw ValidationException::withMessages(['payment' => 'Для проекта 36 не настроена касса AV8.']);
        }

        return (string) $id;
    }

    private function orderResponse(object $order, int $courseId): array
    {
        return [
            'zout_id' => (int) $order->id,
            'zout_num' => (string) $order->num,
            'course_id' => $courseId,
            'client_id' => (int) $order->client1,
        ];
    }

    private function assertWalletBelongsToUser(int $userId, string $walletAddress): void
    {
        $addresses = collect();

        if (Schema::hasTable('user_wallets')) {
            $addresses = $addresses->merge(DB::table('user_wallets')->where('user_id', $userId)->pluck('address'));
        }
        if (Schema::hasTable('zklogin_identities')) {
            $addresses = $addresses->merge(DB::table('zklogin_identities')
                ->where('user_id', $userId)
                ->whereNotNull('wallet_address')
                ->pluck('wallet_address'));
        }

        $belongs = $addresses->contains(fn ($address) => $this->normalizeSuiAddress((string) $address) === $walletAddress);
        if (! $belongs) {
            throw ValidationException::withMessages(['wallet_address' => 'Кошелёк не привязан к авторизованному пользователю.']);
        }
    }

    private function verifySuiPayment(string $digest, string $sender, string $receiver, string $coinType, string $requiredAmount): void
    {
        $rpcUrl = trim((string) config('services.sui.rpc_url')) ?: 'https://sui-testnet-rpc.publicnode.com';

        $response = Http::timeout(15)->acceptJson()->post($rpcUrl, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'sui_getTransactionBlock',
            'params' => [$digest, [
                'showInput' => true,
                'showEffects' => true,
                'showBalanceChanges' => true,
            ]],
        ]);

        if (! $response->successful() || ! is_array($response->json('result'))) {
            throw ValidationException::withMessages(['digest' => 'Транзакция Sui не найдена или RPC временно недоступен.']);
        }

        $transaction = $response->json('result');
        if (data_get($transaction, 'effects.status.status') !== 'success') {
            throw ValidationException::withMessages(['digest' => 'Транзакция Sui не завершилась успешно.']);
        }
        if ($this->normalizeSuiAddress((string) data_get($transaction, 'transaction.data.sender')) !== $sender) {
            throw ValidationException::withMessages(['wallet_address' => 'Транзакция отправлена с другого кошелька.']);
        }

        $received = '0';
        foreach ((array) data_get($transaction, 'balanceChanges', []) as $change) {
            $owner = data_get($change, 'owner.AddressOwner');
            $amount = (string) data_get($change, 'amount', '0');
            if (
                is_string($owner)
                && $this->normalizeSuiAddress($owner) === $receiver
                && strtolower((string) data_get($change, 'coinType')) === $coinType
                && ! str_starts_with($amount, '-')
            ) {
                $received = $this->addUnsignedIntegers($received, $amount);
            }
        }

        if ($this->compareUnsignedIntegers($received, $requiredAmount) < 0) {
            throw ValidationException::withMessages(['digest' => 'Транзакция не содержит требуемую оплату курса в AV8.']);
        }
    }

    private function normalizeSuiAddress(string $address): string
    {
        $address = strtolower(trim($address));
        if (! preg_match('/^0x[0-9a-f]{1,64}$/', $address)) {
            return '';
        }

        return '0x'.str_pad(substr($address, 2), 64, '0', STR_PAD_LEFT);
    }

    private function normalizeAv8Amount(string $amount): string
    {
        $amount = str_replace(',', '.', trim($amount));
        if (! preg_match('/^\d+(?:\.\d+)?$/', $amount)) {
            return '0.000000';
        }
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        $whole = ltrim($whole, '0') ?: '0';

        return $whole.'.'.str_pad(substr($fraction, 0, 6), 6, '0');
    }

    private function av8BaseUnits(string $amount): string
    {
        [$whole, $fraction] = explode('.', $amount, 2);

        return ltrim($whole.str_pad($fraction, 9, '0'), '0') ?: '0';
    }

    private function addUnsignedIntegers(string $left, string $right): string
    {
        $carry = 0;
        $result = '';
        $leftIndex = strlen($left) - 1;
        $rightIndex = strlen($right) - 1;
        while ($leftIndex >= 0 || $rightIndex >= 0 || $carry > 0) {
            $sum = ($leftIndex >= 0 ? (int) $left[$leftIndex--] : 0)
                + ($rightIndex >= 0 ? (int) $right[$rightIndex--] : 0)
                + $carry;
            $result = (string) ($sum % 10).$result;
            $carry = intdiv($sum, 10);
        }

        return ltrim($result, '0') ?: '0';
    }

    private function compareUnsignedIntegers(string $left, string $right): int
    {
        $left = ltrim($left, '0') ?: '0';
        $right = ltrim($right, '0') ?: '0';

        return strlen($left) === strlen($right) ? strcmp($left, $right) : strlen($left) <=> strlen($right);
    }

    private function response(object $zout, ?object $po, bool $alreadyRecorded): array
    {
        return [
            'already_recorded' => $alreadyRecorded,
            'zout_id' => (int) $zout->id,
            'zout_num' => (string) $zout->num,
            'po_id' => $po ? (int) $po->id : null,
            'po_num' => $po ? (string) $po->num : null,
        ];
    }
}
