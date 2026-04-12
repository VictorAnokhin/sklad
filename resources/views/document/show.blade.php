@extends('home')

@section('content')
@include('partials.panel')

<div class="ttable doc-page">

    {{-- Header --}}
    <div class="doc-header">
        <h2>{{ \App\Models\Document::typeName($doc) }} № {{ $document->num }}</h2>
    </div>

    <div class="mb-2 d-flex flex-wrap gap-2">
        <a href="{{ $documentIndexUrl }}" class="btn btn-outline-secondary btn-sm">
            ← До списку {{ \App\Models\Document::typeName($doc) }}
        </a>
        @if(!empty($parentDocumentUrl) && !empty($parentDocument))
        <a href="{{ $parentDocumentUrl }}" class="btn btn-outline-primary btn-sm">
            ← До {{ \App\Models\Document::typeName($parentDocument->type) }} № {{ $parentDocument->num }}
        </a>
        @endif
    </div>

    @if(session('error'))
    <div class="alert alert-danger py-2">
        {{ session('error') }}
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success py-2">
        {{ session('success') }}
    </div>
    @endif

    {{-- Related icons strip (client_info) --}}
    @if(!empty($relatedIcons))
    <div class="alert alert-secondary py-2 related-icons-bar">
        <strong>Зв'язані:</strong> {!! $relatedIcons !!}
    </div>
    @endif

    <form action="{{ route('document.save') }}" method="post" class="compact-form">
        @csrf
        @php
            $documentDateValue = (string) ($document->data ?? '');
            if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $documentDateValue) === 1) {
                $documentDateValue = \DateTimeImmutable::createFromFormat('d-m-Y', $documentDateValue)?->format('Y-m-d') ?? '';
            }
        @endphp
        <input type="hidden" name="doc_id" value="{{ $document->id }}">
        <input type="hidden" name="doc" value="{{ $doc }}">

        <div class="doc-layout">
            {{-- LEFT: Document form --}}
            <div class="doc-form-col">
                <!-- Row 1 varies by doc type:
                     PO/RO:  Дата | Касса | Вид платежа
                     PN/RN/WO1: Дата | Склад
                     Others: Дата | ТТН | Статус -->
                <div class="doc-form-row">
                    <div class="col-f">
                        <label>Дата</label>
                        <input type="date" name="data" class="form-control" value="{{ $documentDateValue }}">
                    </div>
                    @if(in_array($doc, ['PO', 'RO'], true))
                    <div class="col-f">
                        <label>Касса</label>
                        <select name="oplata" class="form-select" required>
                            <option value="">—</option>
                            @foreach($oplataList as $op)
                            <option value="{{ $op->id }}" {{ (string) old('oplata', $document->oplata) === (string) $op->id ? 'selected' : '' }}>{{ $op->name }}</option>
                            @endforeach
                        </select>
                        @error('oplata')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-f">
                        <label>Вид платежа</label>
                        <select name="reestr" class="form-select" required>
                            <option value="">—</option>
                            @foreach($reestrList as $re)
                            <option value="{{ $re->id }}" {{ (string) old('reestr', $document->reestr) === (string) $re->id ? 'selected' : '' }}>{{ $re->name }}</option>
                            @endforeach
                        </select>
                        @error('reestr')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    @elseif(in_array($doc, ['PN', 'RN', 'WO1'], true))
                    <div class="col-f">
                        <label>Склад</label>
                        <select name="sklads" class="form-select" required>
                            <option value="">—</option>
                            @php
                                $selectedSklad = trim((string) old('sklads', $document->sklads ?? ''));
                            @endphp
                            @foreach($skladsList as $sk)
                            <option value="{{ $sk->id }}" {{ $selectedSklad === trim((string) $sk->id) ? 'selected' : '' }}>{{ $sk->name }}</option>
                            @endforeach
                        </select>
                        @error('sklads')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    @else
                    <div class="col-f">
                        <label>ТТН Нова Пошта</label>
                        <input type="text" name="ttn" class="form-control" value="{{ $document->ttn ?? '' }}">
                    </div>
                    <div class="col-f">
                        <label>Статус</label>
                        <select name="status" class="form-select">
                            <option value="0">0 - Новий</option>
                            @foreach(($statusList ?? collect()) as $statusOption)
                            <option value="{{ $statusOption->id }}" {{ (string) $document->status === (string) $statusOption->id ? 'selected' : '' }}>
                                {{ $statusOption->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @if($doc === 'CH')
                    <div class="col-f">
                        <label>Компанія</label>
                        <select name="schet" class="form-select">
                            <option value="">—</option>
                            @foreach(($myCompanies ?? collect()) as $company)
                            <option value="{{ $company->id }}" {{ (string)($document->schet ?? '') === (string)$company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @endif
                </div>

                <!-- Row 2: Клієнт -->
                <div class="doc-form-row-single">
                    <label>Клієнт</label>
                    <div class="client-search-row">
                        <input type="text" id="clientSearchInput" class="form-control"
                            placeholder="Пошук клієнта..." autocomplete="off">
                        <button type="button" id="searchClientBtn" class="btn btn-outline-secondary">Шукати</button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#newClientModal">Новий</button>
                    </div>
                    <div id="clientSearchResults" class="list-group client-search-results">
                    </div>
                    <input type="hidden" name="client1" id="client1_id" value="{{ old('client1', $client ? $client->id : '') }}">
                    <div id="selectedClientDetails"
                        class="alert {{ $client ? 'alert-secondary' : 'alert-warning' }} py-1 mt-1 selected-client-details {{ $client ? 'selected-client-details--filled' : 'selected-client-details--empty' }}">
                        @if($client)
                        <strong>{{ $client->orgname }}</strong> | {{ $client->name2 }} {{ $client->name }} {{ $client->secondname }}<br>
                        {{ $client->phone }} | {{ $client->city }}
                        @else
                        Клієнт не обраний
                        @endif
                    </div>
                    @error('client1')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Row 3: Сума / Бонус -->
                <div class="doc-form-row doc-form-row-numbers">
                    @if(in_array($doc, ['PO', 'RO'], true))
                    <div class="col-f col-f-number">
                        <label>Сума</label>
                        <input type="number" step="0.01" name="summa" id="documentSummaInput" class="form-control form-control-number" value="{{ $document->summa ?? 0 }}">
                    </div>
                    @endif
                    <div class="col-f col-f-number">
                        <label>Бонус</label>
                        <input type="number" step="0.01" name="bonus" class="form-control form-control-number" value="{{ $document->bonus ?? 0 }}">
                    </div>
                </div>

                <!-- Row 4: Коментар -->
                <div class="doc-form-row-comment">
                    <label>Коментар</label>
                    <input type="text" name="content" class="form-control" value="{{ $document->content ?? '' }}">
                </div>

                <!-- Goods add — hidden for PO/RO (payment types) -->
                @if(!in_array($doc, ['PO', 'RO'], true))
                <div class="goods-search-container">
                    <div class="goods-search-row">
                        <input type="text" id="goodsSearchInput" class="form-control"
                            placeholder="Поиск товара..." autocomplete="off">
                        <button type="button" id="searchGoodsBtn" class="btn btn-outline-secondary btn-sm">Шукати</button>
                    </div>
                    <div id="goodsSearchResults" class="list-group goods-search-results">
                    </div>
                </div>

                <!-- Goods table -->
                <h5 class="goods-title">Товари</h5>
                <table class="table table-bordered table-sm" id="goodsTable">
                    <thead class="goods-table-header">
                        <tr>
                            <th class="goods-table-col-code">Код</th>
                            <th>Найменування</th>
                            <th class="goods-table-col-qty">К-ть</th>
                            <th class="goods-table-col-price">Ціна</th>
                            <th class="goods-table-col-sum">Сума</th>
                            <th class="goods-table-col-actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(count($lineItems) > 0)
                        @foreach($lineItems as $item)
                        <tr>
                            <td>
                                <input type="hidden" name="id[]" value="{{ $item->id }}">
                                <input type="hidden" name="pid[]" value="{{ $item->pid }}">
                                <input type="hidden" name="pnum[]" value="{{ $item->pnum }}">
                                <input type="text" class="form-control form-control-sm" value="{{ $item->pnum }}" readonly>
                            </td>
                            <td>
                                <input type="hidden" name="name[]" value="{{ $item->name ?? '' }}">
                                <input type="text" class="form-control form-control-sm" value="{{ $item->name ?? '' }}" readonly>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-qty-decrease">−</button>
                                    <input type="number" step="1" name="pcount[]"
                                        class="form-control form-control-sm goods-count" value="{{ $item->pcount }}">
                                    <button type="button" class="btn btn-outline-secondary btn-qty-increase">+</button>
                                </div>
                            </td>
                            <td><input type="string" name="pprice[]"
                                    class="form-control form-control-sm goods-price" value="{{ $item->pprice }}"></td>
                            <td><input type="string" name="psumma[]" class="form-control form-control-sm goods-sum"
                                    value="{{ $item->psumma }}"></td>
                            <td class="text-center">
                            @if(intval($document->provodka) === 0)
                                <button type="submit" name="bid" value="{{ $item->id }}"
                                    formaction="{{ route('document.body.delete') }}" class="btn btn-sm btn-outline-danger remove-btn"
                                    title="Видалити" onclick="return confirm('Видалити цей товар?');">❌</button>
                            @endif
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr id="emptyGoodsRow">
                            <td colspan="6" class="text-center text-muted">Немає товарів</td>
                        </tr>
                        @endif
                    </tbody>
                </table>

                <!-- Сума field below goods table, right-aligned 30% width -->
                <div class="d-flex justify-content-end mb-3 doc-sum-box">
                    <div class="doc-sum-box-inner">
                        <label class="doc-sum-box-label">💰 Сума</label>
                        <input type="number" step="0.01" name="summa" id="documentSummaInput" class="form-control doc-sum-box-input"
                            value="{{ $document->summa ?? 0 }}">
                    </div>
                </div>

                @endif

                {{-- Action buttons (inside form) --}}
                <div class="doc-actions">
                    @if(in_array($doc, ['RN', 'PN', 'PO', 'RO', 'VN', 'AO', 'WO1'], true))
                    @if((int)($document->provodka ?? 0) === 1)
                    <button type="submit"
                        formaction="{{ route('document.provodka') }}"
                        formmethod="post"
                        class="btn btn-success">
                        ↺ Скасувати проводку
                    </button>
                    @else
                    <div class="form-check d-flex align-items-center post-checkbox">
                        <input type="hidden" name="post_after_save" value="0">
                        <input
                            type="checkbox"
                            class="form-check-input"
                            id="post_after_save"
                            name="post_after_save"
                            value="1"
                            checked>
                        <label class="form-check-label ms-2 post-checkbox-label" for="post_after_save">
                            Провести документ
                        </label>
                    </div>
                    <button type="submit"  name="run" value="Зберегти" class="btn btn-primary">💾 Зберегти</button>
                    @endif
                    @else
                    <button type="submit"  name="run" value="Зберегти" class="btn btn-primary">💾 Зберегти</button>
                    @endif
                    @if(in_array($doc, ['CH', 'RN'], true))
                    <a href="{{ route('document.print', ['doc' => $doc, 'doc_id' => $document->id, 'num' => $document->num, 'year' => $year]) }}"
                        class="btn btn-outline-secondary"
                        target="_blank"
                        rel="noopener noreferrer">
                        Печать
                    </a>
                    @endif
                    @if(intval($document->provodka) === 0)
                    <button type="button" class="btn btn-outline-danger"
                        onclick="if(confirm('Видалити документ та всі товари?')) { document.getElementById('deleteDocForm').submit(); }" >🗑 Видалити
                        </button>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Related documents (client_info1) --}}
            @if(!empty($relatedDocs))
            <div class="doc-related-col">
                <div class="related-panel">
                    <h5>📋 Зв'язані документи</h5>
                    {!! $relatedDocs['html'] !!}
                </div>
            </div>
            @endif
        </div>
    </form>

    {{-- Hidden form for document deletion --}}
    <form id="deleteDocForm" action="{{ route('document.destroy') }}" method="post" class="delete-form">
        @csrf
        <input type="hidden" name="doc_id" value="{{ $document->id }}">
        <input type="hidden" name="doc" value="{{ $doc }}">
    </form>
</div>

<!-- Modal: New Client -->
<div class="modal fade" id="newClientModal" tabindex="-1" aria-labelledby="newClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newClientModalLabel">Новий клієнт</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Ім'я</label>
                    <input type="text" class="form-control" id="newClientName">
                </div>
                <div class="mb-3">
                    <label class="form-label">Прізвище</label>
                    <input type="text" class="form-control" id="newClientSecondname">
                </div>
                <div class="mb-3">
                    <label class="form-label">Телефон</label>
                    <input type="text" class="form-control" id="newClientPhone" placeholder="+380501234567" maxlength="17" inputmode="tel">
                </div>
                <div class="mb-3">
                    <label class="form-label">Місто</label>
                    <input type="text" class="form-control" id="newClientCity">
                </div>
                <div class="mb-3">
                    <label class="form-label">Статус клієнта</label>
                    <select class="form-select" id="newClientStatus">
                        <option value="">Оберіть статус</option>
                        @foreach(($clientStatuses ?? collect()) as $statusOption)
                        <option value="{{ $statusOption->id }}">{{ $statusOption->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="newClientError" class="text-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                <button type="button" class="btn btn-primary" id="saveNewClientBtn">Зберегти</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const documentForm = document.querySelector('form.compact-form');
        const searchInput = document.getElementById('clientSearchInput');
        const searchBtn = document.getElementById('searchClientBtn');
        const resultsContainer = document.getElementById('clientSearchResults');
        const client1Id = document.getElementById('client1_id');
        const clientDetails = document.getElementById('selectedClientDetails');
        const documentSummaInput = document.getElementById('documentSummaInput');

        function performSearch() {
            const q = searchInput.value.trim();
            if (q.length < 2) { resultsContainer.style.display = 'none'; return; }
            fetch("{{ route('client.search') }}?q=" + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    if (data.length === 0) {
                        resultsContainer.innerHTML = '<div class="list-group-item text-muted">Нічого не знайдено</div>';
                    } else {
                        data.forEach(user => {
                            const a = document.createElement('a');
                            a.href = '#'; a.className = 'list-group-item list-group-item-action';
                            a.innerHTML = `<strong>${user.orgname || ''}</strong> | ${user.name2 || ''} ${user.name || ''} ${user.secondname || ''} <br> <small>${user.phone || ''} | ${user.city || ''}</small>`;
                            a.addEventListener('click', function (e) {
                                e.preventDefault();
                                client1Id.value = user.id;
                                clientDetails.className = 'alert alert-secondary py-1 mt-1';
                                clientDetails.style.background = '#f8f9fa';
                                clientDetails.style.border = '1px solid #ddd';
                                clientDetails.style.fontSize = '0.85rem';
                                clientDetails.innerHTML = a.innerHTML;
                                resultsContainer.style.display = 'none';
                                searchInput.value = '';
                            });
                            resultsContainer.appendChild(a);
                        });
                    }
                    resultsContainer.style.display = 'block';
                })
                .catch(err => console.error('Search failed:', err));
        }
        searchBtn.addEventListener('click', performSearch);
        let clientSearchTimeout = null;
        searchInput.addEventListener('input', function (e) {
            clearTimeout(clientSearchTimeout);
            clientSearchTimeout = setTimeout(performSearch, 400);
        });
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); performSearch(); }
        });
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target) && !searchBtn.contains(e.target)) {
                resultsContainer.style.display = 'none';
            }
        });

        // ================= NEW CLIENT MODAL =================
        const saveNewClientBtn = document.getElementById('saveNewClientBtn');
        const newClientPhoneField = document.getElementById('newClientPhone');

        const formatPhoneInput = (value) => {
            const digits = value.replace(/\D/g, '').slice(0, 12);
            if (digits.length === 0) {
                return '';
            }

            if (digits.length <= 3) {
                return `+${digits}`;
            }

            if (digits.length <= 5) {
                return `+${digits.slice(0, 3)} ${digits.slice(3)}`;
            }

            if (digits.length <= 8) {
                return `+${digits.slice(0, 3)} ${digits.slice(3, 5)} ${digits.slice(5)}`;
            }

            if (digits.length <= 10) {
                return `+${digits.slice(0, 3)} ${digits.slice(3, 5)} ${digits.slice(5, 8)} ${digits.slice(8)}`;
            }

            return `+${digits.slice(0, 3)} ${digits.slice(3, 5)} ${digits.slice(5, 8)} ${digits.slice(8, 10)} ${digits.slice(10)}`;
        };

        newClientPhoneField.addEventListener('input', function () {
            this.value = formatPhoneInput(this.value);
        });

        saveNewClientBtn.addEventListener('click', function () {
            const nameField = document.getElementById('newClientName');
            const secondnameField = document.getElementById('newClientSecondname');
            const phoneField = newClientPhoneField;
            const cityField = document.getElementById('newClientCity');
            const statusField = document.getElementById('newClientStatus');
            const name = nameField.value.trim();
            const secondname = secondnameField.value.trim();
            const phone = phoneField.value.trim();
            const city = cityField.value.trim();
            const idstatus = statusField.value;
            const errorDiv = document.getElementById('newClientError');
            [nameField, secondnameField, phoneField, statusField].forEach(field => field.classList.remove('is-invalid'));
            if (!name && !secondname && !phone) {
                nameField.classList.add('is-invalid');
                secondnameField.classList.add('is-invalid');
                phoneField.classList.add('is-invalid');
                errorDiv.textContent = 'Заповніть хоча б одне поле: імʼя, прізвище або телефон';
                errorDiv.style.display = 'block';
                return;
            }
            if (!idstatus) {
                statusField.classList.add('is-invalid');
                errorDiv.textContent = 'Оберіть статус клієнта';
                errorDiv.style.display = 'block';
                return;
            }
            errorDiv.style.display = 'none';
            saveNewClientBtn.disabled = true;
            saveNewClientBtn.textContent = 'Зберігаємо...';
            fetch("{{ route('client.quickStore') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ name, secondname, phone, city, idstatus })
            })
            .then(async res => {
                const payload = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(payload.message || 'Не вдалося зберегти клієнта');
                }
                return payload;
            })
            .then(user => {
                client1Id.value = user.id;
                clientDetails.className = 'alert alert-secondary py-1 mt-1';
                clientDetails.style.background = '#f8f9fa';
                clientDetails.style.border = '1px solid #ddd';
                clientDetails.style.fontSize = '0.85rem';
                clientDetails.innerHTML = `<strong>${user.secondname || ''} ${user.name || ''}</strong><br>${user.phone || ''} | ${user.city || ''}`;
                const modal = bootstrap.Modal.getInstance(document.getElementById('newClientModal'));
                modal.hide();
                nameField.value = '';
                secondnameField.value = '';
                phoneField.value = '';
                cityField.value = '';
                statusField.value = '';
            })
            .catch(err => { errorDiv.textContent = 'Помилка: ' + err.message; errorDiv.style.display = 'block'; })
            .finally(() => { saveNewClientBtn.disabled = false; saveNewClientBtn.textContent = 'Зберегти'; });
        });

        // ================= GOODS SEARCH =================
        const goodsSearchInput = document.getElementById('goodsSearchInput');
        const searchGoodsBtn = document.getElementById('searchGoodsBtn');
        const goodsResultsContainer = document.getElementById('goodsSearchResults');
        const tableBody = document.querySelector('#goodsTable tbody');

        const updateDocumentSum = () => {
            if (!documentSummaInput || !tableBody) {
                return;
            }

            const total = Array.from(tableBody.querySelectorAll('.goods-sum'))
                .reduce((carry, input) => carry + (parseFloat(input.value) || 0), 0);

            documentSummaInput.value = total.toFixed(2);
        };

        const submitDocumentSave = () => {
            if (!documentForm) {
                return;
            }

            const runInput = document.createElement('input');
            runInput.type = 'hidden';
            runInput.name = 'run';
            runInput.value = 'Зберегти';
            documentForm.appendChild(runInput);
            documentForm.requestSubmit();
        };

        const bindGoodsRowInputs = (countInput, priceInput, sumInput) => {
            if (!countInput || !priceInput || !sumInput) {
                return;
            }

            const tr = countInput.closest('tr');
            const docType = tr?.dataset.docType || '';

            const updateRowSum = () => {
                const quantity = parseFloat(countInput.value) || 0;
                let price = parseFloat(priceInput.value) || 0;

                // For ZIN documents: use comp.pay1 (purchase price)
                if (docType === 'ZIN' && tr) {
                    const compPay1 = parseFloat(tr.dataset.priceCompPay1) || 0;
                    if (compPay1 > 0) {
                        priceInput.value = compPay1.toFixed(2);
                        price = compPay1;
                    }
                }

                // For ZOUT documents: recalculate price based on quantity
                if (docType === 'ZOUT' && tr) {
                    const basePrice = parseFloat(tr.dataset.priceBase) || 0;
                    const wholesalePrice = parseFloat(tr.dataset.priceWholesale) || 0;
                    const wholesaleFrom = parseInt(tr.dataset.wholesaleFrom) || 0;

                    // If wholesale price is set and quantity meets threshold, use wholesale price
                    if (wholesaleFrom > 0 && wholesalePrice > 0 && quantity >= wholesaleFrom) {
                        priceInput.value = wholesalePrice.toFixed(2);
                        price = wholesalePrice;
                    } else if (basePrice > 0) {
                        priceInput.value = basePrice.toFixed(2);
                        price = basePrice;
                    }
                }

                sumInput.value = (quantity * price).toFixed(2);
                updateDocumentSum();
            };

            const updateRowPriceFromSum = () => {
                const quantity = parseFloat(countInput.value) || 0;
                const sum = parseFloat(sumInput.value) || 0;

                if (quantity > 0) {
                    const price = sum / quantity;
                    priceInput.value = price.toFixed(2);
                    updateDocumentSum();
                }
            };

            const handleSaveOnEnter = (event) => {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                updateRowSum();
                submitDocumentSave();
            };

            countInput.addEventListener('input', updateRowSum);
            priceInput.addEventListener('input', updateRowSum);
            sumInput.addEventListener('input', updateRowPriceFromSum);
            countInput.addEventListener('keydown', handleSaveOnEnter);
            priceInput.addEventListener('keydown', handleSaveOnEnter);
            sumInput.addEventListener('keydown', handleSaveOnEnter);
        };

        document.querySelectorAll('table tbody tr').forEach(tr => {
            if (tr.id === 'emptyGoodsRow') return;
            const cnt = tr.querySelector('.goods-count');
            const prc = tr.querySelector('.goods-price');
            const sum = tr.querySelector('.goods-sum');
            bindGoodsRowInputs(cnt, prc, sum);
        });

        updateDocumentSum();

        function performGoodsSearch() {
            const q = goodsSearchInput.value.trim();
            if (q.length < 2) { goodsResultsContainer.style.display = 'none'; return; }
            const docType = '{{ $doc }}';
            fetch("{{ route('goods.search') }}?q=" + encodeURIComponent(q) + "&doc=" + encodeURIComponent(docType))
                .then(res => res.json())
                .then(data => {
                    goodsResultsContainer.innerHTML = '';
                    if (data.length === 0) {
                        goodsResultsContainer.innerHTML = '<div class="list-group-item text-muted">Нічого не знайдено</div>';
                    } else {
                        data.forEach(good => {
                            const a = document.createElement('a');
                            a.href = '#'; a.className = 'list-group-item list-group-item-action py-2';
                            a.innerHTML = `<strong>${good.pnum}</strong> - ${good.name || ''} <br><small class="text-muted">Ціна: ${good.price} грн | Залишок: ${good.count || 0}</small>`;
                            a.addEventListener('click', function (e) {
                                e.preventDefault();
                                const emptyRow = document.getElementById('emptyGoodsRow');
                                if (emptyRow) emptyRow.remove();
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td><input type="hidden" name="id[]" value="0"><input type="hidden" name="pid[]" value="${good.id}"><input type="hidden" name="pnum[]" value="${good.pnum}"><input type="text" class="form-control form-control-sm" value="${good.pnum}" readonly></td>
                                    <td><input type="hidden" name="name[]" value="${good.name || ''}"><input type="text" class="form-control form-control-sm" value="${good.name || ''}" readonly></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <button type="button" class="btn btn-outline-secondary btn-qty-decrease">−</button>
                                            <input type="number" step="1" name="pcount[]" class="form-control form-control-sm goods-count" value="1">
                                            <button type="button" class="btn btn-outline-secondary btn-qty-increase">+</button>
                                        </div>
                                    </td>
                                    <td><input type="number" step="0.01" name="pprice[]" class="form-control form-control-sm goods-price" value="${good.price}"></td>
                                    <td><input type="number" step="0.01" name="psumma[]" class="form-control form-control-sm goods-sum" value="${good.price}"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-new-row remove-btn">❌</button></td>`;
                                tr.dataset.priceCompPay = good.priceCompPay || 0;
                                tr.dataset.priceCompPay1 = good.priceCompPay1 || 0;
                                tr.dataset.priceBase = good.priceBase || 0;
                                tr.dataset.priceWholesale = good.priceWholesale || 0;
                                tr.dataset.wholesaleFrom = good.wholesaleFrom || 0;
                                tr.dataset.docType = '{{ $doc }}';
                                tableBody.appendChild(tr);
                                const pcount = tr.querySelector('.goods-count');
                                const pprice = tr.querySelector('.goods-price');
                                const psum = tr.querySelector('.goods-sum');
                                bindGoodsRowInputs(pcount, pprice, psum);
                                tr.querySelector('.remove-new-row').addEventListener('click', () => {
                                    tr.remove();
                                    if (tableBody.querySelectorAll('tr').length === 0) {
                                        tableBody.innerHTML = '<tr id="emptyGoodsRow"><td colspan="6" class="text-center text-muted">Немає товарів</td></tr>';
                                    }
                                    updateDocumentSum();
                                });
                                updateDocumentSum();
                                goodsResultsContainer.style.display = 'none';
                                goodsSearchInput.value = '';
                            });
                            goodsResultsContainer.appendChild(a);
                        });
                    }
                    goodsResultsContainer.style.display = 'block';
                })
                .catch(err => console.error('Goods search failed:', err));
        }
        searchGoodsBtn.addEventListener('click', performGoodsSearch);
        let goodsSearchTimeout = null;
        goodsSearchInput.addEventListener('input', function(e) {
            clearTimeout(goodsSearchTimeout);
            goodsSearchTimeout = setTimeout(performGoodsSearch, 400);
        });
        goodsSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); performGoodsSearch(); }
        });
        document.addEventListener('click', function(e) {
            if (!goodsSearchInput.contains(e.target) && !goodsResultsContainer.contains(e.target) && !searchGoodsBtn.contains(e.target)) {
                goodsResultsContainer.style.display = 'none';
            }
        });

        // ================= QUANTITY +/- BUTTONS =================
        document.addEventListener('click', function(e) {
            const decreaseBtn = e.target.closest('.btn-qty-decrease');
            const increaseBtn = e.target.closest('.btn-qty-increase');
            
            if (decreaseBtn) {
                const inputGroup = decreaseBtn.closest('.input-group');
                const countInput = inputGroup?.querySelector('.goods-count');
                if (countInput) {
                    const currentVal = parseFloat(countInput.value) || 0;
                    const step = parseFloat(countInput.step) || 1;
                    countInput.value = Math.max(0, currentVal - step).toFixed(3).replace(/\.?0+$/, '');
                    countInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
            
            if (increaseBtn) {
                const inputGroup = increaseBtn.closest('.input-group');
                const countInput = inputGroup?.querySelector('.goods-count');
                if (countInput) {
                    const currentVal = parseFloat(countInput.value) || 0;
                    const step = parseFloat(countInput.step) || 1;
                    countInput.value = (currentVal + step).toFixed(3).replace(/\.?0+$/, '');
                    countInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        });
    });
</script>
@endsection
