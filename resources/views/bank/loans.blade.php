@extends('home')

@section('title')
Кредиты
@endsection

@section('content')
@php
    $stages = [
        [
            'label' => 'Этап 1',
            'title' => 'Инициация и обеспечение',
            'mode' => 'Офчейн',
            'items' => [
                ['Подача заявки', 'Заемщик, например автобизнес, запрашивает кредит.'],
                ['Верификация и скоринг', 'Риск-менеджеры AV8 проверяют заемщика и оценивают ликвидность залога: рыночную стоимость автомобиля, спецтехники или госномеров.'],
                ['Параметры сделки', 'Фиксируются LTV до 60-70%, процентная ставка для заемщика, срок кредита и доходность для инвесторов.'],
            ],
        ],
        [
            'label' => 'Этап 2',
            'title' => 'Токенизация и развертывание пула',
            'mode' => 'Ончейн',
            'items' => [
                ['Реализация', 'Этот этап реализован в av8fund-react: параметры кредита и залога раскрываются на витрине пула, а доли инвесторов закрепляются через on-chain механику.'],
            ],
        ],
        [
            'label' => 'Этап 3',
            'title' => 'Краудфандинг и сбор средств',
            'mode' => 'On-chain сбор',
            'items' => [
                ['Открытие пула', 'Проект появляется на витрине платформы AV8.fund.'],
                ['Инвестирование', 'Пользователи подключают кошельки, например Sui Wallet, и вносят средства в поддерживаемых стейблкоинах или токенах в смарт-контракт пула.'],
                ['Фиксация долей', 'За каждый депозит смарт-контракт автоматически закрепляет за кошельком инвестора его долю в пуле.'],
            ],
        ],
        [
            'label' => 'Этап 4',
            'title' => 'Выдача кредита и Lock-up период',
            'mode' => 'Развилка сценариев',
            'items' => [
                ['Сценарий А: успех', 'Пул собирает Hard Cap. Смарт-контракт блокирует средства, активируется Lock-up. Платформа конвертирует криптоактивы в фиат и выдает кредит заемщику. Залог переходит под юридическое управление AV8.'],
                ['Сценарий Б: несбор', 'Если Soft Cap не достигнут к дедлайну, срабатывает функция refund(). Инвесторы в один клик забирают 100% своих средств из смарт-контракта без комиссий.'],
            ],
        ],
        [
            'label' => 'Этап 5',
            'title' => 'Обслуживание, погашение и Split-выплаты',
            'mode' => 'Cashflow',
            'items' => [
                ['Регулярные платежи', 'Заемщик вносит ежемесячные платежи в фиате. Платформа заводит эти средства обратно на блокчейн, конвертирует в стейблкоины и отправляет на смарт-контракт пула.'],
                ['Автоматический Split', '[X]% — Management Fee / Performance Fee уходит на системный кошелек AV8 за управление. [Y]% — пропорционально распределяется на балансы инвесторов пула как начисление доходности.'],
                ['Окончание срока', 'Заемщик гасит тело кредита, смарт-контракт возвращает инвесторам базовые инвестиции, пул закрывается и переходит в статус «Выплачен».'],
            ],
        ],
    ];
@endphp

