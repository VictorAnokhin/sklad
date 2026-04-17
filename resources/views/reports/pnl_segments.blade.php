@extends('home')

@section('title', 'P&L по сегментам')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.pnlsegments'),
        'periodResetUrl' => route('reports.pnlsegments'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Управлінський P&amp;L по сегментах</h3>
                    <div class="text-muted small">Період: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Категорії, канали продажів та регіони</div>
            </div>

            <div class="row g-3">
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Виручка</div><div class="fs-4 fw-bold text-primary">{{ number_format((float) $revenueTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Собівартість</div><div class="fs-4 fw-bold text-light">{{ number_format((float) $costTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Валова прибуток</div><div class="fs-4 fw-bold {{ $grossProfitTotal >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $grossProfitTotal, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-3"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Маржа</div><div class="fs-4 fw-bold {{ $grossMarginTotal >= 0 ? 'text-warning' : 'text-danger' }}">{{ number_format((float) $grossMarginTotal, 1, '.', ' ') }}%</div></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">По категоріях</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark"><tr><th>Категорія</th><th class="text-end">Виручка</th><th class="text-end">Прибуток</th><th class="text-end">Маржа</th></tr></thead>
                            <tbody>
                                @forelse($byCategory as $item)
                                <tr>
                                    <td>{{ $item->segment_name }}</td>
                                    <td class="text-end">{{ number_format((float) $item->revenue, 2, '.', ' ') }}</td>
                                    <td class="text-end {{ $item->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $item->gross_profit, 2, '.', ' ') }}</td>
                                    <td class="text-end">{{ number_format((float) $item->gross_margin, 1, '.', ' ') }}%</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted">Немає даних.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">По каналах</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark"><tr><th>Канал</th><th class="text-end">Виручка</th><th class="text-end">Прибуток</th><th class="text-end">Маржа</th></tr></thead>
                            <tbody>
                                @forelse($byChannel as $item)
                                <tr>
                                    <td>{{ $item->segment_name }}</td>
                                    <td class="text-end">{{ number_format((float) $item->revenue, 2, '.', ' ') }}</td>
                                    <td class="text-end {{ $item->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $item->gross_profit, 2, '.', ' ') }}</td>
                                    <td class="text-end">{{ number_format((float) $item->gross_margin, 1, '.', ' ') }}%</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted">Немає даних.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">По регіонах</h4>
                    <div class="table-responsive">
                        <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                            <thead class="table-dark"><tr><th>Регіон</th><th class="text-end">Виручка</th><th class="text-end">Прибуток</th><th class="text-end">Маржа</th></tr></thead>
                            <tbody>
                                @forelse($byRegion as $item)
                                <tr>
                                    <td>{{ $item->segment_name }}</td>
                                    <td class="text-end">{{ number_format((float) $item->revenue, 2, '.', ' ') }}</td>
                                    <td class="text-end {{ $item->gross_profit >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $item->gross_profit, 2, '.', ' ') }}</td>
                                    <td class="text-end">{{ number_format((float) $item->gross_margin, 1, '.', ' ') }}%</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-muted">Немає даних.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
