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
@endphp
<div class="bank-page">
    @include('bank.partials.nav')

    <ul class="nav nav-tabs bank-modal-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="bankExchangeAv8Tab" data-bs-toggle="tab" data-bs-target="#bankExchangeAv8Pane" type="button" role="tab">
                Фиат/AV8
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="bankExchangeCryptoTab" data-bs-toggle="tab" data-bs-target="#bankExchangeCryptoPane" type="button" role="tab">
                Фиат/Крипта
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="bankExchangeAv8Pane" role="tabpanel" aria-labelledby="bankExchangeAv8Tab">
    <section class="bank-hero">
        <div>
            <div class="bank-label">Fiat / Crypto → AV8</div>
            <h1>Обмен фиат/крипта на AV8</h1>
            <p>Заявки формы av8fund-react `/swap`, параметры расчета и история on-chain исполнения.</p>
        </div>
        <div class="bank-hero__metrics">
            <div>
                <span>Заявок</span>
                <strong>{{ $fiatAv8Orders->count() }}</strong>
            </div>
            <div>
                <span>К выдаче AV8</span>
                <strong>{{ number_format($av8Total, 2, '.', ' ') }}</strong>
            </div>
        </div>
    </section>

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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Заявок обмена пока нет.</td>
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

        <div class="tab-pane fade" id="bankExchangeCryptoPane" role="tabpanel" aria-labelledby="bankExchangeCryptoTab">
            <section class="bank-panel bank-table-panel">
                <div class="bank-table-header">
                    <div>
                        <div class="bank-label">Фиат/Крипта</div>
                        <div class="bank-meta">Ручные операции обменки: покупка и продажа криптовалюты за фиат.</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="bank-meta">{{ number_format($fiatCryptoTotal, 2, '.', ' ') }} фиатная сумма</div>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#fiatCryptoExchangeModal">
                            Обмен
                        </button>
                    </div>
                </div>

                <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
                    <table class="table table-dark table-hover table-sm align-middle bank-table bank-table--exchange-orders">
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
                                @endphp
                                <tr>
                                    <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                                    <td class="bank-table__date">{{ $order->created_at }}</td>
                                    <td>
                                        <span class="bank-pill {{ $side === 'sell' ? 'bank-pill--outgoing' : 'bank-pill--currency' }}">{{ $side === 'sell' ? 'Продать' : 'Купить' }}</span>
                                        <div class="bank-meta">{{ $fiatAccountLabel !== '' || $cryptoAccountLabel !== '' ? trim($fiatAccountLabel . ' ↔ ' . $cryptoAccountLabel) : 'Счета не указаны' }}</div>
                                    </td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $order->pay_amount, 2, '.', ' ') }} {{ $order->pay_currency }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($cryptoAmount, 8, '.', ' ') }} {{ $cryptoCurrency }}</td>
                                    <td class="text-end">{{ number_format((float) $order->rate_usdc, 8, '.', ' ') }}</td>
                                    <td>
                                        <span class="bank-status">{{ $order->status }}</span>
                                        <div class="bank-meta">{{ $ledgerTransactionId > 0 ? 'TX #' . $ledgerTransactionId : 'Ledger pending' }}</div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Операции Фиат/Крипта пока не созданы.</td>
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
                <form method="POST" action="{{ route('bank.exchange.crypto.store') }}" data-fiat-crypto-form>
                    @csrf
                    <input type="hidden" name="side" value="buy" data-fiat-crypto-side>
                    <div class="modal-header">
                        <div>
                            <div class="bank-label">Обменка</div>
                            <h5 class="modal-title" id="fiatCryptoExchangeModalLabel">Фиат/Крипта</h5>
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
                                    @foreach(['USDC', 'USDT', 'SUI', 'BTC', 'ETH'] as $currency)
                                        <option value="{{ $currency }}">{{ $currency }}</option>
                                    @endforeach
                                </select>
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
                                <input type="number" name="fiat_amount" class="form-control" min="0.01" step="0.01" inputmode="decimal" data-fiat-crypto-fiat>
                            </div>
                            <div class="col-12" data-fiat-crypto-sell-field hidden>
                                <label class="form-label">Крипта</label>
                                <input type="number" name="crypto_amount" class="form-control" min="0.00000001" step="0.00000001" inputmode="decimal" data-fiat-crypto-crypto>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Курс Фиат/Крипта</label>
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
                                <textarea name="note" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@include('bank.partials.styles')
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('swapOrderModal');
        const statusLabels = @json($exchangeOrderStatuses);
        const statusRouteTemplate = @json(route('bank.exchange-orders.status', ['order' => '__ORDER__']));
        if (!modal) {
            return;
        }

        function valueOrDash(value) {
            const normalized = String(value ?? '').trim();
            return normalized !== '' ? normalized : '—';
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

            const statusForm = modal.querySelector('[data-order-status-form]');
            const statusSelect = modal.querySelector('[data-order-status-select]');
            if (statusForm instanceof HTMLFormElement) {
                statusForm.action = statusRouteTemplate.replace('__ORDER__', encodeURIComponent(String(order.id || '')));
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

        document.querySelectorAll('.bank-order-row').forEach((row) => {
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
        const fiatCryptoBuyOutput = document.querySelector('[data-fiat-crypto-buy-output]');
        const fiatCryptoSellOutput = document.querySelector('[data-fiat-crypto-sell-output]');

        function selectedOption(select) {
            return select?.selectedOptions?.[0] || null;
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

        fiatCryptoForm?.addEventListener('reset', () => {
            window.setTimeout(() => {
                setFiatCryptoSide('buy');
                filterFiatCryptoAccounts();
            }, 0);
        });

        fiatCryptoForm?.addEventListener('submit', (event) => {
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
    });
</script>
@endpush
