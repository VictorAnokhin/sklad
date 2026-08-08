@extends('home')

@section('title')
Параметры акций
@endsection

@section('content')
@php
    $blockLabels = [
        'cheapness' => 'Блок 1. Оценка цены',
        'debt' => 'Блок 2. Долги',
        'efficiency' => 'Блок 3. Эффективность',
        'growth' => 'Блок 4. Рост и дивиденды',
    ];
    $multipliers = ($multipliers ?? collect())->sortBy([
        ['sort_order', 'asc'],
        ['id', 'asc'],
    ])->values();
    $groupedMultipliers = collect($blockLabels)->mapWithKeys(fn ($label, $key) => [
        $key => $multipliers->filter(fn ($item) => (string) ($item->block ?? 'cheapness') === $key)->values(),
    ]);
@endphp

<div class="bank-page bank-stock-parameters-page">
    @include('bank.partials.invest_nav')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">Проверьте поля формы и попробуйте снова.</div>
    @endif

    <section class="bank-stock-parameters-header">
        <div>
            <div class="bank-label">Stock Analysis</div>
            <h1>Параметры акций</h1>
            <div class="bank-meta">Значение может быть полем snapshot или формулой: pe, payout, market_cap / sales.</div>
        </div>
        <a class="btn btn-sm btn-outline-light" href="{{ route('bank.stock-analysis') }}">Назад к акциям</a>
    </section>

    <section class="bank-stock-parameters-panel">
        <div class="bank-stock-parameters-panel__head">
            <div>
                <div class="bank-label">Добавить</div>
                <h2>Новый параметр</h2>
            </div>
        </div>
        <form method="POST" action="{{ route('bank.stock-analysis.multipliers.store') }}" class="bank-stock-parameter-form">
            @csrf
            <input type="hidden" name="return_url" value="{{ url()->current() }}">
            <label>
                <span>Название</span>
                <input type="text" name="name" value="{{ old('name') }}" required>
            </label>
            <label>
                <span>Группа</span>
                <select name="block" required>
                    @foreach($blockLabels as $key => $label)
                        <option value="{{ $key }}" @selected(old('block', 'cheapness') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span>Значение</span>
                <input type="text" name="formula" value="{{ old('formula') }}" placeholder="pe или market_cap / sales" required>
            </label>
            <label>
                <span>Порядок</span>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="100000">
            </label>
            <label class="bank-stock-parameter-form__wide">
                <span>Описание</span>
                <textarea name="description" rows="3" placeholder="Что означает параметр и как понимать значения">{{ old('description') }}</textarea>
            </label>
            <label class="bank-stock-parameter-check">
                <input type="checkbox" name="table_visible" value="1" @checked(old('table_visible'))>
                <span>Показывать в таблице акций</span>
            </label>
            <div class="bank-stock-parameter-actions">
                <button type="submit" class="btn btn-primary">Добавить параметр</button>
            </div>
        </form>
    </section>

    @foreach($blockLabels as $blockKey => $blockLabel)
        <section class="bank-stock-parameters-panel">
            <div class="bank-stock-parameters-panel__head">
                <div>
                    <div class="bank-label">Группа</div>
                    <h2>{{ $blockLabel }}</h2>
                </div>
                <span>{{ $groupedMultipliers[$blockKey]->count() }}</span>
            </div>

            <div class="bank-stock-parameter-list">
                @forelse($groupedMultipliers[$blockKey] as $multiplier)
                    <article class="bank-stock-parameter-card">
                        <form method="POST" action="{{ route('bank.stock-analysis.multipliers.update', $multiplier->id) }}" class="bank-stock-parameter-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="return_url" value="{{ url()->current() }}">
                            <label>
                                <span>Название</span>
                                <input type="text" name="name" value="{{ $multiplier->name }}" required>
                            </label>
                            <label>
                                <span>Группа</span>
                                <select name="block" required>
                                    @foreach($blockLabels as $key => $label)
                                        <option value="{{ $key }}" @selected((string) ($multiplier->block ?? 'cheapness') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>
                                <span>Значение</span>
                                <input type="text" name="formula" value="{{ $multiplier->formula }}" required>
                            </label>
                            <label>
                                <span>Порядок</span>
                                <input type="number" name="sort_order" value="{{ (int) ($multiplier->sort_order ?? 0) }}" min="0" max="100000">
                            </label>
                            <label class="bank-stock-parameter-form__wide">
                                <span>Описание</span>
                                <textarea name="description" rows="3">{{ $multiplier->description }}</textarea>
                            </label>
                            <label class="bank-stock-parameter-check">
                                <input type="checkbox" name="table_visible" value="1" @checked((bool) ($multiplier->table_visible ?? false))>
                                <span>Показывать в таблице акций</span>
                            </label>
                            <div class="bank-stock-parameter-actions">
                                <button type="submit" class="btn btn-primary">Сохранить</button>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('bank.stock-analysis.multipliers.destroy', $multiplier->id) }}" class="bank-stock-parameter-delete" onsubmit="return confirm('Удалить параметр {{ $multiplier->name }}?')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="return_url" value="{{ url()->current() }}">
                            <button type="submit" class="btn btn-outline-danger">Удалить</button>
                        </form>
                    </article>
                @empty
                    <div class="bank-stock-parameter-empty">В этой группе пока нет параметров.</div>
                @endforelse
            </div>
        </section>
    @endforeach
