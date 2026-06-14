@extends('home')

@section('title')
Инвестиции
@endsection

@section('content')
@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 2, '.', ' ');
    $formatBps = static fn ($value): string => number_format((float) $value / 100, 2, '.', ' ') . '%';
@endphp

<div class="bank-page bank-invest-page" data-bank-invest-page>
    @include('bank.partials.nav')

    <section class="bank-hero bank-invest-hero">
        <div>
            <div class="bank-label">Invest cockpit</div>
            <h1>Инвестиции</h1>
            <p>Единый экран капитала: портфель банка, ликвидность, DeFi-позиции и пулы AV8 Capital по образцу Portfolio и Capital.</p>
        </div>
        <div class="bank-hero__metrics">
            <div>
                <span>NAV портфеля</span>
                <strong>{{ $formatMoney($summary['nav']) }} USD</strong>
            </div>
            <div>
                <span>Средняя доходность пулов</span>
                <strong>{{ $formatBps($summary['avg_apy_bps']) }}</strong>
            </div>
        </div>
    </section>

    <section class="bank-invest-tabs" role="tablist" aria-label="Invest sections">
        <button type="button" class="bank-invest-tab is-active" data-bank-invest-tab="portfolio">Портфолио</button>
        <button type="button" class="bank-invest-tab" data-bank-invest-tab="pools">Пулы</button>
    </section>

    <section data-bank-invest-panel="portfolio">
        <section class="bank-grid bank-grid--summary">
            <div class="bank-panel bank-panel--accent">
                <div class="bank-label">Portfolio NAV</div>
                <div class="bank-value">{{ $formatMoney($summary['nav']) }}</div>
                <div class="bank-meta">Депозиты, ликвидность и пулы в единой оценке.</div>
            </div>
            <div class="bank-panel">
                <div class="bank-label">Ликвидная часть</div>
                <div class="bank-value">{{ $formatMoney($summary['liquid']) }}</div>
                <div class="bank-meta">Свободные депозитные позиции.</div>
            </div>
            <div class="bank-panel">
                <div class="bank-label">DeFi / пулы</div>
                <div class="bank-value">{{ $formatMoney($summary['defi']) }}</div>
                <div class="bank-meta">Баланс пулов по последним событиям.</div>
            </div>
            <div class="bank-panel">
                <div class="bank-label">Portfolio Health</div>
                <div class="bank-value">{{ number_format((float) $summary['health'], 0, '.', ' ') }}%</div>
                <div class="bank-meta">Доля ликвидности в NAV.</div>
            </div>
        </section>

        <section class="bank-invest-grid">
            <div class="bank-panel bank-invest-command">
                <div class="bank-label">Command routes</div>
                <h2>Контроль капитала</h2>
                <p>Как в Portfolio: быстрые операционные маршруты вынесены рядом с агрегированным состоянием портфеля.</p>
                <div class="bank-invest-actions">
                    <a href="{{ route('bank.deposit') }}" class="bank-invest-action">
                        <strong>Депозитные счета</strong>
                        <span>Пополнения, выводы, лимиты</span>
                    </a>
                    <a href="{{ route('bank.payments') }}" class="bank-invest-action">
                        <strong>Платежи</strong>
                        <span>Потоки fiat и ledger</span>
                    </a>
                    <a href="{{ route('bank.clearing') }}" class="bank-invest-action">
                        <strong>Клиринг</strong>
                        <span>Внутренние расчеты холдинга</span>
                    </a>
                </div>
            </div>

            <div class="bank-panel bank-invest-health">
                <div class="bank-label">Portfolio flight path</div>
                <h2>NAV trajectory</h2>
                <div class="bank-invest-chart" aria-label="NAV trajectory preview">
                    <span style="height: 32%"></span>
                    <span style="height: 48%"></span>
                    <span style="height: 42%"></span>
                    <span style="height: 64%"></span>
                    <span style="height: 58%"></span>
                    <span style="height: 76%"></span>
                    <span style="height: 84%"></span>
                </div>
                <div class="bank-meta">График отражает структуру экрана Portfolio; фактическая динамика подключается через события и оценки активов.</div>
            </div>
        </section>

        <section class="bank-panel bank-table-panel">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">Asset manifest</div>
                    <div class="bank-meta">Основные категории активов и их доли в портфеле.</div>
                </div>
                <div class="bank-meta">{{ $portfolioRows->count() }} позиций</div>
            </div>
            <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
                <table class="table table-dark table-hover table-sm align-middle bank-table">
                    <thead>
                        <tr>
                            <th class="bank-table__num">№</th>
                            <th>Актив</th>
                            <th>Категория</th>
                            <th>Валюта</th>
                            <th class="text-end">Оценка</th>
                            <th class="text-end">Доля</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($portfolioRows as $row)
                            <tr>
                                <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $row->name }}</strong>
                                    <div class="bank-meta">{{ $row->description !== '' ? $row->description : '—' }}</div>
                                </td>
                                <td><span class="bank-pill {{ $row->group === 'defi' ? 'bank-pill--company' : 'bank-pill--currency' }}">{{ $row->type }}</span></td>
                                <td>{{ $row->currency }}</td>
                                <td class="text-end fw-semibold">{{ $formatMoney($row->value_usd) }}</td>
                                <td class="text-end">{{ number_format((float) $row->share, 1, '.', ' ') }}%</td>
                                <td><span class="bank-status {{ $row->status === 'active' ? '' : 'bank-status--pending' }}">{{ $row->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Портфель пока пуст.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>

    <section data-bank-invest-panel="pools" hidden>
        <section class="bank-grid bank-grid--summary">
            <div class="bank-panel bank-panel--accent">
                <div class="bank-label">Capital pools</div>
                <div class="bank-value">{{ $summary['pools'] }}</div>
                <div class="bank-meta">Всего пулов в fund_pools.</div>
            </div>
            <div class="bank-panel">
                <div class="bank-label">Активные</div>
                <div class="bank-value">{{ $summary['active_pools'] }}</div>
                <div class="bank-meta">Доступны для участия.</div>
            </div>
            <div class="bank-panel">
                <div class="bank-label">События</div>
                <div class="bank-value">{{ $summary['events'] }}</div>
                <div class="bank-meta">Последние синхронизированные события.</div>
            </div>
            <div class="bank-panel">
                <div class="bank-label">APY</div>
                <div class="bank-value">{{ $formatBps($summary['avg_apy_bps']) }}</div>
                <div class="bank-meta">Средняя целевая/реализованная ставка.</div>
            </div>
        </section>

        <section class="bank-panel bank-invest-pools">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">Capital</div>
                    <div class="bank-meta">Пулы концентрируют классы активов и показывают TVL, порог входа, доходность и snapshot.</div>
                </div>
                <div class="bank-meta">{{ $pools->count() }} пулов</div>
            </div>

            <div class="bank-pool-list">
                @forelse($pools as $pool)
                    @php
                        $spark = [28, 42, 36, 58, 52, 70, 64];
                        $apy = (int) $pool->apy_bps;
                    @endphp
                    <article class="bank-pool-card">
                        <div class="bank-pool-card__main">
                            <div class="bank-pool-avatar">
                                @if($pool->logo_url !== '')
                                    <img src="{{ $pool->logo_url }}" alt="{{ $pool->name }}">
                                @else
                                    {{ mb_substr($pool->symbol ?: 'AV8', 0, 3) }}
                                @endif
                            </div>
                            <div>
                                <div class="bank-pool-title">
                                    <strong>{{ $pool->name }}</strong>
                                    @if($pool->is_default_deposit)
                                        <span class="bank-status">default</span>
                                    @endif
                                    @if(! $pool->active)
                                        <span class="bank-status bank-status--pending">paused</span>
                                    @endif
                                </div>
                                <div class="bank-meta">
                                    <span class="bank-pill bank-pill--currency">{{ $pool->symbol }}</span>
                                    <span class="bank-pill bank-pill--company">AV8</span>
                                    <span class="bank-mono" title="{{ $pool->pool_object_id }}">{{ $pool->pool_object_short }}</span>
                                </div>
                                @if($pool->description !== '')
                                    <div class="bank-meta">{{ $pool->description }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="bank-pool-card__metric">
                            <span>TVL / Supply</span>
                            <strong>{{ $formatMoney($pool->balance_usdc) }} USDC</strong>
                        </div>
                        <div class="bank-pool-card__metric">
                            <span>Entry threshold</span>
                            <strong>{{ $pool->min_deposit_usdc > 0 ? $formatMoney($pool->min_deposit_usdc) . ' USDC' : number_format((float) $pool->min_av8_balance, 2, '.', ' ') . ' AV8' }}</strong>
                        </div>
                        <div class="bank-pool-card__metric">
                            <span>Fee / APY</span>
                            <strong>{{ $formatBps($apy) }}</strong>
                        </div>
                        <div class="bank-pool-spark" aria-label="Pool performance snapshot">
                            @foreach($spark as $height)
                                <span style="height: {{ max(12, min(90, $height + ($apy % 17))) }}%"></span>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="bank-empty">Пулы пока не заведены. Создайте их через админку fund_pools.</div>
                @endforelse
            </div>
        </section>

        <section class="bank-panel bank-table-panel">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">On-chain events</div>
                    <div class="bank-meta">Последние события pool_manager / fund_core.</div>
                </div>
                <div class="bank-meta">{{ $poolEvents->count() }} событий</div>
            </div>
            <div class="table-responsive bank-table-scroll">
                <table class="table table-dark table-hover table-sm align-middle bank-table">
                    <thead>
                        <tr>
                            <th class="bank-table__num">№</th>
                            <th>Дата</th>
                            <th>Событие</th>
                            <th>Pool</th>
                            <th>Owner</th>
                            <th class="text-end">Amount USDC</th>
                            <th>TX</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($poolEvents as $event)
                            <tr>
                                <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                                <td>{{ $event->event_at !== '' ? $event->event_at : '—' }}</td>
                                <td><span class="bank-pill bank-pill--company">{{ $event->event_type }}</span></td>
                                <td class="bank-mono" title="{{ $event->pool_object_id }}">{{ $event->pool_object_short }}</td>
                                <td class="bank-mono" title="{{ $event->owner_address }}">{{ $event->owner_short }}</td>
                                <td class="text-end fw-semibold">{{ $formatMoney($event->amount_usdc) }}</td>
                                <td class="bank-mono" title="{{ $event->tx_digest }}">{{ $event->tx_short }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">События пулов пока не синхронизированы.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>

@include('bank.partials.styles')

<style>
    .bank-invest-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 12px;
        padding: 6px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.58);
    }

    .bank-invest-tab {
        min-height: 40px;
        padding: 8px 16px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.35);
        color: rgba(226, 232, 240, 0.9);
        font-weight: 800;
    }

    .bank-invest-tab.is-active {
        background: #fbbf24;
        border-color: #fbbf24;
        color: #111827;
    }

    .bank-invest-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
        gap: 12px;
        margin-bottom: 12px;
    }

    .bank-invest-command h2,
    .bank-invest-health h2 {
        margin: 0 0 8px;
        color: #fff;
    }

    .bank-invest-command p {
        margin: 0 0 16px;
        color: rgba(203, 213, 225, 0.78);
    }

    .bank-invest-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .bank-invest-action {
        display: grid;
        gap: 5px;
        min-height: 92px;
        padding: 14px;
        border-radius: 10px;
        border: 1px solid rgba(34, 211, 238, 0.18);
        background: rgba(8, 47, 73, 0.24);
        color: #e0f2fe;
        text-decoration: none;
    }

    .bank-invest-action span {
        color: rgba(186, 230, 253, 0.72);
        font-size: 0.84rem;
    }

    .bank-invest-chart,
    .bank-pool-spark {
        display: flex;
        align-items: end;
        gap: 6px;
        height: 96px;
        padding: 12px;
        border-radius: 12px;
        background: rgba(2, 6, 23, 0.42);
        border: 1px solid rgba(148, 163, 184, 0.14);
    }

    .bank-invest-chart span,
    .bank-pool-spark span {
        flex: 1;
        min-width: 8px;
        border-radius: 999px 999px 0 0;
        background: linear-gradient(180deg, #4ade80, rgba(74, 222, 128, 0.18));
    }

    .bank-pool-list {
        display: grid;
        gap: 10px;
        padding: 14px;
    }

    .bank-pool-card {
        display: grid;
        grid-template-columns: minmax(280px, 2.2fr) repeat(3, minmax(130px, 0.8fr)) minmax(150px, 0.8fr);
        gap: 14px;
        align-items: center;
        padding: 14px;
        border-radius: 14px;
        background: rgba(2, 6, 23, 0.45);
        border: 1px solid rgba(148, 163, 184, 0.16);
    }

    .bank-pool-card__main {
        display: flex;
        min-width: 0;
        gap: 12px;
        align-items: center;
    }

    .bank-pool-avatar {
        display: inline-flex;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 50%;
        background: linear-gradient(135deg, #38bdf8, #22d3ee);
        color: #082f49;
        font-weight: 900;
        font-size: 0.78rem;
    }

    .bank-pool-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .bank-pool-title {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        color: #fff;
    }

    .bank-pool-card__metric {
        display: grid;
        gap: 4px;
    }

    .bank-pool-card__metric span {
        color: rgba(148, 163, 184, 0.9);
        font-size: 0.74rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .bank-pool-card__metric strong {
        color: #fff;
        font-size: 0.98rem;
    }

    .bank-pool-spark {
        height: 72px;
        padding: 10px;
    }

    @media (max-width: 1100px) {
        .bank-invest-grid,
        .bank-pool-card {
            grid-template-columns: 1fr;
        }

        .bank-invest-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-bank-invest-page]');
        if (!root) {
            return;
        }

        const tabs = root.querySelectorAll('[data-bank-invest-tab]');
        const panels = root.querySelectorAll('[data-bank-invest-panel]');

        function activate(name) {
            tabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.bankInvestTab === name);
            });
            panels.forEach((panel) => {
                panel.hidden = panel.dataset.bankInvestPanel !== name;
            });
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => activate(tab.dataset.bankInvestTab || 'portfolio'));
        });
    });
</script>
@endpush
