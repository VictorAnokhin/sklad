@extends('home')

@section('title')
Управление бизнесом
@endsection

@section('content')
<style>
    .business-home {
        --business-bg: #070b10;
        --business-panel: rgba(255, 255, 255, 0.055);
        --business-panel-strong: rgba(255, 255, 255, 0.085);
        --business-border: rgba(255, 255, 255, 0.12);
        --business-muted: rgba(255, 255, 255, 0.72);
        --business-accent: #fbbf24;
        --business-accent-dark: #f59e0b;
        --business-green: #34d399;
        color: #fff;
        overflow: hidden;
    }

    .business-home__hero {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
        gap: 32px;
        align-items: stretch;
        min-height: 560px;
        padding: 42px;
        border: 1px solid var(--business-border);
        border-radius: 8px;
        background:
            linear-gradient(90deg, rgba(7, 11, 16, 0.97) 0%, rgba(7, 11, 16, 0.87) 52%, rgba(7, 11, 16, 0.35) 100%),
            url("{{ asset('images/micro_business_hero_1776457020084.png') }}") right center/cover no-repeat;
        box-shadow: 0 28px 90px rgba(0, 0, 0, 0.34);
    }

    .business-home__reveal {
        opacity: 0;
        transform: translateY(34px);
        transition: opacity 0.68s ease, transform 0.68s ease;
        will-change: opacity, transform;
    }

    .business-home__reveal.is-visible {
        opacity: 1;
        transform: translateY(0);
    }

    .business-home__grid .business-home__reveal:nth-child(2) {
        transition-delay: 0.09s;
    }

    .business-home__grid .business-home__reveal:nth-child(3) {
        transition-delay: 0.18s;
    }

    .business-home__feature-list {
        display: grid;
        gap: 32px;
    }

    .business-home__feature-row {
        display: grid;
        grid-template-columns: minmax(220px, 0.42fr) minmax(0, 1fr);
        gap: 30px;
        align-items: stretch;
    }

    .business-home__feature-visual,
    .business-home__feature-row .business-home__card {
        transition: opacity 0.72s ease, transform 0.72s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .business-home__feature-visual {
        display: grid;
        place-items: center;
        min-height: 220px;
        padding: 28px;
        border: 1px solid rgba(251, 191, 36, 0.18);
        border-radius: 8px;
        background:
            radial-gradient(circle at 32% 22%, rgba(251, 191, 36, 0.18), transparent 34%),
            linear-gradient(135deg, rgba(255, 255, 255, 0.075), rgba(255, 255, 255, 0.028));
        opacity: 0;
        transform: translateX(-58px);
    }

    .business-home__feature-letter {
        color: transparent;
        -webkit-text-stroke: 1px rgba(251, 191, 36, 0.9);
        font-family: var(--header);
        font-size: clamp(6.6rem, 12vw, 10.4rem);
        font-weight: 900;
        line-height: 0.86;
        text-shadow: 0 0 28px rgba(251, 191, 36, 0.28);
        transition: color 0.25s ease, text-shadow 0.25s ease, -webkit-text-stroke-color 0.25s ease;
    }

    .business-home__feature-row:hover .business-home__feature-letter {
        color: rgba(251, 191, 36, 0.12);
        -webkit-text-stroke-color: #fbbf24;
        text-shadow: 0 0 18px rgba(251, 191, 36, 0.52), 0 0 46px rgba(251, 191, 36, 0.24);
    }

    .business-home__capital-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 14px;
        margin-top: 34px;
    }

    .business-home__capital-card {
        display: grid;
        gap: 12px;
        align-content: start;
        min-height: 172px;
        padding: 18px 14px;
        border: 1px solid rgba(251, 191, 36, 0.16);
        border-radius: 8px;
        background:
            radial-gradient(circle at 50% 18%, rgba(251, 191, 36, 0.13), transparent 42%),
            rgba(255, 255, 255, 0.045);
        opacity: 0;
        transform: translateX(42px);
        transition: opacity 0.62s ease, transform 0.62s ease, border-color 0.22s ease, background 0.22s ease;
    }

    .business-home__capital-card.is-visible {
        opacity: 1;
        transform: translateX(0);
    }

    .business-home__capital-card:hover {
        transform: translateY(-4px);
        border-color: rgba(251, 191, 36, 0.42);
        background:
            radial-gradient(circle at 50% 18%, rgba(251, 191, 36, 0.19), transparent 46%),
            rgba(255, 255, 255, 0.07);
    }

    .business-home__capital-card.is-visible:hover {
        transform: translateY(-4px);
    }

    .business-home__capital-card:nth-child(6) {
        transition-delay: 0.06s;
    }

    .business-home__capital-card:nth-child(5) {
        transition-delay: 0.12s;
    }

    .business-home__capital-card:nth-child(4) {
        transition-delay: 0.18s;
    }

    .business-home__capital-card:nth-child(3) {
        transition-delay: 0.24s;
    }

    .business-home__capital-card:nth-child(2) {
        transition-delay: 0.3s;
    }

    .business-home__capital-card:nth-child(1) {
        transition-delay: 0.36s;
    }

    .business-home__capital-letter {
        color: transparent;
        -webkit-text-stroke: 1px rgba(251, 191, 36, 0.9);
        font-family: var(--header);
        font-size: clamp(3.6rem, 6vw, 5.35rem);
        font-weight: 900;
        line-height: 0.9;
        text-align: center;
        text-shadow: 0 0 24px rgba(251, 191, 36, 0.28);
        transition: color 0.25s ease, text-shadow 0.25s ease, -webkit-text-stroke-color 0.25s ease;
    }

    .business-home__capital-card:hover .business-home__capital-letter {
        color: rgba(251, 191, 36, 0.12);
        -webkit-text-stroke-color: #fbbf24;
        text-shadow: 0 0 18px rgba(251, 191, 36, 0.52), 0 0 42px rgba(251, 191, 36, 0.24);
    }

    .business-home__capital-label {
        color: rgba(255, 255, 255, 0.76);
        font-size: 0.82rem;
        font-weight: 700;
        line-height: 1.35;
        text-align: center;
    }

    .business-home__capital-copy {
        margin-top: 30px;
        color: var(--business-muted);
        font-size: 1.03rem;
        line-height: 1.68;
    }

    .business-home__feature-row .business-home__card {
        min-height: 220px;
        opacity: 0;
        transform: translateX(34px);
    }

    .business-home__feature-row.is-visible .business-home__feature-visual,
    .business-home__feature-row.is-visible .business-home__card {
        opacity: 1;
        transform: translateX(0);
    }

    .business-home__feature-row.is-visible .business-home__card {
        transition-delay: 0.12s;
    }

    .business-home__eyebrow {
        display: inline-flex;
        align-items: center;
        width: fit-content;
        min-height: 32px;
        padding: 0.36rem 0.7rem;
        border: 1px solid rgba(251, 191, 36, 0.35);
        border-radius: 8px;
        background: rgba(251, 191, 36, 0.08);
        color: #fde68a;
        font-size: 0.86rem;
        font-weight: 800;
    }

    .business-home__hero h2 {
        max-width: 760px;
        margin: 22px 0 18px;
        color: #fff;
        font-size: clamp(2.15rem, 4.8vw, 4.3rem);
        line-height: 1.04;
        font-weight: 900;
    }

    .business-home__lead {
        max-width: 720px;
        margin: 0;
        color: rgba(255, 255, 255, 0.84);
        font-size: 1.13rem;
        line-height: 1.7;
    }

    .business-home__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 28px;
    }

    .business-home__button,
    .business-home__button-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: 0.82rem 1.2rem;
        border-radius: 8px;
        font-weight: 850;
        text-decoration: none;
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .business-home__button {
        border: 1px solid rgba(251, 191, 36, 0.42);
        background: linear-gradient(135deg, var(--business-accent), var(--business-accent-dark));
        color: #111827;
        box-shadow: 0 18px 34px rgba(245, 158, 11, 0.2);
    }

    .business-home__button-secondary {
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
    }

    .business-home__button:hover,
    .business-home__button-secondary:hover {
        transform: translateY(-2px);
    }

    .business-home__visual {
        align-self: end;
        display: grid;
        gap: 14px;
        padding: 22px;
        border: 1px solid rgba(255, 255, 255, 0.13);
        border-radius: 8px;
        background: rgba(3, 7, 12, 0.66);
        backdrop-filter: blur(14px);
    }

    .business-home__metric {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        align-items: center;
        padding: 14px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.05);
    }

    .business-home__metric span {
        color: var(--business-muted);
        font-size: 0.9rem;
    }

    .business-home__metric strong {
        color: #fff;
        font-size: 1.2rem;
    }

    .business-home__metric b {
        color: var(--business-green);
        font-size: 0.92rem;
    }

    .business-home__section {
        margin-top: 56px;
        padding: 42px;
        border: 1px solid var(--business-border);
        border-radius: 8px;
        background: var(--business-panel);
        backdrop-filter: blur(18px);
    }

    .business-home__section-header {
        display: grid;
        grid-template-columns: minmax(0, 0.85fr) minmax(260px, 1fr);
        gap: 38px;
        align-items: start;
        margin-bottom: 36px;
    }

    .business-home__section h3 {
        margin: 0;
        color: #fff;
        font-size: clamp(1.55rem, 2.5vw, 2.35rem);
        line-height: 1.16;
        font-weight: 900;
    }

    .business-home__section-header p,
    .business-home__copy {
        margin: 0;
        color: var(--business-muted);
        font-size: 1.03rem;
        line-height: 1.68;
    }

    .business-home__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 22px;
    }

    .business-home__card {
        min-height: 220px;
        padding: 20px;
        border: 1px solid rgba(255, 255, 255, 0.11);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.045);
        transition: transform 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .business-home__card:hover {
        transform: translateY(-4px);
        border-color: rgba(251, 191, 36, 0.38);
        background: rgba(255, 255, 255, 0.07);
    }

    .business-home__card-marker {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        margin-bottom: 15px;
        border-radius: 8px;
        background: rgba(251, 191, 36, 0.12);
        color: var(--business-accent);
        font-weight: 900;
    }

    .business-home__card h4 {
        margin: 0 0 10px;
        color: #fff;
        font-size: 1.08rem;
        font-weight: 850;
    }

    .business-home__card p,
    .business-home__card li {
        color: var(--business-muted);
        font-size: 0.96rem;
        line-height: 1.6;
    }

    .business-home__card ul,
    .business-home__pilot-list {
        display: grid;
        gap: 10px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .business-home__card li,
    .business-home__pilot-list li {
        position: relative;
        padding-left: 22px;
    }

    .business-home__card li::before,
    .business-home__pilot-list li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0.65em;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--business-accent);
    }

    .business-home__split {
        display: grid;
        grid-template-columns: minmax(0, 0.95fr) minmax(320px, 1.05fr);
        gap: 28px;
        align-items: stretch;
    }

    .business-home__callout {
        padding: 26px;
        border: 1px solid rgba(56, 189, 248, 0.24);
        border-radius: 8px;
        background: linear-gradient(135deg, rgba(56, 189, 248, 0.1), rgba(255, 255, 255, 0.045));
    }

    .business-home__callout strong {
        color: #fff;
    }

    .business-home__report-list {
        display: grid;
        grid-template-columns: 1fr;
        gap: 18px;
    }

    .business-home__report-list .business-home__card {
        min-height: 0;
    }

    .business-home__pilot {
        display: grid;
        grid-template-columns: minmax(240px, 0.78fr) minmax(0, 1fr) auto;
        gap: 36px;
        align-items: center;
        padding: 42px;
        border: 1px solid rgba(251, 191, 36, 0.28);
        border-radius: 8px;
        background:
            linear-gradient(135deg, rgba(251, 191, 36, 0.11), rgba(56, 189, 248, 0.07)),
            var(--business-panel-strong);
    }

    .business-home__pilot-image {
        min-height: 300px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 8px;
        background:
            linear-gradient(180deg, rgba(7, 11, 16, 0.12), rgba(7, 11, 16, 0.42)),
            url("{{ asset('images/business_partnership_handshake_7256363.jpeg') }}") center/cover no-repeat;
        box-shadow: 0 22px 48px rgba(0, 0, 0, 0.28);
    }

    .business-home__pilot h3 {
        margin-bottom: 12px;
    }

    .business-home__pilot-list {
        margin-top: 18px;
        gap: 14px;
    }

    .business-home__pilot-list li {
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.6;
    }

    .business-home__pilot-action {
        display: grid;
        gap: 12px;
        justify-items: end;
        min-width: 230px;
    }

    .business-home__pilot-note {
        color: rgba(255, 255, 255, 0.66);
        font-size: 0.9rem;
        text-align: right;
    }

    @media (max-width: 991.98px) {
        .business-home__hero,
        .business-home__section-header,
        .business-home__split,
        .business-home__feature-row,
        .business-home__pilot {
            grid-template-columns: 1fr;
        }

        .business-home__hero {
            padding: 30px;
            background:
                linear-gradient(180deg, rgba(7, 11, 16, 0.96), rgba(7, 11, 16, 0.7)),
                url("{{ asset('images/micro_business_hero_1776457020084.png') }}") center/cover no-repeat;
        }

        .business-home__grid {
            grid-template-columns: 1fr;
        }

        .business-home__capital-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .business-home__feature-visual,
        .business-home__feature-row .business-home__card {
            transform: translateY(24px);
        }

        .business-home__feature-row.is-visible .business-home__feature-visual,
        .business-home__feature-row.is-visible .business-home__card {
            transform: translateY(0);
        }

        .business-home__pilot-action {
            justify-items: start;
        }

        .business-home__pilot-note {
            text-align: left;
        }

        .business-home__pilot-image {
            min-height: 260px;
        }
    }

    @media (max-width: 640px) {
        .business-home__hero,
        .business-home__section,
        .business-home__pilot {
            padding: 22px;
        }

        .business-home__section {
            margin-top: 34px;
        }

        .business-home__hero {
            min-height: auto;
        }

        .business-home__actions,
        .business-home__button,
        .business-home__button-secondary {
            width: 100%;
        }

        .business-home__capital-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .business-home__reveal,
        .business-home__feature-visual,
        .business-home__feature-row .business-home__card,
        .business-home__capital-card {
            opacity: 1;
            transform: none;
            transition: none;
        }
    }
