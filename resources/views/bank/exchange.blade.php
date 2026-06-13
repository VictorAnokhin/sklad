@extends('home')

@section('title')
Обмен фиат/крипта
@endsection

@section('content')
@php
    $ordersTotal = (float) $swapOrders->sum('pay_amount');
    $av8Total = (float) $swapOrders->sum('expected_av8');
    $frontendSwapUrl = rtrim((string) config('app.frontend_url', ''), '/') . '/swap';
    if ($frontendSwapUrl === '/swap') {
        $frontendSwapUrl = '/swap';
    }
@endphp
<div class="bank-page">
    @include('bank.partials.nav')

    <section class="bank-hero">
        <div>
            <div class="bank-label">Fiat / Crypto → AV8</div>
            <h1>Обмен фиат/крипта на AV8</h1>
            <p>Основные параметры формы обмена av8fund-react `/swap`, входящие заявки клиентов и история on-chain исполнения.</p>
        </div>
        <div class="bank-hero__metrics">
            <div>
                <span>Заявок</span>
                <strong>{{ $swapOrders->count() }}</strong>
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

    <section class="bank-panel bank-exchange-config">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Параметры frontend-формы</div>
                <div class="bank-meta">Эти значения использует форма av8fund-react `/swap`; заявка дополнительно пересчитывается на Laravel API.</div>
            </div>
            <a class="bank-account-link" href="{{ $frontendSwapUrl }}" target="_blank" rel="noopener">Открыть swap</a>
        </div>
        <div class="bank-exchange-grid">
            <div>
                <span>Поддерживаемый фиат</span>
                <strong>USD, EUR, UAH</strong>
            </div>
            <div>
                <span>Поддерживаемая крипта</span>
                <strong>USDC, USDT, SUI</strong>
            </div>
            <div>
                <span>Минимум покупки</span>
                <strong>{{ $exchangeSettings->min_buy_usdc > 0 ? number_format((float) $exchangeSettings->min_buy_usdc, 2, '.', ' ') . ' USDC' : 'Не задан' }}</strong>
            </div>
            <div>
                <span>Максимум покупки</span>
                <strong>{{ $exchangeSettings->max_buy_usdc > 0 ? number_format((float) $exchangeSettings->max_buy_usdc, 2, '.', ' ') . ' USDC' : 'Не задан' }}</strong>
            </div>
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
                        <th>Клиент</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($swapOrders as $order)
                        @php
                            $orderMeta = [];
                            if (! empty($order->meta)) {
                                $decodedMeta = json_decode((string) $order->meta, true);
                                $orderMeta = is_array($decodedMeta) ? $decodedMeta : [];
                            }
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
                                'wallet_address' => (string) $order->wallet_address,
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
                            <td class="bank-mono bank-table__wallet">{{ $order->wallet_address !== '' ? $order->wallet_address : '—' }}</td>
                            <td>
                                <div>{{ $order->client_email ?: '—' }}</div>
                                <div class="bank-meta">{{ $order->client_phone ?: '' }}</div>
                            </td>
                            <td><span class="bank-status">{{ $order->status }}</span></td>
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
                        <th>Wallet</th>
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
                            <td class="bank-mono">{{ $event->owner_address ?: '—' }}</td>
                            <td class="bank-mono">{{ $event->tx_digest ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Blockchain Listener еще не передал события обмена.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
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
    });
</script>
@endpush
