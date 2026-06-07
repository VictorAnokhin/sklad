@extends('home')

@section('title')
{{ $pnum && $pnum !== '0' ? 'Редагування товару' : 'Новий товар' }}
@endsection

@section('content')
<div class="container mt-2">
    @php
        $comp = $comp ?? (object) [];
        $descript = $descript ?? (object) [];
    @endphp

    <style>
        /* ---- Compact dark form ---- */
        .gs-form label {
            display: block;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: rgba(251,191,36,0.75);
            margin-bottom: 3px;
        }

        .gs-form .form-control,
        .gs-form .form-select {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 6px;
            color: #e2e8f0;
            font-size: 1rem;
            padding: 9px 12px;
            height: auto;
            min-height: 42px;
            transition: border-color 0.2s, background 0.2s;
        }

        .gs-form .form-control:focus,
        .gs-form .form-select:focus {
            background: rgba(255,255,255,0.08);
            border-color: rgba(251,191,36,0.55);
            box-shadow: 0 0 0 3px rgba(251,191,36,0.1);
            color: #fff;
            outline: none;
        }

        .gs-form .form-control[readonly] {
            background: rgba(255,255,255,0.03);
            color: rgba(255,255,255,0.45);
            cursor: default;
        }

        .gs-form .form-control.profit-field {
            background: rgba(251,191,36,0.08);
            border-color: rgba(251,191,36,0.3);
            color: #fbbf24;
            font-weight: 700;
        }

        .gs-form select option {
            background: #1e2130;
            color: #e2e8f0;
        }

        .gs-form textarea.form-control {
            min-height: 90px;
            resize: vertical;
        }

        /* Row spacing */
        .gs-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }

        .gs-col { flex: 1 1 0; min-width: 140px; }
        .gs-col-2 { flex: 2 2 0; min-width: 200px; }
        .gs-col-3 { flex: 3 3 0; min-width: 240px; }

        /* Section divider */
        .gs-section {
            padding: 12px 0 4px;
            margin-bottom: 6px;
            border-top: 1px solid rgba(255,255,255,0.07);
        }

        .gs-section-title {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.3);
            margin-bottom: 8px;
        }

        /* Price table */
        .gs-price-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .gs-price-table thead tr {
            background: linear-gradient(135deg, rgba(251,191,36,0.12), rgba(245,158,11,0.06));
            border-bottom: 1px solid rgba(251,191,36,0.25);
        }

        .gs-price-table thead th {
            padding: 7px 10px;
            font-size: 0.7rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: rgba(251,191,36,0.8);
            border: none;
            white-space: nowrap;
        }

        .gs-price-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .gs-price-table tbody tr:hover {
            background: rgba(251,191,36,0.04);
        }

        .gs-price-table tbody td {
            padding: 5px 8px;
            border: none;
            color: #e2e8f0;
        }

        .gs-price-table .form-control,
        .gs-price-table .form-control-sm {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 5px;
            color: #e2e8f0;
            font-size: 0.88rem;
            padding: 5px 8px;
            min-height: 34px;
        }

        .gs-price-table .form-control:focus,
        .gs-price-table .form-control-sm:focus {
            border-color: rgba(251,191,36,0.4);
            outline: none;
            box-shadow: none;
        }

        .gs-price-table .input-group-text {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem;
            padding: 4px 8px;
        }

        /* Photo cards */
        .gs-photo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 8px;
        }

        .gs-photo-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.09);
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.2s;
        }

        .gs-photo-card:hover {
            border-color: rgba(251,191,36,0.25);
        }

        .gs-photo-card__img {
            width: 100%;
            height: auto;
            max-height: 220px;
            object-fit: contain;
            background: #0d0f18;
            display: block;
        }

        .gs-photo-card__empty {
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.02);
        }

        .gs-photo-card__body {
            padding: 8px 10px;
        }

        .gs-photo-card__meta {
            font-size: 0.68rem;
            color: rgba(255,255,255,0.3);
            margin-bottom: 4px;
        }

        .gs-photo-card label {
            font-size: 0.7rem;
            font-weight: 600;
            color: rgba(251,191,36,0.7);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }

        .gs-photo-card .form-control {
            font-size: 0.82rem;
            padding: 5px 8px;
            min-height: 34px;
        }

        /* Input group */
        .gs-form .input-group .input-group-text {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
        }

        .gs-form .input-group .form-control {
            border-left: none;
            border-right: none;
        }

        .gs-form .input-group .form-control:first-child {
            border-left: 1px solid rgba(255,255,255,0.12);
            border-right: none;
        }

        .gs-form .input-group .form-control:last-child {
            border-right: 1px solid rgba(255,255,255,0.12);
        }

        /* Checkboxes */
        .gs-check {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            color: #e2e8f0;
            padding: 9px 12px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            min-height: 42px;
            transition: border-color 0.2s, background 0.2s;
        }

        .gs-check:hover {
            border-color: rgba(251,191,36,0.3);
            background: rgba(251,191,36,0.05);
        }

        .gs-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #fbbf24;
            cursor: pointer;
        }

        /* Sticky actions */
        .gs-sticky-actions {
            position: sticky;
            bottom: 0;
            z-index: 20;
            margin-top: 16px;
            padding: 10px 0;
            border-top: 1px solid rgba(255,255,255,0.08);
            background: rgba(15,17,26,0.92);
            backdrop-filter: blur(12px);
            display: flex;
            gap: 8px;
        }

        .gs-btn-save {
            padding: 9px 24px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border: none;
            border-radius: 8px;
            color: #000;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .gs-btn-save:hover { opacity: 0.88; }

        .gs-btn-back {
            padding: 9px 20px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 0.95rem;
            text-decoration: none;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
        }

        .gs-btn-back:hover { background: rgba(255,255,255,0.1); color: #fff; }

        .gs-btn-delete {
            padding: 9px 20px;
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.35);
            border-radius: 8px;
            color: #ef4444;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .gs-btn-delete:hover { background: rgba(239,68,68,0.22); }

        /* Alert */
        .gs-alert-success {
            padding: 8px 14px;
            background: rgba(34,197,94,0.1);
            border: 1px solid rgba(34,197,94,0.3);
            border-radius: 7px;
            color: #22c55e;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .gs-alert-danger {
            padding: 8px 14px;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 7px;
            color: #ef4444;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
    </style>

    @if(session('success'))
    <div class="gs-alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="gs-alert-danger">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    @php
        $selectedTop = (string)($catalogSelectedTop ?? ($comp->idglava ?? ''));
        $selectedSub = (string)($catalogSelectedSub ?? ($comp->idcaption ?? ''));
        $availableSubs = collect($catalogAvailableSubs ?? ($subs[$selectedTop] ?? []));
    @endphp

    <form method="POST" action="{{ route('goods.save') }}" enctype="multipart/form-data" class="gs-form">
        @csrf
        <input type="hidden" name="id1" value="{{ $pnum ?? '0' }}">

        {{-- Категорії --}}
        <div class="gs-section">
            <div class="gs-section-title">Категорія</div>
            <div class="gs-row">
                <div class="gs-col">
                    <label>Категорія</label>
                    <select name="idglava" id="goodsShowTopSelect" class="form-select" onchange="goodsShowFillSubs(this.value)">
                        <option value="">— Оберіть категорію —</option>
                        @foreach(($tops ?? []) as $top)
                        <option value="{{ $top->id }}" {{ $selectedTop === (string)$top->id ? 'selected' : '' }}>
                            {{ $top->val }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="gs-col">
                    <label>Підкатегорія</label>
                    <select name="idcaption" id="goodsShowSubSelect" class="form-select" {{ $selectedTop === '' ? 'disabled' : '' }}>
                        <option value="">— Оберіть підкатегорію —</option>
                        @foreach($availableSubs as $sub)
                        <option value="{{ $sub->id }}" {{ $selectedSub === (string)$sub->id ? 'selected' : '' }}>
                            {{ $sub->val }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Фільтри каталогу (filter) → comp.htmlkeyspop: id_групи:id_значення, --}}
        <div class="gs-section" id="goods-catalog-filters-section">
            <div class="gs-section-title">Фільтри каталогу</div>
            <p class="small text-muted mb-2" style="color: rgba(255,255,255,0.45) !important;">
                Оберіть підкатегорію — з’являться групи з таблиці <code>filter</code> для цієї категорії та батьківської. У полі нижче зберігається рядок на кшталт <code>729:730,761:762,</code> (ключ — id групи, значення — id опції).
            </p>
            <div id="goods-catalog-filters-ui" class="gs-row" style="margin-bottom: 10px;"></div>
            <p class="small text-muted mb-1" id="goods-catalog-filters-hint" style="display:none;color: rgba(251,191,36,0.65) !important;"></p>
            <div class="gs-row">
                <div class="gs-col-3">
                    <label>htmlkeyspop (автозаповнення)</label>
                    <textarea name="htmlkeyspop" id="goods-htmlkeyspop" class="form-control" rows="2"
                        placeholder="729:730,761:762,">{{ $comp->htmlkeyspop ?? '' }}</textarea>
                </div>
            </div>
        </div>

        {{-- Назви --}}
        <div class="gs-section">
            <div class="gs-section-title">Назва</div>
            <div class="gs-row">
                <div class="gs-col">
                    <label>Назва RU</label>
                    <input type="text" name="name_client_ru" class="form-control" value="{{ $descript->name ?? '' }}">
                </div>
                <div class="gs-col">
                    <label>Назва UA</label>
                    <input type="text" name="name_client_ua" class="form-control" value="{{ $descript->name_ua ?? '' }}">
                </div>
                <div class="gs-col">
                    <label>Назва EN</label>
                    <input type="text" name="name_client_en" class="form-control" value="{{ $descript->name_en ?? '' }}">
                </div>
            </div>
        </div>

        {{-- Коди --}}
        <div class="gs-section">
            <div class="gs-section-title">Коди та документ</div>
            <div class="gs-row">
                <div class="gs-col">
                    <label>Код</label>
                    <input type="text" name="nickname" class="form-control" value="{{ $comp->nickname ?? '' }}">
                </div>
                <div class="gs-col">
                    <label>Артикул / Cod</label>
                    <input type="text" class="form-control" value="{{ $comp->cod ?? '' }}" readonly>
                </div>
                <div class="gs-col">
                    <label>Назва документа</label>
                    <input type="text" name="name_doc" class="form-control" value="{{ $comp->namedoc ?? '' }}">
                </div>
            </div>
        </div>

        {{-- Ціни основні --}}
        <div class="gs-section">
            <div class="gs-section-title">Ціни та гарантія</div>
            <div class="gs-row">
                <div class="gs-col">
                    <label>Ціна закупки</label>
                    <input type="text" step="0.01" id="pay1Input" name="pay1" class="form-control" value="{{ $comp->pay1 ?? 0 }}">
                </div>
                <div class="gs-col">
                    <label>Ціна продажу</label>
                    <input type="text" step="0.01" id="payInput" name="pay" class="form-control" value="{{ $comp->pay ?? 0 }}">
                </div>
                <div class="gs-col">
                    <label>Маржа / Profit</label>
                    <input type="text" step="0.01" id="profitpayInput" name="profitpay" class="form-control profit-field" value="{{ $comp->profitpay ?? 0 }}" readonly>
                </div>
                <div class="gs-col">
                    <label>Гарантія</label>
                    <input type="text" name="garant" class="form-control" value="{{ $comp->garant ?? '' }}">
                </div>
            </div>
        </div>

        {{-- Ціни з таблиці price --}}
        <div class="gs-section">
            <div class="gs-section-title">Ціни продажу з таблиці `price`</div>
            <div class="table-responsive">
                <table class="gs-price-table">
                    <thead>
                        <tr>
                            <th>Група</th>
                            <th>Ціна продажу (pay)</th>
                            <th>Ціна зі знижкою (pay1)</th>
                            <th>Стара ціна (oldpay)</th>
                            <th>Умова знижки</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($priceGroups ?? []) as $group)
                        @php $row = $prices[$group->id] ?? null; @endphp
                        <tr>
                            <td>
                                {{ $group->name ?? ('#' . $group->id) }}
                                <input type="hidden" name="tgroup[{{ $group->id }}]" value="1">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="tpay[{{ $group->id }}]" class="form-control"
                                    value="{{ $row !== null ? $row->pay : 0 }}">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="tpay1[{{ $group->id }}]" class="form-control"
                                    value="{{ $row !== null ? $row->pay1 : 0 }}">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="toldpay[{{ $group->id }}]" class="form-control"
                                    value="{{ $row !== null ? $row->oldpay : 0 }}">
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">від</span>
                                    <input type="number" step="0.001" name="tcount[{{ $group->id }}]" class="form-control"
                                        value="{{ $row !== null ? $row->count : 0 }}">
                                    <span class="input-group-text">шт.</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Склад та флаги --}}
        <div class="gs-section">
            <div class="gs-section-title">Склад та атрибути</div>
            <div class="gs-row">
                <div class="gs-col">
                    <input type="checkbox" name="hit" value="1" id="hit" {{ (string)($comp->hit ?? '0') === '1' ? 'checked' : '' }}>
                    <label>Hit</label>
                    
                </div>
                <div class="gs-col">
                    <label>Склад</label>
                    <select name="sklad" class="form-select">
                        <option value="1" {{ (string)($comp->sklad ?? '0') === '1' ? 'selected' : '' }}>Є в наявності</option>
                        <option value="0" {{ (string)($comp->sklad ?? '0') === '0' ? 'selected' : '' }}>Немає</option>
                    </select>
                </div>
                <div class="gs-col" style="display:flex;align-items:flex-end;">
                    <label class="gs-check" style="min-height:42px;width:100%;">
                        <input type="checkbox" name="constanta" value="1" id="constanta" {{ (string)($comp->constanta ?? '0') === '1' ? 'checked' : '' }}>
                        На маркетплейс
                    </label>
                </div>
                <div class="gs-col" style="display:flex;align-items:flex-end;">
                    <label class="gs-check" style="min-height:42px;width:100%;">
                        Top
                        <input type="text" name="top" class="form-control" value="{{ $comp->top ?? 0 }}">
                    </label>
                </div>
            </div>
        </div>

        {{-- Описи --}}
        <div class="gs-section">
            <div class="gs-section-title">Описи</div>
            <div class="gs-row">
                <div class="gs-col">
                    <label>Опис RU</label>
                    <textarea name="description_ru" class="form-control">{{ $descript->description ?? '' }}</textarea>
                </div>
                <div class="gs-col">
                    <label>Опис UA</label>
                    <textarea name="description_ua" class="form-control">{{ $descript->description_ua ?? '' }}</textarea>
                </div>
                <div class="gs-col">
                    <label>Опис EN</label>
                    <textarea name="description_en" class="form-control">{{ $descript->description_en ?? '' }}</textarea>
                </div>
            </div>
        </div>

        {{-- HTML / SEO --}}
        <div class="gs-section">
            <div class="gs-section-title">HTML / SEO</div>
            <div class="gs-row">
                <div class="gs-col-2">
                    <label>HTML description</label>
                    <textarea name="htmldescr" class="form-control">{{ $comp->htmldescr ?? '' }}</textarea>
                </div>
                <div class="gs-col">
                    <label>HTML keys</label>
                    <textarea name="htmlkeys" class="form-control">{{ $comp->htmlkeys ?? '' }}</textarea>
                </div>
            </div>
        </div>

        {{-- Відео --}}
        <div class="gs-section">
            <div class="gs-section-title">Відео</div>
            <div class="gs-row">
                <div class="gs-col">
                    <label>Video 1</label>
                    <input type="text" name="video1" class="form-control" value="{{ $comp->nvideo1 ?? '' }}">
                </div>
                <div class="gs-col">
                    <label>Video 2</label>
                    <input type="text" name="video2" class="form-control" value="{{ $comp->nvideo2 ?? '' }}">
                </div>
            </div>
        </div>

        {{-- Фотографії --}}
        @php
            $photoFields = [
                ['column' => 'nfoto',  'input' => 'foto1',  'label' => 'Фото 1'],
                ['column' => 'nfoto1', 'input' => 'foto2',  'label' => 'Фото 2'],
                ['column' => 'nfoto2', 'input' => 'foto3',  'label' => 'Фото 3'],
                ['column' => 'nfoto3', 'input' => 'foto4',  'label' => 'Фото 4'],
                ['column' => 'nfoto4', 'input' => 'foto5',  'label' => 'Фото 5'],
                ['column' => 'nfoto5', 'input' => 'foto6',  'label' => 'Фото 6'],
                ['column' => 'nfoto6', 'input' => 'foto7',  'label' => 'Фото 7'],
                ['column' => 'nfoto7', 'input' => 'foto8',  'label' => 'Фото 8'],
                ['column' => 'nfoto8', 'input' => 'foto9',  'label' => 'Фото 9'],
                ['column' => 'nfoto9', 'input' => 'foto10', 'label' => 'Фото 10'],
            ];
        @endphp

        <div class="gs-section">
            <div class="gs-section-title">Фотографії товару</div>
            <div class="gs-photo-grid">
                @foreach($photoFields as $photo)
                @php $imagePath = \App\Support\MediaUrl::image($comp->{$photo['column']} ?? ''); @endphp
                <div class="gs-photo-card">
                    @if($imagePath)
                        <img src="{{ $imagePath }}" alt="{{ $photo['label'] }}" class="gs-photo-card__img">
                    @else
                        <div class="gs-photo-card__empty">Немає зображення</div>
                    @endif
                    <div class="gs-photo-card__body">
                        <div class="gs-photo-card__meta">{{ $photo['column'] }}</div>
                        <label>{{ $photo['label'] }}</label>
                        <input type="file" name="{{ $photo['input'] }}" class="form-control" accept="image/*">
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="gs-sticky-actions">
            <button type="submit" class="gs-btn-save">💾 Зберегти</button>
            <a href="{{ route('goods.index') }}" class="gs-btn-back">← Назад</a>
            @php $compId = $comp->id ?? null; @endphp
            @if($compId && $pnum && $pnum !== '0')
                <input type="hidden" name="id" value="{{ $compId }}">
                <input type="hidden" name="cod" value="{{ $comp->cod ?? '' }}">
                <button
                    type="submit"
                    class="gs-btn-delete"
                    formaction="{{ route('goods.destroy') }}"
                    formmethod="POST"
                    formnovalidate
                    onclick="return confirm('Ви впевнені, що хочете видалити цей товар?');"
                >🗑 Видалити</button>
            @endif
        </div>
    </form>
