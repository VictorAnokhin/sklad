@extends('home')

@section('title')
Обмен фиат/крипта
@endsection

@section('content')
@php
    $fiatCryptoOrders = $swapOrders->where('source', 'bank.exchange.crypto')->values();
    $fiatAv8Orders = $swapOrders->reject(fn ($order) => (string) $order->source === 'bank.exchange.crypto')->values();
    $ordersTotal = (float) $fiatAv8Orders->sum('pay_amount');
    $av8Total = (float) $fiatAv8Orders->sum('expected_av8');
    $fiatCryptoTotal = (float) $fiatCryptoOrders->sum('pay_amount');
    $activeExchangeTab = session('bank_exchange_tab');
    $fiatCryptoAccountIds = collect();
    $fiatCryptoLastOperatedAt = null;
    foreach ($fiatCryptoOrders as $order) {
        $decodedMeta = ! empty($order->meta) ? json_decode((string) $order->meta, true) : [];
        $decodedMeta = is_array($decodedMeta) ? $decodedMeta : [];
        foreach (['fiat_account_id', 'crypto_account_id'] as $accountKey) {
            if (! empty($decodedMeta[$accountKey])) {
                $fiatCryptoAccountIds->push((int) $decodedMeta[$accountKey]);
            }
        }
        $orderOperatedAt = (string) ($decodedMeta['operated_at'] ?? $order->created_at ?? '');
        if ($orderOperatedAt !== '' && ($fiatCryptoLastOperatedAt === null || $orderOperatedAt > $fiatCryptoLastOperatedAt)) {
            $fiatCryptoLastOperatedAt = $orderOperatedAt;
        }
    }
    $fiatCryptoAccountIds = $fiatCryptoAccountIds->unique()->values();
    $fiatCryptoAccountsInUse = $fiatCryptoAccountIds->isNotEmpty()
        ? $operationalAccounts->filter(fn ($account) => $fiatCryptoAccountIds->contains((int) $account->id))->values()
        : collect();
    $operationalAccountsById = $operationalAccounts->keyBy(fn ($account) => (int) $account->id);
    $latestFiatCryptoLedgerId = (int) $fiatCryptoOrders
        ->map(function ($order): int {
            $meta = ! empty($order->meta) ? json_decode((string) $order->meta, true) : [];
            $meta = is_array($meta) ? $meta : [];

            return ! empty($meta['reversed_at']) || (string) $order->status === 'cancelled'
                ? 0
                : (int) ($meta['ledger_transaction_id'] ?? 0);
        })
        ->max();
