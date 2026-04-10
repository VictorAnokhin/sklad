@extends('home')

@section('title', 'Гроші')

@section('content')
@php
    $perPage = 30;
    $currentPage = (int) floor(($pos ?? 0) / $perPage) + 1;
    $totalPages = max(1, (int) ceil(($total ?? 0) / $perPage));
    $from = ($total ?? 0) > 0 ? (($pos ?? 0) + 1) : 0;
    $to = min(($pos ?? 0) + $perPage, $total ?? 0);
    $windowStart = max(1, min($currentPage - 1, max(1, $totalPages - 3)));
    $windowEnd = min($totalPages, $windowStart + 3);
    $activeFilters = array_filter($filters ?? [], fn($value) => $value !== '' && $value !== null);
    $returnFilters = array_merge($filters ?? [], ['pos' => $pos ?? 0]);
@endphp

<div class="ttable" style="padding: 12px 16px; margin-bottom: 16px;">
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <button type="button"
            onclick="moneyFilterToggle()"
            class="btn {{ !empty($activeFilters) ? 'btn-warning' : 'btn-outline-secondary' }}">
            🔍 Фільтр
        </button>
        <a href="{{ route('money.show', array_merge(['id' => 0, 'type' => 'PO'], [
            'return_q' => $returnFilters['q'] ?? null,
            'return_filter_type' => $returnFilters['type'] ?? null,
            'return_money' => $returnFilters['money'] ?? null,
            'return_reestr' => $returnFilters['reestr'] ?? null,
            'return_date_from' => $returnFilters['date_from'] ?? null,
            'return_date_to' => $returnFilters['date_to'] ?? null,
            'return_pos' => $returnFilters['pos'] ?? null,
        ])) }}" class="btn btn-success">+ Прихід</a>
        <a href="{{ route('money.show', array_merge(['id' => 0, 'type' => 'RO'], [
            'return_q' => $returnFilters['q'] ?? null,
            'return_filter_type' => $returnFilters['type'] ?? null,
            'return_money' => $returnFilters['money'] ?? null,
            'return_reestr' => $returnFilters['reestr'] ?? null,
            'return_date_from' => $returnFilters['date_from'] ?? null,
            'return_date_to' => $returnFilters['date_to'] ?? null,
            'return_pos' => $returnFilters['pos'] ?? null,
        ])) }}" class="btn btn-danger">+ Видача</a>
    </div>
</div>
<div class="ttable" style="padding: 16px;">

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(!empty($activeFilters))
    <div class="alert alert-warning" style="margin-bottom: 16px;">
        Увімкнено фільтр по ордерах.
        <a href="{{ route('money.index') }}" style="margin-left: 8px;">Скинути</a>
    </div>
    @endif

    {{-- Зведення --}}
    <div style="display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap;">
        <div class="glass-card" style="flex:1;min-width:200px;text-align:center;">
            <div style="font-size:2rem;">📥</div>
            <div style="font-weight:bold;font-size:1.1em;">Приход (PO)</div>
            <div style="color:var(--accent-amber);font-size:1.25rem;font-weight:700;">{{ number_format($sumPO, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card" style="flex:1;min-width:200px;text-align:center;">
            <div style="font-size:2rem;">📤</div>
            <div style="font-weight:bold;font-size:1.1em;">Видача (RO)</div>
            <div style="color:#f87171;font-size:1.25rem;font-weight:700;">{{ number_format($sumRO, 2, '.', ' ') }} грн</div>
        </div>
        <div class="glass-card" style="flex:1;min-width:200px;text-align:center;">
            <div style="font-size:2rem;">💰</div>
            <div style="font-weight:bold;font-size:1.1em;">Баланс</div>
            <div style="color:var(--accent-amber);font-size:1.25rem;font-weight:700;">{{ number_format($sumPO - $sumRO, 2, '.', ' ') }} грн</div>
        </div>
    </div>



    {{-- Список документів --}}

    @if($documents->isEmpty())
    <div style="text-align:center; padding:20px; color:#CC0000;">Документи відсутні...</div>
    @else
    <table class="table table-bordered table-sm">
        <thead style="background:#efefef;">
            <tr>
                <th>#</th>
                <th>Тип</th>
                <th>Дата</th>
                <th>Клієнт</th>
                <th>Каса</th>
                <th>Сума (грн)</th>
                <th>Коментар</th>
                <th>Пров.</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $doc)
            <tr>
                <td>{{ $doc->num }}</td>
                <td>
                    @if($doc->type === 'PO')
                    <span style="color:green; font-weight:bold;">📥 PO</span>
                    @else
                    <span style="color:red; font-weight:bold;">📤 RO</span>
                    @endif
                </td>
                <td>{{ $doc->data ?? '—' }}</td>
                <td style="font-size:0.9em;">
                    {{ $doc->orgname ?? '' }}
                    {{ trim(($doc->secondname ?? '') . ' ' . ($doc->name ?? '') . ' ' . ($doc->name2 ?? '')) }}
                    @if($doc->phone)<br><small>{{ $doc->phone }}</small>@endif
                </td>
                <td>{{ $kassasMap[$doc->money ?? ''] ?? ($doc->money ?: '—') }}</td>
                <td style="font-weight:bold; color:{{ $doc->type === 'PO' ? 'green' : 'red' }};">
                    {{ number_format($doc->summa ?? 0, 2, '.', ' ') }}
                </td>
                <td style="font-size:0.9em;">{{ $doc->content ?? '' }}</td>
                <td style="text-align:center;">{{ $doc->provodka ? '✅' : '' }}</td>
                <td>
                    <a href="{{ route('money.show', array_merge(['id' => $doc->id, 'type' => $doc->type], [
                        'return_q' => $returnFilters['q'] ?? null,
                        'return_filter_type' => $returnFilters['type'] ?? null,
                        'return_money' => $returnFilters['money'] ?? null,
                        'return_reestr' => $returnFilters['reestr'] ?? null,
                        'return_date_from' => $returnFilters['date_from'] ?? null,
                        'return_date_to' => $returnFilters['date_to'] ?? null,
                        'return_pos' => $returnFilters['pos'] ?? null,
                    ])) }}" class="btn btn-sm btn-outline-primary">✏</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if(($total ?? 0) > $perPage)
    <div class="money-pagination">
        <div class="money-pagination__meta">
            Показано {{ $from }}-{{ $to }} з {{ $total }}
        </div>

        <div class="money-pagination__controls">
            @if(($pos ?? 0) > 0)
            <a href="{{ route('money.index', array_merge($filters ?? [], ['pos' => max(0, ($pos ?? 0) - $perPage)])) }}" class="money-pagination__nav">
                ← Назад
            </a>
            @endif

            <div class="money-pagination__pages">
                @for($page = $windowStart; $page <= $windowEnd; $page++)
                    @php $pagePos = ($page - 1) * $perPage; @endphp
                    <a href="{{ route('money.index', array_merge($filters ?? [], ['pos' => $pagePos])) }}"
                        class="money-pagination__page {{ $page === $currentPage ? 'is-active' : '' }}">
                        {{ $page }}
                    </a>
                @endfor
            </div>

            @if((($pos ?? 0) + $perPage) < ($total ?? 0))
            <a href="{{ route('money.index', array_merge($filters ?? [], ['pos' => ($pos ?? 0) + $perPage])) }}" class="money-pagination__nav">
                Вперед →
            </a>
            @endif
        </div>
    </div>
    @endif
    @endif

