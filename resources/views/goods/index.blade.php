@extends('home')

@section('title')
{{ __('goods.title') }} ({{ $total ?? 0 }})
@endsection

@section('content')
<div class="container mt-4">
    @php
        $selectedTop = (string)($idglava ?? '');
        $selectedSub = (string)($idcaption ?? '');
        $availableSubs = collect($subs[$selectedTop] ?? []);
        $isCategoryFiltered = $selectedTop !== '' || $selectedSub !== '';
        $isFilterActive = $isCategoryFiltered
            || !empty($filters['fName'])
            || ($filters['skladNone'] ?? '') === '1'
            || ($filters['showAllGoods'] ?? '') === '1'
            || ($filters['hitOnly'] ?? '') === '1';
        $goodsFilterBtnCls = $isFilterActive ? 'button_submit_start' : 'button_submit_start0';
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
            {{-- Кнопка фильтра в стиле document --}}
            <div onclick="goodsFilterToggle()"
                 class="{{ $goodsFilterBtnCls }}"
                 style="width:70px;height:70px;cursor:pointer;background:linear-gradient(135deg,#fbbf24,#f59e0b);border:none;border-radius:16px;box-shadow:0 4px 12px rgba(251,191,36,0.3);transition:all 0.3s ease;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <img src="/img/icon-category.png" alt="{{ __('document.filter.icon_alt') }}" style="width:32px;filter:brightness(0);">
                <span style="font-size:0.7rem;font-weight:600;color:#000;margin-top:4px;">{{ __('document.filter.search') }}</span>
            </div>
        </div>
        <a href="{{ route('goods.show', ['pnum' => 0]) }}" class="btn btn-primary">{{ __('goods.add') }}</a>
    </div>

    {{-- Убрано информирование о невыбранных категориях по просьбе пользователя --}}

    {{-- Модальное окно фильтра --}}
    <div id="goodsFilterModal" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);z-index:9999;justify-content:center;align-items:center;">
        <div class="glass-card" style="width:700px;max-width:90vw;max-height:80vh;overflow-y:auto;position:relative;margin:0 auto;padding:24px;">
            <div onclick="goodsFilterToggle()" style="position:absolute;top:12px;right:16px;cursor:pointer;font-size:1.5rem;color:var(--muted-foreground);transition:color 0.2s;z-index:10;">✕</div>

            <h3 style="margin:0 0 16px 0;color:var(--foreground);font-family:var(--header);font-size:1.25rem;">🔍 {{ __('document.filter.title') }}</h3>

            <form action="{{ route('goods.index') }}" method="GET" id="goodsFilterForm">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

                    <div style="grid-column: 1 / -1;">
                        <label style="display:block;margin-bottom:4px;font-size:0.85rem;">{{ __('goods.search_label') }}</label>
                        <input type="text" name="fName" autocomplete="off"
                               placeholder="{{ __('goods.search_placeholder') }}"
                               value="{{ $filters['fName'] ?? '' }}"
                               style="width:100%;padding:8px 12px;font-size:0.9rem;">
                    </div>

                    <div>
                        <label style="display:block;margin-bottom:4px;font-size:0.85rem;">{{ __('goods.category') }}</label>
                        <select name="igla" id="goodsModalTopSelect" style="width:100%;padding:8px 12px;font-size:0.9rem;" onchange="goodsModalFillSubs(this.value)">
                            <option value="">{{ __('goods.all') }}</option>
                            @foreach(($tops ?? []) as $top)
                            <option value="{{ $top->id }}" {{ $selectedTop === (string)$top->id ? 'selected' : '' }}>
                                {{ $top->val }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label style="display:block;margin-bottom:4px;font-size:0.85rem;">{{ __('goods.subcategory') }}</label>
                        <select name="idcapt" id="goodsModalSubSelect" style="width:100%;padding:8px 12px;font-size:0.9rem;" {{ $selectedTop === '' ? 'disabled' : '' }}>
                            <option value="">{{ __('goods.all') }}</option>
                            @foreach($availableSubs as $sub)
                            <option value="{{ $sub->id }}" {{ $selectedSub === (string)$sub->id ? 'selected' : '' }}>
                                {{ $sub->val }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:flex;align-items:flex-end;padding-bottom:8px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.9rem;">
                            <input type="checkbox" name="skladNone" value="1" style="width:auto;"
                                   {{ ($filters['skladNone'] ?? '') === '1' ? 'checked' : '' }}>
                            {{ __('goods.show_without_stock') }}
                        </label>
                    </div>

                    <div style="display:flex;align-items:flex-end;padding-bottom:8px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.9rem;">
                            <input type="checkbox" name="hitOnly" value="1" style="width:auto;"
                                   {{ ($filters['hitOnly'] ?? '') === '1' ? 'checked' : '' }}>
                            {{ __('goods.show_hits') }}
                        </label>
                    </div>

                    <div style="display:flex;align-items:flex-end;padding-bottom:8px;">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.9rem;">
                            <input type="checkbox" name="showAllGoods" value="1" style="width:auto;"
                                   {{ ($filters['showAllGoods'] ?? '') === '1' ? 'checked' : '' }}>
                            {{ __('goods.show_all_goods') }}
                        </label>
                    </div>

                </div>

                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="submit" style="flex:1;padding:10px 16px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border:none;border-radius:8px;box-shadow:0 4px 12px rgba(251,191,36,0.3);color:#000;font-weight:600;font-size:0.9rem;cursor:pointer;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center;gap:6px;">
                        <span>🔍</span> {{ __('document.filter.find') }}
                    </button>
                    <a href="{{ route('goods.index') }}?fName=&igla=&idcapt=&skladNone=&hitOnly=&showAllGoods="
                       style="flex:1;padding:10px 16px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.2);border-radius:8px;color:var(--foreground);font-weight:600;font-size:0.9rem;cursor:pointer;transition:all 0.3s ease;display:flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;">
                        <span>✕</span> {{ __('document.filter.reset') }}
                    </a>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    var goodsAllSubs = @json(
        collect($subs ?? [])->map(fn($items) =>
            collect($items)->map(fn($s) => ['id' => $s->id, 'val' => $s->val])->values()
        )
    );

    function goodsFilterToggle() {
        var d = document.getElementById('goodsFilterModal');
        if (d.style.display === 'none' || d.style.display === '') {
            d.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        } else {
            d.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    function goodsModalFillSubs(topId) {
        var sub = document.getElementById('goodsModalSubSelect');
        if (!sub) return;
        sub.innerHTML = '';
        var allOpt = document.createElement('option');
        allOpt.value = '';
        allOpt.textContent = '{{ __('goods.all') }}';
        sub.appendChild(allOpt);
        if (topId && goodsAllSubs[topId]) {
            goodsAllSubs[topId].forEach(function(item) {
                var opt = document.createElement('option');
                opt.value = item.id;
                opt.textContent = item.val;
                sub.appendChild(opt);
            });
            sub.disabled = false;
        } else {
            sub.disabled = true;
        }
    }

    document.getElementById('goodsFilterModal').addEventListener('click', function(e) {
        if (e.target === this) goodsFilterToggle();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var d = document.getElementById('goodsFilterModal');
            if (d && d.style.display === 'flex') goodsFilterToggle();
        }
    });
    </script>
    @endpush

    {{-- Desktop: Table View --}}
    <div class="table-responsive goods-desktop-table">
        <table class="goods-index-table">
            <thead>
                <tr>
                    <th>{{ __('goods.table.id') }}</th>
                    <th>{{ __('goods.table.image') }}</th>
                    <th>{{ __('goods.table.name') }}</th>
                    <th>{{ __('goods.table.price') }}</th>
                    <th>{{ __('goods.table.price1') }}</th>
                    <th>{{ __('goods.table.old_price') }}</th>
                    <th>{{ __('goods.table.count') }}</th>
                    <th>{{ __('goods.table.stock') }}</th>
                    <th>{{ __('goods.table.brand') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($comps as $comp)
                @php
                    $stockQty = (float)($comp->price_count ?? 0);
                    $stockFlag = (string)($comp->sklad ?? '0') === '1';
                    $hasStock = $stockFlag || $stockQty > 0;
                @endphp
                <tr>
                    <td><a href="{{ route('goods.show', ['pnum' => $comp->id]) }}">{{ $comp->id }}</a></td>
                    <td class="text-center align-middle">
                        @php
                            $previewImage = \App\Support\MediaUrl::image($comp->nfoto ?? '');
                        @endphp
                        @if($previewImage)
                            <a href="{{ route('goods.show', ['pnum' => $comp->id]) }}">
                                <img
                                    src="{{ $previewImage }}"
                                    alt="{{ $comp->name ?? $comp->nickname }}"
                                    style="width:72px;height:48px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6;"
                                >
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('goods.show', ['pnum' => $comp->id]) }}">
                            {{ $comp->name ?? $comp->nickname }}
                        </a>
                        @if((string)($comp->hit ?? '0') === '1')
                            <span class="goods-hit-badge">{{ __('goods.hit') }}</span>
                        @endif
                    </td>
                    <td>{{ number_format((float)($comp->price_pay ?? 0), 2, '.', ' ') }}</td>
                    <td>{{ number_format((float)($comp->price_pay1 ?? 0), 2, '.', ' ') }}</td>
                    <td>{{ number_format((float)($comp->price_oldpay ?? 0), 2, '.', ' ') }}</td>
                    <td>{{ rtrim(rtrim(number_format((float)($comp->price_count ?? 0), 3, '.', ''), '0'), '.') }}</td>
                    <td>
                        <div class="d-flex flex-column gap-1">
                            <span>{{ $comp->price_sklad_name ?? '—' }}</span>
                            <span class="goods-stock-badge {{ $hasStock ? 'in-stock' : 'out-of-stock' }}" style="width:max-content;">
                                {{ $hasStock ? __('goods.in_stock') : __('goods.out_of_stock') }}
                            </span>
                        </div>
                    </td>
                    <td>{{ $comp->price_tgroup ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center">{{ __('goods.empty') }}</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Mobile: Card View --}}
    <div class="d-md-none goods-mobile-grid">
        @forelse($comps as $comp)
        @php
            $previewImage = \App\Support\MediaUrl::image($comp->nfoto ?? '');
            $stockQty = (float)($comp->price_count ?? 0);
            $stockFlag = (string)($comp->sklad ?? '0') === '1';
            $hasStock = $stockFlag || $stockQty > 0;
        @endphp
        <div class="goods-mobile-card">
            <a href="{{ route('goods.show', ['pnum' => $comp->id]) }}" class="goods-mobile-card-link">
                @if($previewImage)
                <div class="goods-mobile-image">
                    <img src="{{ $previewImage }}" alt="{{ $comp->name ?? $comp->nickname }}" loading="lazy">
                </div>
                @endif
                <div class="goods-mobile-content">
                    <div class="goods-mobile-header">
                        <h3 class="goods-mobile-name">
                            {{ $comp->name ?? $comp->nickname }}
                            @if((string)($comp->hit ?? '0') === '1')
                                <span class="goods-hit-badge">{{ __('goods.hit') }}</span>
                            @endif
                        </h3>
                        @if($hasStock)
                        <span class="goods-stock-badge in-stock">{{ __('goods.in_stock') }}</span>
                        @else
                        <span class="goods-stock-badge out-of-stock">{{ __('goods.out_of_stock') }}</span>
                        @endif
                    </div>
                    
                    <div class="goods-mobile-prices">
                        <div class="goods-price-item">
                            <span class="price-label">{{ __('goods.table.price') }}</span>
                            <span class="price-value">{{ number_format((float)($comp->price_pay ?? 0), 2, '.', ' ') }}</span>
                        </div>
                        @if(($comp->price_pay1 ?? 0) > 0)
                        <div class="goods-price-item">
                            <span class="price-label">{{ __('goods.table.price1') }}</span>
                            <span class="price-value">{{ number_format((float)($comp->price_pay1 ?? 0), 2, '.', ' ') }}</span>
                        </div>
                        @endif
                        @if(($comp->price_oldpay ?? 0) > 0)
                        <div class="goods-price-item old-price">
                            <span class="price-label">{{ __('goods.table.old_price') }}</span>
                            <span class="price-value">{{ number_format((float)($comp->price_oldpay ?? 0), 2, '.', ' ') }}</span>
                        </div>
                        @endif
                    </div>

                    <div class="goods-mobile-meta">
                        @if(($comp->price_count ?? 0) > 0)
                        <div class="meta-item">
                            <span class="meta-label">{{ __('goods.table.count') }}</span>
                            <span class="meta-value">{{ rtrim(rtrim(number_format((float)($comp->price_count ?? 0), 3, '.', ''), '0'), '.') }}</span>
                        </div>
                        @endif
                        @if($comp->price_sklad_name)
                        <div class="meta-item">
                            <span class="meta-label">{{ __('goods.table.stock') }}</span>
                            <span class="meta-value">{{ $comp->price_sklad_name }}</span>
                        </div>
                        @endif
                        @if($comp->price_tgroup)
                        <div class="meta-item">
                            <span class="meta-label">{{ __('goods.table.brand') }}</span>
                            <span class="meta-value">{{ $comp->price_tgroup }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </a>
        </div>
        @empty
        <div class="goods-mobile-empty">
            <p>{{ __('goods.empty') }}</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @php
        $pageParams = array_merge($filters ?? [], ['igla' => $idglava ?? '', 'idcapt' => $idcaption ?? '', 'sort' => $sort ?? '']);
    @endphp
    @include('partials.navigator', [
            'pos' => $pos,
            'pos2' => $pos2,
            'max' => $total,
            'routeName' => 'goods.index',
            'routeParams' => $pageParams,
    ])
</div>
@endsection