@endphp
<div class="bank-page">
    @include('bank.partials.nav')

    <ul class="nav nav-tabs bank-modal-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeExchangeTab === 'crypto' ? '' : 'active' }}" id="bankExchangeAv8Tab" data-bs-toggle="tab" data-bs-target="#bankExchangeAv8Pane" type="button" role="tab" data-bank-exchange-tab="av8">
                Заявки
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeExchangeTab === 'crypto' ? 'active' : '' }}" id="bankExchangeCryptoTab" data-bs-toggle="tab" data-bs-target="#bankExchangeCryptoPane" type="button" role="tab" data-bank-exchange-tab="crypto">
                Операции
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $activeExchangeTab === 'crypto' ? '' : 'show active' }}" id="bankExchangeAv8Pane" role="tabpanel" aria-labelledby="bankExchangeAv8Tab">
    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel">
            <div class="bank-label">Курс AV8</div>
            <div class="bank-value">{{ number_format((float) $exchangeSettings->rate_usdc, 6, '.', ' ') }}</div>
            <div class="bank-meta">USDC за 1 AV8</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Комиссия</div>
            <div class="bank-value">{{ number_format((float) $exchangeSettings->fee_percent, 2, '.', ' ') }}%</div>
            <div class="bank-meta">Применяется к сумме оплаты</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Quote TTL</div>
            <div class="bank-value">{{ (int) $exchangeSettings->quote_ttl_seconds }} c</div>
            <div class="bank-meta">Срок действия расчета</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Статус покупки</div>
            <div class="bank-value">{{ $exchangeSettings->mint_paused ? 'Пауза' : 'Активно' }}</div>
            <div class="bank-meta">{{ $exchangeSettings->pricing_model }}{{ $exchangeSettings->updated_at !== '' ? ' · ' . $exchangeSettings->updated_at : '' }}</div>
        </div>
    </section>

    <section class="bank-panel bank-table-panel">
        @if(session('success'))
            <div class="alert alert-success mx-3 mt-3 mb-0">{{ session('success') }}</div>
        @endif
        @if($errors->has('status'))
            <div class="alert alert-danger mx-3 mt-3 mb-0">{{ $errors->first('status') }}</div>
        @endif
        <div class="bank-table-header">
            <div>
                <div class="bank-label">История заявок обмена</div>
                <div class="bank-meta">Заявки, созданные формой av8fund-react swap.</div>
            </div>
            <div class="bank-meta">{{ number_format($ordersTotal, 2, '.', ' ') }} входящая сумма</div>
        </div>

        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--exchange-orders">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Дата</th>
                        <th>Оплата</th>
                        <th class="text-end">AV8</th>
                        <th>Кошелек</th>
                        <th>Статус</th>
                        <th class="text-end">Действие</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fiatAv8Orders as $order)
                        @php
                            $orderMeta = [];
                            if (! empty($order->meta)) {
                                $decodedMeta = json_decode((string) $order->meta, true);
                                $orderMeta = is_array($decodedMeta) ? $decodedMeta : [];
                            }
                            $walletAddress = (string) $order->wallet_address;
                            $walletAddressShort = $walletAddress !== '' && mb_strlen($walletAddress) > 18
                                ? mb_substr($walletAddress, 0, 10) . '...' . mb_substr($walletAddress, -6)
                                : $walletAddress;
                            $orderPayload = [
                                'id' => $order->id,
                                'created_at' => (string) $order->created_at,
                                'mode' => strtoupper((string) $order->mode),
                                'pay_currency' => (string) $order->pay_currency,
                                'pay_amount' => number_format((float) $order->pay_amount, 2, '.', ' '),
                                'rate_usdc' => number_format((float) $order->rate_usdc, 8, '.', ' '),
                                'fee_percent' => number_format((float) $order->fee_percent, 4, '.', ' '),
                                'fee_amount' => number_format((float) $order->fee_amount, 8, '.', ' '),
                                'expected_av8' => number_format((float) $order->expected_av8, 8, '.', ' '),
                                'payment_method' => (string) $order->payment_method,
                                'wallet_address' => $walletAddress,
                                'client_email' => (string) ($order->client_email ?? ''),
                                'client_phone' => (string) ($order->client_phone ?? ''),
                                'status' => (string) $order->status,
                                'source' => (string) $order->source,
                                'meta' => $orderMeta,
                            ];
                        @endphp
                        <tr
                            class="bank-order-row"
                            role="button"
                            tabindex="0"
                            data-bs-toggle="modal"
                            data-bs-target="#swapOrderModal"
                            data-order="{{ json_encode($orderPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                        >
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td class="bank-table__date">{{ $order->created_at }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="bank-pill bank-pill--currency">{{ strtoupper($order->mode) }}</span>
                                    <span>{{ $order->payment_method !== '' ? $order->payment_method : $order->pay_currency }}</span>
                                </div>
                                <div class="bank-meta">{{ number_format((float) $order->pay_amount, 2, '.', ' ') }} {{ $order->pay_currency }}</div>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format((float) $order->expected_av8, 6, '.', ' ') }}</td>
                            <td class="bank-mono bank-table__wallet" title="{{ $walletAddress }}">{{ $walletAddressShort !== '' ? $walletAddressShort : '—' }}</td>
                            <td><span class="bank-status">{{ $order->status }}</span></td>
                            <td class="text-end">
                                <button type="button"
                                    class="btn btn-sm btn-primary"
                                    data-order-exchange-open
                                    data-order="{{ json_encode($orderPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
                                    Оформить
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Заявок обмена пока нет.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="modal fade bank-order-modal" id="swapOrderModal" tabindex="-1" aria-labelledby="swapOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="bank-label">Заявка обмена</div>
                        <h5 class="modal-title" id="swapOrderModalLabel">Заявка</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <div class="bank-order-modal__summary">
                        <div>
                            <span>AV8 к зачислению</span>
                            <strong data-order-field="expected_av8"></strong>
                        </div>
                        <div>
                            <span>Телефон</span>
                            <strong data-order-field="client_phone"></strong>
                        </div>
                        <div>
                            <span>Email</span>
                            <strong data-order-field="client_email"></strong>
                        </div>
                        <div>
                            <span>Статус</span>
                            <strong data-order-field="status_label"></strong>
                        </div>
                    </div>

                    <form method="POST" class="bank-order-modal__status-form" data-order-status-form>
                        @csrf
                        <label for="swapOrderStatus">Изменить статус</label>
                        <div class="d-flex gap-2">
                            <select id="swapOrderStatus" name="status" class="form-select" data-order-status-select required>
                                @foreach($exchangeOrderStatuses as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary">Сохранить</button>
                        </div>
                    </form>

                    <div class="bank-order-modal__grid">
                        <div>
                            <span>Дата</span>
                            <strong data-order-field="created_at"></strong>
                        </div>
                        <div>
                            <span>Оплата</span>
                            <strong data-order-field="pay"></strong>
                        </div>
                        <div>
                            <span>Тип</span>
                            <strong data-order-field="mode"></strong>
                        </div>
                        <div>
                            <span>Метод оплаты</span>
                            <strong data-order-field="payment_method"></strong>
                        </div>
                        <div>
                            <span>Курс</span>
                            <strong data-order-field="rate_usdc"></strong>
                        </div>
                        <div>
                            <span>Комиссия</span>
                            <strong data-order-field="fee"></strong>
                        </div>
                        <div>
                            <span>Источник</span>
                            <strong data-order-field="source"></strong>
                        </div>
                    </div>

                    <div class="bank-order-modal__block">
                        <span>Номер кошелька</span>
                        <strong class="bank-mono" data-order-field="wallet_address"></strong>
                    </div>

                    <div class="bank-order-modal__block">
                        <span>Meta</span>
                        <pre class="bank-order-modal__meta" data-order-field="meta"></pre>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" data-order-exchange-submit>Оформить</button>
                </div>
            </div>
        </div>
    </div>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Blockchain history</div>
                <div class="bank-meta">Успешные события покупки/вывода из Blockchain Listener.</div>
            </div>
            <div class="bank-meta">{{ $blockchainExchangeEvents->count() }} событий</div>
        </div>

        <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
            <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--exchange-events">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Дата</th>
                        <th>Событие</th>
                        <th class="text-end">USDC</th>
                        <th>TX</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blockchainExchangeEvents as $event)
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>{{ $event->event_at ?: '—' }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ strtoupper($event->event_type) }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format((float) $event->amount, 2, '.', ' ') }}</td>
                            <td class="bank-mono">
                                @if($event->tx_digest)
                                    <span title="{{ $event->tx_digest }}">{{ $event->tx_digest_short }}</span>
                                    <a
                                        href="{{ $event->tx_explorer_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="bank-account-link ms-2"
                                    >Sui</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Blockchain Listener еще не передал события обмена.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
        </div>

        <div class="tab-pane fade {{ $activeExchangeTab === 'crypto' ? 'show active' : '' }}" id="bankExchangeCryptoPane" role="tabpanel" aria-labelledby="bankExchangeCryptoTab">
            <section class="bank-panel bank-table-panel">
                @if(session('success'))
                    <div class="alert alert-success mx-3 mt-3 mb-0">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger mx-3 mt-3 mb-0">{{ session('error') }}</div>
                @endif
                <div class="bank-table-header">
                    <div>
                        <div class="bank-label">Операции</div>
                        <div class="bank-meta">Ручные операции обменки: покупка и продажа криптовалюты за фиат.</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="bank-meta">{{ number_format($fiatCryptoTotal, 2, '.', ' ') }} фиатная сумма</div>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#fiatCryptoExchangeModal">
                            Обмен
                        </button>
                    </div>
                </div>

                <div class="bank-filter-panel" data-fiat-crypto-filter>
                    <div class="bank-filter-panel__controls">
                        @foreach([
                            'today' => 'Сегодня',
                            'yesterday' => 'Вчера',
                            'month' => 'За месяц',
                            'year' => 'За год',
                            'previous_year' => 'За прошлый год',
                            'manual' => 'Ручной ввод',
                        ] as $filterKey => $filterLabel)
                            <button type="button" class="btn btn-sm btn-outline-light" data-fiat-crypto-date-filter="{{ $filterKey }}">
                                {{ $filterLabel }}
                            </button>
                        @endforeach
                    </div>
                    <div class="bank-filter-panel__manual" data-fiat-crypto-manual-dates hidden>
                        <label>
                            <span>С</span>
                            <input type="date" class="form-control form-control-sm" data-fiat-crypto-date-from>
                        </label>
                        <label>
                            <span>По</span>
                            <input type="date" class="form-control form-control-sm" data-fiat-crypto-date-to>
                        </label>
                    </div>
                    <div class="bank-yield-summary">
                        <div>
                            <div class="bank-meta">Операций</div>
                            <div class="bank-yield-summary__value" data-fiat-crypto-summary-count>0</div>
                        </div>
                        <div>
                            <div class="bank-meta">Покупки</div>
                            <div class="bank-yield-summary__value" data-fiat-crypto-summary-buy>—</div>
                        </div>
                        <div>
                            <div class="bank-meta">Продажи</div>
                            <div class="bank-yield-summary__value" data-fiat-crypto-summary-sell>—</div>
                        </div>
                        <div>
                            <div class="bank-meta">Доходность в фиате</div>
                            <div class="bank-yield-summary__value" data-fiat-crypto-summary-net>—</div>
                        </div>
                    </div>
                </div>

                <div class="bank-exchange-performance">
                    <div class="bank-exchange-performance__header">
                        <div>
                            <div class="bank-label">Эффективность обменки</div>
                            <div class="bank-meta">KPI считаются по операциям выбранного периода. Прибыльность = средний курс продажи против среднего курса покупки.</div>
                        </div>
                        <div class="bank-meta">
                            Последняя операция: {{ $fiatCryptoLastOperatedAt ? $fiatCryptoLastOperatedAt : '—' }}
                        </div>
                    </div>
                    <div class="bank-exchange-performance__grid">
                        <div>
                            <span>Фиатный оборот</span>
                            <strong data-fiat-crypto-performance-turnover>—</strong>
                        </div>
                        <div>
                            <span>Крипто оборот</span>
                            <strong data-fiat-crypto-performance-crypto>—</strong>
                        </div>
                        <div>
                            <span>Средний курс покупки</span>
                            <strong data-fiat-crypto-performance-buy-rate>—</strong>
                        </div>
                        <div>
                            <span>Средний курс продажи</span>
                            <strong data-fiat-crypto-performance-sell-rate>—</strong>
                        </div>
                        <div>
                            <span>Спред / прибыльность</span>
                            <strong data-fiat-crypto-performance-margin>—</strong>
                        </div>
                        <div>
                            <span>Расчетная прибыль</span>
                            <strong data-fiat-crypto-performance-profit>—</strong>
                        </div>
                    </div>
                    <div class="bank-exchange-accounts">
                        <div class="bank-meta">Балансы счетов, задействованных в обмене</div>
                        @if($fiatCryptoAccountsInUse->isNotEmpty())
                            <div class="bank-exchange-accounts__list">
                                @foreach($fiatCryptoAccountsInUse as $account)
                                    <div class="bank-exchange-account">
                                        <span title="{{ $account->label }}">{{ $account->label }}</span>
                                        <strong>{{ number_format((float) $account->balance, in_array(strtoupper((string) $account->currency), ['USDC', 'USDT', 'SUI', 'BTC', 'ETH'], true) ? 8 : 2, '.', ' ') }} {{ $account->currency }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bank-meta">Пока нет операций со счетами обменки.</div>
                        @endif
                    </div>
                </div>

                <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
                    <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--exchange-crypto">
                        <thead>
                            <tr>
                                <th class="bank-table__num">№</th>
                                <th>Дата</th>
                                <th>Операция</th>
                                <th class="text-end">Фиат</th>
                                <th class="text-end">Крипта</th>
                                <th class="text-end">Курс</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fiatCryptoOrders as $order)
                                @php
                                    $meta = [];
                                    if (! empty($order->meta)) {
                                        $decoded = json_decode((string) $order->meta, true);
                                        $meta = is_array($decoded) ? $decoded : [];
                                    }
                                    $side = (string) ($meta['side'] ?? $order->mode);
                                    $cryptoCurrency = (string) ($meta['crypto_currency'] ?? 'USDC');
                                    $cryptoAmount = (float) ($meta['crypto_amount'] ?? $order->expected_av8);
                                    $fiatAccountLabel = (string) ($meta['fiat_account_label'] ?? '');
                                    $cryptoAccountLabel = (string) ($meta['crypto_account_label'] ?? '');
                                    $ledgerTransactionId = (int) ($meta['ledger_transaction_id'] ?? 0);
                                    $reversalLedgerTransactionId = (int) ($meta['reversal_ledger_transaction_id'] ?? 0);
                                    $isReversed = ! empty($meta['reversed_at']) || (string) $order->status === 'cancelled';
                                    $operatedAt = (string) ($meta['operated_at'] ?? $order->created_at);
                                    $operationDate = substr($operatedAt, 0, 10);
                                    $fiatAccountId = (int) ($meta['fiat_account_id'] ?? 0);
                                    $cryptoAccountId = (int) ($meta['crypto_account_id'] ?? 0);
                                    $fiatAccountBalance = (float) ($operationalAccountsById->get($fiatAccountId)->balance ?? 0);
                                    $cryptoAccountBalance = (float) ($operationalAccountsById->get($cryptoAccountId)->balance ?? 0);
                                    $canReverse = ! $isReversed && (
                                        ($side === 'buy' && $cryptoAccountBalance + 0.00000001 >= $cryptoAmount)
                                        || ($side === 'sell' && $fiatAccountBalance + 0.000001 >= (float) $order->pay_amount)
                                    );
                                    $canCancelSave = ! $isReversed && $ledgerTransactionId > 0 && $ledgerTransactionId === $latestFiatCryptoLedgerId && $canReverse;
                                    $reverseBlockReason = $isReversed
                                        ? 'Проводка уже отменена.'
                                        : ($side === 'buy'
                                            ? 'Недостаточно средств на крипто-счете для отмены.'
                                            : 'Недостаточно средств на фиатном счете для отмены.');
                                    $fiatCryptoPayload = [
                                        'id' => (int) $order->id,
                                        'operated_at' => $operationDate,
                                        'side' => $side,
                                        'side_label' => $side === 'sell' ? 'Продажа крипты' : 'Покупка крипты',
                                        'fiat_amount' => number_format((float) $order->pay_amount, 8, '.', ''),
                                        'fiat_currency' => (string) $order->pay_currency,
                                        'crypto_amount' => number_format($cryptoAmount, 8, '.', ''),
                                        'crypto_currency' => $cryptoCurrency,
                                        'rate' => number_format((float) $order->rate_usdc, 8, '.', ''),
                                        'fiat_account_id' => $fiatAccountId,
                                        'crypto_account_id' => $cryptoAccountId,
                                        'fiat_account_label' => $fiatAccountLabel,
                                        'crypto_account_label' => $cryptoAccountLabel,
                                        'ledger_transaction_id' => $ledgerTransactionId,
                                        'reversal_ledger_transaction_id' => $reversalLedgerTransactionId,
                                        'status' => (string) $order->status,
                                        'status_label' => $isReversed ? 'Проводка отменена' : 'Сохранено',
                                        'can_edit' => $isReversed || $ledgerTransactionId <= 0,
                                        'is_reversed' => $isReversed,
                                        'can_cancel_save' => $canCancelSave,
                                        'can_reverse' => $canReverse,
                                        'reverse_block_reason' => $canReverse ? '' : $reverseBlockReason,
                                        'reverse_url' => route('bank.exchange.crypto.reverse', ['order' => (int) $order->id]),
                                        'note' => (string) ($meta['note'] ?? ''),
                                    ];
                                @endphp
                                <tr
                                    class="bank-fiat-crypto-row"
                                    role="button"
                                    tabindex="0"
                                    data-fiat-crypto-row
                                    data-bs-toggle="modal"
                                    data-bs-target="#fiatCryptoExchangeModal"
                                    data-fiat-crypto-order="{{ json_encode($fiatCryptoPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}"
                                    data-operation-date="{{ $operationDate }}"
                                    data-operation-side="{{ $side }}"
                                    data-fiat-amount="{{ number_format((float) $order->pay_amount, 8, '.', '') }}"
                                    data-fiat-currency="{{ $order->pay_currency }}"
                                    data-crypto-amount="{{ number_format($cryptoAmount, 8, '.', '') }}"
                                    data-crypto-currency="{{ $cryptoCurrency }}"
                                    data-rate="{{ number_format((float) $order->rate_usdc, 8, '.', '') }}"
                                >
                                    <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                                    <td class="bank-table__date">{{ $operatedAt }}</td>
                                    <td>
                                        <span class="bank-pill {{ $side === 'sell' ? 'bank-pill--outgoing' : 'bank-pill--currency' }}">{{ $side === 'sell' ? 'Продать' : 'Купить' }}</span>
                                        <div class="bank-meta">{{ $fiatAccountLabel !== '' || $cryptoAccountLabel !== '' ? trim($fiatAccountLabel . ' ↔ ' . $cryptoAccountLabel) : 'Счета не указаны' }}</div>
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $order->pay_amount, 2, '.', ' ') }} {{ $order->pay_currency }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($cryptoAmount, 8, '.', ' ') }} {{ $cryptoCurrency }}</td>
                                    <td class="text-end">{{ number_format((float) $order->rate_usdc, 8, '.', ' ') }}</td>
                                    <td>
                                        <span class="bank-status {{ $isReversed ? 'bank-status--reversed' : '' }}">{{ $isReversed ? 'Отменено' : 'Сохранено' }}</span>
                                        <div class="bank-meta">{{ $reversalLedgerTransactionId > 0 ? 'Сторно TX #' . $reversalLedgerTransactionId : ($ledgerTransactionId > 0 ? 'TX #' . $ledgerTransactionId : 'Ledger pending') }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Операции пока не созданы.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade bank-order-modal" id="fiatCryptoExchangeModal" tabindex="-1" aria-labelledby="fiatCryptoExchangeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('bank.exchange.crypto.store') }}" data-default-action="{{ route('bank.exchange.crypto.store') }}" data-fiat-crypto-form>
                    @csrf
                    <input type="hidden" name="side" value="buy" data-fiat-crypto-side>
                    <div class="modal-header">
                        <div>
                            <div class="bank-label">Операции</div>
                            <h5 class="modal-title" id="fiatCryptoExchangeModalLabel" data-fiat-crypto-form-title>Операция обмена</h5>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs bank-modal-tabs mb-3" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" type="button" data-fiat-crypto-tab="buy">Купить</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" type="button" data-fiat-crypto-tab="sell">Продать</button>
                            </li>
                        </ul>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Фиат</label>
                                <select name="fiat_currency" class="form-select" data-fiat-crypto-fiat-currency required>
                                    @foreach(['USD', 'EUR', 'UAH'] as $currency)
                                        <option value="{{ $currency }}">{{ $currency }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Крипта</label>
                                <select name="crypto_currency" class="form-select" data-fiat-crypto-crypto-currency required>
                                    @foreach(['AV8', 'USDC', 'USDT', 'SUI', 'BTC', 'ETH'] as $currency)
                                        <option value="{{ $currency }}">{{ $currency }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Дата</label>
                                <input type="date" name="operated_at" class="form-control" data-fiat-crypto-date>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Фиатный счет</label>
                                <select name="fiat_account_id" class="form-select" required data-fiat-crypto-fiat-account>
                                    <option value="">Выберите счет</option>
                                    @foreach($operationalAccounts as $account)
                                        <option value="{{ $account->id }}" data-currency="{{ $account->currency }}" data-balance="{{ number_format((float) $account->balance, 8, '.', '') }}">
                                            {{ $account->label }} · {{ $account->currency }} · {{ number_format((float) $account->balance, 2, '.', ' ') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="bank-meta" data-fiat-crypto-fiat-account-meta></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Крипто-счет</label>
                                <select name="crypto_account_id" class="form-select" required data-fiat-crypto-crypto-account>
                                    <option value="">Выберите счет</option>
                                    @foreach($operationalAccounts as $account)
                                        <option value="{{ $account->id }}" data-currency="{{ $account->currency }}" data-balance="{{ number_format((float) $account->balance, 8, '.', '') }}">
                                            {{ $account->label }} · {{ $account->currency }} · {{ number_format((float) $account->balance, 8, '.', ' ') }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="bank-meta" data-fiat-crypto-crypto-account-meta></div>
                            </div>
                            <div class="col-12" data-fiat-crypto-buy-field>
                                <label class="form-label">Сумма Фиат</label>
                                <input type="text" name="fiat_amount" class="form-control" inputmode="numeric" data-terminal-amount data-fiat-crypto-fiat>
                            </div>
                            <div class="col-12" data-fiat-crypto-sell-field hidden>
                                <label class="form-label">Крипта</label>
                                <input type="number" name="crypto_amount" class="form-control" min="0.00000001" step="0.00000001" inputmode="decimal" data-fiat-crypto-crypto>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Курс</label>
                                <input type="number" name="rate" class="form-control" min="0.00000001" step="0.00000001" inputmode="decimal" required data-fiat-crypto-rate>
                            </div>
                            <div class="col-12" data-fiat-crypto-buy-result>
                                <label class="form-label">Крипта</label>
                                <input type="number" class="form-control" readonly data-fiat-crypto-buy-output>
                            </div>
                            <div class="col-12" data-fiat-crypto-sell-result hidden>
                                <label class="form-label">Фиат</label>
                                <input type="number" class="form-control" readonly data-fiat-crypto-sell-output>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Комментарий</label>
                                <textarea name="note" class="form-control" rows="3" data-fiat-crypto-note></textarea>
                            </div>
                            <div class="col-12">
                                <label class="d-flex align-items-center gap-2 mb-0">
                                    <input type="checkbox" name="post_ledger" value="1" class="form-check-input m-0" data-fiat-crypto-post-ledger checked>
                                    <span>Проводка</span>
                                </label>
                                <div class="bank-meta" data-fiat-crypto-post-ledger-meta>При сохранении будут обновлены остатки счетов и создана ledger-проводка.</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary" data-fiat-crypto-submit>Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include('bank.partials.styles')
@include('bank.partials.terminal_amount_inputs')
<style>
    .bank-page .bank-table--exchange-orders,
    .bank-page .bank-table--exchange-events,
    .bank-page .bank-table--exchange-crypto {
        table-layout: fixed;
    }

    .bank-page .bank-table--exchange-orders {
        min-width: 760px;
    }

    .bank-page .bank-table--exchange-events {
        min-width: 640px;
    }

    .bank-page .bank-table--exchange-crypto {
        min-width: 820px;
    }

    .bank-page .bank-table--exchange-orders th,
    .bank-page .bank-table--exchange-orders td,
    .bank-page .bank-table--exchange-events th,
    .bank-page .bank-table--exchange-events td,
    .bank-page .bank-table--exchange-crypto th,
    .bank-page .bank-table--exchange-crypto td {
        overflow: hidden;
        padding: 0.22rem 0.35rem;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.15;
    }

    .bank-page .bank-table--exchange-orders .bank-meta,
    .bank-page .bank-table--exchange-events .bank-meta,
    .bank-page .bank-table--exchange-crypto .bank-meta {
        overflow: hidden;
        margin-top: 1px;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 0.68rem;
        line-height: 1.1;
    }

    .bank-page .bank-table--exchange-orders .bank-pill,
    .bank-page .bank-table--exchange-orders .bank-status,
    .bank-page .bank-table--exchange-events .bank-pill,
    .bank-page .bank-table--exchange-events .bank-status,
    .bank-page .bank-table--exchange-crypto .bank-pill,
    .bank-page .bank-table--exchange-crypto .bank-status {
        min-height: 18px;
        padding: 1px 5px;
        font-size: 0.68rem;
        line-height: 1.1;
    }

    .bank-page .bank-filter-panel {
        display: grid;
        gap: 0.55rem;
        margin-bottom: 0.75rem;
        padding: 0.65rem;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.35);
    }

    .bank-page .bank-filter-panel__controls,
    .bank-page .bank-filter-panel__manual {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .bank-page .bank-filter-panel__controls .btn {
        padding: 0.18rem 0.5rem;
        font-size: 0.74rem;
        line-height: 1.25;
    }

    .bank-page .bank-filter-panel__controls .btn.active {
        color: #0f172a;
        background: #e2e8f0;
        border-color: #e2e8f0;
    }

    .bank-page .bank-filter-panel__manual label {
        display: flex;
        align-items: center;
        gap: 0.3rem;
        margin: 0;
        color: #94a3b8;
        font-size: 0.72rem;
    }

    .bank-page .bank-filter-panel__manual .form-control {
        width: 142px;
        min-height: 28px;
        padding: 0.15rem 0.45rem;
    }

    .bank-page .bank-yield-summary {
        display: grid;
        grid-template-columns: repeat(4, minmax(120px, 1fr));
        gap: 0.45rem;
    }

    .bank-page .bank-yield-summary > div {
        min-width: 0;
        padding: 0.45rem 0.55rem;
        border-radius: 7px;
        background: rgba(2, 6, 23, 0.28);
    }

    .bank-page .bank-yield-summary__value {
        overflow: hidden;
        margin-top: 0.12rem;
        color: #f8fafc;
        font-size: 0.82rem;
        font-weight: 700;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-page .bank-exchange-performance {
        display: grid;
        gap: 0.65rem;
        margin-bottom: 0.75rem;
        padding: 0.75rem;
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.22);
    }

    .bank-page .bank-exchange-performance__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .bank-page .bank-exchange-performance__grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(130px, 1fr));
        gap: 0.45rem;
    }

    .bank-page .bank-exchange-performance__grid > div,
    .bank-page .bank-exchange-account {
        min-width: 0;
        padding: 0.5rem 0.55rem;
        border-radius: 7px;
        background: rgba(15, 23, 42, 0.46);
    }

    .bank-page .bank-exchange-performance__grid span,
    .bank-page .bank-exchange-account span {
        display: block;
        overflow: hidden;
        color: #94a3b8;
        font-size: 0.7rem;
        line-height: 1.15;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-page .bank-exchange-performance__grid strong,
    .bank-page .bank-exchange-account strong {
        display: block;
        overflow: hidden;
        margin-top: 0.15rem;
        color: #f8fafc;
        font-size: 0.82rem;
        line-height: 1.2;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-page .bank-exchange-accounts {
        display: grid;
        gap: 0.4rem;
    }

    .bank-page .bank-exchange-accounts__list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.45rem;
    }

    .bank-page .bank-table--exchange-orders th:nth-child(1),
    .bank-page .bank-table--exchange-orders td:nth-child(1),
    .bank-page .bank-table--exchange-events th:nth-child(1),
    .bank-page .bank-table--exchange-events td:nth-child(1),
    .bank-page .bank-table--exchange-crypto th:nth-child(1),
    .bank-page .bank-table--exchange-crypto td:nth-child(1) {
        width: 38px;
    }

    .bank-page .bank-table--exchange-orders th:nth-child(2),
    .bank-page .bank-table--exchange-orders td:nth-child(2),
    .bank-page .bank-table--exchange-events th:nth-child(2),
    .bank-page .bank-table--exchange-events td:nth-child(2),
    .bank-page .bank-table--exchange-crypto th:nth-child(2),
    .bank-page .bank-table--exchange-crypto td:nth-child(2) {
        width: 128px;
    }

    .bank-page .bank-table--exchange-orders th:nth-child(3),
    .bank-page .bank-table--exchange-orders td:nth-child(3) {
        width: 190px;
    }

    .bank-page .bank-table--exchange-orders th:nth-child(4),
    .bank-page .bank-table--exchange-orders td:nth-child(4),
    .bank-page .bank-table--exchange-orders th:nth-child(6),
    .bank-page .bank-table--exchange-orders td:nth-child(6) {
        width: 92px;
    }

    .bank-page .bank-table--exchange-orders th:nth-child(5),
    .bank-page .bank-table--exchange-orders td:nth-child(5) {
        width: 210px;
    }

    .bank-page .bank-table--exchange-events th:nth-child(3),
    .bank-page .bank-table--exchange-events td:nth-child(3) {
        width: 92px;
    }

    .bank-page .bank-table--exchange-events th:nth-child(4),
    .bank-page .bank-table--exchange-events td:nth-child(4) {
        width: 112px;
    }

    .bank-page .bank-table--exchange-events th:nth-child(5),
    .bank-page .bank-table--exchange-events td:nth-child(5) {
        width: 270px;
    }

    .bank-page .bank-table--exchange-crypto th:nth-child(3),
    .bank-page .bank-table--exchange-crypto td:nth-child(3) {
        width: 210px;
    }

    .bank-page .bank-table--exchange-crypto th:nth-child(4),
    .bank-page .bank-table--exchange-crypto td:nth-child(4),
    .bank-page .bank-table--exchange-crypto th:nth-child(5),
    .bank-page .bank-table--exchange-crypto td:nth-child(5),
    .bank-page .bank-table--exchange-crypto th:nth-child(6),
    .bank-page .bank-table--exchange-crypto td:nth-child(6) {
        width: 112px;
    }

    .bank-page .bank-table--exchange-crypto th:nth-child(7),
    .bank-page .bank-table--exchange-crypto td:nth-child(7) {
        width: 128px;
    }

    @media (max-width: 1199px) {
        .bank-page .bank-exchange-performance__grid {
            grid-template-columns: repeat(3, minmax(130px, 1fr));
        }
    }

    @media (max-width: 767px) {
        .bank-page .bank-yield-summary,
        .bank-page .bank-exchange-performance__grid {
            grid-template-columns: repeat(2, minmax(120px, 1fr));
        }
    }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('swapOrderModal');
        const statusLabels = @json($exchangeOrderStatuses);
        const statusRouteTemplate = @json(route('bank.exchange-orders.status', ['order' => '__ORDER__']));
        const fiatCryptoUpdateRouteTemplate = @json(route('bank.exchange.crypto.update', ['order' => '__ORDER__']));
        const serverExchangeTab = @json($activeExchangeTab);
        const exchangeTabStorageKey = 'bank.exchange.activeTab';
        const exchangeTabButtons = document.querySelectorAll('[data-bank-exchange-tab]');
        const fiatCryptoModalElement = document.getElementById('fiatCryptoExchangeModal');
        let currentSwapOrder = null;
        if (!modal) {
            return;
        }

        function activateExchangeTab(tab) {
            const button = Array.from(exchangeTabButtons).find((item) => item.dataset.bankExchangeTab === tab);
            if (button && window.bootstrap?.Tab) {
                bootstrap.Tab.getOrCreateInstance(button).show();
            }
        }

        exchangeTabButtons.forEach((button) => {
            button.addEventListener('shown.bs.tab', () => {
                window.localStorage?.setItem(exchangeTabStorageKey, button.dataset.bankExchangeTab || 'av8');
            });
        });

        activateExchangeTab(serverExchangeTab || window.localStorage?.getItem(exchangeTabStorageKey) || 'av8');

        function valueOrDash(value) {
            const normalized = String(value ?? '').trim();
            return normalized !== '' ? normalized : '—';
        }

        function parseOrderNumber(value) {
            return Number(String(value ?? '').replace(/\s/g, '').replace(',', '.')) || 0;
        }

        function setText(selector, value) {
            const element = modal.querySelector(selector);
            if (element) {
                element.textContent = valueOrDash(value);
            }
        }

        modal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (!(trigger instanceof HTMLElement)) {
                return;
            }

            let order = {};
            try {
                order = JSON.parse(trigger.dataset.order || '{}');
            } catch (error) {
                order = {};
            }

            setText('#swapOrderModalLabel', `Заявка #${valueOrDash(order.id)}`);
            setText('[data-order-field="pay"]', `${valueOrDash(order.pay_amount)} ${valueOrDash(order.pay_currency)}`);
            setText('[data-order-field="expected_av8"]', `${valueOrDash(order.expected_av8)} AV8`);
            setText('[data-order-field="status_label"]', statusLabels[order.status] || order.status);
            setText('[data-order-field="created_at"]', order.created_at);
            setText('[data-order-field="mode"]', order.mode);
            setText('[data-order-field="payment_method"]', order.payment_method);
            setText('[data-order-field="rate_usdc"]', order.rate_usdc);
            setText('[data-order-field="fee"]', `${valueOrDash(order.fee_percent)}% · ${valueOrDash(order.fee_amount)}`);
            setText('[data-order-field="source"]', order.source);
            setText('[data-order-field="client_email"]', order.client_email);
            setText('[data-order-field="client_phone"]', order.client_phone);
            setText('[data-order-field="wallet_address"]', order.wallet_address);
            currentSwapOrder = order;

            const statusForm = modal.querySelector('[data-order-status-form]');
            const statusSelect = modal.querySelector('[data-order-status-select]');
            if (statusForm instanceof HTMLFormElement) {
                statusForm.action = statusRouteTemplate.replace('__ORDER__', encodeURIComponent(String(order.id || '')));
                statusForm.addEventListener('submit', () => {
                    window.localStorage?.setItem(exchangeTabStorageKey, 'av8');
                }, { once: true });
            }
            if (statusSelect instanceof HTMLSelectElement) {
                statusSelect.value = order.status || 'new';
            }

            const meta = modal.querySelector('[data-order-field="meta"]');
            if (meta) {
                const hasMeta = order.meta && Object.keys(order.meta).length > 0;
                meta.textContent = hasMeta ? JSON.stringify(order.meta, null, 2) : '—';
            }
        });

        function openFiatCryptoFromSwapOrder(order) {
            if (!fiatCryptoModalElement) {
                return;
            }

            const showExchangeModal = () => {
                const trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.dataset.swapOrderExchange = JSON.stringify(order || {});
                bootstrap.Modal.getOrCreateInstance(fiatCryptoModalElement).show(trigger);
            };

            const swapModal = bootstrap.Modal.getInstance(modal);
            if (swapModal && modal.classList.contains('show')) {
                modal.addEventListener('hidden.bs.modal', showExchangeModal, { once: true });
                swapModal.hide();
                return;
            }

            showExchangeModal();
        }

        modal.querySelector('[data-order-exchange-submit]')?.addEventListener('click', () => {
            openFiatCryptoFromSwapOrder(currentSwapOrder || {});
        });

        document.querySelectorAll('[data-order-exchange-open]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                let order = {};
                try {
                    order = JSON.parse(button.dataset.order || '{}');
                } catch (error) {
                    order = {};
                }
                openFiatCryptoFromSwapOrder(order);
            });
        });

        document.querySelectorAll('.bank-order-row').forEach((row) => {
            row.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                row.click();
            });
        });

        document.querySelectorAll('.bank-fiat-crypto-row').forEach((row) => {
            row.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                event.preventDefault();
                row.click();
            });
        });

        const fiatCryptoForm = document.querySelector('[data-fiat-crypto-form]');
        const fiatCryptoSide = document.querySelector('[data-fiat-crypto-side]');
        const fiatCryptoTabs = document.querySelectorAll('[data-fiat-crypto-tab]');
        const fiatCryptoBuyField = document.querySelector('[data-fiat-crypto-buy-field]');
        const fiatCryptoSellField = document.querySelector('[data-fiat-crypto-sell-field]');
        const fiatCryptoBuyResult = document.querySelector('[data-fiat-crypto-buy-result]');
        const fiatCryptoSellResult = document.querySelector('[data-fiat-crypto-sell-result]');
        const fiatCryptoFiat = document.querySelector('[data-fiat-crypto-fiat]');
        const fiatCryptoCrypto = document.querySelector('[data-fiat-crypto-crypto]');
        const fiatCryptoRate = document.querySelector('[data-fiat-crypto-rate]');
        const fiatCryptoFiatCurrency = document.querySelector('[data-fiat-crypto-fiat-currency]');
        const fiatCryptoCryptoCurrency = document.querySelector('[data-fiat-crypto-crypto-currency]');
        const fiatCryptoFiatAccount = document.querySelector('[data-fiat-crypto-fiat-account]');
        const fiatCryptoCryptoAccount = document.querySelector('[data-fiat-crypto-crypto-account]');
        const fiatCryptoFiatAccountMeta = document.querySelector('[data-fiat-crypto-fiat-account-meta]');
        const fiatCryptoCryptoAccountMeta = document.querySelector('[data-fiat-crypto-crypto-account-meta]');
        const fiatCryptoDate = document.querySelector('[data-fiat-crypto-date]');
        const fiatCryptoNote = document.querySelector('[data-fiat-crypto-note]');
        const fiatCryptoPostLedger = document.querySelector('[data-fiat-crypto-post-ledger]');
        const fiatCryptoPostLedgerMeta = document.querySelector('[data-fiat-crypto-post-ledger-meta]');
        const fiatCryptoFormTitle = document.querySelector('[data-fiat-crypto-form-title]');
        const fiatCryptoSubmit = document.querySelector('[data-fiat-crypto-submit]');
        const fiatCryptoBuyOutput = document.querySelector('[data-fiat-crypto-buy-output]');
        const fiatCryptoSellOutput = document.querySelector('[data-fiat-crypto-sell-output]');
        const fiatCryptoFilterStorageKey = 'bank.exchange.fiatCryptoDateFilter';
        const fiatCryptoRows = document.querySelectorAll('[data-fiat-crypto-row]');
        const fiatCryptoDateFilters = document.querySelectorAll('[data-fiat-crypto-date-filter]');
        const fiatCryptoManualDates = document.querySelector('[data-fiat-crypto-manual-dates]');
        const fiatCryptoDateFrom = document.querySelector('[data-fiat-crypto-date-from]');
        const fiatCryptoDateTo = document.querySelector('[data-fiat-crypto-date-to]');
        const fiatCryptoSummaryCount = document.querySelector('[data-fiat-crypto-summary-count]');
        const fiatCryptoSummaryBuy = document.querySelector('[data-fiat-crypto-summary-buy]');
        const fiatCryptoSummarySell = document.querySelector('[data-fiat-crypto-summary-sell]');
        const fiatCryptoSummaryNet = document.querySelector('[data-fiat-crypto-summary-net]');
        const fiatCryptoPerformanceTurnover = document.querySelector('[data-fiat-crypto-performance-turnover]');
        const fiatCryptoPerformanceCrypto = document.querySelector('[data-fiat-crypto-performance-crypto]');
        const fiatCryptoPerformanceBuyRate = document.querySelector('[data-fiat-crypto-performance-buy-rate]');
        const fiatCryptoPerformanceSellRate = document.querySelector('[data-fiat-crypto-performance-sell-rate]');
        const fiatCryptoPerformanceMargin = document.querySelector('[data-fiat-crypto-performance-margin]');
        const fiatCryptoPerformanceProfit = document.querySelector('[data-fiat-crypto-performance-profit]');

        function selectedOption(select) {
            return select?.selectedOptions?.[0] || null;
        }

        function localDateString(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function fiatCryptoDateRange(preset) {
            const today = new Date();
            const start = new Date(today);
            const end = new Date(today);

            if (preset === 'yesterday') {
                start.setDate(today.getDate() - 1);
                end.setDate(today.getDate() - 1);
            } else if (preset === 'month') {
                start.setDate(1);
            } else if (preset === 'year') {
                start.setMonth(0, 1);
            } else if (preset === 'previous_year') {
                start.setFullYear(today.getFullYear() - 1, 0, 1);
                end.setFullYear(today.getFullYear() - 1, 11, 31);
            } else if (preset === 'manual') {
                return {
                    from: fiatCryptoDateFrom?.value || '',
                    to: fiatCryptoDateTo?.value || '',
                };
            }

            return {
                from: localDateString(start),
                to: localDateString(end),
            };
        }

        function formatFiatAmount(value, currency, withSign = false) {
            const sign = withSign && value > 0 ? '+' : '';
            return `${sign}${value.toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })} ${currency}`;
        }

        function formatFiatGroups(groups, withSign = false) {
            const entries = Object.entries(groups).filter(([, value]) => Math.abs(value) > 0.000001);
            if (entries.length === 0) {
                return '0.00';
            }

            return entries
                .map(([currency, value]) => formatFiatAmount(value, currency, withSign))
                .join(' · ');
        }

        function formatCryptoAmount(value, currency) {
            return `${value.toLocaleString('ru-RU', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 8,
            })} ${currency}`;
        }

        function formatCryptoGroups(groups) {
            const entries = Object.entries(groups).filter(([, value]) => Math.abs(value) > 0.00000001);
            if (entries.length === 0) {
                return '0.00';
            }

            return entries
                .map(([currency, value]) => formatCryptoAmount(value, currency))
                .join(' · ');
        }

        function formatRate(value, fiatCurrency, cryptoCurrency) {
            return `${value.toLocaleString('ru-RU', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 8,
            })} ${fiatCurrency}/${cryptoCurrency}`;
        }

        function setPerformanceText(element, value) {
            if (element) {
                element.textContent = value;
            }
        }

        function applyFiatCryptoDateFilter(preset = 'today') {
            const range = fiatCryptoDateRange(preset);
            const totals = {
                buy: {},
                sell: {},
                net: {},
                turnover: {},
                crypto: {},
                pairs: {},
            };
            let visibleCount = 0;

            fiatCryptoDateFilters.forEach((button) => {
                button.classList.toggle('active', button.dataset.fiatCryptoDateFilter === preset);
            });
            if (fiatCryptoManualDates) {
                fiatCryptoManualDates.hidden = preset !== 'manual';
            }

            fiatCryptoRows.forEach((row) => {
                const operationDate = row.dataset.operationDate || '';
                const inRange = (!range.from || operationDate >= range.from) && (!range.to || operationDate <= range.to);
                row.hidden = !inRange;

                if (!inRange) {
                    return;
                }

                visibleCount += 1;
                const side = row.dataset.operationSide || 'buy';
                const currency = row.dataset.fiatCurrency || 'FIAT';
                const amount = Number(row.dataset.fiatAmount || 0);
                const cryptoCurrency = row.dataset.cryptoCurrency || 'CRYPTO';
                const cryptoAmount = Number(row.dataset.cryptoAmount || 0);
                const pairKey = `${currency}/${cryptoCurrency}`;
                totals.buy[currency] = totals.buy[currency] || 0;
                totals.sell[currency] = totals.sell[currency] || 0;
                totals.net[currency] = totals.net[currency] || 0;
                totals.turnover[currency] = totals.turnover[currency] || 0;
                totals.crypto[cryptoCurrency] = totals.crypto[cryptoCurrency] || 0;
                totals.pairs[pairKey] = totals.pairs[pairKey] || {
                    fiatCurrency: currency,
                    cryptoCurrency,
                    buyFiat: 0,
                    buyCrypto: 0,
                    sellFiat: 0,
                    sellCrypto: 0,
                };
                totals.turnover[currency] += amount;
                totals.crypto[cryptoCurrency] += cryptoAmount;

                if (side === 'sell') {
                    totals.sell[currency] += amount;
                    totals.net[currency] += amount;
                    totals.pairs[pairKey].sellFiat += amount;
                    totals.pairs[pairKey].sellCrypto += cryptoAmount;
                } else {
                    totals.buy[currency] += amount;
                    totals.net[currency] -= amount;
                    totals.pairs[pairKey].buyFiat += amount;
                    totals.pairs[pairKey].buyCrypto += cryptoAmount;
                }
            });

            if (fiatCryptoSummaryCount) {
                fiatCryptoSummaryCount.textContent = String(visibleCount);
            }
            if (fiatCryptoSummaryBuy) {
                fiatCryptoSummaryBuy.textContent = formatFiatGroups(totals.buy);
            }
            if (fiatCryptoSummarySell) {
                fiatCryptoSummarySell.textContent = formatFiatGroups(totals.sell);
            }
            if (fiatCryptoSummaryNet) {
                fiatCryptoSummaryNet.textContent = formatFiatGroups(totals.net, true);
            }

            const buyRates = [];
            const sellRates = [];
            const margins = [];
            const profitByCurrency = {};
            Object.values(totals.pairs).forEach((pair) => {
                const avgBuy = pair.buyCrypto > 0 ? pair.buyFiat / pair.buyCrypto : 0;
                const avgSell = pair.sellCrypto > 0 ? pair.sellFiat / pair.sellCrypto : 0;
                if (avgBuy > 0) {
                    buyRates.push(formatRate(avgBuy, pair.fiatCurrency, pair.cryptoCurrency));
                }
                if (avgSell > 0) {
                    sellRates.push(formatRate(avgSell, pair.fiatCurrency, pair.cryptoCurrency));
                }
                if (avgBuy > 0 && avgSell > 0) {
                    const soldCost = pair.sellCrypto * avgBuy;
                    const profit = pair.sellFiat - soldCost;
                    const margin = soldCost > 0 ? (profit / soldCost) * 100 : 0;
                    profitByCurrency[pair.fiatCurrency] = (profitByCurrency[pair.fiatCurrency] || 0) + profit;
                    margins.push(`${margin > 0 ? '+' : ''}${margin.toLocaleString('ru-RU', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    })}% ${pair.fiatCurrency}/${pair.cryptoCurrency}`);
                }
            });

            setPerformanceText(fiatCryptoPerformanceTurnover, formatFiatGroups(totals.turnover));
            setPerformanceText(fiatCryptoPerformanceCrypto, formatCryptoGroups(totals.crypto));
            setPerformanceText(fiatCryptoPerformanceBuyRate, buyRates.length > 0 ? buyRates.join(' · ') : '—');
            setPerformanceText(fiatCryptoPerformanceSellRate, sellRates.length > 0 ? sellRates.join(' · ') : '—');
            setPerformanceText(fiatCryptoPerformanceMargin, margins.length > 0 ? margins.join(' · ') : '—');
            setPerformanceText(fiatCryptoPerformanceProfit, Object.keys(profitByCurrency).length > 0 ? formatFiatGroups(profitByCurrency, true) : '—');
        }

        function calculateFiatCrypto() {
            const side = fiatCryptoSide?.value || 'buy';
            const rate = Number(fiatCryptoRate?.value || 0);
            const fiatCurrency = fiatCryptoFiatCurrency?.value || '';
            const cryptoCurrency = fiatCryptoCryptoCurrency?.value || '';
            const fiatAccountOption = selectedOption(fiatCryptoFiatAccount);
            const cryptoAccountOption = selectedOption(fiatCryptoCryptoAccount);
            if (side === 'buy') {
                const fiat = Number(fiatCryptoFiat?.value || 0);
                if (fiatCryptoBuyOutput) {
                    fiatCryptoBuyOutput.value = fiat > 0 && rate > 0 ? (fiat / rate).toFixed(8) : '';
                }
                if (fiatCryptoFiatAccountMeta) {
                    const balance = Number(fiatAccountOption?.dataset.balance || 0);
                    fiatCryptoFiatAccountMeta.textContent = fiatAccountOption?.value
                        ? `Будет списано ${fiat.toLocaleString('ru-RU', { maximumFractionDigits: 2 })} ${fiatCurrency}. Доступно ${balance.toLocaleString('ru-RU', { maximumFractionDigits: 2 })} ${fiatCurrency}.`
                        : '';
                }
                if (fiatCryptoCryptoAccountMeta) {
                    const crypto = Number(fiatCryptoBuyOutput?.value || 0);
                    fiatCryptoCryptoAccountMeta.textContent = cryptoAccountOption?.value
                        ? `Будет зачислено ${crypto.toLocaleString('ru-RU', { maximumFractionDigits: 8 })} ${cryptoCurrency}.`
                        : '';
                }
            } else {
                const crypto = Number(fiatCryptoCrypto?.value || 0);
                if (fiatCryptoSellOutput) {
                    fiatCryptoSellOutput.value = crypto > 0 && rate > 0 ? (crypto * rate).toFixed(2) : '';
                }
                if (fiatCryptoCryptoAccountMeta) {
                    const balance = Number(cryptoAccountOption?.dataset.balance || 0);
                    fiatCryptoCryptoAccountMeta.textContent = cryptoAccountOption?.value
                        ? `Будет списано ${crypto.toLocaleString('ru-RU', { maximumFractionDigits: 8 })} ${cryptoCurrency}. Доступно ${balance.toLocaleString('ru-RU', { maximumFractionDigits: 8 })} ${cryptoCurrency}.`
                        : '';
                }
                if (fiatCryptoFiatAccountMeta) {
                    const fiat = Number(fiatCryptoSellOutput?.value || 0);
                    fiatCryptoFiatAccountMeta.textContent = fiatAccountOption?.value
                        ? `Будет зачислено ${fiat.toLocaleString('ru-RU', { maximumFractionDigits: 2 })} ${fiatCurrency}.`
                        : '';
                }
            }
        }

        function filterFiatCryptoAccounts() {
            const fiatCurrency = fiatCryptoFiatCurrency?.value || '';
            const cryptoCurrency = fiatCryptoCryptoCurrency?.value || '';
            [
                [fiatCryptoFiatAccount, fiatCurrency],
                [fiatCryptoCryptoAccount, cryptoCurrency],
            ].forEach(([select, currency]) => {
                if (!select) {
                    return;
                }
                Array.from(select.options).forEach((option) => {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    option.hidden = currency !== '' && option.dataset.currency !== currency;
                });
                const current = selectedOption(select);
                if (current?.value && current.dataset.currency !== currency) {
                    select.value = '';
                }
            });
            calculateFiatCrypto();
        }

        function setFiatCryptoSide(side) {
            if (fiatCryptoSide) {
                fiatCryptoSide.value = side;
            }
            fiatCryptoTabs.forEach((tab) => {
                tab.classList.toggle('active', tab.dataset.fiatCryptoTab === side);
            });
            if (fiatCryptoBuyField) {
                fiatCryptoBuyField.hidden = side !== 'buy';
            }
            if (fiatCryptoBuyResult) {
                fiatCryptoBuyResult.hidden = side !== 'buy';
            }
            if (fiatCryptoSellField) {
                fiatCryptoSellField.hidden = side !== 'sell';
            }
            if (fiatCryptoSellResult) {
                fiatCryptoSellResult.hidden = side !== 'sell';
            }
            if (fiatCryptoFiat) {
                fiatCryptoFiat.required = side === 'buy';
            }
            if (fiatCryptoCrypto) {
                fiatCryptoCrypto.required = side === 'sell';
            }
            calculateFiatCrypto();
        }

        function setFiatCryptoFormDisabled(disabled) {
            [
                fiatCryptoFiatCurrency,
                fiatCryptoCryptoCurrency,
                fiatCryptoDate,
                fiatCryptoFiatAccount,
                fiatCryptoCryptoAccount,
                fiatCryptoFiat,
                fiatCryptoCrypto,
                fiatCryptoRate,
                fiatCryptoNote,
                fiatCryptoPostLedger,
            ].forEach((field) => {
                if (field) {
                    field.disabled = disabled;
                }
            });
            fiatCryptoTabs.forEach((tab) => {
                tab.disabled = disabled;
            });
            if (fiatCryptoSubmit) {
                fiatCryptoSubmit.disabled = disabled;
            }
        }

        function resetFiatCryptoFormForCreate() {
            if (fiatCryptoForm instanceof HTMLFormElement) {
                fiatCryptoForm.action = fiatCryptoForm.dataset.defaultAction || fiatCryptoForm.action;
                fiatCryptoForm.dataset.mode = 'save';
                fiatCryptoForm.reset();
            }
            if (fiatCryptoFormTitle) {
                fiatCryptoFormTitle.textContent = 'Операция обмена';
            }
            setFiatCryptoFormDisabled(false);
            if (fiatCryptoPostLedger) {
                fiatCryptoPostLedger.checked = true;
            }
            if (fiatCryptoPostLedgerMeta) {
                fiatCryptoPostLedgerMeta.textContent = 'При сохранении будут обновлены остатки счетов и создана ledger-проводка.';
            }
            if (fiatCryptoSubmit) {
                fiatCryptoSubmit.textContent = 'Сохранить';
                fiatCryptoSubmit.classList.remove('btn-danger');
                fiatCryptoSubmit.classList.add('btn-primary');
            }
            setFiatCryptoSide('buy');
            filterFiatCryptoAccounts();
            if (fiatCryptoDate) {
                fiatCryptoDate.value = new Date().toISOString().slice(0, 10);
            }
        }

        function populateFiatCryptoForm(order) {
            const isPosted = Number(order.ledger_transaction_id || 0) > 0 && !order.is_reversed;
            if (fiatCryptoForm instanceof HTMLFormElement) {
                fiatCryptoForm.dataset.mode = isPosted ? 'reverse' : 'save';
                fiatCryptoForm.action = isPosted
                    ? String(order.reverse_url || '')
                    : fiatCryptoUpdateRouteTemplate.replace('__ORDER__', encodeURIComponent(String(order.id || '')));
            }
            if (fiatCryptoFormTitle) {
                fiatCryptoFormTitle.textContent = `Операция #${valueOrDash(order.id)}`;
            }

            setFiatCryptoFormDisabled(false);
            setFiatCryptoSide(order.side || 'buy');
            if (fiatCryptoFiatCurrency) {
                fiatCryptoFiatCurrency.value = order.fiat_currency || '';
            }
            if (fiatCryptoCryptoCurrency) {
                fiatCryptoCryptoCurrency.value = order.crypto_currency || '';
            }
            filterFiatCryptoAccounts();
            if (fiatCryptoFiatAccount) {
                fiatCryptoFiatAccount.value = String(order.fiat_account_id || '');
            }
            if (fiatCryptoCryptoAccount) {
                fiatCryptoCryptoAccount.value = String(order.crypto_account_id || '');
            }
            if (fiatCryptoDate) {
                fiatCryptoDate.value = order.operated_at || '';
            }
            if (fiatCryptoFiat) {
                fiatCryptoFiat.value = order.fiat_amount || '';
            }
            if (fiatCryptoCrypto) {
                fiatCryptoCrypto.value = order.crypto_amount || '';
            }
            if (fiatCryptoRate) {
                fiatCryptoRate.value = order.rate || '';
            }
            if (fiatCryptoNote) {
                fiatCryptoNote.value = order.note || '';
            }
            if (fiatCryptoPostLedger) {
                fiatCryptoPostLedger.checked = Boolean(order.is_reversed) || Number(order.ledger_transaction_id || 0) > 0;
            }
            if (fiatCryptoPostLedgerMeta) {
                fiatCryptoPostLedgerMeta.textContent = Boolean(order.is_reversed)
                    ? 'Операция отменена. При сохранении с активным чекбоксом будет создана новая проводка, старое сторно не изменится.'
                    : (Number(order.ledger_transaction_id || 0) > 0
                    ? `Проводка уже создана: TX #${order.ledger_transaction_id}.`
                    : 'Включите чекбокс, чтобы при сохранении обновить остатки счетов и создать ledger-проводку.');
            }
            if (!order.can_edit) {
                setFiatCryptoFormDisabled(true);
            }
            if (isPosted) {
                setFiatCryptoFormDisabled(true);
                if (fiatCryptoSubmit) {
                    fiatCryptoSubmit.textContent = 'Отменить сохранение';
                    fiatCryptoSubmit.classList.remove('btn-primary');
                    fiatCryptoSubmit.classList.add('btn-danger');
                    fiatCryptoSubmit.disabled = !order.can_cancel_save;
                }
                if (fiatCryptoPostLedgerMeta) {
                    fiatCryptoPostLedgerMeta.textContent = order.can_cancel_save
                        ? 'Это последняя активная проводка. Можно отменить сохранение и создать сторно.'
                        : 'Отменить можно только последнюю активную проводку обменки.';
                }
            } else if (fiatCryptoSubmit) {
                fiatCryptoSubmit.textContent = 'Сохранить';
                fiatCryptoSubmit.classList.remove('btn-danger');
                fiatCryptoSubmit.classList.add('btn-primary');
            }
            calculateFiatCrypto();
        }

        function populateFiatCryptoFormFromSwapOrder(order) {
            if (fiatCryptoForm instanceof HTMLFormElement) {
                fiatCryptoForm.action = fiatCryptoForm.dataset.defaultAction || fiatCryptoForm.action;
                fiatCryptoForm.dataset.mode = 'swap-order';
                fiatCryptoForm.reset();
            }
            setFiatCryptoFormDisabled(false);
            setFiatCryptoSide('sell');
            if (fiatCryptoFormTitle) {
                fiatCryptoFormTitle.textContent = `Обмен по заявке #${valueOrDash(order.id)}`;
            }
            if (fiatCryptoFiatCurrency) {
                fiatCryptoFiatCurrency.value = 'UAH';
            }
            if (fiatCryptoCryptoCurrency) {
                fiatCryptoCryptoCurrency.value = 'AV8';
            }
            filterFiatCryptoAccounts();
            if (fiatCryptoDate) {
                fiatCryptoDate.value = new Date().toISOString().slice(0, 10);
            }
            if (fiatCryptoFiat) {
                fiatCryptoFiat.value = parseOrderNumber(order.pay_amount).toFixed(2);
            }
            if (fiatCryptoCrypto) {
                fiatCryptoCrypto.value = parseOrderNumber(order.expected_av8).toFixed(8);
            }
            if (fiatCryptoRate) {
                fiatCryptoRate.value = parseOrderNumber(order.rate_usdc).toFixed(8);
            }
            if (fiatCryptoBuyOutput) {
                fiatCryptoBuyOutput.value = parseOrderNumber(order.expected_av8).toFixed(8);
            }
            if (fiatCryptoNote) {
                fiatCryptoNote.value = `Заявка #${valueOrDash(order.id)}: ${valueOrDash(order.client_email)} -> ${valueOrDash(order.wallet_address)}`;
            }
            if (fiatCryptoPostLedger) {
                fiatCryptoPostLedger.checked = true;
            }
            if (fiatCryptoPostLedgerMeta) {
                fiatCryptoPostLedgerMeta.textContent = 'Выберите операционные счета банка для проведения обычного обмена.';
            }
            if (fiatCryptoSubmit) {
                fiatCryptoSubmit.textContent = 'Оформить';
                fiatCryptoSubmit.classList.remove('btn-danger');
                fiatCryptoSubmit.classList.add('btn-primary');
                fiatCryptoSubmit.disabled = false;
            }
            calculateFiatCrypto();
            if (fiatCryptoSellOutput) {
                fiatCryptoSellOutput.value = parseOrderNumber(order.pay_amount).toFixed(2);
            }
        }

        fiatCryptoTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                setFiatCryptoSide(tab.dataset.fiatCryptoTab || 'buy');
            });
        });

        [fiatCryptoFiat, fiatCryptoCrypto, fiatCryptoRate].forEach((field) => {
            field?.addEventListener('input', calculateFiatCrypto);
        });

        [fiatCryptoFiatCurrency, fiatCryptoCryptoCurrency].forEach((field) => {
            field?.addEventListener('change', filterFiatCryptoAccounts);
        });

        [fiatCryptoFiatAccount, fiatCryptoCryptoAccount].forEach((field) => {
            field?.addEventListener('change', calculateFiatCrypto);
        });

        fiatCryptoDateFilters.forEach((button) => {
            button.addEventListener('click', () => {
                const preset = button.dataset.fiatCryptoDateFilter || 'today';
                window.localStorage?.setItem(fiatCryptoFilterStorageKey, preset);
                applyFiatCryptoDateFilter(preset);
            });
        });

        [fiatCryptoDateFrom, fiatCryptoDateTo].forEach((field) => {
            field?.addEventListener('change', () => {
                window.localStorage?.setItem(fiatCryptoFilterStorageKey, 'manual');
                applyFiatCryptoDateFilter('manual');
            });
        });

        fiatCryptoForm?.addEventListener('reset', () => {
            window.setTimeout(() => {
                setFiatCryptoSide(fiatCryptoForm.dataset.mode === 'swap-order' ? 'sell' : 'buy');
                filterFiatCryptoAccounts();
                if (fiatCryptoDate) {
                    fiatCryptoDate.value = new Date().toISOString().slice(0, 10);
                }
            }, 0);
        });

        document.getElementById('fiatCryptoExchangeModal')?.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            if (trigger instanceof HTMLElement && trigger.dataset.swapOrderExchange) {
                let order = {};
                try {
                    order = JSON.parse(trigger.dataset.swapOrderExchange || '{}');
                } catch (error) {
                    order = {};
                }
                populateFiatCryptoFormFromSwapOrder(order);
                return;
            }
            if (trigger instanceof HTMLElement && trigger.dataset.fiatCryptoOrder) {
                let order = {};
                try {
                    order = JSON.parse(trigger.dataset.fiatCryptoOrder || '{}');
                } catch (error) {
                    order = {};
                }
                populateFiatCryptoForm(order);
                return;
            }

            resetFiatCryptoFormForCreate();
        });

        fiatCryptoForm?.addEventListener('submit', (event) => {
            window.localStorage?.setItem(exchangeTabStorageKey, 'crypto');
            if (fiatCryptoForm.dataset.mode === 'reverse') {
                if (!confirm('Отменить сохранение и создать сторно проводки?')) {
                    event.preventDefault();
                }
                return;
            }
            if (!fiatCryptoPostLedger?.checked) {
                return;
            }
            const side = fiatCryptoSide?.value || 'buy';
            const fiat = Number(fiatCryptoFiat?.value || 0);
            const crypto = Number(fiatCryptoCrypto?.value || 0);
            const fiatBalance = Number(selectedOption(fiatCryptoFiatAccount)?.dataset.balance || 0);
            const cryptoBalance = Number(selectedOption(fiatCryptoCryptoAccount)?.dataset.balance || 0);
            if (side === 'buy' && fiatBalance + 0.000001 < fiat) {
                event.preventDefault();
                alert('Недостаточно средств на фиатном операционном счете.');
            }
            if (side === 'sell' && cryptoBalance + 0.00000001 < crypto) {
                event.preventDefault();
                alert('Недостаточно средств на крипто операционном счете.');
            }
        });

        filterFiatCryptoAccounts();
        applyFiatCryptoDateFilter(window.localStorage?.getItem(fiatCryptoFilterStorageKey) || 'today');
    });
</script>
@endpush
