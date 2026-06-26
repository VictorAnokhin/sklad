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

<div class="bank-page bank-loans-page">
    @include('bank.partials.nav')

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
        .bank-loans-page .bank-loans-stage {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