</div>

<script>
var goodsShowAllSubs = @json(
    collect($subs ?? [])->map(fn($items) =>
        collect($items)->map(fn($s) => ['id' => $s->id, 'val' => $s->val])->values()
    )
);
var goodsShowLocale = @json($locale ?? 'ru');
var goodsCatalogFilterGroupsUrl = @json(route('goods.catalogFilterGroups'));

function goodsFilterLabel(row) {
    if (!row) return '';
    var loc = (goodsShowLocale || 'ru').toLowerCase();
    if (loc === 'en' && row.valen) return row.valen;
    if (loc === 'ru' && row.valru) return row.valru;
    if ((loc === 'ua' || loc === 'uk') && row.val) return row.val;
    return row.val || row.valru || row.valen || ('#' + row.id);
}

function goodsParseHtmlkeyspop(str) {
    var map = {};
    String(str || '').replace(/\s+/g, '').split(',').forEach(function(seg) {
        if (!seg) return;
        var i = seg.indexOf(':');
        if (i === -1) return;
        var g = seg.slice(0, i);
        var v = seg.slice(i + 1);
        if (/^\d+$/.test(g) && /^\d+$/.test(v)) map[g] = v;
    });
    return map;
}

function goodsSerializeHtmlkeyspop(map) {
    return Object.keys(map).sort(function(a, b) { return Number(a) - Number(b); })
        .map(function(g) { return g + ':' + map[g] + ','; }).join('');
}

