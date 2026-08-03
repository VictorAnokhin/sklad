@extends('home')

@section('title', 'Утилиты')

@section('content')
@php
    $investmentUtility = $investmentUtility ?? [];
    $utilities = $utilities ?? [$investmentUtility];
    $capitalUtility = collect($utilities)->firstWhere('slug', 'capital-efficiency') ?? [];
    $utilityTitleTranslations = $investmentUtility['title_translations'] ?? [];
    $utilityDescriptionTranslations = $investmentUtility['description_translations'] ?? [];
    $capitalUtilityTitleTranslations = $capitalUtility['title_translations'] ?? [];
    $capitalUtilityDescriptionTranslations = $capitalUtility['description_translations'] ?? [];
    $investmentUtilitySchemaJson = json_encode($investmentUtility['schema_json'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $capitalUtilitySchemaJson = json_encode($capitalUtility['schema_json'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<div class="education-utilities-page">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <div class="education-utilities-card">
        <div class="education-utilities-actions">
            <button type="button" class="education-utility-app education-utility-app--add" data-bs-toggle="modal" data-bs-target="#addUtilityModal">
                <span class="education-utility-app__icon">+</span>
                <span class="education-utility-app__title">Добавить</span>
                <span class="education-utility-app__meta">Новая утилита</span>
            </button>
            @foreach($utilities as $utility)
                @php
                    $utilitySlug = $utility['slug'] ?? '';
                    $utilityModalSlug = preg_replace('/[^A-Za-z0-9\-_]/', '-', $utilitySlug);
                    $modalTarget = $utilitySlug === 'capital-efficiency'
                        ? '#capitalEfficiencyModal'
                        : ($utilitySlug === 'investment-simulation'
                            ? '#investmentSimulationModal'
                            : '#utilitySettingsModal-' . $utilityModalSlug);
                    $utilityIcon = ($utility['icon'] ?? '') === 'chart' ? '↗' : '∑';
                @endphp
                <button type="button" class="education-utility-app" data-bs-toggle="modal" data-bs-target="{{ $modalTarget }}">
                    <span class="education-utility-app__icon">
                        @if(!empty($utility['icon_url']))
                            <img src="{{ $utility['icon_url'] }}" alt="{{ $utility['title'] ?? 'Утилита' }}">
                        @else
                            {{ $utilityIcon }}
                        @endif
                    </span>
                    <span class="education-utility-app__title">{{ $utility['title'] ?? 'Утилита' }}</span>
                    <span class="education-utility-app__meta">
                        Рейтинг {{ (int) ($utility['position'] ?? 0) }} · {{ number_format((float) ($utility['cost_av8'] ?? 0), 2, '.', ' ') }} AV8
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>

<div class="modal fade" id="addUtilityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title fs-5">Добавить утилиту</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <form method="POST" action="{{ route('education.utilities.store') }}">
                @csrf
                <div class="modal-body">
                    <section class="capital-efficiency-section">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="newUtilityTitle">Название</label>
                                <input class="form-control" id="newUtilityTitle" name="title" type="text" required placeholder="Например: Расчет доходности проекта">
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="newUtilitySlug">Код утилиты</label>
                                <input class="form-control" id="newUtilitySlug" name="slug" type="text" placeholder="project-yield">
                                <div class="form-text text-secondary">Можно оставить пустым, код будет создан из названия.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="newUtilityDescription">Описание</label>
                                <textarea class="form-control" id="newUtilityDescription" name="description" rows="4" style="resize:vertical;" placeholder="Кратко опишите назначение утилиты"></textarea>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-warning">Добавить</button>
                </div>
            </form>
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
                <ul class="nav nav-tabs border-secondary mb-3" id="capital-utility-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active bg-dark text-warning border-secondary" id="capital-calculation-tab"
                                data-bs-toggle="tab" data-bs-target="#capital-calculation-pane" type="button"
                                role="tab" aria-controls="capital-calculation-pane" aria-selected="true">
                            Расчет
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link bg-dark text-light border-secondary" id="capital-settings-tab"
                                data-bs-toggle="tab" data-bs-target="#capital-settings-pane" type="button"
                                role="tab" aria-controls="capital-settings-pane" aria-selected="false">
                            Настройки
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="capital-calculation-pane" role="tabpanel"
                         aria-labelledby="capital-calculation-tab" tabindex="0">
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
                    <div class="tab-pane fade" id="capital-settings-pane" role="tabpanel"
                         aria-labelledby="capital-settings-tab" tabindex="0">
                        <form method="POST" action="{{ route('education.utilities.update', ['utility' => $capitalUtility['slug'] ?? 'capital-efficiency']) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="title_translations[ua]" value="{{ $capitalUtilityTitleTranslations['ua'] ?? 'Оцінка ефективності капіталовкладень' }}">
                            <input type="hidden" name="title_translations[ru]" value="{{ $capitalUtilityTitleTranslations['ru'] ?? 'Оценка эффективности капиталовложений' }}">
                            <input type="hidden" name="title_translations[en]" value="{{ $capitalUtilityTitleTranslations['en'] ?? 'Capital efficiency assessment' }}">
                            <input type="hidden" name="title_translations[es]" value="{{ $capitalUtilityTitleTranslations['es'] ?? 'Evaluación de eficiencia de capital' }}">
                            <input type="hidden" name="title_translations[fr]" value="{{ $capitalUtilityTitleTranslations['fr'] ?? 'Évaluation de l’efficacité du capital' }}">
                            <section class="capital-efficiency-section">
                                <h3>Настройки доступа</h3>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="capitalUtilityRating">Рейтинг</label>
                                        <input class="form-control" id="capitalUtilityRating" name="position" type="number" min="0" value="{{ (int) ($capitalUtility['position'] ?? 0) }}">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="capitalUtilityCostAv8">Оплата, AV8</label>
                                        <input class="form-control" id="capitalUtilityCostAv8" name="cost_av8" type="number" min="0" step="0.000001" value="{{ $capitalUtility['cost_av8'] ?? '0' }}">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label" for="capitalUtilityIconFile">Иконка утилиты, JPG/PNG</label>
                                    <div class="education-utility-icon-upload">
                                        @if(!empty($capitalUtility['icon_url']))
                                            <img src="{{ $capitalUtility['icon_url'] }}" alt="{{ $capitalUtility['title'] ?? 'Иконка утилиты' }}">
                                        @endif
                                        <input class="form-control" id="capitalUtilityIconFile" name="icon_file" type="file" accept="image/png,image/jpeg,image/webp">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label" for="capitalUtilityDescriptionRu">Описание</label>
                                    <textarea class="form-control" id="capitalUtilityDescriptionRu" name="description_translations[ru]" rows="5" style="resize:vertical;">{{ $capitalUtilityDescriptionTranslations['ru'] ?? '' }}</textarea>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label" for="capitalUtilitySchemaJson">JSON-схема утилиты</label>
                                    <textarea class="form-control font-monospace" id="capitalUtilitySchemaJson" name="schema_json" rows="14" spellcheck="false" style="resize:vertical;">{{ old('schema_json', $capitalUtilitySchemaJson) }}</textarea>
                                    @error('schema_json')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="capital-efficiency-actions">
                                    <button type="submit" class="btn btn-warning">Сохранить</button>
                                </div>
                            </section>
                        </form>
                    </div>
                </div>
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
                        <form method="POST" action="{{ route('education.utilities.update', ['utility' => $investmentUtility['slug'] ?? 'investment-simulation']) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="title_translations[ua]" value="{{ $utilityTitleTranslations['ua'] ?? 'Моделювання інвестиційного вкладення' }}">
                            <input type="hidden" name="title_translations[ru]" value="{{ $utilityTitleTranslations['ru'] ?? 'Моделирование инвестиционного вложения' }}">
                            <input type="hidden" name="title_translations[en]" value="{{ $utilityTitleTranslations['en'] ?? 'Investment simulation' }}">
                            <input type="hidden" name="title_translations[es]" value="{{ $utilityTitleTranslations['es'] ?? 'Simulación de inversión' }}">
                            <input type="hidden" name="title_translations[fr]" value="{{ $utilityTitleTranslations['fr'] ?? 'Simulation d’investissement' }}">
                            <section class="capital-efficiency-section">
                                <h3>Настройки доступа</h3>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="investmentUtilityRating">Рейтинг</label>
                                        <input class="form-control" id="investmentUtilityRating" name="position" type="number" min="0" value="{{ (int) ($investmentUtility['position'] ?? 0) }}">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="investmentUtilityCostAv8">Оплата, AV8</label>
                                        <input class="form-control" id="investmentUtilityCostAv8" name="cost_av8" type="number" min="0" step="0.000001" value="{{ $investmentUtility['cost_av8'] ?? '0' }}">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label" for="investmentUtilityIconFile">Иконка утилиты, JPG/PNG</label>
                                    <div class="education-utility-icon-upload">
                                        @if(!empty($investmentUtility['icon_url']))
                                            <img src="{{ $investmentUtility['icon_url'] }}" alt="{{ $investmentUtility['title'] ?? 'Иконка утилиты' }}">
                                        @endif
                                        <input class="form-control" id="investmentUtilityIconFile" name="icon_file" type="file" accept="image/png,image/jpeg,image/webp">
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
                                            <textarea class="form-control" id="investmentUtilityDescriptionUa" name="description_translations[ua]" rows="5" style="resize:vertical;">{{ $utilityDescriptionTranslations['ua'] ?? '' }}</textarea>
                                        </div>
                                        <div class="tab-pane fade show active" id="investment-utility-description-ru-pane" role="tabpanel"
                                             aria-labelledby="investment-utility-description-ru-tab" tabindex="0">
                                            <textarea class="form-control" id="investmentUtilityDescriptionRu" name="description_translations[ru]" rows="5" style="resize:vertical;">{{ $utilityDescriptionTranslations['ru'] ?? '' }}</textarea>
                                        </div>
                                        <div class="tab-pane fade" id="investment-utility-description-en-pane" role="tabpanel"
                                             aria-labelledby="investment-utility-description-en-tab" tabindex="0">
                                            <textarea class="form-control" id="investmentUtilityDescriptionEn" name="description_translations[en]" rows="5" style="resize:vertical;">{{ $utilityDescriptionTranslations['en'] ?? '' }}</textarea>
                                        </div>
                                        <div class="tab-pane fade" id="investment-utility-description-es-pane" role="tabpanel"
                                             aria-labelledby="investment-utility-description-es-tab" tabindex="0">
                                            <textarea class="form-control" id="investmentUtilityDescriptionEs" name="description_translations[es]" rows="5" style="resize:vertical;">{{ $utilityDescriptionTranslations['es'] ?? '' }}</textarea>
                                        </div>
                                        <div class="tab-pane fade" id="investment-utility-description-fr-pane" role="tabpanel"
                                             aria-labelledby="investment-utility-description-fr-tab" tabindex="0">
                                            <textarea class="form-control" id="investmentUtilityDescriptionFr" name="description_translations[fr]" rows="5" style="resize:vertical;">{{ $utilityDescriptionTranslations['fr'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="form-label" for="investmentUtilitySchemaJson">JSON-схема утилиты</label>
                                    <textarea class="form-control font-monospace" id="investmentUtilitySchemaJson" name="schema_json" rows="14" spellcheck="false" style="resize:vertical;">{{ old('schema_json', $investmentUtilitySchemaJson) }}</textarea>
                                    @error('schema_json')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="capital-efficiency-actions">
                                    <button type="submit" class="btn btn-warning">Сохранить</button>
                                </div>
                            </section>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($utilities as $utility)
    @php
        $utilitySlug = $utility['slug'] ?? '';
        if (in_array($utilitySlug, ['capital-efficiency', 'investment-simulation'], true)) {
            continue;
        }
        $utilityModalSlug = preg_replace('/[^A-Za-z0-9\-_]/', '-', $utilitySlug);
        $customTitleTranslations = $utility['title_translations'] ?? [];
        $customDescriptionTranslations = $utility['description_translations'] ?? [];
        $customSchemaJson = json_encode($utility['schema_json'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp
    <div class="modal fade" id="utilitySettingsModal-{{ $utilityModalSlug }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-dark text-light border-secondary">
                <div class="modal-header border-secondary">
                    <h2 class="modal-title fs-5">{{ $utility['title'] ?? 'Утилита' }}</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
                <div class="modal-body">
                    <form id="customUtilityUpdateForm-{{ $utilityModalSlug }}" method="POST" action="{{ route('education.utilities.update', ['utility' => $utilitySlug]) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <section class="capital-efficiency-section">
                            <h3>Настройки утилиты</h3>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label" for="customUtilityTitleRu-{{ $utilityModalSlug }}">Название</label>
                                    <input class="form-control" id="customUtilityTitleRu-{{ $utilityModalSlug }}" name="title_translations[ru]" type="text" value="{{ $customTitleTranslations['ru'] ?? ($utility['title'] ?? '') }}">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label" for="customUtilityRating-{{ $utilityModalSlug }}">Рейтинг</label>
                                    <input class="form-control" id="customUtilityRating-{{ $utilityModalSlug }}" name="position" type="number" min="0" value="{{ (int) ($utility['position'] ?? 0) }}">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label" for="customUtilityCostAv8-{{ $utilityModalSlug }}">Оплата, AV8</label>
                                    <input class="form-control" id="customUtilityCostAv8-{{ $utilityModalSlug }}" name="cost_av8" type="number" min="0" step="0.000001" value="{{ $utility['cost_av8'] ?? '0' }}">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label" for="customUtilityIconFile-{{ $utilityModalSlug }}">Иконка утилиты, JPG/PNG</label>
                                <div class="education-utility-icon-upload">
                                    @if(!empty($utility['icon_url']))
                                        <img src="{{ $utility['icon_url'] }}" alt="{{ $utility['title'] ?? 'Иконка утилиты' }}">
                                    @endif
                                    <input class="form-control" id="customUtilityIconFile-{{ $utilityModalSlug }}" name="icon_file" type="file" accept="image/png,image/jpeg,image/webp">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label" for="customUtilityDescriptionRu-{{ $utilityModalSlug }}">Описание</label>
                                <textarea class="form-control" id="customUtilityDescriptionRu-{{ $utilityModalSlug }}" name="description_translations[ru]" rows="5" style="resize:vertical;">{{ $customDescriptionTranslations['ru'] ?? ($utility['description'] ?? '') }}</textarea>
                            </div>
                            <div class="mt-3">
                                <label class="form-label" for="customUtilitySchemaJson-{{ $utilityModalSlug }}">JSON-схема утилиты</label>
                                <textarea class="form-control font-monospace" id="customUtilitySchemaJson-{{ $utilityModalSlug }}" name="schema_json" rows="14" spellcheck="false" style="resize:vertical;">{{ old('schema_json', $customSchemaJson) }}</textarea>
                                @error('schema_json')
                                    <div class="text-danger small mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </section>
                    </form>
                    <form id="customUtilityDeleteForm-{{ $utilityModalSlug }}" method="POST" action="{{ route('education.utilities.destroy', ['utility' => $utilitySlug]) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
                <div class="modal-footer border-secondary">
                    <button
                        type="submit"
                        form="customUtilityDeleteForm-{{ $utilityModalSlug }}"
                        class="btn btn-outline-danger"
                        onclick="return confirm('Удалить утилиту {{ addslashes($utility['title'] ?? 'Утилита') }}?')"
                    >
                        Удалить
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" form="customUtilityUpdateForm-{{ $utilityModalSlug }}" class="btn btn-warning">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
<style>
    .education-utilities-page {
        padding-bottom: 32px;
    }

    .education-utilities-card {
        padding: 22px;
        border: 1px solid rgba(148, 163, 184, .32);
        border-radius: 14px;
        background: #111827;
    }

    .education-utilities-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        width: 100%;
    }

    .education-utility-app {
        display: grid;
        justify-items: center;
        gap: 8px;
        min-height: 150px;
        padding: 16px 12px;
        border: 1px solid rgba(250, 204, 21, .32);
        border-radius: 18px;
        color: #f8fafc;
        background:
            radial-gradient(circle at top, rgba(250, 204, 21, .18), transparent 42%),
            rgba(15, 23, 42, .96);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.08), 0 16px 36px rgba(0,0,0,.22);
        text-align: center;
        transition: transform .18s ease, border-color .18s ease, background .18s ease;
    }

    .education-utility-app:hover {
        transform: translateY(-2px);
        border-color: rgba(250, 204, 21, .68);
        background:
            radial-gradient(circle at top, rgba(250, 204, 21, .26), transparent 44%),
            rgba(15, 23, 42, .98);
    }

    .education-utility-app--add {
        border-color: rgba(34, 197, 94, .46);
        background:
            radial-gradient(circle at top, rgba(34, 197, 94, .22), transparent 44%),
            rgba(15, 23, 42, .96);
    }

    .education-utility-app--add:hover {
        border-color: rgba(34, 197, 94, .78);
        background:
            radial-gradient(circle at top, rgba(34, 197, 94, .32), transparent 44%),
            rgba(15, 23, 42, .98);
    }

    .education-utility-app__icon {
        display: grid;
        width: 56px;
        height: 56px;
        place-items: center;
        border-radius: 16px;
        color: #111827;
        background: linear-gradient(135deg, #fde68a, #f59e0b);
        font-size: 28px;
        font-weight: 800;
        overflow: hidden;
    }

    .education-utility-app--add .education-utility-app__icon {
        color: #ecfdf5;
        background: linear-gradient(135deg, #22c55e, #0f766e);
    }

    .education-utility-app__icon img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .education-utility-app__title {
        font-size: .94rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .education-utility-app__meta {
        color: #94a3b8;
        font-size: .78rem;
    }

    .education-utility-icon-upload {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .education-utility-icon-upload img {
        width: 64px;
        height: 64px;
        flex: 0 0 auto;
        border: 1px solid rgba(250, 204, 21, .35);
        border-radius: 16px;
        object-fit: cover;
        background: rgba(15, 23, 42, .9);
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
        .capital-efficiency-grid,
        .capital-results {
            grid-template-columns: 1fr;
        }

        .education-utilities-card {
            display: grid;
        }

        .education-utilities-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .capital-flow-grid {
            grid-template-columns: 1fr;
        }

        .capital-efficiency-actions {
            display: grid;
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .education-utilities-actions {
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