<div class="bank-page bank-loans-page" data-bank-loans-page data-loan-has-errors="{{ isset($errors) && $errors->any() ? '1' : '0' }}">
    @include('bank.partials.nav')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <section class="bank-grid bank-grid--summary">
        <div class="bank-panel bank-panel--accent">
            <div class="bank-label">Кредитный сценарий</div>
            <div class="bank-value">5 этапов</div>
            <div class="bank-meta">От заявки заемщика до закрытия пула и выплат инвесторам.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">LTV</div>
            <div class="bank-value">60-70%</div>
            <div class="bank-meta">Целевой диапазон отношения кредита к стоимости залога.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Залог</div>
            <div class="bank-value">RWA</div>
            <div class="bank-meta">Авто, спецтехника, госномера и другие ликвидные активы.</div>
        </div>
        <div class="bank-panel">
            <div class="bank-label">Гарант</div>
            <div class="bank-value">AV8</div>
            <div class="bank-meta">Агент-гарант юридического управления и реализации залога.</div>
        </div>
    </section>

    <div class="bank-modal" data-loan-modal hidden>
        <div class="bank-modal__backdrop" data-loan-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="loanModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Кредитная заявка</div>
                    <h2 id="loanModalTitle" data-loan-modal-title>Новая заявка</h2>
                </div>
                <button type="button" class="bank-modal__close" data-loan-close aria-label="Закрыть">×</button>
            </div>
            <form method="POST" action="{{ route('bank.loan.store') }}" class="bank-loan-form" data-loan-request-form>
            @csrf
            <input type="hidden" name="loan_id" value="{{ old('loan_id') }}" data-loan-field="loan_id">
            @php($selectedBorrower = old('borrower_id') ? $borrowers->firstWhere('id', (int) old('borrower_id')) : null)
            <div class="bank-field bank-field--wide bank-loan-borrower-field">
                <span>Заемщик</span>
                <input type="text" class="form-control" placeholder="Поиск по клиентам..." autocomplete="off" value="{{ $selectedBorrower?->display_name ?? '' }}" data-loan-borrower-search>
                <input type="hidden" name="borrower_id" value="{{ old('borrower_id') }}" data-loan-field="borrower_id" required>
                <div class="bank-loan-borrower-results" data-loan-borrower-results hidden></div>
                <div class="bank-loan-borrower-details" data-loan-borrower-details>
                    @if($selectedBorrower)
                        <strong>{{ $selectedBorrower->display_name }}</strong>{{ $selectedBorrower->contact_line !== '' ? ' · ' . $selectedBorrower->contact_line : '' }}
                    @else
                        Заемщик не выбран
                    @endif
                </div>
            </div>

            <label class="bank-field">
                <span>Тип залога</span>
                <input type="text" name="collateral_type" value="{{ old('collateral_type', 'Автомобиль') }}" class="form-control" list="loanCollateralOptions" data-loan-field="collateral_type" required>
                <datalist id="loanCollateralOptions">
                    @foreach($collateralOptions as $collateralOption)
                        <option value="{{ $collateralOption }}"></option>
                    @endforeach
                </datalist>
            </label>

            <label class="bank-field">
                <span>Рыночная стоимость</span>
                <input type="number" step="0.01" min="0" name="market_value" value="{{ old('market_value') }}" class="form-control" data-loan-field="market_value" data-loan-market-value required>
            </label>

            <label class="bank-field">
                <span>LTV сделки</span>
                <select name="ltv" class="form-select" data-loan-field="ltv" data-loan-ltv required>
                    @foreach([40, 50, 60, 70, 80, 90, 100] as $ltv)
                        <option value="{{ $ltv }}" {{ (string) old('ltv', '70') === (string) $ltv ? 'selected' : '' }}>{{ $ltv }}%</option>
                    @endforeach
                </select>
            </label>

            <label class="bank-field">
                <span>Сумма кредита</span>
                <input type="number" step="0.01" min="0" name="loan_amount" value="{{ old('loan_amount') }}" class="form-control" data-loan-field="loan_amount" data-loan-amount-input required>
            </label>

            <label class="bank-field">
                <span>Процентная ставка</span>
                <input type="number" step="0.01" min="0" max="100" name="interest_rate" value="{{ old('interest_rate') }}" class="form-control" data-loan-field="interest_rate" required>
            </label>

            <label class="bank-field">
                <span>Срок кредита</span>
                <select name="loan_term_months" class="form-select" data-loan-field="loan_term_months" required>
                    <option value="1" {{ old('loan_term_months') === '1' ? 'selected' : '' }}>1 мес</option>
                    <option value="3" {{ old('loan_term_months') === '3' ? 'selected' : '' }}>3 мес</option>
                    <option value="6" {{ old('loan_term_months') === '6' ? 'selected' : '' }}>6 мес</option>
                    <option value="9" {{ old('loan_term_months') === '9' ? 'selected' : '' }}>9 мес</option>
                    <option value="12" {{ old('loan_term_months', '12') === '12' ? 'selected' : '' }}>1 год</option>
                    <option value="24" {{ old('loan_term_months') === '24' ? 'selected' : '' }}>2 года</option>
                    <option value="36" {{ old('loan_term_months') === '36' ? 'selected' : '' }}>3 года</option>
                </select>
            </label>

            <label class="bank-field">
                <span>Доходность для инвесторов</span>
                <input type="number" step="0.01" min="0" max="100" name="investor_yield" value="{{ old('investor_yield') }}" class="form-control" data-loan-field="investor_yield" required>
            </label>

            <label class="bank-field">
                <span>Дедлайн</span>
                <select name="deadline_days" class="form-select" data-loan-field="deadline_days" required>
                    <option value="0" {{ old('deadline_days') === '0' ? 'selected' : '' }}>Сразу</option>
                    <option value="1" {{ old('deadline_days') === '1' ? 'selected' : '' }}>1 день</option>
                    @foreach([3, 7, 14, 21] as $days)
                        <option value="{{ $days }}" {{ (string) old('deadline_days', '7') === (string) $days ? 'selected' : '' }}>{{ $days }} дней</option>
                    @endforeach
                </select>
            </label>

            <label class="bank-field bank-field--wide">
                <span>Комментарий риск-менеджера</span>
                <textarea name="comment" class="form-control" rows="3" placeholder="Описание залога, VIN/госномер, условия удержания, примечания скоринга" data-loan-field="comment">{{ old('comment') }}</textarea>
            </label>

            <div class="bank-loan-result">
                <div>
                    <div class="bank-label">Расчетная сумма кредита</div>
                    <div class="bank-value" data-loan-amount>0.00</div>
                    <div class="bank-meta">Рыночная стоимость × LTV подставляется в поле суммы, но сумму можно изменить вручную.</div>
                </div>
                <div class="bank-modal__actions bank-loan-modal-actions">
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <button type="submit" name="loan_action" value="delete" class="btn btn-outline-danger" formnovalidate data-loan-delete hidden>Удалить</button>
                    <button type="button" class="btn btn-secondary" data-loan-close>Отменить</button>
                </div>
            </div>
            </form>
        </div>
    </div>

    <div class="bank-modal" data-loan-payment-modal hidden>
        <div class="bank-modal__backdrop" data-loan-payment-close></div>
        <div class="bank-modal__dialog bank-modal__dialog--accounts" role="dialog" aria-modal="true" aria-labelledby="loanPaymentModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">График погашения</div>
                    <h2 id="loanPaymentModalTitle" data-loan-payment-title>PO платеж</h2>
                </div>
                <button type="button" class="bank-modal__close" data-loan-payment-close aria-label="Закрыть">×</button>
            </div>
            <div class="bank-modal__body">
                <div class="bank-loan-payment-summary">
                    <div><span>К оплате всего</span><strong data-payment-total>0.00</strong></div>
                    <div><span>Оплачено</span><strong data-payment-paid>0.00</strong></div>
                    <div><span>Остаток</span><strong data-payment-remaining>0.00</strong></div>
                </div>

                <div class="table-responsive bank-loan-payment-table-wrap">
                    <table class="table table-dark table-sm align-middle bank-table bank-loan-payment-table">
                        <thead>
                            <tr>
                                <th>Пункт</th>
                                <th>Дата</th>
                                <th class="text-end">К оплате</th>
                                <th class="text-end">Оплачено</th>
                                <th class="text-end">Остаток</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody data-payment-schedule></tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('bank.loan.payments.store') }}" class="bank-loan-payment-form" data-loan-payment-form>
                    @csrf
                    <input type="hidden" name="loan_id" value="" data-payment-loan-id>
                    <label class="bank-field">
                        <span>Сумма</span>
                        <input type="number" step="0.01" min="0.01" name="amount" value="" class="form-control" data-payment-amount required>
                    </label>
                    <div class="bank-modal__actions">
                        <button type="submit" class="btn btn-primary">Сохранить</button>
                        <button type="button" class="btn btn-secondary" data-loan-payment-close>Отменить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="bank-modal" data-loan-filter-modal hidden>
        <div class="bank-modal__backdrop" data-loan-filter-close></div>
        <div class="bank-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="loanFilterModalTitle">
            <div class="bank-modal__header">
                <div>
                    <div class="bank-label">Фильтр</div>
                    <h2 id="loanFilterModalTitle">Кредитные заявки</h2>
                </div>
                <button type="button" class="bank-modal__close" data-loan-filter-close aria-label="Закрыть">×</button>
            </div>
            <form method="GET" action="{{ route('bank.loanDocs.index') }}" class="bank-loan-filter-form">
                <label class="bank-field">
                    <span>Дата от</span>
                    <input type="date" name="date_from" value="{{ $loanFilters['date_from'] ?? '' }}" class="form-control">
                </label>
                <label class="bank-field">
                    <span>Дата до</span>
                    <input type="date" name="date_to" value="{{ $loanFilters['date_to'] ?? '' }}" class="form-control">
                </label>
                <div class="bank-modal__actions bank-loan-filter-actions">
                    @if(!empty($loanFilters['active']))
                        <a href="{{ route('bank.loanDocs.index') }}" class="btn btn-outline-light">Сбросить</a>
                    @endif
                    <button type="submit" class="btn btn-primary">Применить</button>
                    <button type="button" class="btn btn-secondary" data-loan-filter-close>Отменить</button>
                </div>
            </form>
        </div>
    </div>

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Документооборот</div>
                <div class="bank-loan-title-row">
                    <h2 class="mb-1">Кредитные заявки</h2>
                    <button type="button" class="bank-icon-add" data-loan-open aria-label="Новая заявка" title="Новая заявка">+</button>
                </div>
                <div class="bank-meta">ZOUT — заявка, RN — выдача кредита, RA — документы залога, PO/RO — платежи заемщика и выдача средств.</div>
            </div>
            <div class="bank-loan-toolbar">
                <button type="button" class="btn btn-sm {{ !empty($loanFilters['active']) ? 'btn-info' : 'btn-outline-light' }}" data-loan-filter-open>Фильтр</button>
                <div class="bank-meta">{{ $loanRequests->count() }} заявок</div>
            </div>
        </div>

        <div class="table-responsive bank-table-scroll">
            <table class="table table-dark table-hover table-sm align-middle bank-table">
                <thead>
                    <tr>
                        <th class="bank-table__num">№</th>
                        <th>Заемщик</th>
                        <th class="text-end">Сумма кредита</th>
                        <th>Дата / дедлайн</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loanRequests as $requestRow)
                        @php($loanMeta = $requestRow->loan_meta ?? [])
                        @php($repaymentSchedule = $requestRow->repayment_schedule ?? [])
                        @php($loanComment = str_replace(["\r", "\n"], ' ', (string) ($loanMeta['comment'] ?? '')))
                        <tr data-loan-edit
                            data-loan-id="{{ $requestRow->id }}"
                            data-loan-num="{{ $requestRow->num }}"
                            data-loan-show-url="{{ $requestRow->show_url }}"
                            data-borrower-name="{{ $requestRow->borrower_name }}"
                            data-payment-schedule='@json($repaymentSchedule)'
                            data-borrower-id="{{ $requestRow->client1 }}"
                            data-collateral-type="{{ $loanMeta['collateral_type'] ?? 'auto' }}"
                            data-market-value="{{ $loanMeta['market_value'] ?? '' }}"
                            data-ltv="{{ $loanMeta['ltv'] ?? '70' }}"
                            data-loan-amount="{{ $loanMeta['loan_amount'] ?? $requestRow->summa }}"
                            data-interest-rate="{{ $loanMeta['interest_rate'] ?? '' }}"
                            data-loan-term-months="{{ $loanMeta['loan_term_months'] ?? '12' }}"
                            data-investor-yield="{{ $loanMeta['investor_yield'] ?? '' }}"
                            data-deadline-days="{{ $loanMeta['deadline_days'] ?? '7' }}"
                            data-comment="{{ $loanComment }}">
                            <td class="bank-table__num bank-mono">#{{ $requestRow->num }}</td>
                            <td>
                                <a href="{{ $requestRow->show_url }}" class="text-white fw-semibold" data-loan-action-link>{{ $requestRow->borrower_name }}</a>
                                <div class="bank-meta">{{ \Illuminate\Support\Str::of((string) $requestRow->content)->replace('[AV8_LOAN_REQUEST]', '')->limit(140) }}</div>
                            </td>
                            <td class="text-end fw-semibold">{{ number_format((float) $requestRow->summa, 2, '.', ' ') }}</td>
                            <td>
                                <div>{{ $requestRow->data }}</div>
                                <div class="bank-meta">{{ $requestRow->data2 }}</div>
                            </td>
                            <td>
                                <div class="bank-loan-actions">
                                    <a href="{{ $requestRow->show_url }}" class="btn btn-sm btn-outline-light" data-loan-action-link>ZOUT</a>
                                    <a href="{{ $requestRow->rn_url }}" class="btn btn-sm btn-outline-info" data-loan-action-link>RN выдача</a>
                                    <a href="{{ $requestRow->ra_url }}" class="btn btn-sm btn-outline-warning" data-loan-action-link>RA залог</a>
                                    <button type="button" class="btn btn-sm btn-outline-success" data-loan-payment-open>PO платеж</button>
                                    <a href="{{ $requestRow->ro_url }}" class="btn btn-sm btn-outline-primary" data-loan-action-link>RO выдача</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Кредитных заявок пока нет.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bank-panel bank-loans-accounting">
        <div class="bank-label">Вариант учета</div>
        <h2>Рекомендуемый маршрут</h2>
        <div class="bank-loans-accounting__grid">
            <div><strong>ZOUT</strong><span>Заявка на кредит и параметры залога.</span></div>
            <div><strong>RN</strong><span>Выдача кредита по сценарию trade-flow. Можно использовать как акт выдачи кредитного продукта.</span></div>
            <div><strong>PO</strong><span>Платежи заемщика: проценты, тело кредита, регулярное обслуживание.</span></div>
            <div><strong>RO</strong><span>Бухгалтерски точнее для фактического исходящего денежного потока при выдаче кредита.</span></div>
        </div>
    </section>

    <section class="bank-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Кредиты</div>
                <h2 class="mb-1">Сценарий кредитного пула</h2>
                <div class="bank-meta">Операционная карта для type=bank: офчейн-скоринг, on-chain пул, сбор средств, Lock-up и split-выплаты.</div>
            </div>
        </div>

        <div class="bank-loans-timeline">
            @foreach($stages as $stage)
                <article class="bank-loans-stage">
                    <div class="bank-loans-stage__marker">{{ $loop->iteration }}</div>
                    <div class="bank-loans-stage__content">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                            <span class="bank-status">{{ $stage['label'] }}</span>
                            <span class="bank-meta">{{ $stage['mode'] }}</span>
                        </div>
                        <h3>{{ $stage['title'] }}</h3>
                        <div class="bank-loans-checklist">
                            @foreach($stage['items'] as [$title, $body])
                                <div class="bank-loans-checklist__item">
                                    <strong>{{ $title }}</strong>
                                    <span>{{ $body }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="bank-panel bank-loans-security">
        <div class="bank-label">Ключевой элемент безопасности</div>
        <h2>AV8 как агент-гарант</h2>
        <p>
            Если заемщик допускает дефолт, запускается юридический сценарий изъятия и реализации физического залога.
            Вырученные деньги направляются на компенсацию инвесторам пула через тот же смарт-контракт.
        </p>
    </section>
</div>

@include('bank.partials.styles')

<style>
    .bank-loans-page .bank-loan-form {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        padding: 18px 20px 20px;
    }

    .bank-loans-page .bank-loan-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
    }

    .bank-loans-page .bank-loan-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .bank-loans-page .bank-icon-add {
        display: inline-flex;
        width: 30px;
        height: 30px;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(45, 212, 191, 0.34);
        border-radius: 8px;
        background: rgba(20, 184, 166, 0.16);
        color: #ccfbf1;
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1;
    }

    .bank-loans-page .bank-icon-add:hover {
        background: rgba(20, 184, 166, 0.26);
        border-color: rgba(45, 212, 191, 0.54);
    }

    .bank-loans-page .bank-field {
        display: grid;
        gap: 7px;
    }

    .bank-loans-page .bank-field--wide {
        grid-column: span 2;
    }

    .bank-loans-page .bank-field span {
        color: rgba(148, 163, 184, 0.95);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .bank-loans-page .bank-loan-form .form-control,
    .bank-loans-page .bank-loan-form .form-select {
        min-height: 42px;
        border-color: rgba(255, 255, 255, 0.16);
        background-color: rgba(2, 6, 23, 0.72);
        color: #f8fafc;
    }

    .bank-loans-page .bank-loan-form textarea.form-control {
        min-height: 88px;
    }

    .bank-loans-page .bank-loan-borrower-field {
        position: relative;
    }

    .bank-loans-page .bank-loan-borrower-results {
        position: absolute;
        top: 68px;
        right: 0;
        left: 0;
        z-index: 4;
        max-height: 250px;
        overflow: auto;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: #f8fafc;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.3);
    }

    .bank-loans-page .bank-loan-borrower-result {
        display: block;
        width: 100%;
        padding: 9px 11px;
        border: 0;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        background: #fff;
        color: #0f172a;
        text-align: left;
    }

    .bank-loans-page .bank-loan-borrower-result:hover {
        background: #e0f2fe;
    }

    .bank-loans-page .bank-loan-borrower-result small,
    .bank-loans-page .bank-loan-borrower-details {
        color: rgba(148, 163, 184, 0.94);
        font-size: 0.82rem;
    }

    .bank-loans-page .bank-loan-borrower-details {
        min-height: 20px;
    }

    .bank-loans-page .bank-loan-result {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px;
        border: 1px solid rgba(45, 212, 191, 0.18);
        border-radius: 18px;
        background: rgba(20, 184, 166, 0.08);
    }

    .bank-loans-page .bank-loan-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .bank-loans-page [data-loan-edit] {
        cursor: pointer;
    }

    .bank-loans-page [data-loan-edit]:hover td {
        background-color: rgba(45, 212, 191, 0.08);
    }

    .bank-loans-page .bank-loan-modal-actions {
        margin-top: 0;
    }

    .bank-loans-page .bank-loan-payment-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }

    .bank-loans-page .bank-loan-payment-summary div {
        display: grid;
        gap: 5px;
        padding: 12px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 8px;
        background: rgba(2, 6, 23, 0.42);
    }

    .bank-loans-page .bank-loan-payment-summary span {
        color: rgba(148, 163, 184, 0.92);
        font-size: 0.76rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .bank-loans-page .bank-loan-payment-summary strong {
        color: #f8fafc;
        font-size: 1.05rem;
    }

    .bank-loans-page .bank-loan-payment-table-wrap {
        max-height: 340px;
        margin-bottom: 14px;
        overflow: auto;
    }

    .bank-loans-page .bank-loan-payment-form {
        display: grid;
        grid-template-columns: minmax(180px, 280px) 1fr;
        gap: 14px;
        align-items: end;
    }

    .bank-loans-page .bank-loan-filter-form {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        padding: 18px 20px 20px;
    }

    .bank-loans-page .bank-loan-filter-actions {
        grid-column: 1 / -1;
        margin-top: 0;
    }

    .bank-loans-page .bank-payment-status {
        display: inline-flex;
        min-width: 86px;
        justify-content: center;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .bank-loans-page .bank-payment-status--paid {
        background: rgba(34, 197, 94, 0.16);
        color: #bbf7d0;
    }

    .bank-loans-page .bank-payment-status--partial {
        background: rgba(250, 204, 21, 0.15);
        color: #fef08a;
    }

    .bank-loans-page .bank-payment-status--pending {
        background: rgba(148, 163, 184, 0.14);
        color: #cbd5e1;
    }

    .bank-loans-page .bank-loans-accounting {
        border-color: rgba(96, 165, 250, 0.22);
        background: rgba(15, 23, 42, 0.58);
    }

    .bank-loans-page .bank-loans-accounting h2 {
        margin: 0 0 14px;
        color: #fff;
    }

    .bank-loans-page .bank-loans-accounting__grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .bank-loans-page .bank-loans-accounting__grid div {
        display: grid;
        gap: 6px;
        padding: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 14px;
        background: rgba(2, 6, 23, 0.46);
        color: rgba(226, 232, 240, 0.8);
    }

    .bank-loans-page .bank-loans-accounting__grid strong {
        color: #bfdbfe;
        font-size: 1rem;
    }

    .bank-loans-page .bank-loans-timeline {
        display: grid;
        gap: 16px;
        margin-top: 24px;
    }

    .bank-loans-page .bank-loans-stage {
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: 16px;
        padding: 18px;
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 20px;
        background: rgba(7, 12, 23, 0.64);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }

    .bank-loans-page .bank-loans-stage__marker {
        display: flex;
        width: 42px;
        height: 42px;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(45, 212, 191, 0.3);
        border-radius: 999px;
        background: rgba(45, 212, 191, 0.1);
        color: #ccfbf1;
        font-weight: 800;
    }

    .bank-loans-page .bank-loans-stage h3,
    .bank-loans-page .bank-loans-security h2 {
        margin: 0 0 12px;
        color: #fff;
        font-weight: 700;
    }

    .bank-loans-page .bank-loans-checklist {
        display: grid;
        gap: 10px;
    }

    .bank-loans-page .bank-loans-checklist__item {
        display: grid;
        gap: 4px;
        padding: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 14px;
        background: rgba(15, 23, 42, 0.56);
        color: rgba(226, 232, 240, 0.82);
    }

    .bank-loans-page .bank-loans-checklist__item strong {
        color: #f8fafc;
    }

    .bank-loans-page .bank-loans-security {
        border-color: rgba(45, 212, 191, 0.22);
        background: linear-gradient(135deg, rgba(20, 184, 166, 0.12), rgba(7, 12, 23, 0.76));
    }

    .bank-loans-page .bank-loans-security p {
        max-width: 980px;
        margin: 0;
        color: rgba(226, 232, 240, 0.82);
        font-size: 1rem;
        line-height: 1.7;
    }

    @media (max-width: 640px) {
        .bank-loans-page .bank-loan-result {
            align-items: stretch;
            flex-direction: column;
        }

        .bank-loans-page .bank-loans-stage {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 980px) {
        .bank-loans-page .bank-loan-form,
        .bank-loans-page .bank-loans-accounting__grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 720px) {
        .bank-loans-page .bank-loan-form,
        .bank-loans-page .bank-loans-accounting__grid,
        .bank-loans-page .bank-loan-payment-summary,
        .bank-loans-page .bank-loan-payment-form,
        .bank-loans-page .bank-loan-filter-form {
            grid-template-columns: 1fr;
        }

        .bank-loans-page .bank-field--wide {
            grid-column: span 1;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-bank-loans-page]');
        if (!root) return;

        const modal = root.querySelector('[data-loan-modal]');
        const paymentModal = root.querySelector('[data-loan-payment-modal]');
        const filterModal = root.querySelector('[data-loan-filter-modal]');
        const form = root.querySelector('[data-loan-request-form]');
        if (!form) return;

        const titleNode = root.querySelector('[data-loan-modal-title]');
        const deleteButton = form.querySelector('[data-loan-delete]');
        const borrowerSearch = form.querySelector('[data-loan-borrower-search]');
        const borrowerResults = form.querySelector('[data-loan-borrower-results]');
        const borrowerDetails = form.querySelector('[data-loan-borrower-details]');
        const paymentTitle = root.querySelector('[data-loan-payment-title]');
        const paymentForm = root.querySelector('[data-loan-payment-form]');
        const paymentLoanId = root.querySelector('[data-payment-loan-id]');
        const paymentAmount = root.querySelector('[data-payment-amount]');
        const paymentRows = root.querySelector('[data-payment-schedule]');
        const paymentTotal = root.querySelector('[data-payment-total]');
        const paymentPaid = root.querySelector('[data-payment-paid]');
        const paymentRemaining = root.querySelector('[data-payment-remaining]');
        const marketInput = form.querySelector('[data-loan-market-value]');
        const ltvSelect = form.querySelector('[data-loan-ltv]');
        const loanAmountInput = form.querySelector('[data-loan-amount-input]');
        const amountNode = form.querySelector('[data-loan-amount]');
        const fields = {};

        form.querySelectorAll('[data-loan-field]').forEach((field) => {
            fields[field.dataset.loanField] = field;
        });

        const formatAmount = (value) => new Intl.NumberFormat('ru-RU', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number.isFinite(value) ? value : 0);

        const recalc = () => {
            const market = Number(String(marketInput?.value || '').replace(',', '.')) || 0;
            const ltv = Number(ltvSelect?.value || 0) || 0;
            const amount = market * ltv / 100;
            if (amountNode) amountNode.textContent = formatAmount(amount);
            if (loanAmountInput) loanAmountInput.value = amount > 0 ? amount.toFixed(2) : '';
        };

        const syncAmountPreview = () => {
            const amount = Number(String(loanAmountInput?.value || '').replace(',', '.')) || 0;
            if (amountNode) amountNode.textContent = formatAmount(amount);
        };

        const setField = (name, value) => {
            if (fields[name]) fields[name].value = value ?? '';
        };

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const formatBorrowerName = (user) => [user.secondname || '', user.name || user.name2 || ''].filter(Boolean).join(' ').trim();
        const borrowerLabel = (user) => [user.orgname || '', formatBorrowerName(user)].filter(Boolean).join(' ').trim() || `Client #${user.id}`;
        const borrowerDetailsHtml = (user) => {
            const label = borrowerLabel(user);
            const contact = [user.phone || '', user.region || '', user.city || '', user.poshta || ''].filter(Boolean).join(' · ');

            return `<strong>${escapeHtml(label)}</strong>${contact ? ' · ' + escapeHtml(contact) : ''}`;
        };

        const setBorrower = (id, label, detailsHtml = '') => {
            setField('borrower_id', id || '');
            if (borrowerSearch) borrowerSearch.value = label || '';
            if (borrowerDetails) borrowerDetails.innerHTML = detailsHtml || (label ? `<strong>${escapeHtml(label)}</strong>` : 'Заемщик не выбран');
            if (borrowerResults) borrowerResults.hidden = true;
        };

        const openModal = (shouldRecalc = true) => {
            if (!modal) return;
            modal.hidden = false;
            if (shouldRecalc) {
                recalc();
            } else {
                syncAmountPreview();
            }
            setTimeout(() => borrowerSearch?.focus(), 30);
        };

        const closeModal = () => {
            if (modal) modal.hidden = true;
        };

        const closePaymentModal = () => {
            if (paymentModal) paymentModal.hidden = true;
        };

        const openFilterModal = () => {
            if (filterModal) filterModal.hidden = false;
        };

        const closeFilterModal = () => {
            if (filterModal) filterModal.hidden = true;
        };

        const openNewLoan = () => {
            form.reset();
            setField('loan_id', '');
            setBorrower('', '');
            setField('collateral_type', 'Автомобиль');
            setField('market_value', '');
            setField('ltv', '70');
            setField('loan_amount', '');
            setField('interest_rate', '');
            setField('loan_term_months', '12');
            setField('investor_yield', '');
            setField('deadline_days', '7');
            setField('comment', '');
            if (titleNode) titleNode.textContent = 'Новая заявка';
            if (deleteButton) deleteButton.hidden = true;
            openModal();
        };

        const openExistingLoan = (row) => {
            setField('loan_id', row.dataset.loanId || '');
            setBorrower(row.dataset.borrowerId || '', row.dataset.borrowerName || '');
            setField('collateral_type', row.dataset.collateralType || 'Автомобиль');
            setField('market_value', row.dataset.marketValue || '');
            setField('ltv', row.dataset.ltv || '70');
            setField('loan_amount', row.dataset.loanAmount || '');
            setField('interest_rate', row.dataset.interestRate || '');
            setField('loan_term_months', row.dataset.loanTermMonths || '12');
            setField('investor_yield', row.dataset.investorYield || '');
            setField('deadline_days', row.dataset.deadlineDays || '7');
            setField('comment', row.dataset.comment || '');
            if (titleNode) titleNode.textContent = `Заявка #${row.dataset.loanNum || row.dataset.loanId || ''}`;
            if (deleteButton) deleteButton.hidden = false;
            openModal(false);
        };

        const statusLabel = (status) => ({
            paid: 'Оплачено',
            partial: 'Частично',
            pending: 'Ожидает',
        })[status] || 'Ожидает';

        const openPaymentModal = (row) => {
            let schedule = {};
            try {
                schedule = JSON.parse(row.dataset.paymentSchedule || '{}');
            } catch (error) {
                schedule = {};
            }

            if (paymentTitle) paymentTitle.textContent = `PO платеж по заявке #${row.dataset.loanNum || row.dataset.loanId || ''}`;
            if (paymentLoanId) paymentLoanId.value = row.dataset.loanId || '';
            if (paymentAmount) paymentAmount.value = Number(schedule.next_amount || schedule.remaining_total || 0).toFixed(2);
            if (paymentTotal) paymentTotal.textContent = formatAmount(Number(schedule.total_due || 0));
            if (paymentPaid) paymentPaid.textContent = formatAmount(Number(schedule.paid_total || 0));
            if (paymentRemaining) paymentRemaining.textContent = formatAmount(Number(schedule.remaining_total || 0));

            if (paymentRows) {
                paymentRows.innerHTML = '';
                (schedule.rows || []).forEach((item) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="bank-mono">#${item.number}</td>
                        <td>${item.due_date || ''}</td>
                        <td class="text-end">${formatAmount(Number(item.amount || 0))}</td>
                        <td class="text-end">${formatAmount(Number(item.paid || 0))}</td>
                        <td class="text-end">${formatAmount(Number(item.remaining || 0))}</td>
                        <td><span class="bank-payment-status bank-payment-status--${item.status || 'pending'}">${statusLabel(item.status)}</span></td>
                    `;
                    paymentRows.appendChild(tr);
                });
            }

            if (paymentModal) paymentModal.hidden = false;
            setTimeout(() => paymentAmount?.focus(), 30);
        };

        marketInput?.addEventListener('input', recalc);
        ltvSelect?.addEventListener('change', recalc);
        loanAmountInput?.addEventListener('input', syncAmountPreview);

        root.querySelectorAll('[data-loan-open]').forEach((button) => {
            button.addEventListener('click', openNewLoan);
        });

        root.querySelectorAll('[data-loan-edit]').forEach((row) => {
            row.addEventListener('click', () => {
                if (row.dataset.loanShowUrl) {
                    window.location.href = row.dataset.loanShowUrl;
                }
            });
        });

        root.querySelectorAll('[data-loan-action-link]').forEach((link) => {
            link.addEventListener('click', (event) => event.stopPropagation());
        });

        root.querySelectorAll('[data-loan-payment-open]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const row = button.closest('[data-loan-edit]');
                if (row) openPaymentModal(row);
            });
        });

        root.querySelectorAll('[data-loan-filter-open]').forEach((button) => {
            button.addEventListener('click', openFilterModal);
        });

        root.querySelectorAll('[data-loan-close]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        root.querySelectorAll('[data-loan-payment-close]').forEach((button) => {
            button.addEventListener('click', closePaymentModal);
        });

        root.querySelectorAll('[data-loan-filter-close]').forEach((button) => {
            button.addEventListener('click', closeFilterModal);
        });

        let borrowerSearchTimeout = null;
        const runBorrowerSearch = () => {
            const q = (borrowerSearch?.value || '').trim();
            if (!borrowerResults) return;
            if (q.length < 2) {
                borrowerResults.hidden = true;
                borrowerResults.innerHTML = '';
                return;
            }

            fetch(`{{ route('client.search') }}?${new URLSearchParams({ q }).toString()}`)
                .then((response) => response.json())
                .then((users) => {
                    borrowerResults.innerHTML = '';
                    if (!Array.isArray(users) || users.length === 0) {
                        borrowerResults.innerHTML = '<div class="bank-loan-borrower-result">Ничего не найдено</div>';
                    } else {
                        users.forEach((user) => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'bank-loan-borrower-result';
                            button.innerHTML = `${borrowerDetailsHtml(user)}${user.usergroup_name ? `<br><small>${escapeHtml(user.usergroup_name)}</small>` : ''}`;
                            button.addEventListener('click', () => setBorrower(user.id, borrowerLabel(user), borrowerDetailsHtml(user)));
                            borrowerResults.appendChild(button);
                        });
                    }
                    borrowerResults.hidden = false;
                })
                .catch(() => {
                    borrowerResults.innerHTML = '<div class="bank-loan-borrower-result">Ошибка поиска</div>';
                    borrowerResults.hidden = false;
                });
        };

        borrowerSearch?.addEventListener('input', () => {
            setField('borrower_id', '');
            if (borrowerDetails) borrowerDetails.textContent = 'Выберите заемщика из списка';
            clearTimeout(borrowerSearchTimeout);
            borrowerSearchTimeout = setTimeout(runBorrowerSearch, 350);
        });

        borrowerSearch?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                runBorrowerSearch();
            }
        });

        document.addEventListener('click', (event) => {
            if (!borrowerSearch?.contains(event.target) && !borrowerResults?.contains(event.target)) {
                if (borrowerResults) borrowerResults.hidden = true;
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
            if (event.key === 'Escape' && paymentModal && !paymentModal.hidden) closePaymentModal();
            if (event.key === 'Escape' && filterModal && !filterModal.hidden) closeFilterModal();
        });

        deleteButton?.addEventListener('click', (event) => {
            if (!window.confirm('Удалить кредитную заявку?')) {
                event.preventDefault();
            }
        });

        recalc();

        if (root.dataset.loanHasErrors === '1') {
            if (fields.loan_id?.value) {
                if (titleNode) titleNode.textContent = `Заявка #${fields.loan_id.value}`;
                if (deleteButton) deleteButton.hidden = false;
            } else if (deleteButton) {
                deleteButton.hidden = true;
            }
            openModal(false);
        }
    });
</script>
@endsection
