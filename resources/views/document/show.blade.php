@extends('home')

@section('content')
@include('partials.panel')

<div class="ttable" style="padding:20px; max-width: 900px; margin: 0 auto; background: #fff; border-radius: 8px;">
    <h2>{{ $doc === 'ZOUT' ? 'Замовлення' : ($doc === 'ZIN' ? 'Закупівля' : 'Документ') }} № {{ $document->num }}</h2>

    <form action="{{ route('document.save') }}" method="post">
        @csrf



        <div class="row">
            <!-- ДАТЫ -->
            <div class="col-md-4 mb-3">
                <label>Дата</label>
                <input type="text" name="data" class="form-control" value="{{ $document->data ?? '' }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>ТТН Нова Пошта</label>
                <input type="text" name="ttn" class="form-control" value="{{ $document->ttn ?? '' }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>Статус</label>
                <select name="status" class="form-select">
                    <option value="0">0 - Новий</option>
                    @foreach($confMap as $id => $conf)
                    <option value="{{ $id }}" {{ $document->status == $id ? 'selected' : '' }}>{{
                        $conf->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- ИНФО О КЛИЕНТЕ -->
            <div class="col-md-12 mb-3">
                <label>Клієнт</label>

                <!-- Search Input -->
                <div class="input-group mb-2">
                    <input type="text" id="clientSearchInput" style="width: 70%;" class="form-control"
                        placeholder="Пошук клієнта (Ім'я, телефон, організація)..." autocomplete="off">
                    <button class="btn" style="width: 15%;" type="button" id="searchClientBtn">Шукати</button>
                    <button class="btn" style="width: 15%;" type="button" data-bs-toggle="modal"
                        data-bs-target="#newClientModal">Новий</button>
                </div>

                <!-- Dropdown Results -->
                <div id="clientSearchResults" class="list-group mb-2"
                    style="display:none; max-height: 200px; overflow-y: auto; position: absolute; z-index: 1000; width: 95%; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                </div>

                <!-- Hidden Input for saving -->
                <input type="hidden" name="client1" id="client1_id" value="{{ $client ? $client->id : '' }}">

                <!-- Selected Client Details -->
                <div id="selectedClientDetails"
                    class="alert {{ $client ? 'alert-secondary' : 'alert-warning' }} py-2 mt-2"
                    style="{{ $client ? 'background:#f8f9fa; border:1px solid #ddd;' : '' }}">
                    @if($client)
                    <strong>{{ $client->orgname }}</strong> |
                    {{ $client->name2 }} {{ $client->name }} {{ $client->secondname }}<br>
                    {{ $client->phone }} | {{ $client->city }}
                    @else
                    Клієнт не обраний
                    @endif
                </div>
            </div>

            <!-- СУММЫ -->
            <div class="col-md-4 mb-3">
                <label>Сума</label>
                <input type="number" step="0.01" name="summa" class="form-control" value="{{ $document->summa ?? 0 }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>Знижка</label>
                <input type="number" step="0.01" name="discount" class="form-control"
                    value="{{ $document->discount ?? 0 }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>Бонус</label>
                <input type="number" step="0.01" name="bonus" class="form-control" value="{{ $document->bonus ?? 0 }}">
            </div>

            <!-- КОНТЕНТ -->
            <div class="col-md-12 mb-3">
                <label>Коментар (content)</label>
                <input type="text" name="content" class="form-control" value="{{ $document->content ?? '' }}">
            </div>
        </div>

        <hr>

        <!-- ДОДАТИ ТОВАР -->
        <div class="mb-4 p-3 border rounded bg-light" style="position: relative;">
            <h5 class="mb-3">Додати товар до документа</h5>
            <div class="input-group">
                <input type="text" id="goodsSearchInput" class="form-control w-70"
                    placeholder="Поиск товара (Код или Название)..." autocomplete="off">
                <button class="btn btn-outline-secondary w-25" type="button" id="searchGoodsBtn">Шукати</button>
            </div>
            <!-- Dropdown Results -->
            <div id="goodsSearchResults" class="list-group"
                style="display:none; position: absolute; z-index: 1000; width: 95%; max-height: 250px; overflow-y: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-top: 5px;">
            </div>
        </div>

        <!-- ТОВАРИ (z_body) -->
        <h4>Товари в документі</h4>
        <table class="table table-bordered table-sm" id="goodsTable">
            <thead style="background:#efefef">
                <tr>
                    <th>Код (pnum)</th>
                    <th>Найменування (pname)</th>
                    <th>К-ть (pcount)</th>
                    <th>Ціна (pprice)</th>
                    <th>Сума (psumma)</th>
                    <th>Дії</th>
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
                        <input type="text" class="form-control form-control-sm" value="{{ $item->name ?? '' }}"
                            readonly>
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
                            title="Видалити" onclick="return confirm('Видалити цей товар з документу?');">
                            ❌
                        </button>
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
        <div class="row" style="margin: 20px; display: flex; gap: 10px; align-items: center;">
            <div class="col-md-4 mb-2">
                <button type="submit" name="run" value="Зберегти" class="btn btn-primary"
                    style="padding: 5px 10px; font-weight:bold;">Зберегти</button>
            </div>
            <div class="col-md-4 mb-2">
                <button type="button" class="btn btn-danger" style="padding: 5px 10px; font-weight:bold;"
                    onclick="if(confirm('Видалити документ та всі товари в ньому? Цю дію неможливо скасувати!')) { document.getElementById('deleteDocForm').submit(); }">
                    🗑 Видалити документ
                </button>
            </div>
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
            if (q.length < 2) {
                resultsContainer.style.display = 'none';
                return;
            }

            fetch("{{ route('client.search') }}?q=" + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => {
                    resultsContainer.innerHTML = '';
                    if (data.length === 0) {
                        resultsContainer.innerHTML = '<div class="list-group-item text-muted">Нічого не знайдено</div>';
                    } else {
                        data.forEach(user => {
                            const a = document.createElement('a');
                            a.href = '#';
                            a.className = 'list-group-item list-group-item-action';
                            a.innerHTML = `<strong>${user.orgname || ''}</strong> | ${user.name2 || ''} ${user.name || ''} ${user.secondname || ''} <br> <small>${user.phone || ''} | ${user.city || ''}</small>`;

                            a.addEventListener('click', function (e) {
                                e.preventDefault();
                                // Update hidden input
                                client1Id.value = user.id;
                                // Update UI
                                clientDetails.className = 'alert alert-secondary py-2 mt-2';
                                clientDetails.style.background = '#f8f9fa';
                                clientDetails.style.border = '1px solid #ddd';
                                clientDetails.innerHTML = a.innerHTML; // Reuse the HTML
                                // Hide dropdown and clear input
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
            clientSearchTimeout = setTimeout(performSearch, 400); // 400ms debounce
        });

        // Optional: search on Enter key (prevent form submit)
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearch();
            }
        });

        // Hide dropdown if clicked outside
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
                errorDiv.textContent = 'Заповніть хоча б одне поле: Ім\'я, Прізвище або Телефон';
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
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ name, secondname, phone, city })
            })
                .then(res => res.json())
                .then(user => {
                    // Update hidden input
                    client1Id.value = user.id;
                    // Update UI
                    clientDetails.className = 'alert alert-secondary py-2 mt-2';
                    clientDetails.style.background = '#f8f9fa';
                    clientDetails.style.border = '1px solid #ddd';
                    clientDetails.innerHTML = `<strong>${user.secondname} ${user.name}</strong><br>${user.phone} | ${user.city}`;

                    // Close modal and reset
                    const modal = bootstrap.Modal.getInstance(document.getElementById('newClientModal'));
                    modal.hide();
                    document.getElementById('newClientName').value = '';
                    document.getElementById('newClientSecondname').value = '';
                    document.getElementById('newClientPhone').value = '';
                    document.getElementById('newClientCity').value = '';
                })
                .catch(err => {
                    errorDiv.textContent = 'Помилка збереження: ' + err.message;
                    errorDiv.style.display = 'block';
                })
                .finally(() => {
                    saveNewClientBtn.disabled = false;
                    saveNewClientBtn.textContent = 'Зберегти';
                });
        });

        // ================= GOOD SEARCH LOGIC =================
        const goodsSearchInput = document.getElementById('goodsSearchInput');
        const searchGoodsBtn = document.getElementById('searchGoodsBtn');
        const goodsResultsContainer = document.getElementById('goodsSearchResults');
        const tableBody = document.querySelector('#goodsTable tbody');

        // Existing row calculators
        document.querySelectorAll('table tbody tr').forEach(tr => {
            if (tr.id === 'emptyGoodsRow') return;
            const cnt = tr.querySelector('.goods-count');
            const prc = tr.querySelector('.goods-price');
            const sum = tr.querySelector('.goods-sum');
            if (cnt && prc && sum) {
                const updateExistingSum = () => {
                    const c = parseFloat(cnt.value) || 0;
                    const p = parseFloat(prc.value) || 0;
                    sum.value = (c * p).toFixed(2);
                };
                cnt.addEventListener('input', updateExistingSum);
                prc.addEventListener('input', updateExistingSum);
            }
        });

        function performGoodsSearch() {
            const q = goodsSearchInput.value.trim();
            if (q.length < 2) {
                goodsResultsContainer.style.display = 'none';
                return;
            }

            fetch("{{ route('goods.search') }}?q=" + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => {
                    goodsResultsContainer.innerHTML = '';
                    if (data.length === 0) {
                        goodsResultsContainer.innerHTML = '<div class="list-group-item text-muted">Нічого не знайдено</div>';
                    } else {
                        data.forEach(good => {
                            const a = document.createElement('a');
                            a.href = '#';
                            a.className = 'list-group-item list-group-item-action py-2';
                            a.innerHTML = `<strong>${good.pnum}</strong> - ${good.name || ''} <br><small class="text-muted">Ціна: ${good.price} грн | Залишок: ${good.count || 0}</small>`;

                            a.addEventListener('click', function (e) {
                                e.preventDefault();

                                const emptyRow = document.getElementById('emptyGoodsRow');
                                if (emptyRow) emptyRow.remove();

                                const tr = document.createElement('tr');
                                // Instead of formaction for delete, since it's not saved to DB yet, we just remove the row via DOM
                                tr.innerHTML = `
                                    <td>
                                        <input type="hidden" name="id[]" value="0">
                                        <input type="hidden" name="pid[]" value="${good.id}">
                                        <input type="hidden" name="pnum[]" value="${good.pnum}">
                                        <input type="text" class="form-control form-control-sm" value="${good.pnum}" readonly>
                                    </td>
                                    <td>
                                        <input type="hidden" name="name[]" value="${good.name || ''}">
                                        <input type="text" class="form-control form-control-sm" value="${good.name || ''}" readonly>
                                    </td>
                                    <td><input type="number" step="0.001" name="pcount[]" class="form-control form-control-sm goods-count" value="1"></td>
                                    <td><input type="number" step="0.01" name="pprice[]" class="form-control form-control-sm goods-price" value="${good.price}"></td>
                                    <td><input type="number" step="0.01" name="psumma[]" class="form-control form-control-sm goods-sum" value="${good.price}"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-new-row" title="Видалити">❌</button>
                                    </td>
                                `;

                                tableBody.appendChild(tr);

                                const pcount = tr.querySelector('.goods-count');
                                const pprice = tr.querySelector('.goods-price');
                                const psum = tr.querySelector('.goods-sum');
                                const rmBtn = tr.querySelector('.remove-new-row');

                                const updateSum = () => {
                                    const c = parseFloat(pcount.value) || 0;
                                    const p = parseFloat(pprice.value) || 0;
                                    psum.value = (c * p).toFixed(2);
                                };

                                pcount.addEventListener('input', updateSum);
                                pprice.addEventListener('input', updateSum);
                                rmBtn.addEventListener('click', () => {
                                    tr.remove();
                                    if (tableBody.querySelectorAll('tr').length === 0) {
                                        tableBody.innerHTML = '<tr id="emptyGoodsRow"><td colspan="6" class="text-center text-muted">Немає товарів</td></tr>';
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
        goodsSearchInput.addEventListener('input', function (e) {
            clearTimeout(goodsSearchTimeout);
            goodsSearchTimeout = setTimeout(performGoodsSearch, 400); // 400ms debounce
        });

        goodsSearchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performGoodsSearch();
            }
        });

        document.addEventListener('click', function (e) {
            if (!goodsSearchInput.contains(e.target) && !goodsResultsContainer.contains(e.target) && !searchGoodsBtn.contains(e.target)) {
                goodsResultsContainer.style.display = 'none';
            }
        });

    });
</script>

@endsection