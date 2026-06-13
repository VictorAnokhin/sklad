@extends('home')

@section('title')
Кассы / Счета
@endsection

@section('content')
<div class="bank-page">
    @include('bank.partials.nav')

    <section class="bank-grid bank-grid--summary">
        @forelse($totalByCurrency as $currency => $total)
            <div class="bank-panel">
                <div class="bank-label">{{ $currency }}</div>
                <div class="bank-value">{{ number_format((float) $total, 2, '.', ' ') }}</div>
                <div class="bank-meta">Суммарный остаток по кассам проекта</div>
            </div>
        @empty
            <div class="bank-panel">
                <div class="bank-label">Кассы</div>
                <div class="bank-value">0</div>
                <div class="bank-meta">Для проекта еще не настроены записи conf type=oplata</div>
            </div>
        @endforelse
    </section>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Кассы и счета</div>
                <div class="bank-meta">Источник данных: conf where type = oplata and firma = {{ $project->id }}</div>
            </div>
            <div class="bank-meta">{{ $cashAccounts->count() }} записей</div>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover table-sm align-middle bank-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Валюта</th>
                        <th class="text-end">Остаток</th>
                        <th>Документы</th>
                        <th>Цвет / адрес</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashAccounts as $account)
                        <tr>
                            <td class="bank-mono">{{ $account->id }}</td>
                            <td>
                                <div class="fw-semibold">{{ $account->label }}</div>
                                <div class="bank-meta">firma {{ $account->firma }}</div>
                            </td>
                            <td><span class="bank-pill">{{ $account->currency }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format((float) $account->balance, 2, '.', ' ') }}</td>
                            <td>{{ $account->doc !== '' ? $account->doc : '—' }}</td>
                            <td class="bank-mono">{{ $account->color !== '' ? $account->color : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Кассы/счета не настроены.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@include('bank.partials.styles')
@endsection
