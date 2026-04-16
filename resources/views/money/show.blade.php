@extends('home')

@section('title', $document->id ? __('money.edit_title', ['num' => $document->num]) . ' — ' . $document->type : __('money.create_title'))

@section('content')
@include('money.partials.top-actions', ['returnFilters' => $returnFilters ?? []])

<div class="ttable money-show-page" style="padding: 20px; max-width: 700px; margin: 0 auto; border-radius: 8px;">

    @php
    $isNew = empty($document->id);
    $type = request('type', $document->type ?? 'PO');
    $isPO = $type === 'PO';
    $backUrl = route('money.index', $returnFilters ?? []);
    $selectedCashboxId = old('money', $document->effective_cashbox_id ?? $document->money ?? $document->oplata ?? '');
    @endphp

    <h3 style="color:{{ $isPO ? 'green' : 'red' }};">
        {{ $isPO ? '📥 ' . __('money.heading_income') : '📤 ' . __('money.heading_outcome') }}
        @if(!$isNew) № {{ $document->num }} @endif
    </h3>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('money.save') }}" method="post">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id ?? 0 }}">
        <input type="hidden" name="type" value="{{ $type }}">
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
                <input type="text" name="data" class="form-control" value="{{ $document->data ?? date('d-m-Y') }}"
                    placeholder="{{ __('money.date_placeholder') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label>{{ __('money.field_cashbox') }}</label>
                <select name="money" class="form-control" required>
                    <option value="">{{ __('money.select_cashbox') }}</option>
                    @foreach($kassas as $kassa)
                    <option value="{{ $kassa->id }}" {{ (string)$selectedCashboxId === (string)$kassa->id ? 'selected' : '' }}>
                        {{ $kassa->name }}
                    </option>
                    @endforeach
                </select>
                @error('money')
                <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
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

        {{-- Клієнт --}}
        <div class="mb-3">
            <label>{{ __('money.field_client') }}</label>
            <div id="selectedClientDetails"
                class="alert {{ (!$isNew && !empty($document->id) && !empty($document->client1)) ? 'alert-secondary' : 'alert-warning' }} py-2 mt-2"
                style="{{ (!$isNew && !empty($document->id) && !empty($document->client1)) ? 'border:1px solid var(--border);' : '' }}">
                @if(!$isNew && !empty($document->id) && !empty($document->client1))
                <strong>{{ $document->orgname ?? '' }}</strong> |
                {{ trim(($document->secondname ?? '') . ' ' . ($document->name ?? '') . ' ' . ($document->name2 ?? '')) }}<br>
                {{ $document->phone ?? '' }} | {{ $document->region ? $document->region . ' | ' : '' }}{{ $document->city ?? '' }}{{ $document->poshta ? ' | ' . $document->poshta : '' }}
                @else
                {{ __('money.client_not_selected') }}
                @endif
            </div>

            {{-- Пошук клієнта --}}
            <div class="input-group mb-2">
                <input type="text" id="clientSearchInput" class="form-control" placeholder="{{ __('money.search_client') }}"
                    autocomplete="off" style="width:70%;">
                <button type="button" class="btn" id="searchClientBtn" style="width:15%;">{{ __('money.search') }}</button>
            </div>
            <div id="clientSearchResults" class="list-group mb-2"
                style="display:none; max-height:200px; overflow-y:auto; position:absolute; z-index:1000; width:60%; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
            </div>
            <input type="hidden" name="client1" id="client1_id" value="{{ $document->client1 ?? '' }}">
        </div>

        <div class="mb-3">
            <label>{{ __('money.field_comment') }}</label>
            <input type="text" name="content" class="form-control" value="{{ $document->content ?? '' }}">
        </div>

        @if((int)($document->provodka ?? 0) === 0)
        <div class="mb-3 form-check">
            <input type="hidden" name="post_after_save" value="0">
            <input
                type="checkbox"
                class="form-check-input"
                id="post_after_save"
                name="post_after_save"
                value="1"
                checked>
            <label class="form-check-label" for="post_after_save">
                {{ __('money.checkbox_post') }}
            </label>
        </div>
        @endif

        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="{{ $backUrl }}" class="btn btn-outline-secondary">← {{ __('money.btn_back') }}</a>
            @if((int)($document->provodka ?? 0) === 1)
            <button type="submit"
                formaction="{{ route('money.provodka') }}"
                formmethod="post"
                class="btn btn-success">
                ↺ {{ __('money.btn_unpost') }}
            </button>
            @else
            <button type="submit" class="btn">💾 {{ __('money.btn_save') }}</button>
            @endif
            @if((int)($document->provodka ?? 0) === 0 && !$isNew)
            <button type="button"
                class="btn btn-danger"
                onclick="if(confirm('{{ __('money.confirm_delete') }}')) { document.getElementById('deleteMoneyForm').submit(); }">
                🗑 {{ __('money.btn_delete') }}
            </button>
            @endif
        </div>

    </form>

    @if(!$isNew)
    <form id="deleteMoneyForm" action="{{ route('money.destroy') }}" method="post" style="display:none;">
        @csrf
        <input type="hidden" name="id" value="{{ $document->id }}">
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('clientSearchInput');
        const searchBtn = document.getElementById('searchClientBtn');
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
                                client1Id.value = user.id;

                                // Update visually
                                clientDetails.className = 'alert alert-secondary py-2 mt-2';
                                clientDetails.style.border = '1px solid var(--border)';
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
