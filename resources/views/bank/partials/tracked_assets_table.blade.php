<section class="bank-panel bank-table-panel bank-tracked-assets-panel">
    <div class="bank-table-header">
        <div>
            <div class="bank-label">{{ $title }}</div>
            <div class="bank-meta">Ручные активы для отслеживания данных в блокчейне.</div>
        </div>
        <div class="bank-meta">{{ $rows->count() }} активов</div>
    </div>
    <form method="POST" action="{{ route('bank.tracked-assets.bulk') }}" data-bulk-form="tracked-{{ $assetType }}">
        @csrf
        <div class="table-responsive bank-table-scroll bank-table-scroll--compact">
            <table class="table table-dark table-hover table-sm align-middle bank-table">
                <thead>
                    <tr>
                        <th class="bank-table__select">
                            <input type="checkbox" data-bulk-check-all="tracked-{{ $assetType }}" aria-label="Выбрать все отслеживаемые активы">
                        </th>
                        <th>Актив</th>
                        <th>Адрес</th>
                        <th>Блокчейн</th>
                        <th>Кошелек / protocol</th>
                        <th class="text-end">Баланс</th>
                        <th class="text-end">Value USD</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $asset)
                        <tr>
                            <td class="bank-table__select">
                                <input type="checkbox" name="tracked_assets[]" value="{{ $asset->id }}" data-bulk-check="tracked-{{ $assetType }}" aria-label="Выбрать {{ $asset->name }}">
                            </td>
                            <td>
                                <strong>{{ $asset->name }}</strong>
                                <div class="bank-meta">{{ $asset->symbol !== '' ? $asset->symbol : strtoupper($asset->asset_type) }}{{ $asset->token_id !== '' ? ' · #' . $asset->token_id : '' }}</div>
                            </td>
                            <td class="bank-mono" title="{{ $asset->asset_address }}">{{ $asset->asset_short }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ strtoupper($asset->blockchain) }}</span></td>
                            <td>
                                <span class="bank-mono" title="{{ $asset->owner_address }}">{{ $asset->owner_short }}</span>
                                @if($asset->protocol !== '')
                                    <div class="bank-meta">{{ $asset->protocol }}</div>
                                @endif
                            </td>
                            <td class="text-end bank-mono">{{ $asset->last_balance !== null ? number_format((float) $asset->last_balance, 6, '.', ' ') : '—' }}</td>
                            <td class="text-end fw-semibold">{{ $asset->last_value_usd !== null ? $formatMoney($asset->last_value_usd) : '—' }}</td>
                            <td><span class="bank-status {{ $asset->hidden ? 'bank-status--pending' : '' }}">{{ $asset->sync_status }}</span></td>
                        </tr>
                    @empty
                        <tr @if($hiddenRows->isNotEmpty()) data-tracked-asset-empty-visible @endif>
                            <td colspan="8" class="text-center text-muted py-4">Отслеживаемые активы этого типа пока не добавлены.</td>
                        </tr>
                    @endforelse
                    @foreach($hiddenRows as $asset)
                        <tr data-tracked-asset-hidden-row hidden>
                            <td class="bank-table__select">
                                <input type="checkbox" name="tracked_assets[]" value="{{ $asset->id }}" data-bulk-check="tracked-{{ $assetType }}" aria-label="Выбрать {{ $asset->name }}">
                            </td>
                            <td>
                                <strong>{{ $asset->name }}</strong>
                                <div class="bank-meta">{{ $asset->symbol !== '' ? $asset->symbol : strtoupper($asset->asset_type) }}{{ $asset->token_id !== '' ? ' · #' . $asset->token_id : '' }}</div>
                            </td>
                            <td class="bank-mono" title="{{ $asset->asset_address }}">{{ $asset->asset_short }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ strtoupper($asset->blockchain) }}</span></td>
                            <td>
                                <span class="bank-mono" title="{{ $asset->owner_address }}">{{ $asset->owner_short }}</span>
                                @if($asset->protocol !== '')
                                    <div class="bank-meta">{{ $asset->protocol }}</div>
                                @endif
                            </td>
                            <td class="text-end bank-mono">{{ $asset->last_balance !== null ? number_format((float) $asset->last_balance, 6, '.', ' ') : '—' }}</td>
                            <td class="text-end fw-semibold">{{ $asset->last_value_usd !== null ? $formatMoney($asset->last_value_usd) : '—' }}</td>
                            <td><span class="bank-status bank-status--pending">hidden</span></td>
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
