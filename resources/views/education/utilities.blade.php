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
        <div class="education-utilities-actions">
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#capitalEfficiencyModal">
                Оценка эффективности капиталовложений
            </button>
            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#investmentSimulationModal">
                Моделирование инвестиционного вложения
            </button>
        </div>
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
                                    <label class="form-label" for="variableCosts">Переменные расходы, EUR</label>
                                    <input class="form-control" id="variableCosts" type="number" min="0" step="1000" value="7500000">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="totalRevenue">Общий доход, EUR</label>
                                    <input class="form-control" id="totalRevenue" type="number" min="0" step="1000" value="22500000">
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

<div class="modal fade" id="investmentSimulationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title fs-5">Моделирование инвестиционного вложения</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs border-secondary mb-3" id="investment-utility-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active bg-dark text-warning border-secondary" id="investment-calculation-tab"
                                data-bs-toggle="tab" data-bs-target="#investment-calculation-pane" type="button"
                                role="tab" aria-controls="investment-calculation-pane" aria-selected="true">
                            Расчет
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link bg-dark text-light border-secondary" id="investment-settings-tab"
                                data-bs-toggle="tab" data-bs-target="#investment-settings-pane" type="button"
                                role="tab" aria-controls="investment-settings-pane" aria-selected="false">
                            Настройки
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="investment-calculation-pane" role="tabpanel"
                         aria-labelledby="investment-calculation-tab" tabindex="0">
                        <form id="investmentSimulationForm" class="capital-efficiency-form">
                            <section class="capital-efficiency-section">
                                <h3>Параметры вложения</h3>
                                <div class="row g-3">
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="simulationInitialAmount">Стартовая сумма, EUR</label>
                                        <input class="form-control" id="simulationInitialAmount" type="number" min="0" step="100" value="10000">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="simulationAnnualRate">Процент, % годовых</label>
                                        <input class="form-control" id="simulationAnnualRate" type="number" min="0" step="0.01" value="12">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="simulationYears">Срок, лет</label>
                                        <input class="form-control" id="simulationYears" type="number" min="1" max="50" step="1" value="5">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="simulationContribution">Пополнение, EUR</label>
                                        <input class="form-control" id="simulationContribution" type="number" min="0" step="100" value="500">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="simulationContributionFrequency">Частота пополнения</label>
                                        <select class="form-select" id="simulationContributionFrequency">
                                            <option value="monthly" selected>Ежемесячно</option>
                                            <option value="quarterly">Ежеквартально</option>
                                            <option value="yearly">Ежегодно</option>
                                            <option value="none">Без пополнений</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label" for="simulationInterestMode">Начисление процентов</label>
                                        <select class="form-select" id="simulationInterestMode">
                                            <option value="compound" selected>Сложный процент</option>
                                            <option value="simple">Без сложного процента</option>
                                        </select>
                                    </div>
                                </div>
                            </section>

                            <div class="capital-efficiency-actions">
                                <button type="button" class="btn btn-warning" id="calculateInvestmentSimulation">Рассчитать</button>
                                <button type="reset" class="btn btn-outline-secondary">Сбросить</button>
                            </div>
                        </form>

                        <div class="capital-results investment-simulation-summary" aria-live="polite">
                            <div class="capital-result-card">
                                <span>Итоговая сумма</span>
                                <strong id="simulationFinalBalance">—</strong>
                                <small>Капитал на конец срока</small>
                            </div>
                            <div class="capital-result-card">
                                <span>Вложено всего</span>
                                <strong id="simulationTotalInvested">—</strong>
                                <small>Старт + пополнения</small>
                            </div>
                            <div class="capital-result-card">
                                <span>Доход</span>
                                <strong id="simulationTotalInterest">—</strong>
                                <small>Начисленные проценты</small>
                            </div>
                        </div>

                        <div class="investment-simulation-table-wrap">
                            <table class="table table-dark table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Период</th>
                                        <th class="text-end">Начало периода</th>
                                        <th class="text-end">Пополнения</th>
                                        <th class="text-end">Проценты</th>
                                        <th class="text-end">Конец периода</th>
                                    </tr>
                                </thead>
                                <tbody id="investmentSimulationRows"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="investment-settings-pane" role="tabpanel"
                         aria-labelledby="investment-settings-tab" tabindex="0">
                        <section class="capital-efficiency-section">
                            <h3>Настройки доступа</h3>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="investmentUtilityRating">Рейтинг</label>
                                    <input class="form-control" id="investmentUtilityRating" name="rating" type="number" min="0" value="0">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="investmentUtilityCostAv8">Оплата, AV8</label>
                                    <input class="form-control" id="investmentUtilityCostAv8" name="cost_av8" type="number" min="0" step="0.000001" value="0">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Описание</label>
                                <ul class="nav nav-tabs border-secondary mb-2" id="investment-utility-description-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-dark text-light border-secondary" id="investment-utility-description-ua-tab"
                                                data-bs-toggle="tab" data-bs-target="#investment-utility-description-ua-pane" type="button"
                                                role="tab" aria-controls="investment-utility-description-ua-pane" aria-selected="false">
                                            UA
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active bg-dark text-warning border-secondary" id="investment-utility-description-ru-tab"
                                                data-bs-toggle="tab" data-bs-target="#investment-utility-description-ru-pane" type="button"
                                                role="tab" aria-controls="investment-utility-description-ru-pane" aria-selected="true">
                                            RU
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-dark text-light border-secondary" id="investment-utility-description-en-tab"
                                                data-bs-toggle="tab" data-bs-target="#investment-utility-description-en-pane" type="button"
                                                role="tab" aria-controls="investment-utility-description-en-pane" aria-selected="false">
                                            EN
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-dark text-light border-secondary" id="investment-utility-description-es-tab"
                                                data-bs-toggle="tab" data-bs-target="#investment-utility-description-es-pane" type="button"
                                                role="tab" aria-controls="investment-utility-description-es-pane" aria-selected="false">
                                            ES
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link bg-dark text-light border-secondary" id="investment-utility-description-fr-tab"
                                                data-bs-toggle="tab" data-bs-target="#investment-utility-description-fr-pane" type="button"
                                                role="tab" aria-controls="investment-utility-description-fr-pane" aria-selected="false">
                                            FR
                                        </button>
                                    </li>
                                </ul>
                                <div class="tab-content border border-secondary rounded-bottom p-2">
                                    <div class="tab-pane fade" id="investment-utility-description-ua-pane" role="tabpanel"
                                         aria-labelledby="investment-utility-description-ua-tab" tabindex="0">
                                        <textarea class="form-control" id="investmentUtilityDescriptionUa" name="description_translations[ua]" rows="5" style="resize:vertical;">Фінансова модель для розрахунку майбутньої вартості вкладення: стартова сума, строк, відсоток, простий або складний відсоток і регулярні поповнення.</textarea>
                                    </div>
                                    <div class="tab-pane fade show active" id="investment-utility-description-ru-pane" role="tabpanel"
                                         aria-labelledby="investment-utility-description-ru-tab" tabindex="0">
                                        <textarea class="form-control" id="investmentUtilityDescriptionRu" name="description_translations[ru]" rows="5" style="resize:vertical;">Финансовая модель для расчета будущей стоимости вложения: стартовая сумма, срок, процент, простой или сложный процент и регулярные пополнения.</textarea>
                                    </div>
                                    <div class="tab-pane fade" id="investment-utility-description-en-pane" role="tabpanel"
                                         aria-labelledby="investment-utility-description-en-tab" tabindex="0">
                                        <textarea class="form-control" id="investmentUtilityDescriptionEn" name="description_translations[en]" rows="5" style="resize:vertical;">A financial model for estimating the future value of an investment: initial amount, term, annual rate, simple or compound interest, and recurring contributions.</textarea>
                                    </div>
                                    <div class="tab-pane fade" id="investment-utility-description-es-pane" role="tabpanel"
                                         aria-labelledby="investment-utility-description-es-tab" tabindex="0">
                                        <textarea class="form-control" id="investmentUtilityDescriptionEs" name="description_translations[es]" rows="5" style="resize:vertical;">Modelo financiero para estimar el valor futuro de una inversión: importe inicial, plazo, tasa anual, interés simple o compuesto y aportes recurrentes.</textarea>
                                    </div>
                                    <div class="tab-pane fade" id="investment-utility-description-fr-pane" role="tabpanel"
                                         aria-labelledby="investment-utility-description-fr-tab" tabindex="0">
                                        <textarea class="form-control" id="investmentUtilityDescriptionFr" name="description_translations[fr]" rows="5" style="resize:vertical;">Modèle financier pour estimer la valeur future d’un investissement : montant initial, durée, taux annuel, intérêt simple ou composé et versements réguliers.</textarea>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
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

    .education-utilities-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 10px;
    }

    #investment-utility-tabs .nav-link.active,
    #investment-utility-description-tabs .nav-link.active {
        color: #ffc107 !important;
        border-bottom-color: #ffc107 !important;
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

    .investment-simulation-table-wrap {
        margin-top: 18px;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 12px;
        overflow-x: auto;
    }

    .investment-simulation-table-wrap table {
        min-width: 720px;
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

        .education-utilities-actions {
            display: grid;
            justify-content: stretch;
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
        const variableCosts = numberValue('variableCosts');
        const totalRevenue = numberValue('totalRevenue');
        const discounted = discountedIncome(rate, flows);
        const npvValue = discounted - investment;
        const irrValue = irr(investment, flows);
        const paybackValue = payback(investment, flows);
        const profitabilityIndex = investment > 0 ? discounted / investment : 0;
        const contributionMargin = totalRevenue - variableCosts;
        const bepRevenue = contributionMargin > 0 ? (fixedCosts / contributionMargin) * totalRevenue : null;
        const safetyMargin = bepRevenue !== null && totalRevenue > 0
            ? (totalRevenue - bepRevenue) / totalRevenue * 100
            : null;

        document.getElementById('resultNpv').textContent = formatter.format(npvValue);
        document.getElementById('resultIrr').textContent = irrValue === null ? '—' : `${(irrValue * 100).toFixed(2)}%`;
        document.getElementById('resultPayback').textContent = paybackValue
            ? `${paybackValue.years} г. ${paybackValue.months} мес.`
            : 'не окупается';
        document.getElementById('resultPi').textContent = profitabilityIndex.toFixed(3);
        document.getElementById('resultBep').textContent = bepRevenue === null
            ? '—'
            : formatter.format(bepRevenue);
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
            <p><strong>BEP:</strong> ${formatter.format(fixedCosts)} / (${formatter.format(totalRevenue)} - ${formatter.format(variableCosts)}) * ${formatter.format(totalRevenue)} = ${bepRevenue === null ? '—' : formatter.format(bepRevenue)}.</p>
            <p>${verdict}</p>
        `;
    }

    calculateButton.addEventListener('click', render);
    form.addEventListener('reset', () => setTimeout(render, 0));
    render();

    const simulationForm = document.getElementById('investmentSimulationForm');
    const simulationButton = document.getElementById('calculateInvestmentSimulation');
    const simulationRows = document.getElementById('investmentSimulationRows');

    function contributionInterval(frequency) {
        return {
            monthly: 1,
            quarterly: 3,
            yearly: 12,
        }[frequency] || 0;
    }

    function shouldAddContribution(month, interval) {
        return interval > 0 && (month - 1) % interval === 0;
    }

    function renderInvestmentSimulation() {
        const initialAmount = numberValue('simulationInitialAmount');
        const annualRate = numberValue('simulationAnnualRate') / 100;
        const years = Math.max(1, Math.min(50, Math.round(numberValue('simulationYears'))));
        const contribution = numberValue('simulationContribution');
        const frequency = document.getElementById('simulationContributionFrequency').value;
        const interestMode = document.getElementById('simulationInterestMode').value;
        const interval = contributionInterval(frequency);
        const monthlyRate = annualRate / 12;
        const rows = [];

        let balance = initialAmount;
        let principal = initialAmount;
        let totalInvested = initialAmount;
        let totalInterest = 0;

        for (let year = 1; year <= years; year += 1) {
            const yearStartBalance = balance;
            let yearContributions = 0;
            let yearInterest = 0;

            for (let monthInYear = 1; monthInYear <= 12; monthInYear += 1) {
                const absoluteMonth = (year - 1) * 12 + monthInYear;
                if (shouldAddContribution(absoluteMonth, interval)) {
                    principal += contribution;
                    balance += contribution;
                    yearContributions += contribution;
                    totalInvested += contribution;
                }

                const interestBase = interestMode === 'compound' ? balance : principal;
                const interest = interestBase * monthlyRate;
                yearInterest += interest;
                totalInterest += interest;
                balance += interest;
            }

            rows.push({
                year,
                start: yearStartBalance,
                contributions: yearContributions,
                interest: yearInterest,
                end: balance,
            });
        }

        document.getElementById('simulationFinalBalance').textContent = formatter.format(balance);
        document.getElementById('simulationTotalInvested').textContent = formatter.format(totalInvested);
        document.getElementById('simulationTotalInterest').textContent = formatter.format(totalInterest);

        simulationRows.innerHTML = rows.map((row) => `
            <tr>
                <td>Год ${row.year}</td>
                <td class="text-end">${formatter.format(row.start)}</td>
                <td class="text-end">${formatter.format(row.contributions)}</td>
                <td class="text-end">${formatter.format(row.interest)}</td>
                <td class="text-end fw-semibold">${formatter.format(row.end)}</td>
            </tr>
        `).join('');
    }

    simulationButton.addEventListener('click', renderInvestmentSimulation);
    simulationForm.addEventListener('reset', () => setTimeout(renderInvestmentSimulation, 0));
    renderInvestmentSimulation();
});
</script>
@endpush