function goodsSyncHtmlkeyspopFromUi() {
    var ta = document.getElementById('goods-htmlkeyspop');
    if (!ta) return;
    var map = goodsParseHtmlkeyspop(ta.value);
    document.querySelectorAll('.goods-cat-filter-select').forEach(function(sel) {
        var gid = sel.getAttribute('data-group-id');
        if (!gid) return;
        if (sel.value) map[gid] = sel.value;
        else delete map[gid];
    });
    ta.value = goodsSerializeHtmlkeyspop(map);
}

function goodsApplyMapToFilterSelects(map) {
    document.querySelectorAll('.goods-cat-filter-select').forEach(function(sel) {
        var gid = sel.getAttribute('data-group-id');
        if (!gid || !map[gid]) return;
        var want = String(map[gid]);
        var has = Array.prototype.some.call(sel.options, function(o) { return o.value === want; });
        if (has) sel.value = want;
    });
}

function goodsReloadCatalogFilters() {
    var mount = document.getElementById('goods-catalog-filters-ui');
    var hint = document.getElementById('goods-catalog-filters-hint');
    var ta = document.getElementById('goods-htmlkeyspop');
    if (!mount || !ta) return;

    var topSel = document.getElementById('goodsShowTopSelect');
    var subSel = document.getElementById('goodsShowSubSelect');
    var idglava = topSel && topSel.value ? topSel.value : '';
    var idcaption = subSel && subSel.value ? subSel.value : '';

    var preset = goodsParseHtmlkeyspop(ta.value);

    if (!idcaption && !idglava) {
        mount.innerHTML = '';
        if (hint) { hint.style.display = 'none'; hint.textContent = ''; }
        return;
    }

    mount.innerHTML = '<div class="text-muted small">Завантаження фільтрів…</div>';
    if (hint) { hint.style.display = 'none'; }

    var url = goodsCatalogFilterGroupsUrl
        + '?idcaption=' + encodeURIComponent(idcaption || '0')
        + '&idglava=' + encodeURIComponent(idglava || '0');

    fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
        .then(function(r) {
            return r.json().then(function(data) { return { ok: r.ok, data: data }; });
        })
        .then(function(res) {
            mount.innerHTML = '';
            if (!res.ok) {
                mount.innerHTML = '<div class="text-danger small">' + (res.data && res.data.message ? res.data.message : 'Помилка завантаження') + '</div>';
                return;
            }
            var groups = (res.data && res.data.groups) || [];
            if (!groups.length) {
                mount.innerHTML = '<div class="text-muted small">Для цієї категорії немає налаштованих фільтрів у таблиці filter.</div>';
                if (hint) { hint.style.display = 'block'; hint.textContent = 'Можна залишити лише рядок у htmlkeyspop вручну.'; }
                return;
            }
            groups.forEach(function(block) {
                var g = block.group;
                if (!g) return;
                var vals = block.values || [];
                var wrap = document.createElement('div');
                wrap.className = 'gs-col';
                wrap.style.minWidth = '220px';
                var lab = document.createElement('label');
                lab.textContent = goodsFilterLabel(g);
                var sel = document.createElement('select');
                sel.className = 'form-select goods-cat-filter-select';
                sel.setAttribute('data-group-id', String(g.id));
                var o0 = document.createElement('option');
                o0.value = '';
                o0.textContent = '— Не обрано —';
                sel.appendChild(o0);
                vals.forEach(function(v) {
                    var o = document.createElement('option');
                    o.value = String(v.id);
                    o.textContent = goodsFilterLabel(v);
                    sel.appendChild(o);
                });
                sel.addEventListener('change', goodsSyncHtmlkeyspopFromUi);
                wrap.appendChild(lab);
                wrap.appendChild(sel);
                mount.appendChild(wrap);
            });
            goodsApplyMapToFilterSelects(preset);
            goodsSyncHtmlkeyspopFromUi();
        })
        .catch(function() {
            mount.innerHTML = '<div class="text-danger small">Помилка завантаження фільтрів.</div>';
        });
}

