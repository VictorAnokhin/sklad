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

    <section class="bank-panel bank-loan-request-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Оформление заявки</div>
                <h2 class="mb-1">Заявка на кредит (ZOUT)</h2>
                <div class="bank-meta">Создает обычный документ-заявку. Выдача кредита оформляется связанным RN, платежи заемщика — связанными PO.</div>
            </div>
            <div class="bank-loan-toolbar">
                <a href="{{ route('document.index', ['doc' => 'ZOUT']) }}" class="btn btn-sm btn-outline-light">Все ZOUT</a>
                <button type="button" class="btn btn-sm btn-primary" data-loan-open>Новая заявка</button>
            </div>
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
            <form method="POST" action="{{ route('bank.loans.store') }}" class="bank-loan-form" data-loan-request-form>
            @csrf
            <input type="hidden" name="loan_id" value="{{ old('loan_id') }}" data-loan-field="loan_id">
            <label class="bank-field bank-field--wide">
                <span>Заемщик</span>
                <select name="borrower_id" class="form-select" data-loan-field="borrower_id" required>
                    <option value="">— выберите клиента —</option>
                    @foreach($borrowers as $borrower)
                        <option value="{{ $borrower->id }}" {{ (string) old('borrower_id') === (string) $borrower->id ? 'selected' : '' }}>
                            {{ $borrower->display_name }}{{ $borrower->contact_line !== '' ? ' · ' . $borrower->contact_line : '' }}
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="bank-field">
                <span>Тип залога</span>
                <select name="collateral_type" class="form-select" data-loan-field="collateral_type" required>
                    <option value="auto" {{ old('collateral_type', 'auto') === 'auto' ? 'selected' : '' }}>Автомобиль</option>
                    <option value="special_equipment" {{ old('collateral_type') === 'special_equipment' ? 'selected' : '' }}>Спецтехника</option>
                    <option value="license_plate" {{ old('collateral_type') === 'license_plate' ? 'selected' : '' }}>Госномер</option>
                    <option value="other" {{ old('collateral_type') === 'other' ? 'selected' : '' }}>Другое</option>
                </select>
            </label>

            <label class="bank-field">
                <span>Рыночная стоимость</span>
                <input type="number" step="0.01" min="0" name="market_value" value="{{ old('market_value') }}" class="form-control" data-loan-field="market_value" data-loan-market-value required>
            </label>

            <label class="bank-field">
                <span>LTV сделки</span>
                <select name="ltv" class="form-select" data-loan-field="ltv" data-loan-ltv required>
                    @foreach([40, 50, 60, 70, 80, 90] as $ltv)
                        <option value="{{ $ltv }}" {{ (string) old('ltv', '70') === (string) $ltv ? 'selected' : '' }}>{{ $ltv }}%</option>
                    @endforeach
                </select>
            </label>

            <label class="bank-field">
                <span>Процентная ставка</span>
                <input type="number" step="0.01" min="0" max="100" name="interest_rate" value="{{ old('interest_rate') }}" class="form-control" data-loan-field="interest_rate" required>
            </label>

            <label class="bank-field">
                <span>Срок кредита</span>
                <select name="loan_term_months" class="form-select" data-loan-field="loan_term_months" required>
                    <option value="6" {{ old('loan_term_months') === '6' ? 'selected' : '' }}>6 мес</option>
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
                    @foreach([3, 7, 14, 21] as $days)
                        <option value="{{ $days }}" {{ (string) old('deadline_days', '7') === (string) $days ? 'selected' : '' }}>{{ $days }} {{ $days === 21 ? 'день' : 'дней' }}</option>
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
                    <div class="bank-meta">Рыночная стоимость × LTV. Эта сумма попадет в ZOUT.</div>
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

    <section class="bank-panel bank-table-panel">
        <div class="bank-table-header">
            <div>
                <div class="bank-label">Документооборот</div>
                <h2 class="mb-1">Кредитные заявки</h2>
                <div class="bank-meta">ZOUT — заявка, RN — выдача кредита, PO — поступающие платежи заемщика.</div>
            </div>
            <div class="bank-meta">{{ $loanRequests->count() }} заявок</div>
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
                        @php($loanComment = str_replace(["\r", "\n"], ' ', (string) ($loanMeta['comment'] ?? '')))
                        <tr data-loan-edit
                            data-loan-id="{{ $requestRow->id }}"
                            data-loan-num="{{ $requestRow->num }}"
                            data-borrower-id="{{ $requestRow->client1 }}"
                            data-collateral-type="{{ $loanMeta['collateral_type'] ?? 'auto' }}"
                            data-market-value="{{ $loanMeta['market_value'] ?? '' }}"
                            data-ltv="{{ $loanMeta['ltv'] ?? '70' }}"
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
                                    <a href="{{ $requestRow->po_url }}" class="btn btn-sm btn-outline-success" data-loan-action-link>PO платеж</a>
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
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 8px;
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
        .bank-loans-page .bank-loans-accounting__grid {
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
        const form = root.querySelector('[data-loan-request-form]');
        if (!form) return;

        const titleNode = root.querySelector('[data-loan-modal-title]');
        const deleteButton = form.querySelector('[data-loan-delete]');
        const marketInput = form.querySelector('[data-loan-market-value]');
        const ltvSelect = form.querySelector('[data-loan-ltv]');
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
            if (amountNode) amountNode.textContent = formatAmount(market * ltv / 100);
        };

        const setField = (name, value) => {
            if (fields[name]) fields[name].value = value ?? '';
        };

        const openModal = () => {
            if (!modal) return;
            modal.hidden = false;
            recalc();
            setTimeout(() => fields.borrower_id?.focus(), 30);
        };

        const closeModal = () => {
            if (modal) modal.hidden = true;
        };

        const openNewLoan = () => {
            form.reset();
            setField('loan_id', '');
            setField('borrower_id', '');
            setField('collateral_type', 'auto');
            setField('market_value', '');
            setField('ltv', '70');
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
            setField('borrower_id', row.dataset.borrowerId || '');
            setField('collateral_type', row.dataset.collateralType || 'auto');
            setField('market_value', row.dataset.marketValue || '');
            setField('ltv', row.dataset.ltv || '70');
            setField('interest_rate', row.dataset.interestRate || '');
            setField('loan_term_months', row.dataset.loanTermMonths || '12');
            setField('investor_yield', row.dataset.investorYield || '');
            setField('deadline_days', row.dataset.deadlineDays || '7');
            setField('comment', row.dataset.comment || '');
            if (titleNode) titleNode.textContent = `Заявка #${row.dataset.loanNum || row.dataset.loanId || ''}`;
            if (deleteButton) deleteButton.hidden = false;
            openModal();
        };

        marketInput?.addEventListener('input', recalc);
        ltvSelect?.addEventListener('change', recalc);

        root.querySelectorAll('[data-loan-open]').forEach((button) => {
            button.addEventListener('click', openNewLoan);
        });

        root.querySelectorAll('[data-loan-edit]').forEach((row) => {
            row.addEventListener('click', () => openExistingLoan(row));
        });

        root.querySelectorAll('[data-loan-action-link]').forEach((link) => {
            link.addEventListener('click', (event) => event.stopPropagation());
        });

        root.querySelectorAll('[data-loan-close]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
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
            openModal();
        }
    });
</script>
@endsection
