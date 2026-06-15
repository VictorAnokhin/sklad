@extends('home')

@section('title')
Инвестиции
@endsection

@section('content')
@php
    $formatMoney = static fn ($value): string => number_format((float) $value, 2, '.', ' ');
    $formatBps = static fn ($value): string => number_format((float) $value / 100, 2, '.', ' ') . '%';
    $defiAssetRows = $assetManifestRows->where('group', 'defi')->values();
    $hiddenDefiAssetRows = $assetManifestHiddenRows->where('group', 'defi')->values();
    $trackedTokenRows = $trackedAssets->get('token', collect());
    $hiddenTrackedTokenRows = $trackedAssets->get('hidden_token', collect());
    $trackedNftRows = $trackedAssets->get('nft', collect());
    $hiddenTrackedNftRows = $trackedAssets->get('hidden_nft', collect());
    $trackedDefiRows = $trackedAssets->get('defi', collect());
    $hiddenTrackedDefiRows = $trackedAssets->get('hidden_defi', collect());
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
                <div class="bank-label">Google wallets</div>
                <div class="bank-value">{{ $walletPortfolio['wallets']->count() }}</div>
                <div class="bank-meta">{{ $formatMoney($summary['wallet_total']) }} USD в cached wallet data.</div>
            </div>
        </section>

        <section class="bank-invest-grid">
            <div class="bank-panel bank-invest-command">
                <div class="bank-label">Катитал</div>
                <div class="bank-value">{{ $formatMoney($summary['wallet_tokens_visible']) }} USD</div>
                <div class="bank-meta">Сумма токенов, которые сейчас не скрыты в таблице Tokens.</div>
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

        <section class="bank-panel bank-invest-wallets">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">Google account wallets</div>
                    <div class="bank-meta">Кошельки, привязанные к текущему аккаунту. Google / zkLogin кошельки помечены бейджем Google.</div>
                </div>
                <div class="bank-meta">{{ $walletPortfolio['wallets']->count() }} кошельков</div>
            </div>
            <div class="bank-wallet-strip">
                @forelse($walletPortfolio['wallets'] as $wallet)
                    <div class="bank-wallet-card">
                        <div>
                            <span class="bank-status {{ $wallet->source === 'google' ? '' : 'bank-status--pending' }}">{{ $wallet->source === 'google' ? 'Google' : 'Linked' }}</span>
                            <strong class="bank-mono" title="{{ $wallet->address }}">{{ $wallet->address_short }}</strong>
                        </div>
                        <div class="bank-meta">{{ strtoupper($wallet->network !== '' ? $wallet->network : 'network') }}{{ $wallet->connected_at !== '' ? ' · ' . $wallet->connected_at : '' }}</div>
                    </div>
                @empty
                    <div class="bank-empty">К текущему аккаунту не привязаны кошельки.</div>
                @endforelse
            </div>
        </section>

        <section class="bank-panel bank-invest-asset-filter" aria-label="Wallet asset filter">
            <div>
                <div class="bank-label">Активы</div>
                <div class="bank-meta">Фильтр по типу активов привязанных кошельков.</div>
            </div>
            <div class="bank-invest-asset-filter__controls">
                <label>
                    <span>Активы</span>
                    <select data-wallet-asset-filter>
                        <option value="tokens">Токены</option>
                        <option value="nft">NFT</option>
                        <option value="defi">DEFI</option>
                    </select>
                </label>
                <label class="bank-checkbox-field bank-checkbox-field--inline">
                    <input type="checkbox" data-wallet-asset-show-hidden>
                    <span>Показать скрытые</span>
                </label>
                <button type="button" class="btn btn-sm btn-outline-light" data-tracked-asset-open>
                    Добавить
                </button>
                <form method="POST" action="{{ route('bank.tracked-assets.refresh') }}" class="bank-inline-form">
                    @csrf
                    <input type="hidden" name="asset_type" data-wallet-asset-refresh-type value="tokens">
                    <button type="submit" class="btn btn-sm btn-primary">Обновить</button>
                </form>
            </div>
        </section>

        <section class="bank-invest-wallet-grid" data-wallet-asset-panel="tokens" data-wallet-asset-hidden-count="{{ $hiddenTokenRows->count() }}">
            <div class="bank-panel bank-table-panel">
                <div class="bank-table-header">
                    <div>
                        <div class="bank-label">Tokens</div>
                        <div class="bank-meta">Токены из cached wallet_tokens по привязанным кошелькам.</div>
                    </div>
                    <div class="bank-meta">{{ $tokenRows->count() }} токенов · {{ $formatMoney($summary['wallet_tokens']) }} USD</div>
                </div>
                <form method="POST" action="{{ route('bank.token-manifest.bulk') }}" data-bulk-form="tokens">
                    @csrf
                <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
                    <table class="table table-dark table-hover table-sm align-middle bank-table">
                        <thead>
                            <tr>
                                <th class="bank-table__select">
                                    <input type="checkbox" data-bulk-check-all="tokens" aria-label="Выбрать все токены">
                                </th>
                                <th>Токен</th>
                                <th>Кошелек</th>
                                <th>Chain</th>
                                <th class="text-end">Баланс</th>
                                <th class="text-end">Цена</th>
                                <th class="text-end">Value USD</th>
                                <th class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tokenRows as $token)
                                <tr
                                    class="bank-clickable-row"
                                    data-token-manifest-open
                                    data-token-name="{{ $token->symbol }}"
                                    data-token-description="{{ $token->name !== '' ? $token->name : ($token->token_short !== '—' ? $token->token_short : 'native') }}"
                                    data-token-wallet="{{ $token->wallet_short }}"
                                    data-token-hidden="{{ $token->manifest_hidden ? '1' : '0' }}"
                                    data-token-action="{{ route('bank.token-manifest.update', ['token' => $token->id]) }}"
                                >
                                    <td class="bank-table__select">
                                        <input type="checkbox" name="tokens[]" value="{{ $token->id }}" data-bulk-check="tokens" aria-label="Выбрать {{ $token->symbol }}">
                                    </td>
                                    <td>
                                        <strong>{{ $token->symbol }}</strong>
                                        <div class="bank-meta">{{ $token->name !== '' ? $token->name : ($token->token_short !== '—' ? $token->token_short : 'native') }}</div>
                                    </td>
                                    <td>
                                        <span class="bank-mono" title="{{ $token->wallet_address }}">{{ $token->wallet_short }}</span>
                                        @if($token->wallet_source === 'google')
                                            <span class="bank-status ms-1">Google</span>
                                        @endif
                                    </td>
                                    <td><span class="bank-pill bank-pill--currency">{{ strtoupper($token->chain !== '' ? $token->chain : 'chain') }}</span></td>
                                    <td class="text-end bank-mono">{{ number_format((float) $token->balance, 6, '.', ' ') }}</td>
                                    <td class="text-end">{{ $token->price_usd !== null ? $formatMoney($token->price_usd) : '—' }}</td>
                                    <td class="text-end fw-semibold">{{ $formatMoney($token->value_usd) }}</td>
                                    <td class="text-end">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-light"
                                            data-token-manifest-open
                                            data-token-name="{{ $token->symbol }}"
                                            data-token-description="{{ $token->name !== '' ? $token->name : ($token->token_short !== '—' ? $token->token_short : 'native') }}"
                                            data-token-wallet="{{ $token->wallet_short }}"
                                            data-token-hidden="{{ $token->manifest_hidden ? '1' : '0' }}"
                                            data-token-action="{{ route('bank.token-manifest.update', ['token' => $token->id]) }}"
                                        >Изменить</button>
                                    </td>
                                </tr>
                            @empty
                                <tr @if($hiddenTokenRows->isNotEmpty()) data-token-manifest-empty-visible @endif>
                                    <td colspan="8" class="text-center text-muted py-4">Cached токены для привязанных кошельков пока не найдены или все позиции скрыты.</td>
                                </tr>
                            @endforelse
                            @foreach($hiddenTokenRows as $token)
                                <tr
                                    class="bank-clickable-row"
                                    data-token-manifest-hidden-row
                                    data-token-manifest-open
                                    data-token-name="{{ $token->symbol }}"
                                    data-token-description="{{ $token->name !== '' ? $token->name : ($token->token_short !== '—' ? $token->token_short : 'native') }}"
                                    data-token-wallet="{{ $token->wallet_short }}"
                                    data-token-hidden="{{ $token->manifest_hidden ? '1' : '0' }}"
                                    data-token-action="{{ route('bank.token-manifest.update', ['token' => $token->id]) }}"
                                    hidden
                                >
                                    <td class="bank-table__select">
                                        <input type="checkbox" name="tokens[]" value="{{ $token->id }}" data-bulk-check="tokens" aria-label="Выбрать {{ $token->symbol }}">
                                    </td>
                                    <td>
                                        <strong>{{ $token->symbol }}</strong>
                                        <div class="bank-meta">{{ $token->name !== '' ? $token->name : ($token->token_short !== '—' ? $token->token_short : 'native') }}</div>
                                    </td>
                                    <td>
                                        <span class="bank-mono" title="{{ $token->wallet_address }}">{{ $token->wallet_short }}</span>
                                        @if($token->wallet_source === 'google')
                                            <span class="bank-status ms-1">Google</span>
                                        @endif
                                    </td>
                                    <td><span class="bank-pill bank-pill--currency">{{ strtoupper($token->chain !== '' ? $token->chain : 'chain') }}</span></td>
                                    <td class="text-end bank-mono">{{ number_format((float) $token->balance, 6, '.', ' ') }}</td>
                                    <td class="text-end">{{ $token->price_usd !== null ? $formatMoney($token->price_usd) : '—' }}</td>
                                    <td class="text-end fw-semibold">{{ $formatMoney($token->value_usd) }}</td>
                                    <td class="text-end">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-light"
                                            data-token-manifest-open
                                            data-token-name="{{ $token->symbol }}"
                                            data-token-description="{{ $token->name !== '' ? $token->name : ($token->token_short !== '—' ? $token->token_short : 'native') }}"
                                            data-token-wallet="{{ $token->wallet_short }}"
                                            data-token-hidden="{{ $token->manifest_hidden ? '1' : '0' }}"
                                            data-token-action="{{ route('bank.token-manifest.update', ['token' => $token->id]) }}"
                                        >Изменить</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                    <div class="bank-bulk-actions">
                        <span class="bank-meta">С выбранными:</span>
                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Удалить</button>
                        <button type="submit" name="action" value="hide" class="btn btn-sm btn-outline-light">Скрыть</button>
                        <button type="submit" name="action" value="show" class="btn btn-sm btn-outline-light">Показать</button>
                    </div>
                </form>
            </div>
            @include('bank.partials.tracked_assets_table', [
                'assetType' => 'token',
                'title' => 'Отслеживаемые токены',
                'rows' => $trackedTokenRows,
                'hiddenRows' => $hiddenTrackedTokenRows,
                'formatMoney' => $formatMoney,
            ])
        </section>

        <section data-wallet-asset-panel="nft" data-wallet-asset-hidden-count="0" hidden>
        <section class="bank-panel bank-table-panel">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">NFT</div>
                    <div class="bank-meta">NFT/RWA позиции из cached protocol payload, если провайдер вернул такие данные.</div>
                </div>
                <div class="bank-meta">{{ $walletPortfolio['nfts']->count() }} NFT · {{ $formatMoney($summary['wallet_nfts']) }} USD</div>
            </div>
            <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
                <table class="table table-dark table-hover table-sm align-middle bank-table">
                    <thead>
                        <tr>
                            <th class="bank-table__select">
                                <input type="checkbox" disabled aria-label="Выбрать все NFT">
                            </th>
                            <th>NFT</th>
                            <th>Collection</th>
                            <th>Кошелек</th>
                            <th>Chain</th>
                            <th class="text-end">Value USD</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($walletPortfolio['nfts'] as $nft)
                            <tr>
                                <td class="bank-table__select">
                                    <input type="checkbox" disabled aria-label="Выбрать {{ $nft->name }}">
                                </td>
                                <td>
                                    <strong>{{ $nft->name }}</strong>
                                    <div class="bank-meta bank-mono" title="{{ $nft->object_id }}">{{ $nft->object_short }}</div>
                                </td>
                                <td>{{ $nft->collection !== '' ? $nft->collection : '—' }}</td>
                                <td>
                                    <span class="bank-mono" title="{{ $nft->wallet_address }}">{{ $nft->wallet_short }}</span>
                                    @if($nft->wallet_source === 'google')
                                        <span class="bank-status ms-1">Google</span>
                                    @endif
                                </td>
                                <td><span class="bank-pill bank-pill--currency">{{ strtoupper($nft->chain !== '' ? $nft->chain : 'chain') }}</span></td>
                                <td class="text-end fw-semibold">{{ $formatMoney($nft->value_usd) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">NFT для привязанных кошельков пока не найдены в кеше.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        @include('bank.partials.tracked_assets_table', [
            'assetType' => 'nft',
            'title' => 'Отслеживаемые NFT',
            'rows' => $trackedNftRows,
            'hiddenRows' => $hiddenTrackedNftRows,
            'formatMoney' => $formatMoney,
        ])
        </section>

        <section data-wallet-asset-panel="defi" data-wallet-asset-hidden-count="{{ $hiddenDefiAssetRows->count() }}" hidden>
        <section class="bank-panel bank-table-panel">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">DEFI</div>
                    <div class="bank-meta">DeFi-позиции из Asset manifest.</div>
                </div>
                <div class="bank-meta">{{ $defiAssetRows->count() }} позиций</div>
            </div>
            <form method="POST" action="{{ route('bank.asset-manifest.bulk') }}" data-bulk-form="defi">
                @csrf
            <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
                <table class="table table-dark table-hover table-sm align-middle bank-table">
                    <thead>
                        <tr>
                            <th class="bank-table__select">
                                <input type="checkbox" data-bulk-check-all="defi" aria-label="Выбрать все DEFI">
                            </th>
                            <th>Позиция</th>
                            <th>Актив</th>
                            <th>Категория</th>
                            <th>Валюта</th>
                            <th class="text-end">Оценка</th>
                            <th class="text-end">Доля</th>
                            <th>Статус</th>
                            <th class="text-end">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($defiAssetRows as $row)
                            <tr>
                                <td class="bank-table__select">
                                    <input type="checkbox" name="assets[]" value="{{ $row->asset_type }}:{{ $row->asset_id }}" data-bulk-check="defi" aria-label="Выбрать {{ $row->name }}">
                                </td>
                                <td class="bank-mono">{{ (int) $row->manifest_position > 0 ? (int) $row->manifest_position : '—' }}</td>
                                <td>
                                    <strong>{{ $row->name }}</strong>
                                    <div class="bank-meta">{{ $row->description !== '' ? $row->description : '—' }}</div>
                                </td>
                                <td><span class="bank-pill {{ $row->group === 'defi' ? 'bank-pill--company' : 'bank-pill--currency' }}">{{ $row->type }}</span></td>
                                <td>{{ $row->currency }}</td>
                                <td class="text-end fw-semibold">{{ $formatMoney($row->value_usd) }}</td>
                                <td class="text-end">{{ number_format((float) $row->share, 1, '.', ' ') }}%</td>
                                <td><span class="bank-status {{ $row->status === 'active' ? '' : 'bank-status--pending' }}">{{ $row->status }}</span></td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-light"
                                        data-asset-manifest-open
                                        data-asset-name="{{ $row->name }}"
                                        data-asset-type="{{ $row->type }}"
                                        data-asset-position="{{ (int) $row->manifest_position }}"
                                        data-asset-hidden="{{ $row->manifest_hidden ? '1' : '0' }}"
                                        data-asset-action="{{ route('bank.asset-manifest.update', ['source' => $row->asset_type, 'asset' => $row->asset_id]) }}"
                                    >Изменить</button>
                                </td>
                            </tr>
                        @empty
                            <tr @if($hiddenDefiAssetRows->isNotEmpty()) data-asset-manifest-empty-visible @endif>
                                <td colspan="9" class="text-center text-muted py-4">Портфель пока пуст или все позиции скрыты.</td>
                            </tr>
                        @endforelse
                        @foreach($hiddenDefiAssetRows as $row)
                            <tr
                                class="bank-clickable-row"
                                data-asset-manifest-hidden-row
                                data-asset-manifest-open
                                data-asset-name="{{ $row->name }}"
                                data-asset-type="{{ $row->type }}"
                                data-asset-position="{{ (int) $row->manifest_position }}"
                                data-asset-hidden="{{ $row->manifest_hidden ? '1' : '0' }}"
                                data-asset-action="{{ route('bank.asset-manifest.update', ['source' => $row->asset_type, 'asset' => $row->asset_id]) }}"
                                hidden
                            >
                                <td class="bank-table__select">
                                    <input type="checkbox" name="assets[]" value="{{ $row->asset_type }}:{{ $row->asset_id }}" data-bulk-check="defi" aria-label="Выбрать {{ $row->name }}">
                                </td>
                                <td class="bank-mono">{{ (int) $row->manifest_position > 0 ? (int) $row->manifest_position : '—' }}</td>
                                <td>
                                    <strong>{{ $row->name }}</strong>
                                    <div class="bank-meta">{{ $row->description !== '' ? $row->description : '—' }}</div>
                                </td>
                                <td><span class="bank-pill {{ $row->group === 'defi' ? 'bank-pill--company' : 'bank-pill--currency' }}">{{ $row->type }}</span></td>
                                <td>{{ $row->currency }}</td>
                                <td class="text-end fw-semibold">{{ $formatMoney($row->value_usd) }}</td>
                                <td class="text-end">{{ number_format((float) $row->share, 1, '.', ' ') }}%</td>
                                <td><span class="bank-status bank-status--pending">hidden</span></td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-light"
                                        data-asset-manifest-open
                                        data-asset-name="{{ $row->name }}"
                                        data-asset-type="{{ $row->type }}"
                                        data-asset-position="{{ (int) $row->manifest_position }}"
                                        data-asset-hidden="{{ $row->manifest_hidden ? '1' : '0' }}"
                                        data-asset-action="{{ route('bank.asset-manifest.update', ['source' => $row->asset_type, 'asset' => $row->asset_id]) }}"
                                    >Изменить</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
                <div class="bank-bulk-actions">
                    <span class="bank-meta">С выбранными:</span>
                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-danger">Удалить</button>
                    <button type="submit" name="action" value="hide" class="btn btn-sm btn-outline-light">Скрыть</button>
                    <button type="submit" name="action" value="show" class="btn btn-sm btn-outline-light">Показать</button>
                </div>
            </form>
        </section>
        @include('bank.partials.tracked_assets_table', [
            'assetType' => 'defi',
            'title' => 'Отслеживаемые DEFI',
            'rows' => $trackedDefiRows,
            'hiddenRows' => $hiddenTrackedDefiRows,
            'formatMoney' => $formatMoney,
        ])
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

    <div class="bank-modal" data-tracked-asset-modal hidden>
        <div class="bank-modal__backdrop" data-tracked-asset-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="trackedAssetModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Активы</div>
                    <h2 id="trackedAssetModalTitle">Добавить актив</h2>
                    <div class="bank-meta">Данные для отслеживания актива в блокчейне.</div>
                </div>
                <button type="button" class="bank-modal__close" data-tracked-asset-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.tracked-assets.store') }}" class="bank-requisites-form">
                @csrf
                <div class="bank-form-grid">
                    <label>
                        <span>Тип</span>
                        <select name="asset_type" data-tracked-asset-type required>
                            <option value="token">Токен</option>
                            <option value="nft">NFT</option>
                            <option value="defi">DEFI</option>
                        </select>
                    </label>
                    <label>
                        <span>Блокчейн</span>
                        <select name="blockchain" required>
                            <option value="solana">Solana</option>
                            <option value="sui">Sui</option>
                            <option value="arbitrum">Arbitrum</option>
                            <option value="ethereum">Ethereum</option>
                            <option value="base">Base</option>
                            <option value="polygon">Polygon</option>
                            <option value="bnb">BNB</option>
                        </select>
                    </label>
                    <label class="bank-form-full">
                        <span>Адрес актива / позиции</span>
                        <input type="text" name="asset_address" required placeholder="0x..., mint, object id, pool address">
                    </label>
                    <label class="bank-form-full">
                        <span>Кошелек владельца</span>
                        <input type="text" name="owner_address" placeholder="Адрес кошелька, если нужен для чтения позиции">
                    </label>
                    <label>
                        <span>Название</span>
                        <input type="text" name="name" placeholder="USDC / Orca LP / NFT">
                    </label>
                    <label>
                        <span>Symbol</span>
                        <input type="text" name="symbol" placeholder="USDC">
                    </label>
                    <label>
                        <span>Protocol</span>
                        <input type="text" name="protocol" placeholder="Orca, Cetus, Aave">
                    </label>
                    <label>
                        <span>Token ID</span>
                        <input type="text" name="token_id" placeholder="Для NFT">
                    </label>
                    <label>
                        <span>Decimals</span>
                        <input type="number" name="decimals" min="0" max="255" step="1" inputmode="numeric" placeholder="6">
                    </label>
                </div>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-tracked-asset-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Добавить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bank-modal" data-tracked-adapter-modal hidden>
        <div class="bank-modal__backdrop" data-tracked-adapter-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="trackedAdapterModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Blockchain adapter</div>
                    <h2 id="trackedAdapterModalTitle">Настройки адаптера</h2>
                    <div class="bank-meta" data-tracked-adapter-context></div>
                </div>
                <button type="button" class="bank-modal__close" data-tracked-adapter-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" class="bank-requisites-form" data-tracked-adapter-form>
                @csrf
                <div class="bank-tracked-preview" data-tracked-adapter-preview></div>
                <div class="bank-adapter-field-list" data-tracked-adapter-fields></div>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-tracked-adapter-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bank-modal" data-token-manifest-modal hidden>
        <div class="bank-modal__backdrop" data-token-manifest-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tokenManifestModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Tokens</div>
                    <h2 id="tokenManifestModalTitle">Изменить токен</h2>
                    <div class="bank-meta" data-token-manifest-context></div>
                </div>
                <button type="button" class="bank-modal__close" data-token-manifest-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" class="bank-requisites-form" data-token-manifest-form>
                @csrf
                <div class="bank-form-grid">
                    <label>
                        <span>Токен</span>
                        <input type="text" data-token-manifest-name readonly>
                    </label>
                    <label>
                        <span>Кошелек</span>
                        <input type="text" data-token-manifest-wallet readonly>
                    </label>
                    <label class="bank-form-full">
                        <span>Описание</span>
                        <input type="text" data-token-manifest-description readonly>
                    </label>
                    <label class="bank-checkbox-field">
                        <input type="checkbox" name="hidden" value="1" data-token-manifest-hidden>
                        <span>Скрыть в Tokens</span>
                    </label>
                </div>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-token-manifest-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bank-modal" data-asset-manifest-modal hidden>
        <div class="bank-modal__backdrop" data-asset-manifest-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="assetManifestModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Asset manifest</div>
                    <h2 id="assetManifestModalTitle">Изменить позицию</h2>
                    <div class="bank-meta" data-asset-manifest-context></div>
                </div>
                <button type="button" class="bank-modal__close" data-asset-manifest-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" class="bank-requisites-form" data-asset-manifest-form>
                @csrf
                <div class="bank-form-grid">
                    <label>
                        <span>Актив</span>
                        <input type="text" data-asset-manifest-name readonly>
                    </label>
                    <label>
                        <span>Категория</span>
                        <input type="text" data-asset-manifest-type readonly>
                    </label>
                    <label>
                        <span>Позиция в таблице</span>
                        <input type="number" name="position" min="0" step="1" inputmode="numeric" data-asset-manifest-position>
                    </label>
                    <label class="bank-checkbox-field">
                        <input type="checkbox" name="hidden" value="1" data-asset-manifest-hidden>
                        <span>Скрыть в Asset manifest</span>
                    </label>
                </div>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-asset-manifest-close>Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
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

    .bank-invest-tabs--sub {
        margin-top: 12px;
    }

    .bank-invest-asset-filter {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 12px;
        align-items: end;
        margin: 12px 0;
        padding: 12px 14px;
    }

    .bank-invest-asset-filter__controls {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: end;
    }

    .bank-inline-form {
        display: inline-flex;
        margin: 0;
    }

    .bank-invest-asset-filter label:not(.bank-checkbox-field) {
        display: grid;
        gap: 5px;
        min-width: 180px;
        color: rgba(203, 213, 225, 0.8);
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .bank-invest-asset-filter select {
        min-height: 38px;
        border: 1px solid rgba(148, 163, 184, 0.24);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.72);
        color: #fff;
        padding: 7px 10px;
        font-size: 0.9rem;
        text-transform: none;
        outline: none;
    }

    .bank-invest-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
        gap: 12px;
        margin-bottom: 12px;
    }

    .bank-invest-wallet-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 12px;
        margin-bottom: 12px;
    }

    .bank-invest-wallet-grid > .bank-table-panel:only-child {
        grid-column: 1 / -1;
    }

    .bank-table-header__actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
        align-items: center;
    }

    .bank-invest-page .bank-table-header {
        padding: 10px 14px;
    }

    .bank-invest-page .bank-table {
        font-size: 0.82rem;
    }

    .bank-invest-page .bank-table th,
    .bank-invest-page .bank-table td {
        padding: 0.34rem 0.5rem;
    }

    .bank-invest-page .bank-table .bank-meta {
        margin-top: 1px;
        font-size: 0.73rem;
        line-height: 1.25;
    }

    .bank-invest-page .bank-table .bank-mono {
        font-size: 0.78rem;
    }

    .bank-invest-page .bank-table .bank-pill,
    .bank-invest-page .bank-table .bank-status {
        min-height: 20px;
        padding: 1px 6px;
        font-size: 0.72rem;
        line-height: 1.2;
    }

    .bank-invest-page .bank-table .btn-sm {
        --bs-btn-padding-y: 0.12rem;
        --bs-btn-padding-x: 0.42rem;
        --bs-btn-font-size: 0.74rem;
    }

    .bank-table__select {
        width: 42px;
        text-align: center;
    }

    .bank-table__select input {
        width: 16px;
        height: 16px;
        vertical-align: middle;
        accent-color: #fbbf24;
    }

    .bank-bulk-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        justify-content: flex-end;
        padding: 10px 14px 14px;
        border-top: 1px solid rgba(148, 163, 184, 0.12);
    }

    .bank-tracked-asset-image {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid rgba(148, 163, 184, 0.2);
        background: rgba(2, 6, 23, 0.6);
    }

    .bank-adapter-field-list {
        display: grid;
        gap: 8px;
    }

    .bank-adapter-field {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 10px;
        align-items: start;
        padding: 10px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 10px;
        background: rgba(2, 6, 23, 0.42);
    }

    .bank-adapter-field input {
        margin-top: 3px;
        accent-color: #fbbf24;
    }

    .bank-tracked-preview {
        display: grid;
        gap: 10px;
        margin-bottom: 12px;
    }

    .bank-tracked-preview__main {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 12px;
        align-items: center;
        padding: 10px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 12px;
        background: rgba(2, 6, 23, 0.42);
    }

    .bank-tracked-preview__main img {
        width: 86px;
        height: 86px;
        border-radius: 12px;
        object-fit: cover;
        border: 1px solid rgba(148, 163, 184, 0.22);
    }

    .bank-tracked-preview__grid {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .bank-tracked-preview__tile {
        min-width: 0;
        padding: 9px;
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.44);
    }

    .bank-clickable-row {
        cursor: pointer;
    }

    .bank-clickable-row:hover td {
        background: rgba(251, 191, 36, 0.08);
    }

    .bank-wallet-strip {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 14px;
    }

    .bank-wallet-card {
        min-width: 240px;
        padding: 12px;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(2, 6, 23, 0.42);
    }

    .bank-wallet-card > div:first-child {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
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

    .bank-checkbox-field {
        display: flex !important;
        flex-direction: row;
        align-items: center;
        gap: 10px !important;
        min-height: 42px;
        padding-top: 22px;
    }

    .bank-checkbox-field input {
        width: 18px !important;
        height: 18px;
        flex: 0 0 18px;
    }

    @media (max-width: 1100px) {
        .bank-invest-grid,
        .bank-invest-wallet-grid,
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

        const walletAssetFilter = root.querySelector('[data-wallet-asset-filter]');
        const walletAssetShowHidden = root.querySelector('[data-wallet-asset-show-hidden]');
        const walletAssetPanels = root.querySelectorAll('[data-wallet-asset-panel]');

        function activateWalletAssetPanel(name) {
            walletAssetPanels.forEach((panel) => {
                panel.hidden = panel.dataset.walletAssetPanel !== name;
            });
            syncHiddenRows();
        }

        function activeWalletAssetPanel() {
            return Array.from(walletAssetPanels).find((panel) => !panel.hidden);
        }

        function syncHiddenRows() {
            const activePanel = activeWalletAssetPanel();
            const showHidden = Boolean(walletAssetShowHidden && walletAssetShowHidden.checked);
            walletAssetPanels.forEach((panel) => {
                const panelActive = panel === activePanel;
                panel.querySelectorAll('[data-token-manifest-hidden-row], [data-asset-manifest-hidden-row]').forEach((row) => {
                    row.hidden = !(panelActive && showHidden);
                });
                panel.querySelectorAll('[data-tracked-asset-hidden-row]').forEach((row) => {
                    row.hidden = !(panelActive && showHidden);
                });
                panel.querySelectorAll('[data-token-manifest-empty-visible], [data-asset-manifest-empty-visible], [data-tracked-asset-empty-visible]').forEach((emptyVisible) => {
                    emptyVisible.hidden = panelActive && showHidden;
                });
            });
        }

        if (walletAssetFilter) {
            walletAssetFilter.addEventListener('change', () => {
                syncTrackedAssetType();
                activateWalletAssetPanel(walletAssetFilter.value || 'tokens');
            });
        }

        if (walletAssetShowHidden) {
            walletAssetShowHidden.addEventListener('change', syncHiddenRows);
        }

        activateWalletAssetPanel(walletAssetFilter ? (walletAssetFilter.value || 'tokens') : 'tokens');

        const trackedAssetModal = root.querySelector('[data-tracked-asset-modal]');
        const trackedAssetType = root.querySelector('[data-tracked-asset-type]');
        const trackedAssetRefreshType = root.querySelector('[data-wallet-asset-refresh-type]');

        function currentAssetType() {
            return walletAssetFilter ? (walletAssetFilter.value || 'tokens') : 'tokens';
        }

        function normalizedTrackedType() {
            const type = currentAssetType();
            return type === 'tokens' ? 'token' : type;
        }

        function syncTrackedAssetType() {
            if (trackedAssetType) {
                trackedAssetType.value = normalizedTrackedType();
            }
            if (trackedAssetRefreshType) {
                trackedAssetRefreshType.value = currentAssetType();
            }
        }

        syncTrackedAssetType();

        root.querySelectorAll('[data-tracked-asset-open]').forEach((button) => {
            button.addEventListener('click', () => {
                syncTrackedAssetType();
                if (trackedAssetModal) {
                    trackedAssetModal.hidden = false;
                    const addressInput = trackedAssetModal.querySelector('[name="asset_address"]');
                    if (addressInput) {
                        addressInput.focus();
                    }
                }
            });
        });

        root.querySelectorAll('[data-tracked-asset-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (trackedAssetModal) {
                    trackedAssetModal.hidden = true;
                }
            });
        });

        const trackedAdapterModal = root.querySelector('[data-tracked-adapter-modal]');
        const trackedAdapterForm = root.querySelector('[data-tracked-adapter-form]');
        const trackedAdapterContext = root.querySelector('[data-tracked-adapter-context]');
        const trackedAdapterFields = root.querySelector('[data-tracked-adapter-fields]');
        const trackedAdapterPreview = root.querySelector('[data-tracked-adapter-preview]');

        function parseJsonAttribute(value, fallback) {
            try {
                return JSON.parse(value || '');
            } catch (error) {
                return fallback;
            }
        }

        function adapterValueFor(payload, key) {
            if (!payload || typeof payload !== 'object') {
                return '';
            }
            const value = payload[key];
            if (value === null || value === undefined || value === '') {
                return '';
            }
            if (Array.isArray(value)) {
                return `${value.length} item(s)`;
            }
            if (typeof value === 'object') {
                return JSON.stringify(value);
            }
            return String(value);
        }

        function appendPreviewTile(parent, label, value) {
            if (value === null || value === undefined || value === '') {
                return;
            }
            const tile = document.createElement('div');
            tile.className = 'bank-tracked-preview__tile';
            const title = document.createElement('div');
            title.className = 'bank-label';
            title.textContent = label;
            const body = document.createElement('div');
            body.className = 'bank-meta bank-mono';
            body.textContent = Array.isArray(value) || typeof value === 'object' ? JSON.stringify(value) : String(value);
            tile.appendChild(title);
            tile.appendChild(body);
            parent.appendChild(tile);
        }

        function openTrackedAdapterModal(source) {
            if (!trackedAdapterModal || !trackedAdapterForm || !trackedAdapterFields) {
                return;
            }

            const fields = parseJsonAttribute(source.dataset.adapterFields, []);
            const selected = parseJsonAttribute(source.dataset.adapterSelected, []);
            const payload = parseJsonAttribute(source.dataset.adapterPayload, {});
            trackedAdapterForm.action = source.dataset.adapterAction || '';
            trackedAdapterFields.innerHTML = '';
            if (trackedAdapterPreview) {
                trackedAdapterPreview.innerHTML = '';
            }
            if (trackedAdapterContext) {
                trackedAdapterContext.textContent = [source.dataset.adapterName || '', source.dataset.adapterType || '', source.dataset.adapterId || '']
                    .filter(Boolean)
                    .join(' · ');
            }

            if (trackedAdapterPreview) {
                const main = document.createElement('div');
                main.className = 'bank-tracked-preview__main';
                const imageUrl = source.dataset.adapterImage || payload.image_url || '';
                if (imageUrl) {
                    const image = document.createElement('img');
                    image.src = imageUrl;
                    image.alt = source.dataset.adapterName || 'Tracked asset';
                    main.appendChild(image);
                }
                const mainText = document.createElement('div');
                const name = document.createElement('strong');
                name.textContent = source.dataset.adapterName || payload.name || 'Tracked asset';
                const meta = document.createElement('div');
                meta.className = 'bank-meta';
                meta.textContent = [source.dataset.adapterType || '', source.dataset.adapterId || ''].filter(Boolean).join(' · ');
                mainText.appendChild(name);
                mainText.appendChild(meta);
                const externalUrl = source.dataset.adapterExternal || payload.external_url || '';
                if (externalUrl) {
                    const link = document.createElement('a');
                    link.href = externalUrl;
                    link.target = '_blank';
                    link.rel = 'noreferrer';
                    link.className = 'btn btn-sm btn-outline-light mt-2';
                    link.textContent = 'Открыть ссылку';
                    mainText.appendChild(link);
                }
                main.appendChild(mainText);
                trackedAdapterPreview.appendChild(main);

                const grid = document.createElement('div');
                grid.className = 'bank-tracked-preview__grid';
                ['description', 'collection', 'owner', 'royalty_bps', 'token_standard', 'compressed', 'attributes', 'creators'].forEach((key) => {
                    appendPreviewTile(grid, key, payload[key]);
                });
                if (grid.children.length > 0) {
                    trackedAdapterPreview.appendChild(grid);
                }
            }

            if (!Array.isArray(fields) || fields.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'bank-empty';
                empty.textContent = 'Адаптер пока не вернул список полей. Нажмите Обновить для чтения данных.';
                trackedAdapterFields.appendChild(empty);
            } else {
                fields.forEach((field) => {
                    const key = String(field.key || '');
                    if (key === '') {
                        return;
                    }
                    const label = document.createElement('label');
                    label.className = 'bank-adapter-field';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'selected_fields[]';
                    checkbox.value = key;
                    checkbox.checked = Array.isArray(selected) ? selected.includes(key) : false;

                    const content = document.createElement('span');
                    const title = document.createElement('strong');
                    title.textContent = field.label || key;
                    const meta = document.createElement('span');
                    meta.className = 'bank-meta';
                    const currentValue = adapterValueFor(payload, key);
                    meta.textContent = [field.type || 'field', currentValue ? `value: ${currentValue}` : 'no value yet'].join(' · ');

                    content.appendChild(title);
                    content.appendChild(document.createElement('br'));
                    content.appendChild(meta);
                    label.appendChild(checkbox);
                    label.appendChild(content);
                    trackedAdapterFields.appendChild(label);
                });
            }

            trackedAdapterModal.hidden = false;
        }

        root.querySelectorAll('[data-tracked-adapter-open]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                openTrackedAdapterModal(button);
            });
        });

        root.querySelectorAll('[data-tracked-adapter-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (trackedAdapterModal) {
                    trackedAdapterModal.hidden = true;
                }
            });
        });

        const manifestModal = root.querySelector('[data-asset-manifest-modal]');
        const manifestForm = root.querySelector('[data-asset-manifest-form]');
        const manifestContext = root.querySelector('[data-asset-manifest-context]');
        const manifestName = root.querySelector('[data-asset-manifest-name]');
        const manifestType = root.querySelector('[data-asset-manifest-type]');
        const manifestPosition = root.querySelector('[data-asset-manifest-position]');
        const manifestHidden = root.querySelector('[data-asset-manifest-hidden]');

        const tokenModal = root.querySelector('[data-token-manifest-modal]');
        const tokenForm = root.querySelector('[data-token-manifest-form]');
        const tokenContext = root.querySelector('[data-token-manifest-context]');
        const tokenName = root.querySelector('[data-token-manifest-name]');
        const tokenDescription = root.querySelector('[data-token-manifest-description]');
        const tokenWallet = root.querySelector('[data-token-manifest-wallet]');
        const tokenHidden = root.querySelector('[data-token-manifest-hidden]');

        root.querySelectorAll('[data-token-manifest-open]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                if (!tokenModal || !tokenForm) {
                    return;
                }

                tokenForm.action = button.dataset.tokenAction || '';
                tokenName.value = button.dataset.tokenName || '';
                tokenDescription.value = button.dataset.tokenDescription || '';
                tokenWallet.value = button.dataset.tokenWallet || '';
                tokenHidden.checked = button.dataset.tokenHidden === '1';
                tokenContext.textContent = [button.dataset.tokenName || '', button.dataset.tokenWallet || '']
                    .filter(Boolean)
                    .join(' · ');
                tokenModal.hidden = false;
                tokenHidden.focus();
            });
        });

        root.querySelectorAll('[data-token-manifest-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (tokenModal) {
                    tokenModal.hidden = true;
                }
            });
        });

        root.querySelectorAll('[data-bulk-check]').forEach((checkbox) => {
            checkbox.addEventListener('click', (event) => event.stopPropagation());
        });

        root.querySelectorAll('[data-bulk-check-all]').forEach((checkbox) => {
            checkbox.addEventListener('click', (event) => event.stopPropagation());
            checkbox.addEventListener('change', () => {
                const group = checkbox.dataset.bulkCheckAll || '';
                root.querySelectorAll(`[data-bulk-check="${group}"]`).forEach((item) => {
                    if (!item.closest('tr')?.hidden && !item.disabled) {
                        item.checked = checkbox.checked;
                    }
                });
            });
        });

        root.querySelectorAll('[data-bulk-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const checked = form.querySelectorAll('[data-bulk-check]:checked');
                if (checked.length === 0) {
                    event.preventDefault();
                    window.alert('Выберите хотя бы одну позицию.');
                }
            });
        });

        root.querySelectorAll('[data-asset-manifest-open]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                if (!manifestModal || !manifestForm) {
                    return;
                }

                manifestForm.action = button.dataset.assetAction || '';
                manifestName.value = button.dataset.assetName || '';
                manifestType.value = button.dataset.assetType || '';
                manifestPosition.value = button.dataset.assetPosition || '0';
                manifestHidden.checked = button.dataset.assetHidden === '1';
                manifestContext.textContent = [button.dataset.assetName || '', button.dataset.assetType || '']
                    .filter(Boolean)
                    .join(' · ');
                manifestModal.hidden = false;
                manifestPosition.focus();
            });
        });

        root.querySelectorAll('[data-asset-manifest-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (manifestModal) {
                    manifestModal.hidden = true;
                }
            });
        });

        syncHiddenRows();
    });
</script>
@endpush