</style>

<main class="business-home">
    <section class="business-home__hero business-home__reveal" aria-label="Платформа управленческого учета AV8 Capital">
        <div>
            <div class="business-home__eyebrow">Управленческий учет для украинского бизнеса</div>
            <h2>Перестаньте управлять финансами на глаз</h2>
            <p class="business-home__lead">
                В условиях постоянной неопределенности, логистических вызовов и сжавшейся маржинальности интуитивное управление быстро приводит к кассовым разрывам. av8capital.space собирает финансы, склад, продажи и аналитику в единую систему, где решения принимаются по цифрам.
            </p>
            <div class="business-home__actions">
                <a href="{{ route('price') }}" class="business-home__button">Оставить заявку</a>
                <a href="{{ route('about') }}" class="business-home__button-secondary">Посмотреть экосистему</a>
            </div>
        </div>

        <aside class="business-home__visual" aria-label="Ключевые финансовые показатели">
            <div class="business-home__metric">
                <div>
                    <span>Маржинальность</span>
                    <strong>по каждой единице</strong>
                </div>
                <b>unit</b>
            </div>
            <div class="business-home__metric">
                <div>
                    <span>Cash Flow</span>
                    <strong>без кассовых разрывов</strong>
                </div>
                <b>live</b>
            </div>
            <div class="business-home__metric">
                <div>
                    <span>Отчетность</span>
                    <strong>для банка и инвестора</strong>
                </div>
                <b>ready</b>
            </div>
        </aside>
    </section>

    <section class="business-home__section business-home__reveal">
        <div class="business-home__section-header">
            <h3>Главная боль современного бизнеса в Украине</h3>
            <p>
                Предприниматели тратят часы на сведение разрозненных таблиц Excel, но в критический момент им не хватает трех вещей, которые напрямую влияют на выживаемость и рост.
            </p>
        </div>

        <div class="business-home__grid">
            <article class="business-home__card business-home__reveal">
                <div class="business-home__card-marker">01</div>
                <h4>Реальная маржинальность</h4>
                <p>Четкое понимание прибыльности каждой единицы товара, услуги, направления или проекта вместо усредненных оценок.</p>
            </article>
            <article class="business-home__card business-home__reveal">
                <div class="business-home__card-marker">02</div>
                <h4>Прозрачный Cash Flow</h4>
                <p>Отчет о движении денежных средств, который показывает будущие разрывы до того, как они станут операционной проблемой.</p>
            </article>
            <article class="business-home__card business-home__reveal">
                <div class="business-home__card-marker">03</div>
                <h4>Профессиональная отчетность</h4>
                <p>Финансовая история, которую можно показать инвестору, партнеру или банку при обсуждении финансирования.</p>
            </article>
        </div>
    </section>

    <section class="business-home__section business-home__reveal">
        <div class="business-home__section-header">
            <h3>Что дает платформа</h3>
            <p>
                AV8 Capital — облачная система управленческого учета и финансового моделирования, созданная практиками для реального бизнеса: автоэкосистем, e-commerce, услуг и партнерских проектов.
            </p>
        </div>

        <div class="business-home__feature-list">
            <div class="business-home__feature-row business-home__reveal">
                <div class="business-home__feature-visual" aria-hidden="true">
                    <span class="business-home__feature-letter">A</span>
                </div>
                <article class="business-home__card">
                    <h4>Управленческий баланс за пару кликов</h4>
                    <p>Доходы, расходы, активы и обязательства остаются под контролем в реальном времени.</p>
                </article>
            </div>

            <div class="business-home__feature-row business-home__reveal">
                <div class="business-home__feature-visual" aria-hidden="true">
                    <span class="business-home__feature-letter">V</span>
                </div>
                <article class="business-home__card">
                    <h4>Контроль маржи и юнит-экономики</h4>
                    <p>Вы видите, какие направления приносят прибыль, а какие сжигают деньги и требуют пересмотра.</p>
                </article>
            </div>

            <div class="business-home__feature-row business-home__reveal">
                <div class="business-home__feature-visual" aria-hidden="true">
                    <span class="business-home__feature-letter">8</span>
                </div>
                <article class="business-home__card">
                    <h4>Инвесторские отчеты автоматически</h4>
                    <p>Операционные результаты упаковываются в стандартизированные метрики, понятные фондам и крупным игрокам.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="business-home__section business-home__reveal">
        <div class="business-home__section-header">
            <h3>Трансформируйте отчеты в</h3>
        </div>

        <div class="business-home__capital-grid" aria-label="Капитал через финансовую отчетность">
            <article class="business-home__capital-card business-home__reveal">
                <div class="business-home__capital-letter">К</div>
                <div class="business-home__capital-label">Финансовый план</div>
            </article>
            <article class="business-home__capital-card business-home__reveal">
                <div class="business-home__capital-letter">А</div>
                <div class="business-home__capital-label">P&amp;L и Cash Flow</div>
            </article>
            <article class="business-home__capital-card business-home__reveal">
                <div class="business-home__capital-letter">П</div>
                <div class="business-home__capital-label">Баланс</div>
            </article>
            <article class="business-home__capital-card business-home__reveal">
                <div class="business-home__capital-letter">И</div>
                <div class="business-home__capital-label">ROI, ROE Показатели эффективности</div>
            </article>
            <article class="business-home__capital-card business-home__reveal">
                <div class="business-home__capital-letter">Т</div>
                <div class="business-home__capital-label">EBITDA</div>
            </article>
            <article class="business-home__capital-card business-home__reveal">
                <div class="business-home__capital-letter">А</div>
                <div class="business-home__capital-label">Доверие</div>
            </article>
            <article class="business-home__capital-card business-home__reveal">
                <div class="business-home__capital-letter">Л</div>
                <div class="business-home__capital-label">Зрелость бизнеса</div>
            </article>
        </div>

        <p class="business-home__capital-copy">
            Инвесторы не верят словам — они верят цифрам. Когда бизнес идет за инвестициями, кредитным лимитом или партнерством, обычные таблицы вызывают скепсис. Профессиональная финансовая история снимает большую часть вопросов уже на первой встрече.
        </p>
    </section>

    <section class="business-home__section business-home__pilot business-home__reveal" aria-label="Пилотное внедрение">
        <div class="business-home__pilot-image" role="img" aria-label="Бизнес-партнеры пожимают друг другу руки"></div>
        <div>
            <div class="business-home__eyebrow">Специальное предложение</div>
            <h3>Первая группа партнерских бизнесов</h3>
            <p class="business-home__copy">
                Мы формируем пилотную группу внедрения системы учета на платформе AV8 Capital.
            </p>
            <ul class="business-home__pilot-list">
                <li>Поможем настроить категории товаров под специфику вашего бизнеса.</li>
                <li>Проведем экспресс-аудит финансовой модели.</li>
                <li>Подготовим вас к презентации для инвесторов или фондов.</li>
            </ul>
        </div>
        <div class="business-home__pilot-action">
            <a href="{{ route('price') }}" class="business-home__button">Навести порядок в финансах</a>
            <span class="business-home__pilot-note">Заявка открывает доступ к обсуждению пилотного внедрения.</span>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const revealItems = document.querySelectorAll('.business-home__reveal');

        if (!revealItems.length) {
            return;
        }

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (reducedMotion || !('IntersectionObserver' in window)) {
            revealItems.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.16,
            rootMargin: '0px 0px -8% 0px',
        });

        revealItems.forEach((item) => observer.observe(item));
    });
</script>
@endpush