</div>

@include('bank.partials.styles')

<style>
    .bank-stock-parameters-page {
        max-width: 1180px;
    }

    .bank-stock-parameters-header,
    .bank-stock-parameters-panel {
        border: 1px solid rgba(148, 163, 184, 0.2);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.76);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
    }

    .bank-stock-parameters-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        padding: 16px;
        margin-bottom: 12px;
    }

    .bank-stock-parameters-header h1,
    .bank-stock-parameters-panel h2 {
        margin: 4px 0;
        color: #f8fafc;
        font-weight: 800;
        line-height: 1.15;
    }

    .bank-stock-parameters-header h1 {
        font-size: 1.55rem;
    }

    .bank-stock-parameters-panel {
        padding: 14px;
        margin-bottom: 12px;
    }

    .bank-stock-parameters-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 12px;
    }

    .bank-stock-parameters-panel__head h2 {
        font-size: 1.05rem;
    }

    .bank-stock-parameters-panel__head > span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(251, 191, 36, 0.14);
        color: #fbbf24;
        font-weight: 800;
    }

    .bank-stock-parameter-list {
        display: grid;
        gap: 10px;
    }

    .bank-stock-parameter-card {
        display: grid;
        gap: 10px;
        padding: 12px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.28);
    }

    .bank-stock-parameter-form {
        display: grid;
        grid-template-columns: minmax(130px, 1fr) minmax(180px, 1.1fr) minmax(180px, 1.1fr) 96px;
        gap: 10px;
        align-items: end;
    }

    .bank-stock-parameter-form label {
        display: grid;
        gap: 5px;
        margin: 0;
        color: rgba(226, 232, 240, 0.86);
        font-size: 0.84rem;
        font-weight: 700;
    }

    .bank-stock-parameter-form label span {
        color: rgba(148, 163, 184, 0.92);
        font-size: 0.72rem;
        text-transform: uppercase;
    }

    .bank-stock-parameter-form input,
    .bank-stock-parameter-form select,
    .bank-stock-parameter-form textarea {
        width: 100%;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.82);
        color: #f8fafc;
        font: inherit;
        font-size: 0.88rem;
        padding: 8px 10px;
    }

    .bank-stock-parameter-form textarea {
        resize: vertical;
    }

    .bank-stock-parameter-form__wide {
        grid-column: 1 / -1;
    }

    .bank-stock-parameter-check {
        display: flex !important;
        flex-direction: row;
        align-items: center;
        gap: 8px !important;
    }

    .bank-stock-parameter-check input {
        width: auto;
    }

    .bank-stock-parameter-check span {
        text-transform: none !important;
    }

    .bank-stock-parameter-actions {
        display: flex;
        justify-content: flex-end;
    }

    .bank-stock-parameter-delete {
        display: flex;
        justify-content: flex-end;
        margin: 0;
    }

    .bank-stock-parameter-empty {
        padding: 18px;
        border: 1px dashed rgba(148, 163, 184, 0.26);
        border-radius: 8px;
        color: rgba(203, 213, 225, 0.74);
        text-align: center;
    }

    @media (max-width: 900px) {
        .bank-stock-parameters-header {
            flex-direction: column;
        }

        .bank-stock-parameter-form {
            grid-template-columns: 1fr;
        }

        .bank-stock-parameter-actions,
        .bank-stock-parameter-delete {
            justify-content: stretch;
        }

        .bank-stock-parameter-actions .btn,
        .bank-stock-parameter-delete .btn {
            width: 100%;
        }
    }
</style>
@endsection
