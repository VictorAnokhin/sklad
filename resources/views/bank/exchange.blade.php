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
                        <th>Тип</th>
                        <th>Оплата</th>
                        <th class="text-end">Сумма</th>
                        <th class="text-end">AV8</th>
                        <th>Кошелек</th>
                        <th>Клиент</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($swapOrders as $order)
                        <tr>
                            <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                            <td>{{ $order->created_at }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ strtoupper($order->mode) }}</span></td>
                            <td>{{ $order->payment_method !== '' ? $order->payment_method : $order->pay_currency }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $order->pay_amount, 2, '.', ' ') }} {{ $order->pay_currency }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $order->expected_av8, 6, '.', ' ') }}</td>
                            <td class="bank-mono">{{ $order->wallet_address !== '' ? $order->wallet_address : '—' }}</td>
                            <td>
                                <div>{{ $order->client_email ?: '—' }}</div>
                                <div class="bank-meta">{{ $order->client_phone ?: '' }}</div>
                            </td>
                            <td><span class="bank-status">{{ $order->status }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Заявок обмена пока нет.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

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
