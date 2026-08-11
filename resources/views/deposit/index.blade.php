@extends('home')

@section('title', __('deposit.title'))

@section('content')
@php
    $datesAreDefault = $datesAreDefault ?? false;
    $activeFilters = array_filter($filters ?? [], function ($value, $key) use ($datesAreDefault) {
        if ($value === '' || $value === null) {
            return false;
        }

        if ($key === 'tab' || ($datesAreDefault && in_array($key, ['date_from', 'date_to'], true))) {
            return false;
        }

        return true;
    }, ARRAY_FILTER_USE_BOTH);
    $hasDateFilter = (($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '');
    $usesPoolDeposits = $usesPoolDeposits ?? false;
    $activeTab = $usesPoolDeposits ? (($filters['tab'] ?? 'deposits') === 'pools' ? 'pools' : 'deposits') : '';
    $resetFilterUrl = $usesPoolDeposits ? route('deposit.index', ['tab' => $activeTab]) : route('deposit.index');
    $depositDocuments = collect($documents ?? [])->reject(fn ($doc) => str_starts_with((string) ($doc->money ?? ''), 'pool:'))->values();
    $poolDocuments = collect($documents ?? [])->filter(fn ($doc) => str_starts_with((string) ($doc->money ?? ''), 'pool:'))->values();
@endphp

<div class="ttable document-compact-wrap" style="padding: 16px;">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!empty($activeFilters))
    <div class="alert alert-warning money-filter-active-notice">
        {{ __('deposit.filter_active') }}
        <a href="{{ $resetFilterUrl }}" style="margin-left: 8px;">{{ __('deposit.reset') }}</a>
    </div>
    @endif

    @if($hasDateFilter)
    <div class="alert alert-secondary money-filter-active-notice" style="border:1px solid var(--border); background:rgba(255,255,255,0.04); color:var(--foreground);">
        {{ $datesAreDefault ? 'Показані операції за останні 30 днів:' : 'Показані операції за вибраний період:' }}
        <strong>{{ $filters['date_from'] ?: '—' }}</strong> - <strong>{{ $filters['date_to'] ?: '—' }}</strong>.
    </div>
    @endif

    <div class="money-summary">
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">🏦</div>
            <div class="money-summary__label">{{ __('deposit.deposit_operations') }}</div>
            @if(!empty($depositTotals) && $depositTotals->isNotEmpty())
            <div class="mt-2" style="display:grid; gap:6px; font-size:0.86rem;">
                @foreach($depositTotals as $depositTotal)
                <div style="display:flex; justify-content:space-between; gap:12px; color:var(--muted-foreground);">
                    <span style="overflow:hidden; text-overflow:ellipsis;">{{ $depositTotal->name }}</span>
                    <strong style="white-space:nowrap; color:var(--foreground);">{{ number_format($depositTotal->total_sum, 2, '.', ' ') }}</strong>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        <div class="glass-card money-summary__card">
            <div class="money-summary__icon">📄</div>
            <div class="money-summary__label">{{ __('deposit.documents_count') }}</div>
            <div class="money-summary__value money-summary__value--income">{{ $total ?? 0 }}</div>
        </div>
    </div>

    @if($usesPoolDeposits ?? false)
    <div class="deposit-tabs" data-deposit-tabs>
        <button type="button" class="deposit-tab {{ $activeTab === 'deposits' ? 'is-active' : '' }}" data-deposit-tab="deposits">Депозиты</button>
        <button type="button" class="deposit-tab {{ $activeTab === 'pools' ? 'is-active' : '' }}" data-deposit-tab="pools">Пулы</button>
    </div>

    <section data-deposit-pane="deposits" {{ $activeTab === 'deposits' ? '' : 'hidden' }}>
        <div class="deposit-pane-header glass-card">
            <div>
                <div class="deposit-pane-title">Операции депозитов</div>
                <div class="deposit-pane-meta">{{ $depositDocuments->count() }} операций текущего проекта</div>
            </div>
            <div class="deposit-pane-actions">
                <button type="button" class="{{ !empty($activeFilters ?? []) && $activeTab === 'deposits' ? 'btn btn-sm btn-warning' : 'btn btn-sm btn-outline-secondary' }}" data-deposit-filter-open data-filter-tab="deposits">Фильтр</button>
                <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'topup', 'target' => 'deposit']) }}" class="btn btn-sm btn-warning">Пополнить</a>
                <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'withdraw', 'target' => 'deposit']) }}" class="btn btn-sm btn-outline-secondary">Вынуть</a>
            </div>
        </div>
        @include('deposit.partials.document-list', [
            'documents' => $depositDocuments,
            'depositMap' => $depositMap,
            'poolMap' => $poolMap ?? collect(),
            'emptyMessage' => 'Операции депозитов пока не созданы.',
        ])
    </section>

    <section data-deposit-pane="pools" {{ $activeTab === 'pools' ? '' : 'hidden' }}>
        <div class="deposit-pane-header glass-card">
            <div>
                <div class="deposit-pane-title">Операции пулов</div>
                <div class="deposit-pane-meta">{{ $poolDocuments->count() }} операций по пулам текущего проекта</div>
            </div>
            <div class="deposit-pane-actions">
                <button type="button" class="{{ !empty($activeFilters ?? []) && $activeTab === 'pools' ? 'btn btn-sm btn-warning' : 'btn btn-sm btn-outline-secondary' }}" data-deposit-filter-open data-filter-tab="pools">Фильтр</button>
                <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'topup', 'target' => 'pool']) }}" class="btn btn-sm btn-warning">Пополнить</a>
                <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'withdraw', 'target' => 'pool']) }}" class="btn btn-sm btn-outline-secondary">Вынуть</a>
            </div>
        </div>
        @include('deposit.partials.document-list', [
            'documents' => $poolDocuments,
            'depositMap' => $depositMap,
            'poolMap' => $poolMap ?? collect(),
            'emptyMessage' => 'Операции пулов пока не созданы.',
        ])
    </section>
    @else
    <div class="deposit-pane-header glass-card">
        <div>
            <div class="deposit-pane-title">{{ __('deposit.deposit_operations') }}</div>
            <div class="deposit-pane-meta">{{ $total ?? 0 }} документов</div>
        </div>
        <div class="deposit-pane-actions">
            <button type="button" class="{{ !empty($activeFilters ?? []) ? 'btn btn-sm btn-warning' : 'btn btn-sm btn-outline-secondary' }}" data-deposit-filter-open>Фильтр</button>
            <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'topup']) }}" class="btn btn-sm btn-warning">{{ __('deposit.add_deposit') }}</a>
            <a href="{{ route('deposit.show', ['id' => 0, 'mode' => 'withdraw']) }}" class="btn btn-sm btn-outline-secondary">{{ __('deposit.add_withdraw') }}</a>
        </div>
    </div>
    @include('deposit.partials.document-list', [
        'documents' => collect($documents ?? []),
        'depositMap' => $depositMap,
        'poolMap' => $poolMap ?? collect(),
        'emptyMessage' => __('deposit.no_documents'),
    ])
    @endif

