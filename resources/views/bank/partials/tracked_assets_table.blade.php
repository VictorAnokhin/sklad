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
                        <th>Preview</th>
                        <th>Адрес</th>
                        <th>Блокчейн</th>
                        <th>Кошелек / protocol</th>
                        <th>Данные</th>
                        <th class="text-end">Баланс</th>
                        <th class="text-end">Value USD</th>
                        <th>Статус</th>
                        <th class="text-end">Настройки</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $asset)
                        @php
                            $availableFieldsJson = e(json_encode($asset->available_fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            $selectedFieldsJson = e(json_encode($asset->selected_fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            $payloadJson = e(json_encode($asset->last_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        @endphp
                        <tr
                            class="bank-clickable-row"
                            data-tracked-adapter-open
                            data-adapter-action="{{ $asset->adapter_action }}"
                            data-adapter-name="{{ $asset->name }}"
                            data-adapter-type="{{ strtoupper($asset->blockchain) }} · {{ strtoupper($asset->asset_type) }}"
                            data-adapter-id="{{ $asset->asset_short }}"
                            data-adapter-fields="{{ $availableFieldsJson }}"
                            data-adapter-selected="{{ $selectedFieldsJson }}"
                            data-adapter-payload="{{ $payloadJson }}"
                            data-adapter-image="{{ $asset->image_url }}"
                            data-adapter-external="{{ $asset->external_url }}"
                        >
                            <td class="bank-table__select">
                                <input type="checkbox" name="tracked_assets[]" value="{{ $asset->id }}" data-bulk-check="tracked-{{ $assetType }}" aria-label="Выбрать {{ $asset->name }}">
                            </td>
                            <td>
                                <strong>{{ $asset->name }}</strong>
                                <div class="bank-meta">{{ $asset->symbol !== '' ? $asset->symbol : strtoupper($asset->asset_type) }}{{ $asset->token_id !== '' ? ' · #' . $asset->token_id : '' }}</div>
                            </td>
                            <td>
                                @if($asset->image_url !== '')
                                    <img src="{{ $asset->image_url }}" alt="{{ $asset->name }}" class="bank-tracked-asset-image">
                                @else
                                    <span class="bank-meta">—</span>
                                @endif
                            </td>
                            <td class="bank-mono" title="{{ $asset->asset_address }}">{{ $asset->asset_short }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ strtoupper($asset->blockchain) }}</span></td>
                            <td>
                                <span class="bank-mono" title="{{ $asset->owner_address }}">{{ $asset->owner_short }}</span>
                                @if($asset->protocol !== '')
                                    <div class="bank-meta">{{ $asset->protocol }}</div>
                                @endif
                            </td>
                            <td>
                                @foreach($asset->selected_fields as $fieldKey)
                                    @continue(in_array($fieldKey, ['name', 'symbol', 'image_url'], true))
                                    @php $fieldValue = data_get($asset->last_payload, $fieldKey); @endphp
                                    @if($fieldValue !== null && $fieldValue !== '' && ! is_array($fieldValue))
                                        <div class="bank-meta"><strong>{{ $fieldKey }}:</strong> {{ $fieldValue }}</div>
                                    @endif
                                @endforeach
                            </td>
                            <td class="text-end bank-mono">{{ $asset->last_balance !== null ? number_format((float) $asset->last_balance, 6, '.', ' ') : '—' }}</td>
                            <td class="text-end fw-semibold">{{ $asset->last_value_usd !== null ? $formatMoney($asset->last_value_usd) : '—' }}</td>
                            <td>
                                <span class="bank-status {{ $asset->sync_error !== '' ? 'bank-status--pending' : '' }}">{{ $asset->sync_status }}</span>
                                @if($asset->sync_error !== '')
                                    <div class="bank-meta">{{ $asset->sync_error }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-light"
                                    data-tracked-adapter-open
                                    data-adapter-action="{{ $asset->adapter_action }}"
                                    data-adapter-name="{{ $asset->name }}"
                                    data-adapter-type="{{ strtoupper($asset->blockchain) }} · {{ strtoupper($asset->asset_type) }}"
                                    data-adapter-id="{{ $asset->asset_short }}"
                                    data-adapter-fields="{{ $availableFieldsJson }}"
                                    data-adapter-selected="{{ $selectedFieldsJson }}"
                                    data-adapter-payload="{{ $payloadJson }}"
                                    data-adapter-image="{{ $asset->image_url }}"
                                    data-adapter-external="{{ $asset->external_url }}"
                                >⚙</button>
                            </td>
                        </tr>
                    @empty
                        <tr @if($hiddenRows->isNotEmpty()) data-tracked-asset-empty-visible @endif>
                            <td colspan="11" class="text-center text-muted py-4">Отслеживаемые активы этого типа пока не добавлены.</td>
                        </tr>
                    @endforelse
                    @foreach($hiddenRows as $asset)
                        @php
                            $availableFieldsJson = e(json_encode($asset->available_fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            $selectedFieldsJson = e(json_encode($asset->selected_fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                            $payloadJson = e(json_encode($asset->last_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        @endphp
                        <tr
                            class="bank-clickable-row"
                            data-tracked-asset-hidden-row
                            data-tracked-adapter-open
                            data-adapter-action="{{ $asset->adapter_action }}"
                            data-adapter-name="{{ $asset->name }}"
                            data-adapter-type="{{ strtoupper($asset->blockchain) }} · {{ strtoupper($asset->asset_type) }}"
                            data-adapter-id="{{ $asset->asset_short }}"
                            data-adapter-fields="{{ $availableFieldsJson }}"
                            data-adapter-selected="{{ $selectedFieldsJson }}"
                            data-adapter-payload="{{ $payloadJson }}"
                            data-adapter-image="{{ $asset->image_url }}"
                            data-adapter-external="{{ $asset->external_url }}"
                            hidden
                        >
                            <td class="bank-table__select">
                                <input type="checkbox" name="tracked_assets[]" value="{{ $asset->id }}" data-bulk-check="tracked-{{ $assetType }}" aria-label="Выбрать {{ $asset->name }}">
                            </td>
                            <td>
                                <strong>{{ $asset->name }}</strong>
                                <div class="bank-meta">{{ $asset->symbol !== '' ? $asset->symbol : strtoupper($asset->asset_type) }}{{ $asset->token_id !== '' ? ' · #' . $asset->token_id : '' }}</div>
                            </td>
                            <td>
                                @if($asset->image_url !== '')
                                    <img src="{{ $asset->image_url }}" alt="{{ $asset->name }}" class="bank-tracked-asset-image">
                                @else
                                    <span class="bank-meta">—</span>
                                @endif
                            </td>
                            <td class="bank-mono" title="{{ $asset->asset_address }}">{{ $asset->asset_short }}</td>
                            <td><span class="bank-pill bank-pill--currency">{{ strtoupper($asset->blockchain) }}</span></td>
                            <td>
                                <span class="bank-mono" title="{{ $asset->owner_address }}">{{ $asset->owner_short }}</span>
                                @if($asset->protocol !== '')
                                    <div class="bank-meta">{{ $asset->protocol }}</div>
                                @endif
                            </td>
                            <td>
                                @foreach($asset->selected_fields as $fieldKey)
                                    @continue(in_array($fieldKey, ['name', 'symbol', 'image_url'], true))
                                    @php $fieldValue = data_get($asset->last_payload, $fieldKey); @endphp
                                    @if($fieldValue !== null && $fieldValue !== '' && ! is_array($fieldValue))
                                        <div class="bank-meta"><strong>{{ $fieldKey }}:</strong> {{ $fieldValue }}</div>
                                    @endif
                                @endforeach
                            </td>
                            <td class="text-end bank-mono">{{ $asset->last_balance !== null ? number_format((float) $asset->last_balance, 6, '.', ' ') : '—' }}</td>
                            <td class="text-end fw-semibold">{{ $asset->last_value_usd !== null ? $formatMoney($asset->last_value_usd) : '—' }}</td>
                            <td><span class="bank-status bank-status--pending">hidden</span></td>
                            <td class="text-end">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-light"
                                    data-tracked-adapter-open
                                    data-adapter-action="{{ $asset->adapter_action }}"
                                    data-adapter-name="{{ $asset->name }}"
                                    data-adapter-type="{{ strtoupper($asset->blockchain) }} · {{ strtoupper($asset->asset_type) }}"
                                    data-adapter-id="{{ $asset->asset_short }}"
                                    data-adapter-fields="{{ $availableFieldsJson }}"
                                    data-adapter-selected="{{ $selectedFieldsJson }}"
                                    data-adapter-payload="{{ $payloadJson }}"
                                    data-adapter-image="{{ $asset->image_url }}"
                                    data-adapter-external="{{ $asset->external_url }}"
                                >⚙</button>
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
