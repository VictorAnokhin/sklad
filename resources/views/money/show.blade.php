@extends('home')

@section('title', $document->id ? __('money.edit_title', ['num' => $document->num]) : __('money.create_title'))

@section('content')
@php
    $activeTab = ($tab ?? 'orders') === 'transfers' ? 'transfers' : 'orders';
    $indexRouteName = $indexRouteName ?? ($activeTab === 'transfers' ? 'money.transfers' : 'money.index');
    $showRouteName = $showRouteName ?? 'money.show';
@endphp

@include('money.partials.top-actions', ['returnFilters' => $returnFilters ?? [], 'tab' => $activeTab, 'indexRouteName' => $indexRouteName, 'showRouteName' => $showRouteName])

<div class="ttable money-show-page" style="padding: 20px; max-width: 760px; margin: 0 auto; border-radius: 8px;">
    @php
        $isNew = empty($document->id);
        $backUrl = route($indexRouteName, $returnFilters ?? []);
        $documentDateValue = (string) ($document->data ?? '');
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $documentDateValue) === 1) {
            $documentDateValue = \DateTimeImmutable::createFromFormat('d-m-Y', $documentDateValue)?->format('Y-m-d') ?? '';
        }
    @endphp

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($activeTab === 'transfers')
    <h3 style="color:#0d6efd;">
        🔄 {{ __('money.heading_transfer') }} @if(!$isNew) № {{ $document->num }} @endif
    </h3>

    <form action="{{ route('money.save') }}" method="post" class="compact-form">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id ?? 0 }}">
        <input type="hidden" name="tab" value="transfers">
        <input type="hidden" name="return_q" value="{{ $returnFilters['q'] ?? '' }}">
        <input type="hidden" name="return_money" value="{{ $returnFilters['money'] ?? '' }}">
        <input type="hidden" name="return_date_from" value="{{ $returnFilters['date_from'] ?? '' }}">
        <input type="hidden" name="return_date_to" value="{{ $returnFilters['date_to'] ?? '' }}">
        <input type="hidden" name="return_pos" value="{{ $returnFilters['pos'] ?? '' }}">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_date') }}</label>
                <input type="date" name="data" class="form-control" value="{{ $documentDateValue }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_sum') }}</label>
                <input type="number" step="0.01" min="0" name="summa" class="form-control" value="{{ old('summa', $document->summa ?? 0) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_status') }}</label>
                <input type="text" class="form-control" value="{{ (int)($document->provodka ?? 0) === 1 ? __('money.status_posted') : __('money.status_draft') }}" disabled>
            </div>
        </div>

        <div class="glass-card" style="margin-bottom:12px; border:1px solid rgba(13, 110, 253, 0.15);">
            <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.08em; color:#0a58ca; margin-bottom:8px;">{{ __('money.label_from_cashbox') }}</div>
            <select name="oplata" class="form-control" required>
                <option value="">{{ __('money.select_cashbox') }}</option>
                @foreach($kassas as $kassa)
                <option value="{{ $kassa->id }}" {{ (string) old('oplata', $document->oplata ?? '') === (string) $kassa->id ? 'selected' : '' }}>
                    {{ $kassa->name }} ({{ number_format((float) ($kassa->balance ?? 0), 2, '.', ' ') }})
                </option>
                @endforeach
            </select>
        </div>

        <div style="text-align:center; font-size:1.6rem; color:#0d6efd; margin:6px 0 12px;">→</div>

        <div class="glass-card" style="margin-bottom:16px; border:1px solid rgba(13, 110, 253, 0.15);">
            <div style="font-size:0.82rem; text-transform:uppercase; letter-spacing:0.08em; color:#0a58ca; margin-bottom:8px;">{{ __('money.label_to_cashbox') }}</div>
            <select name="oplata2" class="form-control" required>
                <option value="">{{ __('money.select_cashbox') }}</option>
                @foreach($targetKassas ?? $kassas as $kassa)
                <option value="{{ $kassa->id }}" {{ (string) old('oplata2', $document->oplata2 ?? '') === (string) $kassa->id ? 'selected' : '' }}>
                    {{ $kassa->name }} ({{ number_format((float) ($kassa->balance ?? 0), 2, '.', ' ') }})
                </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>{{ __('money.field_comment') }}</label>
            <input type="text" name="content" class="form-control" value="{{ old('content', $document->content ?? '') }}">
        </div>

        @if((int)($document->provodka ?? 0) === 0)
        <div class="mb-3 form-check">
            <input type="hidden" name="post_after_save" value="0">
            <input type="checkbox" class="form-check-input" id="post_after_save" name="post_after_save" value="1" checked>
            <label class="form-check-label" for="post_after_save">{{ __('money.checkbox_post') }}</label>
        </div>
        @endif

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">← {{ __('money.btn_back') }}</a>
            @if((int)($document->provodka ?? 0) === 1)
            <button type="submit" formaction="{{ route('money.provodka') }}" formmethod="post" class="btn btn-success">
                ↺ {{ __('money.btn_unpost') }}
            </button>
            @else
            <button type="submit" class="btn">💾 {{ __('money.btn_save') }}</button>
            @endif
            @if((int)($document->provodka ?? 0) === 0 && !$isNew)
            <button type="button" class="btn btn-danger" onclick="if(confirm('{{ __('money.confirm_delete') }}')) { document.getElementById('deleteMoneyForm').submit(); }">
                🗑 {{ __('money.btn_delete') }}
            </button>
            @endif
        </div>
    </form>
    @else
    @php
        $type = request('type', $document->type ?? 'PPO');
        $isPO = $type === 'PPO';
        $selectedOwnerBalance = (float) ($document->owner_balance ?? 0);
        $currentUserId = (string) (\Illuminate\Support\Facades\Auth::id() ?: session('userid', '0'));
        $isDocumentOwner = (string) ($document->client2 ?? '') !== '' && (string) ($document->client2 ?? '') === $currentUserId;
        $authorName = trim(implode(' ', array_filter([
            $document->owner_orgname ?? '',
            $document->owner_secondname ?? '',
            $document->owner_name ?? '',
            $document->owner_fathername ?? '',
        ])));
    @endphp

    <h3 style="color:{{ $isPO ? 'green' : 'red' }};">
        {{ $isPO ? '📥 ' . __('money.heading_income') : '📤 ' . __('money.heading_outcome') }}
        @if(!$isNew) № {{ $document->num }} @endif
    </h3>
    @if($isDocumentOwner)
    <div class="text-muted mb-3">Ваш баланс: {{ number_format($selectedOwnerBalance, 2, '.', ' ') }}</div>
    @elseif($authorName !== '')
    <div class="text-muted mb-3">Автор: {{ $authorName }}</div>
    @endif

    <form action="{{ route('money.save') }}" method="post" class="compact-form">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id ?? 0 }}">
        <input type="hidden" name="type" value="{{ $type }}">
        <input type="hidden" name="tab" value="orders">
        <input type="hidden" name="return_q" value="{{ $returnFilters['q'] ?? '' }}">
        <input type="hidden" name="return_filter_type" value="{{ $returnFilters['type'] ?? '' }}">
        <input type="hidden" name="return_money" value="{{ $returnFilters['money'] ?? '' }}">
        <input type="hidden" name="return_reestr" value="{{ $returnFilters['reestr'] ?? '' }}">
        <input type="hidden" name="return_date_from" value="{{ $returnFilters['date_from'] ?? '' }}">
        <input type="hidden" name="return_date_to" value="{{ $returnFilters['date_to'] ?? '' }}">
        <input type="hidden" name="return_pos" value="{{ $returnFilters['pos'] ?? '' }}">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_date') }}</label>
                <input type="date" name="data" class="form-control" value="{{ $documentDateValue }}" placeholder="{{ __('money.date_placeholder') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_payment_type') }}</label>
                <select name="reestr" class="form-control">
                    <option value="">{{ __('money.select_payment_type') }}</option>
                    @foreach(($reestrList ?? []) as $re)
                    <option value="{{ $re->id }}" {{ (string) old('reestr', $document->reestr ?? '') === (string) $re->id ? 'selected' : '' }}>
                        {{ $re->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_sum') }}</label>
                <input type="number" step="0.01" name="summa" class="form-control" value="{{ $document->summa ?? 0 }}">
            </div>
        </div>

        <div class="mb-3">
            <label>{{ __('money.field_client') }}</label>
            <div id="selectedClientDetails"
                class="alert {{ (!$isNew && !empty($document->id) && !empty($document->client1)) ? 'alert-secondary selected-client-details--filled' : 'alert-warning selected-client-details--empty' }} py-1 mt-1 selected-client-details"
                style="{{ (!$isNew && !empty($document->id) && !empty($document->client1)) ? 'border:1px solid var(--border);' : '' }}">
                @if(!$isNew && !empty($document->id) && !empty($document->client1))
                <strong>{{ $document->orgname ?? '' }}</strong> |
                {{ trim(($document->secondname ?? '') . ' ' . ($document->name ?? '') . ' ' . ($document->name2 ?? '')) }}<br>
                {{ $document->phone ?? '' }} | {{ $document->region ? $document->region . ' | ' : '' }}{{ $document->city ?? '' }}{{ $document->poshta ? ' | ' . $document->poshta : '' }}
                @else
                {{ __('money.client_not_selected') }}
                @endif
            </div>

            <div class="client-search-row d-flex gap-1 mb-2">
                <input type="text" id="clientSearchInput" class="form-control flex-grow-1" placeholder="{{ __('money.search_client') }}" autocomplete="off">
                <button type="button" class="btn btn-outline-secondary" id="editClientBtn" style="{{ !empty($document->client1) ? '' : 'display:none;' }}">
                    Изменить
                </button>
                <button type="button" class="btn btn-outline-primary" id="newClientBtn">
                    Новый
                </button>
            </div>
            <div id="clientSearchResults" class="list-group client-search-results mb-2" style="display:none;"></div>
            <input type="hidden" name="client1" id="client1_id"
                value="{{ $document->client1 ?? '' }}"
                data-orgname="{{ $document->orgname ?? '' }}"
                data-name="{{ $document->name ?? '' }}"
                data-secondname="{{ $document->secondname ?? '' }}"
                data-phone="{{ $document->phone ?? '' }}"
                data-city="{{ $document->city ?? '' }}"
                data-region="{{ $document->region ?? '' }}"
                data-poshta="{{ $document->poshta ?? '' }}"
                data-status="{{ $document->idstatus ?? '' }}">
        </div>

        <div class="mb-3">
            <label>{{ __('money.field_comment') }}</label>
            <input type="text" name="content" class="form-control" value="{{ $document->content ?? '' }}">
        </div>

        @if((int)($document->provodka ?? 0) === 0)
        <div class="mb-3 form-check">
            <input type="hidden" name="post_after_save" value="0">
            <input type="checkbox" class="form-check-input" id="post_after_save" name="post_after_save" value="1" checked>
            <label class="form-check-label" for="post_after_save">{{ __('money.checkbox_post') }}</label>
        </div>
        @endif

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">← {{ __('money.btn_back') }}</a>
            @if((int)($document->provodka ?? 0) === 1)
            <button type="submit" formaction="{{ route('money.provodka') }}" formmethod="post" class="btn btn-success">
                ↺ {{ __('money.btn_unpost') }}
            </button>
            @else
            <button type="submit" class="btn">💾 {{ __('money.btn_save') }}</button>
            @endif
            @if((int)($document->provodka ?? 0) === 0 && !$isNew)
            <button type="button" class="btn btn-danger" onclick="if(confirm('{{ __('money.confirm_delete') }}')) { document.getElementById('deleteMoneyForm').submit(); }">
                🗑 {{ __('money.btn_delete') }}
            </button>
            @endif
        </div>
    </form>

    <div class="modal fade" id="newClientModal" tabindex="-1" aria-labelledby="newClientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newClientModalLabel">Новый клиент</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                </div>
                <div class="modal-body py-2">
                    <input type="hidden" id="newClientId" value="0">
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small mb-0">Организация</label>
                            <input type="text" class="form-control form-control-sm" id="newClientOrgname">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Фамилия</label>
                            <input type="text" class="form-control form-control-sm" id="newClientSecondname">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Имя</label>
                            <input type="text" class="form-control form-control-sm" id="newClientName">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Телефон</label>
                            <input type="text" class="form-control form-control-sm" id="newClientPhone" placeholder="+38 (000) 00-00-000" maxlength="19" inputmode="tel">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Город</label>
                            <input type="text" class="form-control form-control-sm" id="newClientCity">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Область</label>
                            <input type="text" class="form-control form-control-sm" id="newClientRegion">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label small mb-0">Отделение НП</label>
                            <input type="text" class="form-control form-control-sm" id="newClientPoshta">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-0">Статус клиента</label>
                            <select class="form-select form-select-sm" id="newClientStatus">
                                <option value="">Оберіть статус</option>
                                @foreach(($clientStatuses ?? collect()) as $statusOption)
                                <option value="{{ $statusOption->id }}">{{ $statusOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div id="newClientError" class="text-danger small mt-2" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="saveNewClientBtn">Сохранить</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('clientSearchInput');
            const editClientBtn = document.getElementById('editClientBtn');
            const newClientBtn = document.getElementById('newClientBtn');
            const resultsContainer = document.getElementById('clientSearchResults');
            const client1Id = document.getElementById('client1_id');
            const clientDetails = document.getElementById('selectedClientDetails');

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value || '';
                return div.innerHTML;
            }

            function performSearch() {
                const q = searchInput.value.trim();
                if (q.length < 2) { resultsContainer.style.display = 'none'; return; }

                fetch("{{ route('client.search') }}?q=" + encodeURIComponent(q))
                    .then(res => res.json())
                    .then(data => {
                        resultsContainer.innerHTML = '';
                        if (!data.length) {
                            resultsContainer.innerHTML = '<div class="list-group-item text-muted">{{ addslashes(__('money.search_no_results')) }}</div>';
                        } else {
                            data.forEach(user => {
                                const a = document.createElement('a');
                                a.href = '#';
                                a.className = 'list-group-item list-group-item-action';
                                a.innerHTML = `
                                    <strong>${escapeHtml(user.orgname || '')}</strong> |
                                    ${escapeHtml(user.name2 || '')} ${escapeHtml(user.name || '')} ${escapeHtml(user.secondname || '')}
                                    <br>
                                    <small>${escapeHtml(user.phone || '')} | ${user.region ? escapeHtml(user.region) + ' | ' : ''}${escapeHtml(user.city || '')}${user.poshta ? ' | ' + escapeHtml(user.poshta) : ''}</small>
                                `;
                                a.addEventListener('click', function (e) {
                                    e.preventDefault();
                                    const selectedLabel = [user.orgname || '', user.secondname || '', user.name || ''].filter(Boolean).join(' ').trim();
                                    client1Id.value = user.id;
                                    client1Id.dataset.orgname = user.orgname || '';
                                    client1Id.dataset.name = user.name || '';
                                    client1Id.dataset.secondname = user.secondname || '';
                                    client1Id.dataset.phone = user.phone || '';
                                    client1Id.dataset.city = user.city || '';
                                    client1Id.dataset.region = user.region || '';
                                    client1Id.dataset.poshta = user.poshta || '';
                                    client1Id.dataset.status = user.idstatus || '';
                                    if (editClientBtn) {
                                        editClientBtn.style.display = 'inline-block';
                                    }
                                    clientDetails.className = 'alert alert-secondary py-1 mt-1 selected-client-details selected-client-details--filled';
                                    clientDetails.style.border = '1px solid var(--border)';
                                    clientDetails.innerHTML = a.innerHTML;
                                    resultsContainer.style.display = 'none';
                                    searchInput.value = selectedLabel;
                                });
                                resultsContainer.appendChild(a);
                            });
                        }
                        resultsContainer.style.display = 'block';
                    });
            }

            let t = null;
            searchInput.addEventListener('input', () => { clearTimeout(t); t = setTimeout(performSearch, 400); });
            searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); performSearch(); } });
            document.addEventListener('click', e => {
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.style.display = 'none';
                }
            });

            const newClientIdField = document.getElementById('newClientId');
            const newClientOrgnameField = document.getElementById('newClientOrgname');
            const newClientNameField = document.getElementById('newClientName');
            const newClientSecondnameField = document.getElementById('newClientSecondname');
            const newClientPhoneField = document.getElementById('newClientPhone');
            const newClientCityField = document.getElementById('newClientCity');
            const newClientRegionField = document.getElementById('newClientRegion');
            const newClientPoshtaField = document.getElementById('newClientPoshta');
            const newClientStatusField = document.getElementById('newClientStatus');
            const newClientError = document.getElementById('newClientError');
            const saveNewClientBtn = document.getElementById('saveNewClientBtn');
            const newClientModalElement = document.getElementById('newClientModal');
            if (newClientModalElement && newClientModalElement.parentElement !== document.body) {
                document.body.appendChild(newClientModalElement);
            }
            const newClientModal = (typeof bootstrap !== 'undefined' && newClientModalElement)
                ? new bootstrap.Modal(newClientModalElement, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                })
                : null;

            const formatPhoneInput = (value) => {
                const digits = String(value || '').replace(/\D/g, '').slice(0, 12);
                if (digits.length === 0) {
                    return '';
                }
                if (digits.length <= 3) {
                    return `+${digits}`;
                }
                if (digits.length <= 5) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3)}`;
                }
                if (digits.length <= 8) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5)}`;
                }
                if (digits.length <= 10) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5, 8)}-${digits.slice(8)}`;
                }
                return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5, 8)}-${digits.slice(8, 10)}-${digits.slice(10)}`;
            };

            const resetClientModal = () => {
                newClientIdField.value = '0';
                newClientOrgnameField.value = '';
                newClientNameField.value = '';
                newClientSecondnameField.value = '';
                newClientPhoneField.value = '';
                newClientCityField.value = '';
                newClientRegionField.value = '';
                newClientPoshtaField.value = '';
                newClientStatusField.value = '';
                newClientError.style.display = 'none';
            };

            if (newClientBtn) {
                newClientBtn.addEventListener('click', () => {
                    resultsContainer.style.display = 'none';
                    document.getElementById('newClientModalLabel').textContent = 'Новый клиент';
                    resetClientModal();
                    if (newClientModal) {
                        newClientModal.show();
                    }
                });
            }

            if (editClientBtn) {
                editClientBtn.addEventListener('click', () => {
                    resultsContainer.style.display = 'none';
                    document.getElementById('newClientModalLabel').textContent = 'Изменить клиента';
                    newClientIdField.value = client1Id.value || '0';
                    newClientOrgnameField.value = client1Id.dataset.orgname || '';
                    newClientNameField.value = client1Id.dataset.name || '';
                    newClientSecondnameField.value = client1Id.dataset.secondname || '';
                    newClientPhoneField.value = client1Id.dataset.phone || '';
                    newClientCityField.value = client1Id.dataset.city || '';
                    newClientRegionField.value = client1Id.dataset.region || '';
                    newClientPoshtaField.value = client1Id.dataset.poshta || '';
                    newClientStatusField.value = client1Id.dataset.status || '';
                    newClientError.style.display = 'none';
                    newClientPhoneField.dispatchEvent(new Event('input'));
                    if (newClientModal) {
                        newClientModal.show();
                    }
                });
            }

            if (newClientPhoneField) {
                newClientPhoneField.addEventListener('input', function () {
                    this.value = formatPhoneInput(this.value);
                });
            }

            if (saveNewClientBtn) {
                saveNewClientBtn.addEventListener('click', function () {
                    const id = newClientIdField.value || '0';
                    const orgname = newClientOrgnameField.value.trim();
                    const name = newClientNameField.value.trim();
                    const secondname = newClientSecondnameField.value.trim();
                    const phone = newClientPhoneField.value.trim();
                    const city = newClientCityField.value.trim();
                    const region = newClientRegionField.value.trim();
                    const poshta = newClientPoshtaField.value.trim();
                    const idstatus = newClientStatusField.value;

                    [newClientNameField, newClientSecondnameField, newClientPhoneField, newClientStatusField].forEach((field) => field.classList.remove('is-invalid'));

                    if (!name && !secondname && !phone) {
                        newClientNameField.classList.add('is-invalid');
                        newClientSecondnameField.classList.add('is-invalid');
                        newClientPhoneField.classList.add('is-invalid');
                        newClientError.textContent = 'Заполните хотя бы одно поле: имя, фамилию или телефон';
                        newClientError.style.display = 'block';
                        return;
                    }

                    if (!idstatus) {
                        newClientStatusField.classList.add('is-invalid');
                        newClientError.textContent = 'Выберите статус клиента';
                        newClientError.style.display = 'block';
                        return;
                    }

                    newClientError.style.display = 'none';
                    saveNewClientBtn.disabled = true;
                    saveNewClientBtn.textContent = 'Сохраняем...';

                    fetch("{{ route('client.quickStore') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ id, orgname, name, secondname, phone, city, region, poshta, idstatus })
                    })
                        .then(async (res) => {
                            const payload = await res.json().catch(() => ({}));
                            if (!res.ok) {
                                throw new Error(payload.message || 'Не удалось сохранить клиента');
                            }
                            return payload;
                        })
                        .then((user) => {
                            const selectedLabel = [user.orgname || '', user.secondname || '', user.name || ''].filter(Boolean).join(' ').trim();
                            client1Id.value = user.id;
                            client1Id.dataset.orgname = user.orgname || '';
                            client1Id.dataset.name = user.name || '';
                            client1Id.dataset.secondname = user.secondname || '';
                            client1Id.dataset.phone = user.phone || '';
                            client1Id.dataset.city = user.city || '';
                            client1Id.dataset.region = user.region || '';
                            client1Id.dataset.poshta = user.poshta || '';
                            client1Id.dataset.status = user.idstatus || '';

                            if (editClientBtn) {
                                editClientBtn.style.display = 'inline-block';
                            }

                            clientDetails.className = 'alert alert-secondary py-1 mt-1 selected-client-details selected-client-details--filled';
                            clientDetails.style.border = '1px solid var(--border)';
                            clientDetails.innerHTML = `
                                <strong>${escapeHtml(user.orgname || '')}</strong> |
                                ${escapeHtml(user.name2 || '')} ${escapeHtml(user.name || '')} ${escapeHtml(user.secondname || '')}
                                <br>
                                <small>${escapeHtml(user.phone || '')} | ${user.region ? escapeHtml(user.region) + ' | ' : ''}${escapeHtml(user.city || '')}${user.poshta ? ' | ' + escapeHtml(user.poshta) : ''}</small>
                            `;
                            searchInput.value = selectedLabel;

                            if (newClientModal) {
                                newClientModal.hide();
                            }
                        })
                        .catch((error) => {
                            newClientError.textContent = error.message;
                            newClientError.style.display = 'block';
                        })
                        .finally(() => {
                            saveNewClientBtn.disabled = false;
                            saveNewClientBtn.textContent = 'Сохранить';
                        });
                });
            }
        });
    </script>
    @endif

    @if(!$isNew)
    <form id="deleteMoneyForm" action="{{ route('money.destroy') }}" method="post" style="display:none;">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id }}">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <input type="hidden" name="return_q" value="{{ $returnFilters['q'] ?? '' }}">
        <input type="hidden" name="return_filter_type" value="{{ $returnFilters['type'] ?? '' }}">
        <input type="hidden" name="return_money" value="{{ $returnFilters['money'] ?? '' }}">
        <input type="hidden" name="return_reestr" value="{{ $returnFilters['reestr'] ?? '' }}">
        <input type="hidden" name="return_date_from" value="{{ $returnFilters['date_from'] ?? '' }}">
        <input type="hidden" name="return_date_to" value="{{ $returnFilters['date_to'] ?? '' }}">
        <input type="hidden" name="return_pos" value="{{ $returnFilters['pos'] ?? '' }}">
    </form>
    @endif
</div>
@endsection
