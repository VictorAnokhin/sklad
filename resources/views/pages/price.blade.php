@extends('home')

@section('title', 'Цены')

@section('content')
<main class="pricing-page">
    <section class="pricing-hero">
        <div class="pricing-hero__inner">
            <div class="pricing-hero__copy">
                <p class="pricing-eyebrow">AV8 Capital DAO</p>
                <h1>Цены для учета, продаж и финансового контроля</h1>
                <p class="pricing-lead">
                    Выберите пакет под текущий размер команды. Все тарифы включают доступ к рабочему кабинету, базовым справочникам и аналитике.
                </p>
            </div>
            <a href="{{ route('login') }}" class="pricing-login-link">Войти</a>
        </div>
    </section>

    <section class="pricing-plans" aria-label="Тарифы">
        @php
            $plans = [
                [
                    'name' => 'Start',
                    'price' => '$19',
                    'period' => 'в месяц',
                    'description' => 'Для одного проекта и первых продаж.',
                    'features' => [
                        'Заказы, клиенты и товары',
                        'Касса и базовые платежи',
                        'Документы покупки и продажи',
                        'Стартовые отчеты',
                    ],
                ],
                [
                    'name' => 'Business',
                    'price' => '$49',
                    'period' => 'в месяц',
                    'description' => 'Для команды, склада и регулярного учета.',
                    'featured' => true,
                    'features' => [
                        'Все возможности Start',
                        'Склад, активы и финансирование',
                        'P&L, Cash Flow и баланс',
                        'Команда, роли и несколько компаний',
                    ],
                ],
                [
                    'name' => 'Platform',
                    'price' => '$129',
                    'period' => 'в месяц',
                    'description' => 'Для холдинга, маркетплейса и инвестиционных процессов.',
                    'features' => [
                        'Все возможности Business',
                        'Холдинги и связанные проекты',
                        'Каталог, сайт и база знаний',
                        'Банк, активы, депозиты и Web3',
                    ],
                ],
            ];
        @endphp

        <div class="pricing-plans__grid">
            @foreach($plans as $plan)
            <article class="pricing-card {{ !empty($plan['featured']) ? 'pricing-card--featured' : '' }}">
                <div>
                    <div class="pricing-card__topline">
                        <h2>{{ $plan['name'] }}</h2>
                        @if(!empty($plan['featured']))
                        <span>Рекомендуем</span>
                        @endif
                    </div>
                    <p class="pricing-card__description">{{ $plan['description'] }}</p>
                    <div class="pricing-card__price">
                        <strong>{{ $plan['price'] }}</strong>
                        <span>{{ $plan['period'] }}</span>
                    </div>
                </div>

                <ul class="pricing-card__features">
                    @foreach($plan['features'] as $feature)
                    <li>{{ $feature }}</li>
                    @endforeach
                </ul>

                <a href="{{ route('login') }}" class="pricing-card__button">Войти</a>
            </article>
            @endforeach
        </div>
    </section>
</main>

<style>
    .pricing-page {
        --pricing-bg: #071019;
        --pricing-panel: rgba(255, 255, 255, 0.055);
        --pricing-panel-strong: rgba(255, 255, 255, 0.085);
        --pricing-border: rgba(255, 255, 255, 0.12);
        --pricing-muted: rgba(255, 255, 255, 0.68);
        --pricing-text: #f8fafc;
        --pricing-accent: #fbbf24;
        --pricing-accent-2: #38bdf8;
        min-height: calc(100vh - 88px);
        padding: 36px 18px 56px;
        color: var(--pricing-text);
        background:
            linear-gradient(180deg, rgba(7, 16, 25, 0.92), rgba(4, 9, 15, 0.98)),
            url("{{ asset('images/about_hero_1776457354755.png') }}") center/cover fixed;
    }

    .pricing-hero,
    .pricing-plans {
        width: min(1120px, 100%);
        margin: 0 auto;
    }

    .pricing-hero__inner {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 24px;
        padding: 42px 0 28px;
        border-bottom: 1px solid var(--pricing-border);
    }

    .pricing-hero__copy {
        max-width: 760px;
    }

    .pricing-eyebrow {
        margin: 0 0 12px;
        color: var(--pricing-accent);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
    }

    .pricing-hero h1 {
        margin: 0;
        max-width: 760px;
        font-size: clamp(2rem, 4vw, 4.2rem);
        line-height: 1.02;
        font-weight: 760;
        letter-spacing: 0;
    }

    .pricing-lead {
        max-width: 680px;
        margin: 18px 0 0;
        color: var(--pricing-muted);
        font-size: 1.04rem;
        line-height: 1.65;
    }

    .pricing-login-link,
    .pricing-card__button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0.75rem 1.05rem;
        border-radius: 8px;
        border: 1px solid rgba(251, 191, 36, 0.38);
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #111827;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
    }

    .pricing-plans {
        padding-top: 28px;
    }

    .pricing-plans__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 16px;
    }

    .pricing-card {
        display: grid;
        grid-template-rows: auto 1fr auto;
        gap: 20px;
        min-height: 430px;
        padding: 24px;
        border: 1px solid var(--pricing-border);
        border-radius: 8px;
        background: var(--pricing-panel);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.28);
        backdrop-filter: blur(14px);
    }

    .pricing-card--featured {
        border-color: rgba(251, 191, 36, 0.52);
        background: var(--pricing-panel-strong);
    }

    .pricing-card__topline {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .pricing-card h2 {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 760;
    }

    .pricing-card__topline span {
        padding: 0.28rem 0.5rem;
        border-radius: 999px;
        background: rgba(56, 189, 248, 0.14);
        color: #bae6fd;
        font-size: 0.76rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .pricing-card__description {
        margin: 12px 0 0;
        color: var(--pricing-muted);
        line-height: 1.5;
    }

    .pricing-card__price {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-top: 22px;
    }

    .pricing-card__price strong {
        font-size: 2.8rem;
        line-height: 1;
    }

    .pricing-card__price span,
    .pricing-card__features {
        color: var(--pricing-muted);
    }

    .pricing-card__features {
        display: grid;
        gap: 12px;
        margin: 0;
        padding: 0;
        list-style: none;
        line-height: 1.45;
    }

    .pricing-card__features li {
        position: relative;
        padding-left: 22px;
    }

    .pricing-card__features li::before {
        content: "";
        position: absolute;
        top: 0.62em;
        left: 0;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--pricing-accent-2);
    }

    .pricing-card__button {
        width: 100%;
    }

    @media (max-width: 900px) {
        .pricing-hero__inner {
            align-items: flex-start;
            flex-direction: column;
        }

        .pricing-plans__grid {
            grid-template-columns: 1fr;
        }

        .pricing-card {
            min-height: 0;
        }
    }
</style>
@endsection
