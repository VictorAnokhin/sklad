@extends('home')

@section('title', 'Баланс')
@section('header_actions')
    @include('partials.report_panel')
@endsection

@section('content')
<div class="container mt-4 reports-page" data-bs-theme="dark">
    @include('reports.period_form', [
        'periodFormAction' => route('reports.balancesheet'),
        'periodResetUrl' => route('reports.balancesheet'),
    ])

    <div class="card shadow-sm mb-4 bg-transparent border-secondary bg-opacity-10">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="mb-1 text-light">Balance Sheet</h3>
                    <div class="text-muted small">Дата зрізу: {{ $monthLabel }}</div>
                </div>
                <div class="text-muted small">Ліквідність, стійкість і структура капіталу</div>
            </div>

            <div class="row g-3">
                <div class="col-md-4"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Активи @include('reports.hint', ['text' => 'Усе, чим володіє бізнес на дату зрізу: товари, гроші, депозити і дебіторська заборгованість.'])</div><div class="fs-4 fw-bold text-primary">{{ number_format((float) $totalAssets, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-4"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Зобов’язання @include('reports.hint', ['text' => 'Борги бізнесу: кредиторська заборгованість, кредити або інше фінансування.'])</div><div class="fs-4 fw-bold text-danger">{{ number_format((float) $totalLiabilities, 2, '.', ' ') }} грн</div></div></div>
                <div class="col-md-4"><div class="rounded border p-3 h-100"><div class="text-muted small mb-1">Капітал @include('reports.hint', ['text' => 'Власний капітал: активи мінус зобов’язання. Показує чисту вартість бізнесу за даними балансу.'])</div><div class="fs-4 fw-bold {{ $equity >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $equity, 2, '.', ' ') }} грн</div></div></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Активи @include('reports.hint', ['text' => 'Ліва частина балансу: ресурси, які належать бізнесу або мають принести гроші.'])</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Товари / запаси @include('reports.hint', ['text' => 'Вартість товарних залишків за методом середньозваженої собівартості.'])</td><td class="text-end fw-semibold">{{ number_format((float) $inventoryValue, 2, '.', ' ') }}</td></tr>
                            <tr><td>Гроші @include('reports.hint', ['text' => 'Поточний залишок у касах або грошових рахунках.'])</td><td class="text-end fw-semibold">{{ number_format((float) $cashBalance, 2, '.', ' ') }}</td></tr>
                            <tr><td>Депозити @include('reports.hint', ['text' => 'Кошти, розміщені у депозитах або депозитних інструментах.'])</td><td class="text-end fw-semibold">{{ number_format((float) $depositBalance, 2, '.', ' ') }}</td></tr>
                            <tr><td>Дебіторка @include('reports.hint', ['text' => 'Сума, яку клієнти або контрагенти мають сплатити бізнесу.'])</td><td class="text-end fw-semibold">{{ number_format((float) $receivables, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Разом активи @include('reports.hint', ['text' => 'Сума всіх активів: запаси + гроші + депозити + дебіторка.'])</strong></td><td class="text-end"><strong>{{ number_format((float) $totalAssets, 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm h-100 bg-transparent border-secondary">
                <div class="card-body">
                    <h4 class="card-title mb-3 text-light">Зобов’язання і капітал @include('reports.hint', ['text' => 'Права частина балансу: за рахунок чого профінансовані активи — борги і власний капітал.'])</h4>
                    <table class="table table-sm table-dark table-hover align-middle mb-0 bg-transparent">
                        <tbody>
                            <tr><td>Кредиторка @include('reports.hint', ['text' => 'Сума, яку бізнес має сплатити постачальникам або іншим контрагентам.'])</td><td class="text-end fw-semibold">{{ number_format((float) $payables, 2, '.', ' ') }}</td></tr>
                            <tr><td>Кредити / фінансування @include('reports.hint', ['text' => 'Залучені кошти: кредити, позики або інше зовнішнє фінансування.'])</td><td class="text-end fw-semibold">{{ number_format((float) $loans, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Разом зобов’язання @include('reports.hint', ['text' => 'Кредиторка плюс кредити/фінансування.'])</strong></td><td class="text-end"><strong>{{ number_format((float) $totalLiabilities, 2, '.', ' ') }}</strong></td></tr>
                            <tr><td>Капітал @include('reports.hint', ['text' => 'Власний капітал бізнесу: активи мінус зобов’язання.'])</td><td class="text-end fw-semibold {{ $equity >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $equity, 2, '.', ' ') }}</td></tr>
                            <tr class="table-light"><td><strong>Разом пасиви @include('reports.hint', ['text' => 'Зобов’язання плюс капітал. У балансі ця сума має дорівнювати активам.'])</strong></td><td class="text-end"><strong>{{ number_format((float) ($totalLiabilities + $equity), 2, '.', ' ') }}</strong></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