function goodsShowFillSubs(topId) {
    var sub = document.getElementById('goodsShowSubSelect');
    if (!sub) return;
    sub.innerHTML = '';
    var allOpt = document.createElement('option');
    allOpt.value = '';
    allOpt.textContent = '— Оберіть підкатегорію —';
    sub.appendChild(allOpt);
    if (topId && goodsShowAllSubs[topId]) {
        goodsShowAllSubs[topId].forEach(function(item) {
            var opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.val;
            sub.appendChild(opt);
        });
        sub.disabled = false;
    } else {
        sub.disabled = true;
    }
    goodsReloadCatalogFilters();
}

document.addEventListener('DOMContentLoaded', function() {
    const payInput     = document.getElementById('payInput');
    const pay1Input    = document.getElementById('pay1Input');
    const profitInput  = document.getElementById('profitpayInput');
    const subSel = document.getElementById('goodsShowSubSelect');

    const calcMargin = () => {
        const buy  = parseFloat(pay1Input?.value)  || 0;
        const sell = parseFloat(payInput?.value)   || 0;
        if (profitInput) profitInput.value = (sell - buy).toFixed(2);
    };

    payInput?.addEventListener('input',  calcMargin);
    pay1Input?.addEventListener('input', calcMargin);
    calcMargin();

    subSel?.addEventListener('change', goodsReloadCatalogFilters);

    document.getElementById('goods-htmlkeyspop')?.addEventListener('change', function() {
        var map = goodsParseHtmlkeyspop(this.value);
        goodsApplyMapToFilterSelects(map);
    });

    document.querySelector('form.gs-form')?.addEventListener('submit', function() {
        goodsSyncHtmlkeyspopFromUi();
    });

    goodsReloadCatalogFilters();
});
</script>
@endsection
