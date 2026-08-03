@extends('home')

@section('title', 'Утилиты')

@section('content')
<div class="education-utilities-page">
    <div class="education-utilities-card">
        <div>
            <div class="text-secondary small mb-1">{{ $project->name }}</div>
            <h2 class="h4 mb-2">Финансовые инструменты обучения</h2>
            <p class="text-secondary mb-0">
                Расчеты для оценки инвестиционных проектов: NPV, IRR, срок окупаемости, PI и точка безубыточности.
            </p>
        </div>
        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#capitalEfficiencyModal">
            Оценка эффективности капиталовложений
        </button>
    </div>
</div>

<div class="modal fade" id="capitalEfficiencyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title fs-5">Оценка эффективности капиталовложений</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <form id="capitalEfficiencyForm" class="capital-efficiency-form">
                    <div class="capital-efficiency-grid">
                        <section class="capital-efficiency-section">
                            <h3>Инвестиционная модель</h3>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="initialInvestment">Первоначальные инвестиции, EUR</label>
                                    <input class="form-control" id="initialInvestment" type="number" min="0" step="1000" value="45000000">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="discountRate">Ставка дисконтирования, % годовых</label>
                                    <input class="form-control" id="discountRate" type="number" min="0" step="0.01" value="14">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Чистые денежные потоки, EUR</label>
                                <div class="capital-flow-grid">
                                    <label>Год 1<input class="form-control cash-flow-input" type="number" step="1000" value="14000000"></label>
                                    <label>Год 2<input class="form-control cash-flow-input" type="number" step="1000" value="16000000"></label>
                                    <label>Год 3<input class="form-control cash-flow-input" type="number" step="1000" value="18000000"></label>
                                    <label>Год 4<input class="form-control cash-flow-input" type="number" step="1000" value="15000000"></label>
                                    <label>Год 5<input class="form-control cash-flow-input" type="number" step="1000" value="12000000"></label>
                                </div>
                            </div>
                        </section>

                        <section class="capital-efficiency-section">
                            <h3>Операционная модель</h3>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="fixedCosts">Постоянные расходы в месяц, EUR</label>
                                    <input class="form-control" id="fixedCosts" type="number" min="0" step="1000" value="9000000">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="currentVolume">Текущий объем, упаковок/мес.</label>
                                    <input class="form-control" id="currentVolume" type="number" min="0" step="100" value="75000">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="unitPrice">Цена обработки упаковки, EUR</label>
                                    <input class="form-control" id="unitPrice" type="number" min="0" step="0.01" value="300">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="unitVariableCost">Переменные расходы на упаковку, EUR</label>
                                    <input class="form-control" id="unitVariableCost" type="number" min="0" step="0.01" value="100">
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="capital-efficiency-actions">
                        <button type="button" class="btn btn-warning" id="calculateCapitalEfficiency">Рассчитать</button>
                        <button type="reset" class="btn btn-outline-secondary">Сбросить</button>
                    </div>
                </form>

                <div class="capital-results" id="capitalResults" aria-live="polite">
                    <div class="capital-result-card">
                        <span>NPV</span>
                        <strong id="resultNpv">—</strong>
                        <small>Чистая приведенная стоимость</small>
                    </div>
                    <div class="capital-result-card">
                        <span>IRR</span>
                        <strong id="resultIrr">—</strong>
                        <small>Внутренняя норма доходности</small>
                    </div>
                    <div class="capital-result-card">
                        <span>PP</span>
                        <strong id="resultPayback">—</strong>
                        <small>Срок окупаемости</small>
                    </div>
                    <div class="capital-result-card">
                        <span>PI</span>
                        <strong id="resultPi">—</strong>
                        <small>Индекс рентабельности</small>
                    </div>
                    <div class="capital-result-card">
                        <span>BEP</span>
                        <strong id="resultBep">—</strong>
                        <small>Точка безубыточности</small>
                    </div>
                    <div class="capital-result-card">
                        <span>Запас прочности</span>
                        <strong id="resultSafetyMargin">—</strong>
                        <small>Устойчивость к снижению объема</small>
                    </div>
                </div>

                <div class="capital-explanation" id="capitalExplanation"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<style>
    .education-utilities-page {
        padding-bottom: 32px;
    }

    .education-utilities-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        padding: 22px;
        border: 1px solid rgba(148, 163, 184, .32);
        border-radius: 14px;
        background: #111827;
    }

    .capital-efficiency-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .capital-efficiency-section,
    .capital-result-card,
    .capital-explanation {
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 12px;
        background: rgba(15, 23, 42, .72);
    }

    .capital-efficiency-section {
        padding: 16px;
    }

    .capital-efficiency-section h3 {
        margin: 0 0 14px;
        font-size: 1rem;
        color: #ffc107;
    }

    .capital-flow-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
    }

    .capital-flow-grid label {
        color: #cbd5e1;
        font-size: .84rem;
    }

    .capital-efficiency-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 16px;
    }

    .capital-results {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .capital-result-card {
        padding: 14px;
    }

    .capital-result-card span,
    .capital-result-card small {
        display: block;
        color: #94a3b8;
    }

    .capital-result-card strong {
        display: block;
        margin: 6px 0 4px;
        color: #fff;
        font-size: 1.2rem;
    }

    .capital-explanation {
        margin-top: 18px;
        padding: 16px;
        color: #cbd5e1;
    }

    .capital-explanation p {
        margin-bottom: 8px;
    }

    @media (max-width: 991.98px) {
        .education-utilities-card,
        .capital-efficiency-grid,
        .capital-results {
            grid-template-columns: 1fr;
        }

        .education-utilities-card {
            display: grid;
        }

        .capital-flow-grid {
            grid-template-columns: 1fr;
        }

        .capital-efficiency-actions {
            display: grid;
            grid-template-columns: 1fr;
        }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('capitalEfficiencyForm');
    const calculateButton = document.getElementById('calculateCapitalEfficiency');
    const formatter = new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    });

    function numberValue(id) {
        return Number(document.getElementById(id).value || 0);
    }

    function cashFlows() {
        return Array.from(document.querySelectorAll('.cash-flow-input')).map((input) => Number(input.value || 0));
    }

    function npv(rate, investment, flows) {
        return flows.reduce((sum, flow, index) => sum + flow / Math.pow(1 + rate, index + 1), 0) - investment;
    }

    function discountedIncome(rate, flows) {
        return flows.reduce((sum, flow, index) => sum + flow / Math.pow(1 + rate, index + 1), 0);
    }

    function irr(investment, flows) {
        let low = -0.99;
        let high = 10;
        let lowValue = npv(low, investment, flows);
        let highValue = npv(high, investment, flows);

        if (lowValue * highValue > 0) return null;

        for (let i = 0; i < 100; i += 1) {
            const mid = (low + high) / 2;
            const midValue = npv(mid, investment, flows);
            if (Math.abs(midValue) < 0.01) return mid;
            if (lowValue * midValue < 0) {
                high = mid;
                highValue = midValue;
            } else {
                low = mid;
                lowValue = midValue;
            }
        }

        return (low + high) / 2;
    }

    function payback(investment, flows) {
        let cumulative = -investment;
        for (let index = 0; index < flows.length; index += 1) {
            const previous = cumulative;
            cumulative += flows[index];
            if (cumulative >= 0) {
                const months = flows[index] > 0 ? Math.ceil(Math.abs(previous) / flows[index] * 12) : 0;
                return { years: index, months };
            }
        }
        return null;
    }

    function render() {
        const investment = numberValue('initialInvestment');
        const rate = numberValue('discountRate') / 100;
        const flows = cashFlows();
        const fixedCosts = numberValue('fixedCosts');
        const currentVolume = numberValue('currentVolume');
        const unitPrice = numberValue('unitPrice');
        const unitVariableCost = numberValue('unitVariableCost');
        const discounted = discountedIncome(rate, flows);
        const npvValue = discounted - investment;
        const irrValue = irr(investment, flows);
        const paybackValue = payback(investment, flows);
        const profitabilityIndex = investment > 0 ? discounted / investment : 0;
        const contributionMargin = unitPrice - unitVariableCost;
        const bepUnits = contributionMargin > 0 ? fixedCosts / contributionMargin : null;
        const safetyMargin = bepUnits !== null && currentVolume > 0
            ? (currentVolume - bepUnits) / currentVolume * 100
            : null;

        document.getElementById('resultNpv').textContent = formatter.format(npvValue);
        document.getElementById('resultIrr').textContent = irrValue === null ? '—' : `${(irrValue * 100).toFixed(2)}%`;
        document.getElementById('resultPayback').textContent = paybackValue
            ? `${paybackValue.years} г. ${paybackValue.months} мес.`
            : 'не окупается';
        document.getElementById('resultPi').textContent = profitabilityIndex.toFixed(3);
        document.getElementById('resultBep').textContent = bepUnits === null
            ? '—'
            : `${Math.ceil(bepUnits).toLocaleString('ru-RU')} уп./мес.`;
        document.getElementById('resultSafetyMargin').textContent = safetyMargin === null
            ? '—'
            : `${safetyMargin.toFixed(1)}%`;

        const verdict = npvValue > 0
            ? 'NPV положительная: проект добавляет бизнесу расчетную стоимость.'
            : 'NPV отрицательная: проект не покрывает требуемую доходность при заданной ставке.';

        document.getElementById('capitalExplanation').innerHTML = `
            <p><strong>Дисконтированный доход:</strong> ${formatter.format(discounted)}.</p>
            <p><strong>NPV:</strong> ${formatter.format(discounted)} - ${formatter.format(investment)} = ${formatter.format(npvValue)}.</p>
            <p><strong>PI:</strong> на каждый вложенный евро проект возвращает ${profitabilityIndex.toFixed(2)} евро дисконтированной отдачи.</p>
            <p><strong>BEP:</strong> постоянные расходы ${formatter.format(fixedCosts)} / маржа ${formatter.format(contributionMargin)} = ${bepUnits === null ? '—' : Math.ceil(bepUnits).toLocaleString('ru-RU')} упаковок в месяц.</p>
            <p>${verdict}</p>
        `;
    }

    calculateButton.addEventListener('click', render);
    form.addEventListener('reset', () => setTimeout(render, 0));
    render();
});
</script>
@endpush