</div>

<style>
    .deposit-tabs {
        display: inline-flex;
        gap: 4px;
        margin: 0 0 14px;
        padding: 4px;
        border: 1px solid var(--border);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.04);
    }

    .deposit-tab {
        min-width: 112px;
        min-height: 36px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--muted-foreground);
        font-weight: 700;
    }

    .deposit-tab.is-active {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #111827;
    }

    .deposit-pane-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
        padding: 12px 14px;
    }

    .deposit-pane-title {
        color: var(--foreground);
        font-weight: 800;
    }

    .deposit-pane-meta {
        color: var(--muted-foreground);
        font-size: 0.86rem;
    }

    .deposit-pane-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
</style>

<div id="depositFilterModal" class="money-filter-modal">
    <div class="glass-card money-filter-modal__content">
        <div onclick="depositFilterToggle()" class="money-filter-modal__close">✕</div>
        <h3 class="money-filter-modal__title">🔍 {{ __('deposit.filter_title') }}</h3>

        @if($hasDateFilter && ! $datesAreDefault)
        <div style="margin-bottom:16px; padding:10px 12px; border-radius:10px; border:1px solid rgba(251,191,36,0.28); background:rgba(251,191,36,0.08); color:var(--foreground); font-size:0.9rem;">
            Активний діапазон дат:
            <strong>{{ $filters['date_from'] ?: '—' }}</strong> - <strong>{{ $filters['date_to'] ?: '—' }}</strong>.
        </div>
        @endif

        <form action="{{ route('deposit.index') }}" method="get">
            <input type="hidden" name="tab" value="{{ $activeTab }}" data-deposit-filter-tab>
            <div class="money-filter-modal__grid">
                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_search') }}</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" maxlength="30" data-deposit-filter-safe autocomplete="off">
                </div>

                <div class="money-filter-modal__field">
                    <label>{{ __('deposit.field_operation') }}</label>
                    <select name="mode" class="form-control">
                        <option value="">{{ __('money.filter_all_types') }}</option>
                        <option value="topup" {{ ($filters['mode'] ?? '') === 'topup' ? 'selected' : '' }}>{{ __('deposit.op_topup') }}</option>
                        <option value="withdraw" {{ ($filters['mode'] ?? '') === 'withdraw' ? 'selected' : '' }}>{{ __('deposit.op_withdraw') }}</option>
                    </select>
                </div>

                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_date_from') }}</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>

                <div class="money-filter-modal__field">
                    <label>{{ __('money.filter_date_to') }}</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
            </div>

            <div class="money-filter-modal__actions">
                <button type="submit" class="btn btn-warning">{{ __('money.filter_apply') }}</button>
                <a href="{{ $resetFilterUrl }}" class="btn btn-outline-secondary">{{ __('money.filter_reset') }}</a>
            </div>
        </form>
    </div>
