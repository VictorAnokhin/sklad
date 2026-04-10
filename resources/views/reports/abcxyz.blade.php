@extends('home')

@section('title', 'ABC / XYZ аналіз')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.abcxyz'),
        'periodResetUrl' => route('reports.abcxyz'),
    ])

    <div class="card shadow-sm mb-4 border-dark-subtle">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1">ABC / XYZ аналіз асортименту</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Пріоритетні SKU за виручкою та стабільністю попиту</div>
            </div>

            <div class="row g-3">
                @foreach(['A', 'B', 'C'] as $group)
                <div class="col-md-2">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">ABC {{ $group }}</div>
                        <div class="fs-4 fw-bold text-primary">{{ (int) ($abcSummary[$group] ?? 0) }}</div>
                    </div>
                </div>
                @endforeach
                @foreach(['X', 'Y', 'Z'] as $group)
                <div class="col-md-2">
                    <div class="rounded border p-3 h-100">
                        <div class="text-muted small mb-1">XYZ {{ $group }}</div>
                        <div class="fs-4 fw-bold text-secondary">{{ (int) ($xyzSummary[$group] ?? 0) }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h4 class="card-title mb-0">Матриця управління асортиментом</h4>
                <div class="text-muted small">A = драйвер виручки, X = стабільний попит</div>
            </div>

            @if(($items ?? collect())->isEmpty())
            <div class="text-muted">Даних для аналізу за вибраний період немає.</div>
            @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Товар</th>
                            <th>Код</th>
                            <th class="text-end">Продано</th>
                            <th class="text-end">Виручка</th>
                            <th class="text-end">Частка</th>
                            <th class="text-end">Накопичено</th>
                            <th class="text-end">CV</th>
                            <th class="text-center">ABC</th>
                            <th class="text-center">XYZ</th>
                            <th class="text-center">Клас</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr class="{{ $item->matrix_class === 'CZ' ? 'table-warning' : ($item->matrix_class === 'AX' ? 'table-success' : '') }}">
                            <td>{{ $item->product_name }}</td>
                            <td>{{ $item->pnum }}</td>
                            <td class="text-end">{{ number_format((float) $item->qty_total, 3, '.', ' ') }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $item->revenue_total, 2, '.', ' ') }}</td>
                            <td class="text-end">{{ number_format((float) $item->revenue_share, 1, '.', ' ') }}%</td>
                            <td class="text-end">{{ number_format((float) $item->cumulative_share, 1, '.', ' ') }}%</td>
                            <td class="text-end">{{ number_format((float) $item->cv, 2, '.', ' ') }}</td>
                            <td class="text-center"><span class="badge bg-primary">{{ $item->abc_class }}</span></td>
                            <td class="text-center"><span class="badge bg-secondary">{{ $item->xyz_class }}</span></td>
                            <td class="text-center fw-bold">{{ $item->matrix_class }}</td>
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
