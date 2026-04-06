@extends('home')

@section('content')
@include('partials.panel')

<style>
    .doc-page { padding: 12px 20px; }
    .doc-header { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
    .doc-header h2 { margin: 0; font-size: 1.3rem; }
    .related-icons-bar { margin-bottom: 10px; }

    /* Two-column layout */
    .doc-layout { display: flex; gap: 16px; align-items: flex-start; }
    .doc-form-col { flex: 1; min-width: 0; }
    .doc-related-col { width: 340px; flex-shrink: 0; position: sticky; top: 10px; }

    /* Compact form */
    .compact-form .row { margin: 0; }
    .compact-form .col-f { flex: 1 1 0; min-width: 0; padding: 0 6px; }
    .compact-form label { font-size: 0.78rem; font-weight: 600; margin-bottom: 2px; color: #555; }
    .compact-form .form-control,
    .compact-form .form-select,
    .compact-form .input-group { font-size: 0.85rem; padding: 4px 8px; height: auto; }
    .compact-form .input-group .form-control { padding: 4px 8px; }
    .compact-form .input-group .btn { padding: 4px 8px; font-size: 0.8rem; }
    .compact-form .client-search-row { display: flex; gap: 4px; margin-bottom: 4px; }
    .compact-form .client-search-row input { flex: 1; }
    .compact-form .client-search-row button { white-space: nowrap; font-size: 0.78rem; padding: 4px 10px; }

    /* Goods table compact */
    #goodsTable th, #goodsTable td { padding: 4px 6px !important; font-size: 0.85rem; }
    #goodsTable .form-control-sm { font-size: 0.82rem; padding: 2px 5px; }

    /* Related docs panel */
    .related-panel { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 12px; font-size: 0.85rem; }
    .related-panel h5 { font-size: 0.95rem; margin: 0 0 8px; border-bottom: 1px solid #dee2e6; padding-bottom: 6px; }
    .related-panel .tstr { padding: 3px 0; border-bottom: 1px dotted #e0e0e0; }
    .related-panel .ttable { margin: 6px 0; padding: 6px 8px; background: #fff; border-radius: 4px; border: 1px solid #eee; }
    .related-panel .button { font-size: 0.8rem; padding: 3px 10px; }

    /* Action buttons row */
    .doc-actions { display: flex; gap: 8px; margin-top: 10px; }
    .doc-actions .btn { font-size: 0.85rem; padding: 5px 14px; }

    @media (max-width: 992px) {
        .doc-layout { flex-direction: column; }
        .doc-related-col { width: 100%; position: static; }
    }
</style>

<div class="ttable doc-page" style="max-width: 1400px; margin: 0 auto; background: #fff; border-radius: 8px;">

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

    {{-- Related icons strip (client_info) --}}
    @if(!empty($relatedIcons))
    <div class="alert alert-secondary py-2 related-icons-bar" style="font-size:0.9em;">
        <strong>Зв'язані:</strong> {!! $relatedIcons !!}
    </div>
    @endif

    <form action="{{ route('document.save') }}" method="post" class="compact-form">
        @csrf
        <input type="hidden" name="doc_id" value="{{ $document->id }}">
        <input type="hidden" name="doc" value="{{ $doc }}">

        <div class="doc-layout">
            {{-- LEFT: Document form --}}
            <div class="doc-form-col">
                <!-- Row 1 varies by doc type:
                     PO/RO:  Дата | Касса | Вид платежа
                     PN/RN/WO1: Дата | Склад
                     Others: Дата | ТТН | Статус -->
                <div style="display:flex; gap:6px; margin-bottom:6px;">
                    <div class="col-f">
                        <label>Дата</label>
                        <input type="text" name="data" class="form-control" value="{{ $document->data ?? '' }}">
                    </div>
                    @if(in_array($doc, ['PO', 'RO'], true))
                    <div class="col-f">
                        <label>Касса</label>
                        <select name="oplata" class="form-select">
                            <option value="">—</option>
                            @foreach($oplataList as $op)
                            <option value="{{ $op->id }}" {{ $document->oplata == $op->id ? 'selected' : '' }}>{{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-f">
                        <label>Вид платежа</label>
                        <select name="reestr" class="form-select">
                            <option value="">—</option>
                            @foreach($reestrList as $re)
                            <option value="{{ $re->id }}" {{ $document->reestr == $re->id ? 'selected' : '' }}>{{ $re->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @elseif(in_array($doc, ['PN', 'RN', 'WO1'], true))
                    <div class="col-f">
                        <label>Склад</label>
                        <select name="sklads" class="form-select">
                            <option value="">—</option>
                            @foreach($skladsList as $sk)
                            <option value="{{ $sk->id }}" {{ $document->sklads == $sk->id ? 'selected' : '' }}>{{ $sk->name }}</option>
                            @endforeach
                        </select>
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
                            @foreach($confMap as $id => $conf)
                            <option value="{{ $id }}" {{ $document->status == $id ? 'selected' : '' }}>{{ $conf->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>

                <!-- Row 2: Клієнт -->
                <div style="margin-bottom:6px;">
                    <label>Клієнт</label>
                    <div class="client-search-row">
                        <input type="text" id="clientSearchInput" class="form-control"
                            placeholder="Пошук клієнта..." autocomplete="off">
                        <button type="button" id="searchClientBtn" class="btn btn-outline-secondary">Шукати</button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#newClientModal">Новий</button>
                    </div>
                    <div id="clientSearchResults" class="list-group"
                        style="display:none; max-height:180px; overflow-y:auto; position:absolute; z-index:1000; width:calc(100% - 12px); box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    </div>
                    <input type="hidden" name="client1" id="client1_id" value="{{ $client ? $client->id : '' }}">
                    <div id="selectedClientDetails"
                        class="alert {{ $client ? 'alert-secondary' : 'alert-warning' }} py-1 mt-1"
                        style="font-size:0.85rem; {{ $client ? 'background:#f8f9fa; border:1px solid #ddd;' : '' }}">
                        @if($client)
                        <strong>{{ $client->orgname }}</strong> | {{ $client->name2 }} {{ $client->name }} {{ $client->secondname }}<br>
                        {{ $client->phone }} | {{ $client->city }}
                        @else
                        Клієнт не обраний
                        @endif
                    </div>
                </div>

                <!-- Row 3: Сума | Знижка | Бонус -->
                <div style="display:flex; gap:6px; margin-bottom:6px;">
                    <div class="col-f">
                        <label>Сума</label>
                        <input type="number" step="0.01" name="summa" class="form-control" value="{{ $document->summa ?? 0 }}">
                    </div>
                    <div class="col-f">
                        <label>Знижка</label>
                        <input type="number" step="0.01" name="discount" class="form-control" value="{{ $document->discount ?? 0 }}">
                    </div>
                    <div class="col-f">
                        <label>Бонус</label>
                        <input type="number" step="0.01" name="bonus" class="form-control" value="{{ $document->bonus ?? 0 }}">
                    </div>
                </div>

                <!-- Row 4: Коментар -->
                <div style="margin-bottom:8px;">
                    <label>Коментар</label>
                    <input type="text" name="content" class="form-control" value="{{ $document->content ?? '' }}">
                </div>

                <!-- Goods add — hidden for PO/RO (payment types) -->
                @if(!in_array($doc, ['PO', 'RO'], true))
                <div style="position:relative; margin-bottom:8px; padding:8px; border:1px solid #dee2e6; border-radius:4px; background:#fafafa;">
                    <div style="display:flex; gap:4px;">
                        <input type="text" id="goodsSearchInput" class="form-control"
                            placeholder="Поиск товара..." autocomplete="off" style="flex:1;">
                        <button type="button" id="searchGoodsBtn" class="btn btn-outline-secondary btn-sm">Шукати</button>
                    </div>
                    <div id="goodsSearchResults" class="list-group"
                        style="display:none; position:absolute; z-index:1000; width:calc(100% - 16px); max-height:220px; overflow-y:auto; box-shadow:0 4px 6px rgba(0,0,0,0.1); margin-top:4px;">
                    </div>
                </div>

                <!-- Goods table -->
                <h5 style="font-size:0.95rem; margin-bottom:4px;">Товари</h5>
                <table class="table table-bordered table-sm" id="goodsTable">
                    <thead style="background:#efefef">
                        <tr>
                            <th style="width:60px;">Код</th>
                            <th>Найменування</th>
                            <th style="width:60px;">К-ть</th>
                            <th style="width:70px;">Ціна</th>
                            <th style="width:70px;">Сума</th>
                            <th style="width:36px;"></th>
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
                                <input type="text" class="form-control form-control-sm" value="{{ $item->pnum }}" readonly style="width:55px;">
                            </td>
                            <td>
                                <input type="hidden" name="name[]" value="{{ $item->name ?? '' }}">
                                <input type="text" class="form-control form-control-sm" value="{{ $item->name ?? '' }}" readonly>
                            </td>
                            <td><input type="number" step="0.001" name="pcount[]"
                                    class="form-control form-control-sm goods-count" value="{{ $item->pcount }}"></td>
                            <td><input type="number" step="0.01" name="pprice[]"
                                    class="form-control form-control-sm goods-price" value="{{ $item->pprice }}"></td>
                            <td><input type="number" step="0.01" name="psumma[]" class="form-control form-control-sm goods-sum"
                                    value="{{ $item->psumma }}"></td>
                            <td class="text-center">
                                <button type="submit" name="bid" value="{{ $item->id }}"
                                    formaction="{{ route('document.body.delete') }}" class="btn btn-sm btn-outline-danger"
                                    style="padding:1px 6px; font-size:0.8rem;"
                                    title="Видалити" onclick="return confirm('Видалити цей товар?');">❌</button>
                            </td>
                        </tr>
                        @endforeach
                        @else
                        <tr id="emptyGoodsRow">
                            <td colspan="6" class="text-center text-muted" style="font-size:0.85rem;">Немає товарів</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                @endif

                {{-- Action buttons (inside form) --}}
                <div class="doc-actions">
                    <button type="submit" name="run" value="Зберегти" class="btn btn-primary">💾 Зберегти</button>
                    @if(in_array($doc, ['RN', 'PN', 'PO', 'RO', 'VN', 'AO', 'WO1'], true))
                    <button type="submit"
                        formaction="{{ route('document.provodka') }}"
                        formmethod="post"
                        class="btn {{ (int)($document->provodka ?? 0) === 1 ? 'btn-success' : 'btn-warning' }}">
                        {{ (int)($document->provodka ?? 0) === 1 ? '↺ Скасувати проводку' : 'Провести' }}
                    </button>
                    @endif
                    <button type="button" class="btn btn-outline-danger"
                        onclick="if(confirm('Видалити документ та всі товари?')) { document.getElementById('deleteDocForm').submit(); }">🗑 Видалити</button>
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
    <form id="deleteDocForm" action="{{ route('document.destroy') }}" method="post" style="display:none;">
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
                    <input type="text" class="form-control" id="newClientPhone" placeholder="0501234567">
                </div>
                <div class="mb-3">
                    <label class="form-label">Місто</label>
                    <input type="text" class="form-control" id="newClientCity">
                </div>
                <div id="newClientError" class="text-danger" style="display:none;"></div>
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
        const searchInput = document.getElementById('clientSearchInput');
        const searchBtn = document.getElementById('searchClientBtn');
        const resultsContainer = document.getElementById('clientSearchResults');
        const client1Id = document.getElementById('client1_id');
        const clientDetails = document.getElementById('selectedClientDetails');

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
        saveNewClientBtn.addEventListener('click', function () {
            const name = document.getElementById('newClientName').value.trim();
            const secondname = document.getElementById('newClientSecondname').value.trim();
            const phone = document.getElementById('newClientPhone').value.trim();
            const city = document.getElementById('newClientCity').value.trim();
            const errorDiv = document.getElementById('newClientError');
            if (!name && !secondname && !phone) {
                errorDiv.textContent = 'Заповніть хоча б одне поле';
                errorDiv.style.display = 'block'; return;
            }
            errorDiv.style.display = 'none';
            saveNewClientBtn.disabled = true;
            saveNewClientBtn.textContent = 'Зберігаємо...';
            fetch("{{ route('client.quickStore') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ name, secondname, phone, city })
            })
            .then(res => res.json())
            .then(user => {
                client1Id.value = user.id;
                clientDetails.className = 'alert alert-secondary py-1 mt-1';
                clientDetails.style.background = '#f8f9fa';
                clientDetails.style.border = '1px solid #ddd';
                clientDetails.style.fontSize = '0.85rem';
                clientDetails.innerHTML = `<strong>${user.secondname} ${user.name}</strong><br>${user.phone} | ${user.city}`;
                const modal = bootstrap.Modal.getInstance(document.getElementById('newClientModal'));
                modal.hide();
                document.getElementById('newClientName').value = '';
                document.getElementById('newClientSecondname').value = '';
                document.getElementById('newClientPhone').value = '';
                document.getElementById('newClientCity').value = '';
            })
            .catch(err => { errorDiv.textContent = 'Помилка: ' + err.message; errorDiv.style.display = 'block'; })
            .finally(() => { saveNewClientBtn.disabled = false; saveNewClientBtn.textContent = 'Зберегти'; });
        });

        // ================= GOODS SEARCH =================
        const goodsSearchInput = document.getElementById('goodsSearchInput');
        const searchGoodsBtn = document.getElementById('searchGoodsBtn');
        const goodsResultsContainer = document.getElementById('goodsSearchResults');
        const tableBody = document.querySelector('#goodsTable tbody');

        document.querySelectorAll('table tbody tr').forEach(tr => {
            if (tr.id === 'emptyGoodsRow') return;
            const cnt = tr.querySelector('.goods-count');
            const prc = tr.querySelector('.goods-price');
            const sum = tr.querySelector('.goods-sum');
            if (cnt && prc && sum) {
                const updateExistingSum = () => {
                    sum.value = ((parseFloat(cnt.value) || 0) * (parseFloat(prc.value) || 0)).toFixed(2);
                };
                cnt.addEventListener('input', updateExistingSum);
                prc.addEventListener('input', updateExistingSum);
            }
        });

        function performGoodsSearch() {
            const q = goodsSearchInput.value.trim();
            if (q.length < 2) { goodsResultsContainer.style.display = 'none'; return; }
            fetch("{{ route('goods.search') }}?q=" + encodeURIComponent(q))
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
                                    <td><input type="hidden" name="id[]" value="0"><input type="hidden" name="pid[]" value="${good.id}"><input type="hidden" name="pnum[]" value="${good.pnum}"><input type="text" class="form-control form-control-sm" value="${good.pnum}" readonly style="width:55px;"></td>
                                    <td><input type="hidden" name="name[]" value="${good.name || ''}"><input type="text" class="form-control form-control-sm" value="${good.name || ''}" readonly></td>
                                    <td><input type="number" step="0.001" name="pcount[]" class="form-control form-control-sm goods-count" value="1"></td>
                                    <td><input type="number" step="0.01" name="pprice[]" class="form-control form-control-sm goods-price" value="${good.price}"></td>
                                    <td><input type="number" step="0.01" name="psumma[]" class="form-control form-control-sm goods-sum" value="${good.price}"></td>
                                    <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-new-row" style="padding:1px 6px;font-size:0.8rem;">❌</button></td>`;
                                tableBody.appendChild(tr);
                                const pcount = tr.querySelector('.goods-count');
                                const pprice = tr.querySelector('.goods-price');
                                const psum = tr.querySelector('.goods-sum');
                                const updateSum = () => { psum.value = ((parseFloat(pcount.value)||0)*(parseFloat(pprice.value)||0)).toFixed(2); };
                                pcount.addEventListener('input', updateSum);
                                pprice.addEventListener('input', updateSum);
                                tr.querySelector('.remove-new-row').addEventListener('click', () => {
                                    tr.remove();
                                    if (tableBody.querySelectorAll('tr').length === 0) {
                                        tableBody.innerHTML = '<tr id="emptyGoodsRow"><td colspan="6" class="text-center text-muted" style="font-size:0.85rem;">Немає товарів</td></tr>';
                                    }
                                });
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
    });
</script>
@endsection
