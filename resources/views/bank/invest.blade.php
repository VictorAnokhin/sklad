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
    $investorPoolRows = $portfolioRows->where('group', 'defi')->values();
    $investorPoolTotal = (float) $investorPoolRows->sum('value_usd');
    $investorTotal = $investorPoolTotal;
    $investorPoolShare = $investorTotal > 0 ? $investorPoolTotal / $investorTotal * 100 : 0;
    $assetTypeLabels = [
        'token' => 'Токен',
        'nft' => 'NFT',
        'pool' => 'Пул',
        'defi' => 'DeFi',
    ];
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
        <button type="button" class="bank-invest-tab is-active" data-bank-invest-tab="portfolio">Счета</button>
        <button type="button" class="bank-invest-tab" data-bank-invest-tab="operations">Операции</button>
        <button type="button" class="bank-invest-tab" data-bank-invest-tab="assets">Активы</button>
    </section>

    <section data-bank-invest-panel="portfolio">
        <section class="bank-panel bank-table-panel">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">Операционные счета → активы</div>
                    <div class="bank-meta">Распределение по активам считается из операций Счет ↔ Актив, операционные остатки берутся из bank/cash-accounts.</div>
                </div>
                <div class="bank-meta">{{ $operationalAccounts->count() }} счетов</div>
            </div>
            <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
                <table class="table table-dark table-hover table-sm align-middle bank-table bank-accounts-table">
                    <thead>
                        <tr>
                            <th class="bank-table__num">№</th>
                            <th class="bank-accounts-table__account">Операционный счет</th>
                            <th class="bank-accounts-table__metric">Валюта</th>
                            <th class="bank-accounts-table__metric text-end">Остаток счета</th>
                            <th class="bank-accounts-table__metric text-end">В активах</th>
                            <th class="bank-accounts-table__metric">Распределение по активам</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accountAssetAllocations as $allocation)
                            <tr>
                                <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $allocation->account->label }}</strong>
                                    <div class="bank-meta">ID {{ $allocation->account->id }} · {{ $allocation->account->account_type_label }}</div>
                                </td>
                                <td><span class="bank-pill bank-pill--currency">{{ $allocation->account->currency }}</span></td>
                                <td class="text-end fw-semibold">{{ $formatMoney($allocation->available_balance) }}</td>
                                <td class="text-end fw-semibold">{{ $formatMoney($allocation->invested_total) }}</td>
                                <td>
                                    @forelse($allocation->assets as $asset)
                                        <div class="bank-account-asset-row">
                                            <span>
                                                <strong>{{ $asset->asset_label }}</strong>
                                                <small>{{ $assetTypeLabels[$asset->asset_type] ?? $asset->asset_type }} · {{ number_format((float) $asset->share, 1, '.', ' ') }}%</small>
                                            </span>
                                            <span class="bank-mono">{{ $formatMoney($asset->value_usd) }} USD</span>
                                        </div>
                                    @empty
                                        <span class="bank-meta">Операций с активами пока нет.</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Операционные счета не найдены.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>

    {{-- Removed from the Accounts tab: capital, wallets, wallet assets, token and tracked token panels.
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
    --}}

    <section data-bank-invest-panel="operations" hidden>
        <section class="bank-panel bank-table-panel">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">Операции</div>
                    <div class="bank-meta">Движения между операционными счетами и инвестиционными активами.</div>
                </div>
                <div class="bank-table-header__actions">
                    <div class="bank-meta">{{ $investOperationPositions->count() }} позиций · {{ $investOperations->count() }} движений</div>
                    <button type="button" class="btn btn-sm btn-primary" data-invest-operation-open>Создать</button>
                </div>
            </div>
            <div class="table-responsive bank-table-scroll">
                <table class="table table-dark table-hover table-sm align-middle bank-table">
                    <thead>
                        <tr>
                            <th class="bank-table__num">№</th>
                            <th>Счет</th>
                            <th>Актив</th>
                            <th class="text-end">Позиция</th>
                            <th class="text-end">Стоимость</th>
                            <th>Движения</th>
                            <th>Последняя дата</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($investOperationPositions as $position)
                            <tr class="bank-table-row--clickable"
                                data-invest-position-open
                                data-position-account="{{ $position->account_label }}"
                                data-position-asset="{{ $position->asset_label }}"
                                data-position-movements="{{ $position->movements_json }}">
                                <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                                <td>{{ $position->account_label }}</td>
                                <td>
                                    <strong>{{ $position->asset_label }}</strong>
                                    <div class="bank-meta">{{ $assetTypeLabels[$position->asset_type] ?? $position->asset_type }}</div>
                                </td>
                                <td class="text-end bank-mono">{{ number_format((float) $position->quantity, 8, '.', ' ') }}</td>
                                <td class="text-end fw-semibold">{{ $formatMoney($position->value_usd) }} USD</td>
                                <td>{{ $position->movement_count }}</td>
                                <td>{{ $position->last_operated_at !== '' ? $position->last_operated_at : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Позиции Счет / Актив пока не созданы.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>

    <section data-bank-invest-panel="assets" hidden>
        <section class="bank-grid bank-grid--summary">
            <div class="bank-panel bank-panel--accent">
                <div class="bank-label">Активы</div>
                <div class="bank-value">{{ $fixedAssetRows->count() }}</div>
                <div class="bank-meta">Активы, введенные вручную в bank/invest.</div>
            </div>
            <div class="bank-panel">
                <div class="bank-label">Токены</div>
                <div class="bank-value">{{ $fixedAssetRows->where('asset_type', 'token')->count() }}</div>
                <div class="bank-meta">Ручные токены инвестиционного реестра.</div>
            </div>
            <div class="bank-panel">
                <div class="bank-label">Пулы</div>
                <div class="bank-value">{{ $fixedAssetRows->where('asset_type', 'pool')->count() }}</div>
                <div class="bank-meta">Ручные пулы инвестиционного реестра.</div>
            </div>
            <div class="bank-panel">
                <div class="bank-label">Стоимость</div>
                <div class="bank-value">{{ $formatMoney($fixedAssetRows->sum('value_usd')) }}</div>
                <div class="bank-meta">Итоговая стоимость введенных активов.</div>
            </div>
        </section>

        <section class="bank-panel bank-table-panel">
            <div class="bank-table-header">
                <div>
                    <div class="bank-label">Введенные активы</div>
                    <div class="bank-meta">В таблице отображаются только активы, созданные через форму во вкладке Активы.</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="bank-meta">{{ $fixedAssetRows->count() }} записей</div>
                    <button type="button" class="btn btn-sm btn-primary" data-invest-asset-open>Создать</button>
                </div>
            </div>
            <div class="table-responsive bank-table-scroll">
                <table class="table table-dark table-hover table-sm align-middle bank-table bank-assets-table">
                    <thead>
                        <tr>
                            <th class="bank-table__num">№</th>
                            <th class="bank-assets-table__type">Тип</th>
                            <th class="bank-assets-table__date">Дата</th>
                            <th class="bank-assets-table__address">Адрес объекта</th>
                            <th class="bank-assets-table__name">Наименование</th>
                            <th class="text-end bank-assets-table__number">Количество</th>
                            <th class="text-end bank-assets-table__money">Цена</th>
                            <th class="text-end bank-assets-table__money">Стоимость</th>
                            <th class="bank-assets-table__status">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fixedAssetRows as $asset)
                            <tr class="bank-table-row--clickable"
                                data-invest-asset-edit
                                data-action="{{ $asset->update_action }}"
                                data-asset-type="{{ $asset->asset_type }}"
                                data-asset-address="{{ $asset->object_address }}"
                                data-asset-name="{{ $asset->name }}"
                                data-asset-quantity="{{ number_format((float) $asset->quantity, 8, '.', '') }}"
                                data-asset-price="{{ number_format((float) $asset->price_usd, 8, '.', '') }}"
                                data-asset-value="{{ number_format((float) $asset->value_usd, 8, '.', '') }}"
                                data-asset-created-on="{{ $asset->created_on }}">
                                <td class="bank-table__num bank-mono">{{ $loop->iteration }}</td>
                                <td class="bank-assets-table__type"><span class="bank-pill {{ $asset->asset_type === 'pool' ? 'bank-pill--company' : 'bank-pill--currency' }}">{{ $assetTypeLabels[$asset->asset_type] ?? $asset->asset_type }}</span></td>
                                <td class="bank-assets-table__date">{{ $asset->created_on !== '' ? $asset->created_on : '—' }}</td>
                                <td class="bank-mono bank-assets-table__address" title="{{ $asset->object_address }}">{{ $asset->object_short }}</td>
                                <td class="bank-assets-table__name">
                                    <strong>{{ $asset->name }}</strong>
                                    <div class="bank-meta">{{ $asset->currency }}</div>
                                </td>
                                <td class="text-end bank-mono bank-assets-table__number">{{ number_format((float) $asset->quantity, 8, '.', ' ') }}</td>
                                <td class="text-end bank-assets-table__money">{{ $formatMoney($asset->price_usd) }}</td>
                                <td class="text-end fw-semibold bank-assets-table__money">{{ $formatMoney($asset->value_usd) }}</td>
                                <td class="bank-assets-table__status"><span class="bank-status {{ $asset->status === 'manual' ? '' : 'bank-status--pending' }}">{{ $asset->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">Введенные активы пока не созданы.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>

    <div class="bank-modal" data-invest-operation-modal hidden>
        <div class="bank-modal__backdrop" data-invest-operation-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="investOperationModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Операция</div>
                    <h2 id="investOperationModalTitle" data-invest-operation-title>Создать Счет ↔ Актив</h2>
                    <div class="bank-meta" data-invest-operation-subtitle>Фиксирует распределение операционного счета в инвестиционный актив с двойной записью учета.</div>
                </div>
                <button type="button" class="bank-modal__close" data-invest-operation-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.invest-operations.store') }}" class="bank-requisites-form" data-invest-operation-form>
                @csrf
                <input type="hidden" name="_method" value="PUT" data-invest-operation-method disabled>
                <input type="hidden" name="update_account_balance" value="1" data-invest-operation-update-balance>
                <div class="bank-form-grid">
                    <div class="bank-form-full bank-operation-mode" role="tablist" aria-label="Тип операции">
                        <button type="button" class="bank-operation-mode__button is-active" data-invest-operation-direction-tab="account_to_asset">Купить</button>
                        <button type="button" class="bank-operation-mode__button" data-invest-operation-direction-tab="asset_to_account">Продать</button>
                        <button type="button" class="bank-operation-mode__button" data-invest-operation-direction-tab="revaluation">Переоценка</button>
                        <input type="hidden" name="direction" value="account_to_asset" data-invest-operation-direction>
                    </div>

                    <div class="bank-form-full bank-operation-ledger-note" data-invest-operation-ledger-note>
                        <div class="bank-operation-ledger-note__title">Операция будет проведена двойной записью</div>
                        <div class="bank-operation-ledger-note__body" data-invest-operation-ledger-copy>
                            Дт Инвестиционный актив · Кт Операционный счет. Остаток операционного счета уменьшится на сумму операции.
                        </div>
                    </div>

                    <div class="bank-form-section bank-form-full" data-invest-operation-account-section>
                        <div class="bank-form-section__title">1. Источник средств</div>
                        <label>
                            <span>Операционный счет</span>
                            <select name="account_id" required data-invest-operation-account>
                                @forelse($operationalAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->label }} · {{ $account->currency }} · {{ $formatMoney($account->balance) }}</option>
                                @empty
                                    <option value="">Операционные счета не найдены</option>
                                @endforelse
                            </select>
                        </label>
                    </div>

                    <div class="bank-form-section bank-form-full">
                        <div class="bank-form-section__title">2. Актив</div>
                        <label>
                            <span>Инвестиционный актив</span>
                            <select name="asset_key" required data-invest-operation-asset>
                                @forelse($fixedAssetRows as $asset)
                                    <option value="{{ $asset->asset_key }}">{{ $assetTypeLabels[$asset->asset_type] ?? $asset->asset_type }} · {{ $asset->name }} · {{ $formatMoney($asset->value_usd) }} USD</option>
                                @empty
                                    <option value="">Активы не найдены</option>
                                @endforelse
                            </select>
                        </label>
                    </div>

                    <div class="bank-form-section bank-form-full">
                        <div class="bank-form-section__title" data-invest-operation-value-section-title>3. Сумма и параметры сделки</div>
                        <div class="bank-form-grid bank-form-grid--compact">
                            <label>
                                <span>Валюта</span>
                                <input type="text" name="currency" value="USD" maxlength="20" required data-invest-operation-currency>
                            </label>
                            <label>
                                <span data-invest-operation-amount-label>Сумма</span>
                                <input type="text" name="amount" inputmode="numeric" required data-terminal-amount data-terminal-negative="1" data-invest-operation-amount>
                                <small class="bank-field-hint" data-invest-operation-amount-hint>Сумма будет списана со счета и отражена на активе.</small>
                            </label>
                            <label>
                                <span>Дата</span>
                                <input type="date" name="operated_at" data-invest-operation-date>
                            </label>
                        </div>
                    </div>

                    <div class="bank-form-full bank-operation-revaluation-note" data-invest-operation-revaluation-note hidden>
                        <div class="bank-operation-revaluation-note__title">Как работает переоценка</div>
                        <div class="bank-operation-revaluation-note__body">
                            Укажите только изменение стоимости: положительная сумма увеличивает актив и признает доход переоценки, отрицательная уменьшает актив и признает расход. Операционный счет не меняется.
                        </div>
                        <div class="bank-operation-revaluation-note__examples">
                            Пример: <span>+250</span> = Дт Инвестиционный актив / Кт Доход 746. <span>-120</span> = Дт Расход 975 / Кт Инвестиционный актив.
                        </div>
                    </div>
                </div>
                <label class="bank-form-field">
                    <span>Комментарий</span>
                    <textarea name="note" rows="3" data-invest-operation-note></textarea>
                </label>
                <label class="bank-operation-post-ledger">
                    <input type="checkbox" name="post_ledger" value="1" checked data-invest-operation-post-ledger>
                    <span>
                        <strong>Проводка</strong>
                        <small>Создать двойную запись и изменить остаток операционного счета для покупки/продажи.</small>
                    </span>
                </label>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-invest-operation-close>Отмена</button>
                    <button type="submit" class="btn btn-outline-danger me-auto" formnovalidate data-invest-operation-delete hidden>Удалить</button>
                    <button type="submit" class="btn btn-warning" formnovalidate data-invest-operation-reverse hidden>Отменить проводку</button>
                    <button type="submit" class="btn btn-primary" data-invest-operation-submit>Сохранить</button>
                </div>
            </form>
        </div>
    </div>

    <div class="bank-modal" data-invest-position-modal hidden>
        <div class="bank-modal__backdrop" data-invest-position-close></div>
        <div class="bank-modal__dialog bank-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="investPositionModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Движение средств</div>
                    <h2 id="investPositionModalTitle" data-invest-position-title>Операционный счет / Актив</h2>
                    <div class="bank-meta" data-invest-position-subtitle></div>
                </div>
                <button type="button" class="bank-modal__close" data-invest-position-close aria-label="Закрыть">×</button>
            </div>
            <div class="table-responsive bank-table-scroll">
                <table class="table table-dark table-hover table-sm align-middle bank-table">
                    <thead>
                        <tr>
                            <th class="bank-table__num">№</th>
                            <th>Дата</th>
                            <th>Движение</th>
                            <th class="text-end">Количество</th>
                            <th class="text-end">Сумма</th>
                            <th>Учет</th>
                            <th>Комментарий</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody data-invest-position-movements>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Выберите позицию.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bank-modal" data-invest-asset-modal hidden>
        <div class="bank-modal__backdrop" data-invest-asset-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="investAssetModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Активы</div>
                    <h2 id="investAssetModalTitle" data-invest-asset-title>Создать актив</h2>
                    <div class="bank-meta" data-invest-asset-subtitle>Ручная фиксация инвестиционного актива для распределения средств со счетов.</div>
                </div>
                <button type="button" class="bank-modal__close" data-invest-asset-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.invest-assets.store') }}" class="bank-requisites-form" data-invest-asset-form>
                @csrf
                <input type="hidden" name="_method" value="PUT" data-invest-asset-method disabled>
                <div class="bank-form-grid">
                    <label>
                        <span>Тип актива</span>
                        <select name="asset_type" required data-invest-asset-type>
                            <option value="token">Токен</option>
                            <option value="pool">Пул</option>
                        </select>
                    </label>
                    <label>
                        <span>Наименование</span>
                        <input type="text" name="name" maxlength="160" required placeholder="USDC / AV8 Pool" data-invest-asset-name>
                    </label>
                    <label>
                        <span>Дата фиксации</span>
                        <input type="date" name="created_on" data-invest-asset-created-on>
                    </label>
                    <label class="bank-form-full">
                        <span>Адрес объекта</span>
                        <input type="text" name="asset_address" maxlength="190" required placeholder="0x..., mint, pool object id" data-invest-asset-address>
                    </label>
                    <label>
                        <span>Количество</span>
                        <input type="number" name="quantity" min="0" step="0.00000001" inputmode="decimal" data-invest-asset-quantity>
                    </label>
                    <label>
                        <span>Цена</span>
                        <input type="number" name="price_usd" min="0" step="0.00000001" inputmode="decimal" data-invest-asset-price>
                    </label>
                    <label>
                        <span>Стоимость</span>
                        <input type="text" name="value_usd" inputmode="numeric" data-terminal-amount data-invest-asset-value>
                    </label>
                </div>
                <div class="bank-modal__actions">
                    <button type="button" class="btn btn-secondary" data-invest-asset-close>Отмена</button>
                    <button type="submit" class="btn btn-primary" data-invest-asset-submit>Создать</button>
                </div>
            </form>
        </div>
    </div>

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
@include('bank.partials.terminal_amount_inputs')

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

    .bank-operation-mode {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 6px;
        align-items: stretch;
        padding: 4px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.72);
    }

    .bank-operation-mode__button {
        min-height: 38px;
        border: 0;
        border-radius: 6px;
        background: transparent;
        color: rgba(226, 232, 240, 0.82);
        font-weight: 800;
    }

    .bank-operation-mode__button.is-active {
        background: rgba(56, 189, 248, 0.18);
        color: #fff;
        box-shadow: inset 0 0 0 1px rgba(56, 189, 248, 0.36);
    }

    .bank-form-section {
        display: grid;
        gap: 10px;
        padding: 12px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 10px;
        background: rgba(2, 6, 23, 0.22);
    }

    .bank-form-section__title {
        color: rgba(226, 232, 240, 0.88);
        font-size: 12px;
        font-weight: 900;
        letter-spacing: 0.12em;
        text-transform: uppercase;
    }

    .bank-form-grid--compact {
        gap: 10px;
    }

    .bank-field-hint {
        display: block;
        margin-top: 5px;
        color: rgba(148, 163, 184, 0.9);
        font-size: 12px;
        line-height: 1.4;
    }

    .bank-operation-ledger-note,
    .bank-operation-revaluation-note {
        padding: 12px 14px;
        border: 1px solid rgba(56, 189, 248, 0.22);
        border-radius: 10px;
        background: rgba(8, 47, 73, 0.28);
    }

    .bank-operation-revaluation-note {
        border-color: rgba(251, 191, 36, 0.28);
        background: rgba(120, 53, 15, 0.24);
    }

    .bank-operation-ledger-note__title,
    .bank-operation-revaluation-note__title {
        color: #fff;
        font-weight: 900;
    }

    .bank-operation-ledger-note__body,
    .bank-operation-revaluation-note__body,
    .bank-operation-revaluation-note__examples {
        margin-top: 4px;
        color: rgba(226, 232, 240, 0.82);
        font-size: 13px;
        line-height: 1.45;
    }

    .bank-operation-revaluation-note__examples span {
        color: #fde68a;
        font-weight: 900;
    }

    .bank-operation-post-ledger {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin-top: 12px;
        padding: 12px 14px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.48);
    }

    .bank-operation-post-ledger input {
        margin-top: 3px;
    }

    .bank-operation-post-ledger strong,
    .bank-operation-post-ledger small {
        display: block;
    }

    .bank-operation-post-ledger small {
        margin-top: 2px;
        color: rgba(148, 163, 184, 0.9);
        line-height: 1.4;
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

    .bank-invest-page .bank-pill--warning {
        background: rgba(245, 158, 11, 0.12);
        border-color: rgba(245, 158, 11, 0.28);
        color: #fde68a;
    }

    .bank-invest-page .bank-table .btn-sm {
        --bs-btn-padding-y: 0.12rem;
        --bs-btn-padding-x: 0.42rem;
        --bs-btn-font-size: 0.74rem;
    }

    .bank-accounts-table {
        table-layout: fixed;
    }

    .bank-accounts-table .bank-table__num {
        width: 48px;
    }

    .bank-accounts-table__account {
        width: 42%;
    }

    .bank-accounts-table__metric {
        width: 14%;
    }

    .bank-assets-table {
        table-layout: fixed;
        min-width: 900px;
    }

    .bank-assets-table th,
    .bank-assets-table td {
        overflow: hidden;
        padding-right: 0.35rem;
        padding-left: 0.35rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-assets-table .bank-table__num {
        width: 38px;
    }

    .bank-assets-table__type {
        width: 64px;
    }

    .bank-assets-table__date {
        width: 86px;
    }

    .bank-assets-table__address {
        width: 118px;
    }

    .bank-assets-table__name {
        width: 170px;
    }

    .bank-assets-table__number {
        width: 108px;
    }

    .bank-assets-table__money {
        width: 96px;
    }

    .bank-assets-table__status {
        width: 70px;
    }

    .bank-assets-table__name .bank-meta {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .bank-table-row--clickable {
        cursor: pointer;
    }

    .bank-invest-page .bank-modal__dialog--wide {
        max-width: min(1040px, calc(100vw - 28px));
    }

    .bank-investor-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        padding: 14px;
        border-top: 1px solid rgba(148, 163, 184, 0.12);
    }

    .bank-investor-metric {
        min-width: 0;
        padding: 14px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.35);
    }

    .bank-investor-metric--accent {
        border-color: rgba(251, 191, 36, 0.36);
        background: rgba(251, 191, 36, 0.1);
    }

    .bank-investor-metric .bank-value {
        font-size: clamp(1.25rem, 1.8vw, 1.7rem);
        line-height: 1.1;
    }

    .bank-investor-ledger {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        padding: 0 14px 14px;
    }

    .bank-investor-layer {
        min-width: 0;
        padding: 14px;
        border: 1px solid rgba(34, 197, 94, 0.22);
        border-radius: 8px;
        background: rgba(20, 83, 45, 0.18);
    }

    .bank-investor-layer--position {
        border-color: rgba(56, 189, 248, 0.22);
        background: rgba(8, 47, 73, 0.22);
    }

    .bank-investor-layer__head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .bank-investor-layer__head strong {
        color: #fff;
    }

    .bank-investor-layer__amount {
        margin-bottom: 6px;
        color: #fff;
        font-size: 1.25rem;
        font-weight: 900;
        line-height: 1.1;
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

        .bank-investor-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .bank-investor-ledger {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 700px) {
        .bank-investor-metrics {
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

        const investOperationModal = root.querySelector('[data-invest-operation-modal]');
        const investOperationForm = root.querySelector('[data-invest-operation-form]');
        const investOperationMethod = root.querySelector('[data-invest-operation-method]');
        const investOperationTitle = root.querySelector('[data-invest-operation-title]');
        const investOperationSubtitle = root.querySelector('[data-invest-operation-subtitle]');
        const investOperationSubmit = root.querySelector('[data-invest-operation-submit]');
        const investOperationDelete = root.querySelector('[data-invest-operation-delete]');
        const investOperationReverse = root.querySelector('[data-invest-operation-reverse]');
        const investOperationDirection = root.querySelector('[data-invest-operation-direction]');
        const investOperationDirectionTabs = root.querySelectorAll('[data-invest-operation-direction-tab]');
        const investOperationUpdateBalance = root.querySelector('[data-invest-operation-update-balance]');
        const investOperationPostLedger = root.querySelector('[data-invest-operation-post-ledger]');
        const investOperationAccount = root.querySelector('[data-invest-operation-account]');
        const investOperationAsset = root.querySelector('[data-invest-operation-asset]');
        const investOperationCurrency = root.querySelector('[data-invest-operation-currency]');
        const investOperationAmount = root.querySelector('[data-invest-operation-amount]');
        const investOperationAmountLabel = root.querySelector('[data-invest-operation-amount-label]');
        const investOperationAmountHint = root.querySelector('[data-invest-operation-amount-hint]');
        const investOperationValueSectionTitle = root.querySelector('[data-invest-operation-value-section-title]');
        const investOperationQuantity = root.querySelector('[data-invest-operation-quantity]');
        const investOperationPrice = root.querySelector('[data-invest-operation-price]');
        const investOperationTradeFields = root.querySelectorAll('[data-invest-operation-trade-field]');
        const investOperationAccountSection = root.querySelector('[data-invest-operation-account-section]');
        const investOperationLedgerCopy = root.querySelector('[data-invest-operation-ledger-copy]');
        const investOperationRevaluationNote = root.querySelector('[data-invest-operation-revaluation-note]');
        const investOperationDate = root.querySelector('[data-invest-operation-date]');
        const investOperationNote = root.querySelector('[data-invest-operation-note]');
        const investOperationStoreAction = investOperationForm ? investOperationForm.action : '';
        const investPositionModal = root.querySelector('[data-invest-position-modal]');
        const investPositionTitle = root.querySelector('[data-invest-position-title]');
        const investPositionSubtitle = root.querySelector('[data-invest-position-subtitle]');
        const investPositionMovements = root.querySelector('[data-invest-position-movements]');

        function setInvestOperationDirection(direction) {
            if (investOperationDirection) {
                investOperationDirection.value = direction;
            }
            investOperationDirectionTabs.forEach((tab) => {
                tab.classList.toggle('is-active', tab.dataset.investOperationDirectionTab === direction);
            });
            investOperationTradeFields.forEach((field) => {
                field.hidden = direction === 'revaluation';
            });
            if (investOperationUpdateBalance) {
                investOperationUpdateBalance.disabled = direction === 'revaluation';
            }
            if (investOperationAccountSection) {
                investOperationAccountSection.hidden = direction === 'revaluation';
            }
            if (investOperationAccount) {
                investOperationAccount.required = direction !== 'revaluation';
            }
            if (investOperationRevaluationNote) {
                investOperationRevaluationNote.hidden = direction !== 'revaluation';
            }
            if (investOperationAmountLabel) {
                investOperationAmountLabel.textContent = direction === 'revaluation' ? 'Дельта стоимости' : 'Сумма';
            }
            if (investOperationValueSectionTitle) {
                investOperationValueSectionTitle.textContent = direction === 'revaluation'
                    ? '2. Дельта стоимости актива'
                    : '3. Сумма и параметры сделки';
            }
            if (investOperationAmountHint) {
                investOperationAmountHint.textContent = direction === 'revaluation'
                    ? 'Введите изменение стоимости: + увеличивает актив, - уменьшает актив. Остаток счета не меняется.'
                    : direction === 'asset_to_account'
                        ? 'Сумма будет возвращена из актива на операционный счет.'
                        : 'Сумма будет списана со счета и отражена на активе.';
            }
            if (investOperationLedgerCopy) {
                investOperationLedgerCopy.textContent = direction === 'revaluation'
                    ? 'Положительная дельта: Дт Инвестиционный актив · Кт Доход 746. Отрицательная дельта: Дт Расход 975 · Кт Инвестиционный актив.'
                    : direction === 'asset_to_account'
                        ? 'Дт Операционный счет · Кт Инвестиционный актив. Остаток операционного счета увеличится на сумму операции.'
                        : 'Дт Инвестиционный актив · Кт Операционный счет. Остаток операционного счета уменьшится на сумму операции.';
            }
            if (investOperationAmount) {
                if (direction === 'revaluation') {
                    investOperationAmount.removeAttribute('min');
                    investOperationAmount.placeholder = '+200 или -150';
                } else {
                    investOperationAmount.min = '0.00000001';
                    investOperationAmount.placeholder = '';
                }
            }
            if (direction === 'revaluation') {
                if (investOperationQuantity) {
                    investOperationQuantity.value = '';
                }
                if (investOperationPrice) {
                    investOperationPrice.value = '';
                }
            }
        }

        investOperationDirectionTabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                setInvestOperationDirection(tab.dataset.investOperationDirectionTab || 'account_to_asset');
            });
        });

        function formatMoneyValue(value) {
            const number = Number.parseFloat(value || '0');
            return number.toLocaleString('ru-RU', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function formatQuantityValue(value) {
            const number = Number.parseFloat(value || '0');
            return number.toLocaleString('ru-RU', { minimumFractionDigits: 0, maximumFractionDigits: 8 });
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function resetInvestOperationForm() {
            if (investOperationForm) {
                investOperationForm.reset();
                investOperationForm.action = investOperationStoreAction;
            }
            if (investOperationMethod) {
                investOperationMethod.disabled = true;
            }
            if (investOperationForm) {
                investOperationForm.dataset.mode = 'save';
                investOperationForm.dataset.saveAction = investOperationStoreAction;
                investOperationForm.dataset.deleteAction = '';
                investOperationForm.dataset.reverseAction = '';
            }
            setInvestOperationDirection('account_to_asset');
            if (investOperationPostLedger) {
                investOperationPostLedger.checked = true;
                investOperationPostLedger.disabled = false;
            }
            if (investOperationTitle) {
                investOperationTitle.textContent = 'Создать Счет ↔ Актив';
            }
            if (investOperationSubtitle) {
                investOperationSubtitle.textContent = 'Фиксирует распределение операционного счета в инвестиционный актив с двойной записью учета.';
            }
            if (investOperationSubmit) {
                investOperationSubmit.textContent = 'Сохранить';
            }
            if (investOperationDelete) {
                investOperationDelete.hidden = true;
            }
            if (investOperationReverse) {
                investOperationReverse.hidden = true;
            }
        }

        function fillInvestOperationForm(movement) {
            if (!investOperationForm || !movement) {
                return;
            }
            investOperationForm.action = movement.update_action || investOperationStoreAction;
            investOperationForm.dataset.mode = 'save';
            investOperationForm.dataset.saveAction = movement.update_action || investOperationStoreAction;
            investOperationForm.dataset.deleteAction = movement.destroy_action || '';
            investOperationForm.dataset.reverseAction = movement.reverse_action || '';
            if (investOperationMethod) {
                investOperationMethod.disabled = false;
                investOperationMethod.value = 'PUT';
            }
            setInvestOperationDirection(movement.direction || 'account_to_asset');
            if (investOperationPostLedger) {
                investOperationPostLedger.checked = Boolean(movement.is_posted);
                investOperationPostLedger.disabled = false;
            }
            if (investOperationAccount) {
                investOperationAccount.value = String(movement.account_id || '');
            }
            if (investOperationAsset) {
                investOperationAsset.value = movement.asset_key || '';
            }
            if (investOperationCurrency) {
                investOperationCurrency.value = movement.currency || 'USD';
            }
            if (investOperationAmount) {
                investOperationAmount.value = movement.amount || movement.value_usd || '';
            }
            if (investOperationQuantity) {
                investOperationQuantity.value = movement.quantity || '';
            }
            if (investOperationPrice) {
                investOperationPrice.value = movement.price_usd || '';
            }
            if (investOperationDate) {
                investOperationDate.value = String(movement.date || '').slice(0, 10);
            }
            if (investOperationNote) {
                investOperationNote.value = movement.note || '';
            }
            if (investOperationTitle) {
                investOperationTitle.textContent = `Редактировать движение #${movement.id}`;
            }
            if (investOperationSubtitle) {
                investOperationSubtitle.textContent = movement.is_posted
                    ? 'Документ проведен. Можно отменить проводку или сохранить изменения с автоматическим сторно и новой проводкой.'
                    : 'Документ сохранен без проводки. Включите чекбокс, чтобы создать двойную запись.';
            }
            if (investOperationSubmit) {
                investOperationSubmit.textContent = 'Сохранить';
            }
            if (investOperationDelete) {
                investOperationDelete.hidden = !movement.can_edit;
            }
            if (investOperationReverse) {
                investOperationReverse.hidden = !(movement.can_reverse && movement.is_posted);
            }
        }

        if (investOperationSubmit) {
            investOperationSubmit.addEventListener('click', () => {
                if (!investOperationForm) {
                    return;
                }
                investOperationForm.dataset.mode = 'save';
                investOperationForm.action = investOperationForm.dataset.saveAction || investOperationStoreAction;
                if (investOperationMethod) {
                    investOperationMethod.disabled = investOperationForm.action === investOperationStoreAction;
                    investOperationMethod.value = 'PUT';
                }
            });
        }

        if (investOperationDelete) {
            investOperationDelete.addEventListener('click', () => {
                if (!investOperationForm) {
                    return;
                }
                investOperationForm.dataset.mode = 'delete';
                investOperationForm.action = investOperationForm.dataset.deleteAction || investOperationForm.action;
                if (investOperationMethod) {
                    investOperationMethod.disabled = false;
                    investOperationMethod.value = 'DELETE';
                }
            });
        }

        if (investOperationReverse) {
            investOperationReverse.addEventListener('click', () => {
                if (!investOperationForm) {
                    return;
                }
                investOperationForm.dataset.mode = 'reverse';
                investOperationForm.action = investOperationForm.dataset.reverseAction || investOperationForm.action;
                if (investOperationMethod) {
                    investOperationMethod.disabled = true;
                }
            });
        }

        function openInvestMovementEdit(index, movements) {
            const movement = Array.isArray(movements) ? movements[index] : null;
            if (!movement || !movement.can_edit) {
                return;
            }
            fillInvestOperationForm(movement);
            if (investPositionModal) {
                investPositionModal.hidden = true;
            }
            if (investOperationModal) {
                investOperationModal.hidden = false;
                if (investOperationAmount) {
                    investOperationAmount.focus();
                }
            }
        }

        root.querySelectorAll('[data-invest-operation-open]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!investOperationModal) {
                    return;
                }
                resetInvestOperationForm();
                investOperationModal.hidden = false;
                const accountSelect = investOperationModal.querySelector('[name="account_id"]');
                if (accountSelect) {
                    accountSelect.focus();
                }
            });
        });

        root.querySelectorAll('[data-invest-position-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (investPositionModal) {
                    investPositionModal.hidden = true;
                }
            });
        });

        root.querySelectorAll('[data-invest-position-open]').forEach((row) => {
            row.addEventListener('click', () => {
                if (!investPositionModal || !investPositionMovements) {
                    return;
                }

                const movements = parseJsonAttribute(row.dataset.positionMovements, []);
                if (investPositionTitle) {
                    investPositionTitle.textContent = `${row.dataset.positionAccount || 'Счет'} / ${row.dataset.positionAsset || 'Актив'}`;
                }
                if (investPositionSubtitle) {
                    investPositionSubtitle.textContent = `${Array.isArray(movements) ? movements.length : 0} движений по позиции`;
                }

                investPositionMovements.innerHTML = '';
                if (!Array.isArray(movements) || movements.length === 0) {
                    investPositionMovements.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Движения не найдены.</td></tr>';
                } else {
                    movements.forEach((movement, index) => {
                        const rowEl = document.createElement('tr');
                        const ledgerText = movement.ledger_transaction_id > 0 ? `TX #${movement.ledger_transaction_id}` : 'проводки нет';
                        const directionClass = movement.direction === 'revaluation'
                            ? 'bank-pill--warning'
                            : (movement.direction === 'asset_to_account' ? 'bank-pill--currency' : 'bank-pill--company');
                        const editButton = movement.can_edit
                            ? `<button type="button" class="btn btn-sm btn-outline-light" data-invest-movement-edit="${index}">Изменить</button>`
                            : `<span class="bank-meta">${escapeHtml(movement.edit_hint || 'закрыто')}</span>`;
                        if (movement.can_edit) {
                            rowEl.classList.add('bank-table-row--clickable');
                            rowEl.dataset.investMovementEdit = String(index);
                        }
                        rowEl.innerHTML = `
                            <td class="bank-table__num bank-mono">${movement.id}</td>
                            <td>${escapeHtml(movement.date || '—')}</td>
                            <td><span class="bank-pill ${directionClass}">${escapeHtml(movement.direction_label || movement.direction)}</span></td>
                            <td class="text-end bank-mono">${formatQuantityValue(movement.quantity)}</td>
                            <td class="text-end fw-semibold">${formatMoneyValue(movement.value_usd)} USD</td>
                            <td><span class="bank-status ${movement.status === 'posted' ? '' : 'bank-status--pending'}">${escapeHtml(movement.status || 'pending')}</span><div class="bank-meta">${escapeHtml(ledgerText)}</div></td>
                            <td class="bank-meta">${escapeHtml(movement.note || '—')}</td>
                            <td class="text-end">${editButton}</td>
                        `;
                        investPositionMovements.appendChild(rowEl);
                    });

                    investPositionMovements.querySelectorAll('[data-invest-movement-edit]').forEach((button) => {
                        button.addEventListener('click', (event) => {
                            event.stopPropagation();
                            openInvestMovementEdit(Number.parseInt(button.dataset.investMovementEdit || '0', 10), movements);
                        });
                    });
                    investPositionMovements.querySelectorAll('tr[data-invest-movement-edit]').forEach((movementRow) => {
                        movementRow.addEventListener('click', () => {
                            openInvestMovementEdit(Number.parseInt(movementRow.dataset.investMovementEdit || '0', 10), movements);
                        });
                    });
                }

                investPositionModal.hidden = false;
            });
        });

        root.querySelectorAll('[data-invest-operation-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (investOperationModal) {
                    investOperationModal.hidden = true;
                }
            });
        });

        const investAssetModal = root.querySelector('[data-invest-asset-modal]');
        const investAssetForm = root.querySelector('[data-invest-asset-form]');
        const investAssetMethod = root.querySelector('[data-invest-asset-method]');
        const investAssetTitle = root.querySelector('[data-invest-asset-title]');
        const investAssetSubtitle = root.querySelector('[data-invest-asset-subtitle]');
        const investAssetSubmit = root.querySelector('[data-invest-asset-submit]');
        const investAssetType = root.querySelector('[data-invest-asset-type]');
        const investAssetAddress = root.querySelector('[data-invest-asset-address]');
        const investAssetName = root.querySelector('[data-invest-asset-name]');
        const investAssetCreatedOn = root.querySelector('[data-invest-asset-created-on]');
        const investAssetQuantity = root.querySelector('[data-invest-asset-quantity]');
        const investAssetPrice = root.querySelector('[data-invest-asset-price]');
        const investAssetValue = root.querySelector('[data-invest-asset-value]');
        const investAssetStoreAction = investAssetForm ? investAssetForm.action : '';

        function syncInvestAssetValue() {
            if (!investAssetQuantity || !investAssetPrice || !investAssetValue) {
                return;
            }
            const quantity = Number.parseFloat(investAssetQuantity.value || '0');
            const price = Number.parseFloat(investAssetPrice.value || '0');
            if (quantity > 0 && price >= 0) {
                investAssetValue.value = (quantity * price).toFixed(8);
            }
        }

        [investAssetQuantity, investAssetPrice].forEach((field) => {
            if (field) {
                field.addEventListener('input', syncInvestAssetValue);
            }
        });

        root.querySelectorAll('[data-invest-asset-open]').forEach((button) => {
            button.addEventListener('click', () => {
                if (!investAssetModal) {
                    return;
                }
                if (investAssetForm) {
                    investAssetForm.reset();
                    investAssetForm.action = investAssetStoreAction;
                }
                if (investAssetMethod) {
                    investAssetMethod.disabled = true;
                }
                if (investAssetTitle) {
                    investAssetTitle.textContent = 'Создать актив';
                }
                if (investAssetSubtitle) {
                    investAssetSubtitle.textContent = 'Ручная фиксация инвестиционного актива для распределения средств со счетов.';
                }
                if (investAssetSubmit) {
                    investAssetSubmit.textContent = 'Создать';
                }
                if (investAssetCreatedOn) {
                    investAssetCreatedOn.value = new Date().toISOString().slice(0, 10);
                }
                investAssetModal.hidden = false;
                if (investAssetAddress) {
                    investAssetAddress.focus();
                }
            });
        });

        root.querySelectorAll('[data-invest-asset-edit]').forEach((row) => {
            row.addEventListener('click', () => {
                if (!investAssetModal || !investAssetForm) {
                    return;
                }
                investAssetForm.action = row.dataset.action || investAssetStoreAction;
                if (investAssetMethod) {
                    investAssetMethod.disabled = false;
                }
                if (investAssetType) {
                    investAssetType.value = row.dataset.assetType || 'token';
                }
                if (investAssetAddress) {
                    investAssetAddress.value = row.dataset.assetAddress || '';
                }
                if (investAssetName) {
                    investAssetName.value = row.dataset.assetName || '';
                }
                if (investAssetCreatedOn) {
                    investAssetCreatedOn.value = row.dataset.assetCreatedOn || '';
                }
                if (investAssetQuantity) {
                    investAssetQuantity.value = row.dataset.assetQuantity || '';
                }
                if (investAssetPrice) {
                    investAssetPrice.value = row.dataset.assetPrice || '';
                }
                if (investAssetValue) {
                    investAssetValue.value = row.dataset.assetValue || '';
                }
                if (investAssetTitle) {
                    investAssetTitle.textContent = 'Редактировать актив';
                }
                if (investAssetSubtitle) {
                    investAssetSubtitle.textContent = 'Изменение ручной записи инвестиционного актива.';
                }
                if (investAssetSubmit) {
                    investAssetSubmit.textContent = 'Сохранить';
                }
                investAssetModal.hidden = false;
                if (investAssetAddress) {
                    investAssetAddress.focus();
                }
            });
        });

        root.querySelectorAll('[data-invest-asset-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (investAssetModal) {
                    investAssetModal.hidden = true;
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
