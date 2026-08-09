@extends('home')

@section('title')
Обучение
@endsection

@section('content')
<style>
    .manual-page {
        max-width: 1080px;
        margin: 0 auto;
    }

    .manual-hero {
        padding: 2.5rem;
        border-radius: 16px;
        border: 1px solid rgba(251, 191, 36, 0.18);
        background:
            linear-gradient(135deg, rgba(251, 191, 36, 0.12), rgba(15, 23, 42, 0.12)),
            rgba(255, 255, 255, 0.035);
        box-shadow: 0 18px 42px rgba(0, 0, 0, 0.28);
        animation: manualFadeIn 0.65s ease-out both;
    }

    .manual-eyebrow {
        color: #fbbf24;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        margin-bottom: 0.8rem;
    }

    .manual-hero h2 {
        color: #fff;
        font-weight: 800;
        margin: 0 0 1rem;
        line-height: 1.12;
    }

    .manual-hero p {
        max-width: 820px;
        color: rgba(255, 255, 255, 0.78);
        font-size: 1.06rem;
        line-height: 1.7;
        margin: 0;
    }

    .manual-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    .manual-card {
        display: flex;
        flex-direction: column;
        min-height: 100%;
        padding: 1.25rem;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.03);
        transition: transform 0.22s ease, border-color 0.22s ease, background 0.22s ease, box-shadow 0.22s ease;
    }

    .manual-card:hover {
        transform: translateY(-4px);
        border-color: rgba(251, 191, 36, 0.34);
        background: rgba(251, 191, 36, 0.07);
        box-shadow: 0 14px 30px rgba(251, 191, 36, 0.12);
    }

    .manual-card__num {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        border-radius: 10px;
        border: 1px solid rgba(251, 191, 36, 0.32);
        color: #fbbf24;
        font-weight: 900;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    }

    .manual-card h3 {
        color: #fff;
        font-size: 1.05rem;
        font-weight: 800;
        margin: 0 0 0.65rem;
    }

    .manual-card p {
        color: rgba(255, 255, 255, 0.68);
        font-size: 0.92rem;
        line-height: 1.55;
        margin: 0;
    }

    .manual-section {
        margin-top: 1.5rem;
        padding: 1.5rem;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.025);
    }

    .manual-section h3 {
        color: #fbbf24;
        font-weight: 800;
        margin: 0 0 1rem;
    }

    .manual-list {
        display: grid;
        gap: 0.75rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .manual-list li {
        padding: 0.85rem 1rem;
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.42);
        color: rgba(255, 255, 255, 0.74);
        line-height: 1.55;
    }

    .manual-list strong {
        color: #fff;
    }

    .manual-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1.4rem;
    }

    .manual-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0.75rem 1.2rem;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 800;
    }

    .manual-button--primary {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: #111827;
        box-shadow: 0 10px 24px rgba(245, 158, 11, 0.24);
    }

    .manual-button--secondary {
        border: 1px solid rgba(251, 191, 36, 0.28);
        color: #fbbf24;
        background: rgba(251, 191, 36, 0.08);
    }

    @keyframes manualFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 900px) {
        .manual-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .manual-hero,
        .manual-section {
            padding: 1.15rem;
        }

        .manual-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="manual-page">
    <section class="manual-hero">
        <div class="manual-eyebrow">Справочник AV8</div>
        <h2>Как пользоваться системой</h2>
        <p>
            Этот раздел помогает быстро разобраться в основных рабочих сценариях: от входа и выбора проекта
            до документов, клиентов, товаров, финансов, отчетов и инвестиционного блока.
        </p>
        <div class="manual-actions">
            <a href="{{ route('login') }}" class="manual-button manual-button--primary">Войти в систему</a>
            <a href="{{ route('about') }}" class="manual-button manual-button--secondary">Посмотреть путь проекта</a>
        </div>
    </section>

    <div class="manual-grid">
        @foreach([
            ['01', 'Вход и проект', 'Авторизуйтесь, выберите активный проект в верхней панели и проверьте, что работаете в нужном контуре.'],
            ['02', 'Документы', 'Создавайте продажи, закупки, производства и другие операции через разделы документов. Статусы помогают видеть этап обработки.'],
            ['03', 'Клиенты', 'Ведите базу клиентов, группы, контакты, заказы и историю взаимодействий в едином профиле.'],
            ['04', 'Товары и склад', 'Настраивайте номенклатуру, остатки, категории и складские движения, чтобы видеть актуальную картину бизнеса.'],
            ['05', 'Деньги', 'Контролируйте кассы, счета, переводы, депозиты и движение средств по проектам.'],
            ['06', 'Отчеты', 'Используйте отчеты для анализа продаж, запасов, прибыли, денежных потоков и планирования.'],
            ['07', 'Банк и инвестиции', 'Для банковских проектов доступны депозиты, пулы, активы, акции, платежи, сверка и обмен фиат/крипта.'],
            ['08', 'Настройки', 'Настраивайте проект, пользователей, справочники, FAQ, SEO, модели и доступные разделы системы.'],
            ['09', 'Помощник AI', 'Используйте чат и базу знаний, чтобы быстрее находить нужные действия и получать подсказки по работе.'],
        ] as [$num, $title, $text])
            <article class="manual-card">
                <span class="manual-card__num">{{ $num }}</span>
                <h3>{{ $title }}</h3>
                <p>{{ $text }}</p>
            </article>
        @endforeach
    </div>

    <section class="manual-section">
        <h3>Рекомендуемый порядок освоения</h3>
        <ul class="manual-list">
            <li><strong>1. Начните с проекта.</strong> Проверьте выбранный проект и роль пользователя, потому что меню и доступы зависят от типа проекта.</li>
            <li><strong>2. Заполните справочники.</strong> Добавьте клиентов, товары, склады, кассы и счета, чтобы документы создавались быстрее.</li>
            <li><strong>3. Проведите первые операции.</strong> Создайте тестовую продажу или закупку, проверьте статусы и проводки.</li>
            <li><strong>4. Откройте отчеты.</strong> Сравните документы, движение денег, склад и финансовые показатели.</li>
            <li><strong>5. Настройте регулярную работу.</strong> Добавьте пользователей, категории, фильтры, шаблоны и параметры проекта.</li>
        </ul>
    </section>

    <section class="manual-section">
        <h3>Что стоит оформить дальше</h3>
        <ul class="manual-list">
            <li><strong>Пошаговые инструкции</strong> по каждому разделу: документы, клиенты, товары, финансы, банк, отчеты.</li>
            <li><strong>Скриншоты и примеры</strong> типовых операций: продажа, закупка, перевод, депозит, анализ акции.</li>
            <li><strong>Частые ошибки</strong> и способы их исправления: не выбран проект, нет прав, не заполнены справочники, не проведен документ.</li>
        </ul>
    </section>
</div>
@endsection