</div>

<script>
function depositFilterToggle() {
    const modal = document.getElementById('depositFilterModal');
    if (modal.style.display === 'none' || modal.style.display === '') {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    } else {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

function depositFilterOpen(tabName) {
    const tabInput = document.querySelector('[data-deposit-filter-tab]');
    if (tabInput) {
        tabInput.value = tabName || '';
    }
    depositFilterToggle();
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('depositFilterModal');
        if (modal && modal.style.display === 'flex') {
            depositFilterToggle();
        }
    }
});

document.querySelectorAll('#depositFilterModal [data-deposit-filter-safe]').forEach(function(input) {
    const sanitize = function(value) {
        return String(value || '')
            .replace(/[<>{}\[\]\\\/=;:*|~^$#@!?%&+]/g, '')
            .replace(/[^\p{L}\p{M}\p{N}\s.,'"’`-]/gu, '')
            .replace(/\s+/g, ' ')
            .slice(0, 30);
    };

    input.value = sanitize(input.value);
    input.addEventListener('input', function() {
        input.value = sanitize(input.value);
    });
});

document.addEventListener('click', function (e) {
    const modal = document.getElementById('depositFilterModal');
    if (modal && e.target === modal) {
        depositFilterToggle();
    }
});

document.querySelectorAll('[data-deposit-filter-open]').forEach(function (button) {
    button.addEventListener('click', function () {
        depositFilterOpen(button.dataset.filterTab || '');
    });
});

document.querySelectorAll('[data-deposit-tab]').forEach(function (tab) {
    tab.addEventListener('click', function () {
        const tabName = tab.dataset.depositTab || 'deposits';

        document.querySelectorAll('[data-deposit-tab]').forEach(function (button) {
            button.classList.toggle('is-active', button.dataset.depositTab === tabName);
        });
        document.querySelectorAll('[data-deposit-pane]').forEach(function (pane) {
            pane.hidden = pane.dataset.depositPane !== tabName;
        });

        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        url.searchParams.delete('pos');
        window.location.href = url.toString();
    });
});
</script>
@endsection
