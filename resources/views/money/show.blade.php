@extends('home')

@section('title', $document->id ? ('Документ №' . $document->num . ' — ' . $document->type) : 'Новий документ')

@section('content')
@include('partials.panel')

<div class="ttable" style="padding: 20px; max-width: 700px; margin: 0 auto; background: #fff; border-radius: 8px;">

    @php
    $isNew = empty($document->id);
    $type = request('type', $document->type ?? 'PO');
    $isPO = $type === 'PO';
    @endphp

    <h3 style="color:{{ $isPO ? 'green' : 'red' }};">
        {{ $isPO ? '📥 Прихід грошей (PO)' : '📤 Видача грошей (RO)' }}
        @if(!$isNew) № {{ $document->num }} @endif
    </h3>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('money.save') }}" method="post">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id ?? 0 }}">
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Дата</label>
                <input type="text" name="data" class="form-control" value="{{ $document->data ?? date('d-m-Y') }}"
                    placeholder="дд-мм-рррр">
            </div>
            <div class="col-md-4 mb-3">
                <label>Каса</label>
                <select name="money" class="form-control">
                    <option value="">— оберіть касу —</option>
                    @foreach($kassas as $kassa)
                    <option value="{{ $kassa->name }}" {{ ($document->money ?? '') == $kassa->name ? 'selected' : '' }}>
                        {{ $kassa->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Сума (грн)</label>
                <input type="number" step="0.01" name="summa" class="form-control" value="{{ $document->summa ?? 0 }}">
            </div>
        </div>

        {{-- Клієнт --}}
        <div class="mb-3">
            <label>Клієнт</label>
            <div id="selectedClientDetails"
                class="alert {{ (!$isNew && !empty($document->id) && !empty($document->client1)) ? 'alert-secondary' : 'alert-warning' }} py-2 mt-2"
                style="{{ (!$isNew && !empty($document->id) && !empty($document->client1)) ? 'background:#f8f9fa; border:1px solid #ddd;' : '' }}">
                @if(!$isNew && !empty($document->id) && !empty($document->client1))
                <strong>{{ $document->orgname ?? '' }}</strong> |
                {{ trim(($document->secondname ?? '') . ' ' . ($document->name ?? '') . ' ' . ($document->name2 ?? ''))
                }}<br>
                {{ $document->phone ?? '' }} | {{ $document->city ?? '' }}
                @else
                Клієнт не обраний
                @endif
            </div>

            {{-- Пошук клієнта --}}
            <div class="input-group mb-2">
                <input type="text" id="clientSearchInput" class="form-control" placeholder="Пошук клієнта..."
                    autocomplete="off" style="width:70%;">
                <button type="button" class="btn" id="searchClientBtn" style="width:15%;">Шукати</button>
            </div>
            <div id="clientSearchResults" class="list-group mb-2"
                style="display:none; max-height:200px; overflow-y:auto; position:absolute; z-index:1000; width:60%; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            </div>
            <input type="hidden" name="client1" id="client1_id" value="{{ $document->client1 ?? '' }}">
        </div>

        <div class="mb-3">
            <label>Коментар</label>
            <input type="text" name="content" class="form-control" value="{{ $document->content ?? '' }}">
        </div>


        <div class="row" style="width: 100%;">
            <div style="width: 80%; align-items: left;">
                <a href="{{ route('money.index') }}"> <button class="btn" style="width: 20%;">← Назад</button></a>
                <button type="submit" class="btn" style="width: 30%;">💾 Зберегти</button>

            </div>
            <div style="width: 20%;">
                @if(!$isNew)
                <form action="{{ route('money.destroy') }}" method="post" style="margin-top: -38px; text-align: right;"
                    onsubmit="return confirm('Дійсно видалити цей документ?');">
                    @csrf
                    <input type="hidden" name="id" value="{{ $document->id }}">
                    <button type="submit" class="btn btn-danger">🗑</button>
                </form>
                @endif
            </div>
        </div>

    </form>


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
                    if (!data.length) {
                        resultsContainer.innerHTML = '<div class="list-group-item text-muted">Нічого не знайдено</div>';
                    } else {
                        data.forEach(user => {
                            const a = document.createElement('a');
                            a.href = '#';
                            a.className = 'list-group-item list-group-item-action';
                            a.innerHTML = `<strong>${user.orgname || ''}</strong> | ${user.name2 || ''} ${user.name || ''} ${user.secondname || ''}<br><small>${user.phone || ''} | ${user.city || ''}</sma       a.addEventListener('click', function (e) {
                                e.preventDefault();
                                client1Id.value = user.id;

                                // Update visually
                                clientDetails.className = 'alert alert-secondary py-2 mt-2';
                                clientDetails.style.background = '#f8f9fa';
                                clientDetails.style.border = '1px solid #ddd';
                                clientDetails.innerHTML = a.innerHTML;

                                // Hide dropdown and clear input
                                resultsContainer.style.display = 'none';
                                searchInput.value = '';
                            });
                            resultsContainer.appendChild(a);
                        });
                    }
                    resultsContainer.style.display = 'block';
                });
        }

        searchBtn.addEventListener('click', performSearch);
        let t = null;
        searchInput.addEventListener('input', () => { clearTimeout(t); t = setTimeout(performSearch, 400); });
        searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); performSearch(); } });
        document.addEventListener('click', e => {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target))
                resultsContainer.style.display = 'none';
        });
    });
</script>

@endsection