</div>

<div id="moneyFilterModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); backdrop-filter:blur(8px); z-index:9999; justify-content:center; align-items:center;">
    <div class="glass-card" style="width:720px; max-width:92vw; max-height:82vh; overflow-y:auto; position:relative; margin:0 auto; padding:24px;">
        <div onclick="moneyFilterToggle()" style="position:absolute; top:12px; right:16px; cursor:pointer; font-size:1.5rem; color:var(--muted-foreground); z-index:10;">✕</div>
        <h3 style="margin:0 0 16px 0;">🔍 Фільтр ордерів</h3>

        <form action="{{ route('money.index') }}" method="get">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Номер, клієнт або коментар</label>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control">
                </div>

                <div>
                    <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Тип</label>
                    <select name="type" class="form-control">
                        <option value="">Усі</option>
                        <option value="PO" {{ ($filters['type'] ?? '') === 'PO' ? 'selected' : '' }}>Прихід (PO)</option>
                        <option value="RO" {{ ($filters['type'] ?? '') === 'RO' ? 'selected' : '' }}>Видача (RO)</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Каса</label>
                    <select name="money" class="form-control">
                        <option value="">Усі</option>
                        @foreach(($kassasMap ?? []) as $moneyName => $moneyLabel)
                        <option value="{{ $moneyName }}" {{ ($filters['money'] ?? '') === (string)$moneyName ? 'selected' : '' }}>
                            {{ $moneyLabel }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Вид платежу</label>
                    <select name="reestr" class="form-control">
                        <option value="">Усі</option>
                        @foreach(($paymentTypes ?? []) as $paymentType)
                        <option value="{{ $paymentType->id }}" {{ ($filters['reestr'] ?? '') === (string)$paymentType->id ? 'selected' : '' }}>
                            {{ $paymentType->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Дата початку</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>

                <div>
                    <label style="display:block; margin-bottom:4px; font-size:0.85rem;">Дата закінчення</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
            </div>

            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-warning" style="flex:1;">Застосувати</button>
                <a href="{{ route('money.index') }}" class="btn btn-outline-secondary" style="flex:1;">Скинути</a>
            </div>
        </form>
    </div>
</div>

<style>
    .money-pagination {
        margin-top: 18px;
        padding-top: 14px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    .money-pagination__meta {
        color: #9ca3af;
        font-size: 0.92rem;
    }

    .money-pagination__controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .money-pagination__pages {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .money-pagination__page,
    .money-pagination__nav {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 40px;
        height: 40px;
        padding: 0 14px;
        border-radius: 999px;
        text-decoration: none;
        color: #e5e7eb;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: 0.18s ease;
    }

    .money-pagination__page:hover,
    .money-pagination__nav:hover {
        background: rgba(251, 191, 36, 0.12);
        border-color: rgba(251, 191, 36, 0.4);
        color: #fff;
    }

    .money-pagination__page.is-active {
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        color: #111827;
        border-color: transparent;
        font-weight: 700;
        box-shadow: 0 8px 18px rgba(245, 158, 11, 0.25);
    }
</style>

<script>
function moneyFilterToggle() {
    const modal = document.getElementById('moneyFilterModal');
    if (modal.style.display === 'none' || modal.style.display === '') {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    } else {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('moneyFilterModal');
        if (modal && modal.style.display === 'flex') {
            moneyFilterToggle();
        }
    }
});

document.addEventListener('click', function (e) {
    const modal = document.getElementById('moneyFilterModal');
    if (modal && e.target === modal) {
        moneyFilterToggle();
    }
});
</script>
@endsection
