@extends('home')

@section('title')
Параметры акций
@endsection

@section('content')
@php
    $parameters = ($parameters ?? collect())->sortBy([
        ['sort_order', 'asc'],
        ['id', 'asc'],
    ])->values();
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
            <div class="bank-meta">Список полей snapshot из вкладки «Параметры» на странице акции. Мультипликаторы настраиваются во вкладке «Анализ».</div>
        </div>
        <a class="btn btn-sm btn-outline-light" href="{{ route('bank.stock-analysis') }}">Назад к акциям</a>
    </section>

    <section class="bank-stock-parameters-panel">
        <div class="bank-stock-parameters-panel__head">
            <div>
                <div class="bank-label">Snapshot</div>
                <h2>Настройка параметров</h2>
            </div>
            <span>{{ $parameters->count() }}</span>
        </div>

        <div class="bank-stock-parameters-table-wrap">
            <table class="bank-stock-parameters-table">
                <thead>
                    <tr>
                        <th>Параметр</th>
                        <th>Значение</th>
                        <th>Описание</th>
                        <th>Настройка</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parameters as $parameter)
                        <tr>
                            <td>
                                <strong>{{ $parameter->label }}</strong>
                            </td>
                            <td>
                                <code>{{ $parameter->field_key }}</code>
                            </td>
                            <td colspan="3">
                                <form method="POST"
                                      action="{{ route('bank.stock-analysis.parameters.update', $parameter->id) }}"
                                      class="bank-stock-parameter-row-form">
                                    @csrf
                                    @method('PUT')
                                    <textarea name="description" rows="2" placeholder="Описание параметра">{{ $parameter->description }}</textarea>
                                    <textarea name="settings" rows="2" placeholder="Настройка">{{ $parameter->settings }}</textarea>
                                    <button type="submit" class="btn btn-sm btn-primary">Сохранить</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="bank-stock-parameter-empty">Параметры пока не созданы.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
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

    .bank-stock-parameters-table-wrap {
        overflow-x: auto;
    }

    .bank-stock-parameters-table {
        width: 100%;
        border-collapse: collapse;
        color: #e5e7eb;
        font-size: 0.86rem;
    }

    .bank-stock-parameters-table th,
    .bank-stock-parameters-table td {
        padding: 9px 8px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        vertical-align: top;
    }

    .bank-stock-parameters-table th {
        color: rgba(148, 163, 184, 0.92);
        font-size: 0.72rem;
        font-weight: 800;
        text-align: left;
        text-transform: uppercase;
    }

    .bank-stock-parameters-table th:nth-child(1),
    .bank-stock-parameters-table td:nth-child(1) {
        width: 190px;
    }

    .bank-stock-parameters-table th:nth-child(2),
    .bank-stock-parameters-table td:nth-child(2) {
        width: 180px;
    }

    .bank-stock-parameters-table code {
        display: inline-block;
        max-width: 100%;
        padding: 3px 6px;
        border-radius: 6px;
        background: rgba(2, 6, 23, 0.5);
        color: #fbbf24;
        overflow-wrap: anywhere;
    }

    .bank-stock-parameter-row-form {
        display: grid;
        grid-template-columns: minmax(220px, 1fr) minmax(180px, 0.8fr) auto;
        gap: 8px;
        align-items: start;
        margin: 0;
    }

    .bank-stock-parameter-row-form textarea {
        width: 100%;
        min-height: 42px;
        resize: vertical;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(15, 23, 42, 0.82);
        color: #f8fafc;
        font: inherit;
        font-size: 0.84rem;
        padding: 8px 10px;
    }

    .bank-stock-parameter-empty {
        padding: 18px;
        color: rgba(203, 213, 225, 0.74);
        text-align: center;
    }

    @media (max-width: 900px) {
        .bank-stock-parameters-header {
            flex-direction: column;
        }

        .bank-stock-parameter-row-form {
            grid-template-columns: 1fr;
        }

        .bank-stock-parameter-row-form .btn {
            width: 100%;
        }
    }
</style>
@endsection
