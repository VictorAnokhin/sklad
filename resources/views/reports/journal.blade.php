@extends('home')

@section('title', 'Журнал проводок')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.journal'),
        'periodResetUrl' => route('reports.journal'),
        'periodHiddenFields' => ['account_id' => $accountId ?? ''],
    ])

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="{{ route('reports.journal') }}" class="row g-3 align-items-end">
                <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                <input type="hidden" name="date_to" value="{{ $dateTo }}">
                <div class="col-md-8">
                    <label for="account_id" class="form-label">Счет</label>
                    <select name="account_id" id="account_id" class="form-select">
                        <option value="">Все счета</option>
                        @foreach(($accounts ?? collect()) as $account)
                        <option value="{{ $account->id }}" {{ (string) ($accountId ?? '') === (string) $account->id ? 'selected' : '' }}>
                            {{ $account->code }} | {{ $account->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Показати</button>
                    <a href="{{ route('reports.journal') }}" class="btn btn-outline-secondary">Скинути</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1">Журнал проводок</h3>
                    <div class="text-muted small">{{ $periodLabel }}</div>
                </div>
                <div class="small text-muted">
                    Дт: {{ number_format((float) ($totalDebit ?? 0), 2, '.', ' ') }} |
                    Кт: {{ number_format((float) ($totalCredit ?? 0), 2, '.', ' ') }}
                </div>
            </div>

            @if(($rows ?? collect())->isEmpty())
            <div class="text-muted">Проводки за выбранный период не найдены.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Транзакция</th>
                            <th>Счет</th>
                            <th>Описание</th>
                            <th>Reference</th>
                            <th class="text-end">Дт</th>
                            <th class="text-end">Кт</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                        <tr>
                            <td>{{ $row->date }}</td>
                            <td>#{{ $row->transaction_id }}</td>
                            <td>{{ $row->account_code }} | {{ $row->account_name }}</td>
                            <td>{{ $row->description ?: '—' }}</td>
                            <td>{{ trim(($row->reference_type ?: '') . ' ' . ($row->reference_id ?: '')) ?: '—' }}</td>
                            <td class="text-end">{{ number_format((float) $row->debit, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $row->credit, 2, '.', ' ') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
