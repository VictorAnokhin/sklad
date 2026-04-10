@extends('home')

@section('title')
{{ $pnum && $pnum !== '0' ? 'Редагування товару' : 'Новий товар' }}
@endsection

@section('content')
<div class="container mt-4">
    <style>
        .goods-sticky-actions {
            position: sticky;
            bottom: 0;
            z-index: 20;
            margin-top: 24px;
            padding: 12px 16px;
            border-top: 1px solid #dee2e6;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(8px);
            box-shadow: 0 -8px 20px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 768px) {
            .goods-sticky-actions {
                left: 0;
                right: 0;
                margin-left: -12px;
                margin-right: -12px;
                padding-left: 12px;
                padding-right: 12px;
            }
        }
    </style>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $e)
        <div>{{ $e }}</div>
        @endforeach
    </div>
    @endif

    @php
        $selectedTop = (string)($comp->idglava ?? '');
        $selectedSub = (string)($comp->idcaption ?? '');
        $availableSubs = collect($subs[$selectedTop] ?? []);
    @endphp

    <form method="POST" action="{{ route('goods.save') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="id1" value="{{ $pnum ?? '0' }}">

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Категорія</label>
                <select name="idglava" class="form-select" onchange="const sub=this.form.querySelector('[name=idcaption]'); if(sub){sub.value='';} this.form.submit();">
                    <option value="">— Оберіть категорію —</option>
                    @foreach(($tops ?? []) as $top)
                    <option value="{{ $top->id }}" {{ $selectedTop === (string)$top->id ? 'selected' : '' }}>
                        {{ $top->val }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Підкатегорія</label>
                <select name="idcaption" class="form-select" {{ $selectedTop === '' ? 'disabled' : '' }}>
                    <option value="">— Оберіть підкатегорію —</option>
                    @foreach($availableSubs as $sub)
                    <option value="{{ $sub->id }}" {{ $selectedSub === (string)$sub->id ? 'selected' : '' }}>
                        {{ $sub->val }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Назва RU</label>
                <input type="text" name="name_client_ru" class="form-control" value="{{ $descript->name ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Назва UA</label>
                <input type="text" name="name_client_ua" class="form-control" value="{{ $descript->name_ua ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Назва EN</label>
                <input type="text" name="name_client_en" class="form-control" value="{{ $descript->name_en ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Код</label>
                <input type="text" name="nickname" class="form-control" value="{{ $comp->nickname ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Артикул / Cod</label>
                <input type="text" class="form-control" value="{{ $comp->cod ?? '' }}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Назва документа</label>
                <input type="text" name="name_doc" class="form-control" value="{{ $comp->namedoc ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Ціна закупки</label>
                <input type="text" step="0.01" id="pay1Input" name="pay1" class="form-control" value="{{ $comp->pay1 ?? 0 }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Ціна продажи</label>
                <input type="text" step="0.01" id="payInput" name="pay" class="form-control" value="{{ $comp->pay ?? 0 }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Маржа / Profit</label>
                <input type="text" step="0.01" id="profitpayInput" name="profitpay" class="form-control" value="{{ $comp->profitpay ?? 0 }}" readonly style="background:#f0f7ff; font-weight:700;">
            </div>
            <div class="col-md-3">
                <label class="form-label">Гарантія</label>
                <input type="text" name="garant" class="form-control" value="{{ $comp->garant ?? '' }}">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label d-block">Ціни продажу з таблиці `price`</label>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Група</th>
                            <th>Ціна продажу (`pay`)</th>
                            <th>Ціна зі знижкою (`pay1`)</th>
                            <th>Стара ціна (`oldpay`)</th>
                            <th>Умова знижки</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($priceGroups ?? []) as $group)
                        @php
                            $row = $prices[$group->id] ?? null;
                        @endphp
                        <tr>
                            <td>
                                {{ $group->name ?? ('#' . $group->id) }}
                                <input type="hidden" name="tgroup[{{ $group->id }}]" value="1">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="tpay[{{ $group->id }}]" class="form-control form-control-sm"
                                    value="{{ $row->pay ?? $comp->pay ?? 0 }}">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="tpay1[{{ $group->id }}]" class="form-control form-control-sm"
                                    value="{{ $row->pay1 ?? $comp->pay1 ?? 0 }}">
                            </td>
                            <td>
                                <input type="number" step="0.01" name="toldpay[{{ $group->id }}]" class="form-control form-control-sm"
                                    value="{{ $row->oldpay ?? 0 }}">
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">від</span>
                                    <input type="number" step="0.001" name="tcount[{{ $group->id }}]" class="form-control"
                                        value="{{ $row->count ?? 0 }}">
                                    <span class="input-group-text">шт.</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Hit</label>
                <input type="number" name="hit" class="form-control" value="{{ $comp->hit ?? 0 }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Склад</label>
                <select name="sklad" class="form-select">
                    <option value="1" {{ (string)($comp->sklad ?? '0') === '1' ? 'selected' : '' }}>Є в наявності</option>
                    <option value="0" {{ (string)($comp->sklad ?? '0') === '0' ? 'selected' : '' }}>Немає</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="constanta" value="1" id="constanta" {{ (string)($comp->constanta ?? '0') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="constanta">Constanta</label>
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="top" value="1" id="top" {{ (string)($comp->top ?? '0') === '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="top">Top</label>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Опис RU</label>
                <textarea name="description_ru" class="form-control" rows="4">{{ $descript->description ?? '' }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Опис UA</label>
                <textarea name="description_ua" class="form-control" rows="4">{{ $descript->description_ua ?? '' }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Опис EN</label>
                <textarea name="description_en" class="form-control" rows="4">{{ $descript->description_en ?? '' }}</textarea>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">HTML description</label>
                <textarea name="htmldescr" class="form-control" rows="3">{{ $comp->htmldescr ?? '' }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">HTML keys</label>
                <textarea name="htmlkeys" class="form-control" rows="3">{{ $comp->htmlkeys ?? '' }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">HTML keys pop</label>
                <textarea name="htmlkeyspop" class="form-control" rows="3">{{ $comp->htmlkeyspop ?? '' }}</textarea>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Video 1</label>
                <input type="text" name="video1" class="form-control" value="{{ $comp->nvideo1 ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Video 2</label>
                <input type="text" name="video2" class="form-control" value="{{ $comp->nvideo2 ?? '' }}">
            </div>
        </div>

        @php
            $photoFields = [
                ['column' => 'nfoto', 'input' => 'foto1', 'label' => 'Фото 1'],
                ['column' => 'nfoto1', 'input' => 'foto2', 'label' => 'Фото 2'],
                ['column' => 'nfoto2', 'input' => 'foto3', 'label' => 'Фото 3'],
                ['column' => 'nfoto3', 'input' => 'foto4', 'label' => 'Фото 4'],
                ['column' => 'nfoto4', 'input' => 'foto5', 'label' => 'Фото 5'],
                ['column' => 'nfoto5', 'input' => 'foto6', 'label' => 'Фото 6'],
                ['column' => 'nfoto6', 'input' => 'foto7', 'label' => 'Фото 7'],
                ['column' => 'nfoto7', 'input' => 'foto8', 'label' => 'Фото 8'],
                ['column' => 'nfoto8', 'input' => 'foto9', 'label' => 'Фото 9'],
                ['column' => 'nfoto9', 'input' => 'foto10', 'label' => 'Фото 10'],
            ];
        @endphp

        <div class="mb-4">
            <label class="form-label d-block">Фотографії товару</label>
            <div class="row g-3">
                @foreach($photoFields as $photo)
                @php
                    $imagePath = $comp->{$photo['column']} ?? '';
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                        @if($imagePath)
                        <img src="{{ $imagePath }}" alt="{{ $photo['label'] }}" class="card-img-top" style="height: 180px; object-fit: cover;">
                        @else
                        <div class="d-flex align-items-center justify-content-center text-muted" style="height: 180px; background: #f8f9fa;">
                            Немає зображення
                        </div>
                        @endif
                        <div class="card-body">
                            <div class="small text-muted mb-2">{{ $photo['column'] }}</div>
                            <label class="form-label">{{ $photo['label'] }}</label>
                            <input type="file" name="{{ $photo['input'] }}" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="d-flex gap-2 goods-sticky-actions">
            <button type="submit" class="btn btn-success">💾 Зберегти</button>
            <a href="{{ route('goods.index') }}" class="btn btn-secondary">← Назад</a>
            @if($comp->id && $pnum && $pnum !== '0')
                <input type="hidden" name="id" value="{{ $comp->id }}">
                <input type="hidden" name="cod" value="{{ $comp->cod ?? '' }}">
                <button
                    type="submit"
                    class="btn btn-danger"
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
document.addEventListener('DOMContentLoaded', function() {
    const payInput = document.getElementById('payInput');
    const pay1Input = document.getElementById('pay1Input');
    const profitpayInput = document.getElementById('profitpayInput');

    const calculateMargin = () => {
        const purchasePrice = parseFloat(pay1Input?.value) || 0;  // pay1 = цена закупки
        const salePrice = parseFloat(payInput?.value) || 0;        // pay = цена продажи
        const margin = salePrice - purchasePrice;
        
        if (profitpayInput) {
            profitpayInput.value = margin.toFixed(2);
        }
    };

    if (payInput) {
        payInput.addEventListener('input', calculateMargin);
    }
    if (pay1Input) {
        pay1Input.addEventListener('input', calculateMargin);
    }

    // Calculate on page load
    calculateMargin();
});
</script>
@endsection
