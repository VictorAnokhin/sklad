@extends('home')

@section('title', __('team.payroll_title'))

@section('header_actions')
    @auth
        <a href="{{ route('team') }}" class="btn btn-outline-secondary">{{ __('team.payroll_back_team') }}</a>
    @endauth
@endsection

@section('content')
@php
    $periodFormAction = route('team.report');
    $periodResetUrl = route('team.report');
    $periodResetLabel = __('team.payroll_reset_period');
@endphp

<div class="container mt-4 team-payroll-page" data-bs-theme="dark">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h1 class="h3 text-light mb-1">{{ __('team.payroll_title') }}</h1>
            <p class="text-muted small mb-0">{{ __('team.payroll_subtitle') }}</p>
            <p class="text-muted small mb-0">{{ __('team.payroll_period_label') }}: <strong class="text-light">{{ $periodLabel }}</strong></p>
        </div>
    </div>

    @include('reports.period_form')

    <div class="card shadow-sm mb-4 bg-transparent border-secondary">
        <div class="card-body">
            <h2 class="h5 text-light mb-3">{{ __('team.payroll_ledger_heading') }}</h2>
            <p class="text-muted small">{{ __('team.payroll_zp_hint') }}</p>

            @if($teamMemberCount === 0)
                <div class="alert alert-secondary border-secondary">{{ __('team.payroll_no_team') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="text-center" style="width:3rem">#</th>
                                <th scope="col">{{ __('team.payroll_col_name') }}</th>
                                <th scope="col">{{ __('team.payroll_col_position') }}</th>
                                <th scope="col" class="text-end">{{ __('team.payroll_col_count') }}</th>
                                <th scope="col" class="text-end">{{ __('team.payroll_col_amount') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ledgerRows as $idx => $row)
                                <tr>
                                    <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                    <td class="text-light">{{ $row->full_name }}</td>
                                    <td>{{ $row->position !== '' ? $row->position : '—' }}</td>
                                    <td class="text-end">{{ $row->payment_count }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($row->total_paid, 2, '.', ' ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <th colspan="4" class="text-end">{{ __('team.payroll_total') }}</th>
                                <th class="text-end">{{ number_format($grandTotal, 2, '.', ' ') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">{{ __('team.payroll_currency_note') }}</p>
            @endif
        </div>
    </div>

    @if($teamMemberCount > 0)
        <div class="card shadow-sm mb-5 bg-transparent border-secondary">
            <div class="card-body">
                <h2 class="h5 text-light mb-3">{{ __('team.payroll_detail_heading') }}</h2>
                @if($detailLines->isEmpty())
                    <p class="text-muted mb-0">{{ __('team.payroll_no_lines') }}</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-dark table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('team.payroll_doc_num') }}</th>
                                    <th>{{ __('team.payroll_doc_date') }}</th>
                                    <th>{{ __('team.payroll_doc_employee') }}</th>
                                    <th class="text-end">{{ __('team.payroll_col_amount') }}</th>
                                    <th>{{ __('team.payroll_doc_note') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailLines as $line)
                                    @php
                                        $dateParts = explode('-', (string) $line->data);
                                        $lineYear = $dateParts[2] ?? date('Y');
                                    @endphp
                                    <tr>
                                        <td class="text-light">{{ $line->num }}</td>
                                        <td>{{ $line->data }}</td>
                                        <td>{{ trim($line->employee_name ?? '') ?: '—' }}</td>
                                        <td class="text-end">{{ number_format((float) $line->summa, 2, '.', ' ') }}</td>
                                        <td class="small text-muted">{{ \Illuminate\Support\Str::limit((string) ($line->content ?? ''), 80) }}</td>
                                        <td class="text-nowrap">
                                            <a href="{{ route('document.show', ['doc' => 'ZP', 'doc_id' => $line->id, 'num' => $line->num, 'year' => $lineYear]) }}" class="btn btn-sm btn-outline-warning">{{ __('team.payroll_open_doc') }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
