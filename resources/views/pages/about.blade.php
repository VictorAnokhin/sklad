@extends('home')

@section('title')
О проекте
@endsection

@section('content')
<style>
    .animated-card {
        animation: fadeInScale 0.7s ease-out forwards;
    }
    .hover-feature {
        transition: all 0.3s ease;
        padding: 1.5rem;
        border-radius: 12px;
        border: 1px solid transparent;
        background: rgba(255,255,255,0.01);
    }
    .hover-feature:hover {
        transform: translateX(10px);
        background: rgba(255,255,255,0.03);
        border-left: 4px solid #fbbf24;
    }
    .about-step-card {
        position: relative;
        display: grid;
        grid-template-columns: 110px minmax(0, 1fr);
        gap: 1.5rem;
        align-items: start;
        overflow: hidden;
    }
    .about-step-card:hover {
        transform: none;
        border-left: 1px solid rgba(251, 191, 36, 0.35);
    }
    .about-step-number {
        font-size: clamp(4.5rem, 11vw, 7.5rem);
        line-height: 0.86;
        font-weight: 900;
        letter-spacing: -0.08em;
        color: transparent;
        -webkit-text-stroke: 1px rgba(251, 191, 36, 0.85);
        text-shadow: 0 0 28px rgba(251, 191, 36, 0.26);
        transition: color 0.25s ease, text-shadow 0.25s ease, -webkit-text-stroke-color 0.25s ease;
    }
    .about-step-card:hover .about-step-number {
        color: rgba(251, 191, 36, 0.12);
        -webkit-text-stroke-color: #fbbf24;
        text-shadow: 0 0 18px rgba(251, 191, 36, 0.52), 0 0 46px rgba(251, 191, 36, 0.24);
    }
    .about-step-content {
        position: relative;
        z-index: 1;
    }
    .about-step-link {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin-top: 1rem;
        color: #fbbf24;
        font-weight: 700;
        text-decoration: none;
    }
    .about-step-link:hover {
        color: #fde68a;
    }
    @media (max-width: 640px) {
        .about-step-card {
            grid-template-columns: 1fr;
            gap: 0.8rem;
        }
        .about-step-number {
            font-size: 4rem;
        }
    }
    @keyframes fadeInScale {
        from { opacity: 0; transform: scale(0.97) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .hero-image {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        border: 1px solid rgba(255,255,255,0.05);
        animation: glowPulse 4s infinite alternate;
    }
    @keyframes glowPulse {
        from { box-shadow: 0 15px 35px rgba(251, 191, 36, 0.05); }
        to { box-shadow: 0 15px 45px rgba(251, 191, 36, 0.2); }
    }
    .about-project-segment {
        margin-bottom: 2rem;
    }
    .about-project-segment__title {
        color: #fbbf24;
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: 1rem;
    }
    .about-project-links {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }
    .about-project-link {
        display: flex;
        align-items: center;
        min-height: 46px;
        padding: 0.75rem 0.9rem;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 8px;
        background: rgba(255,255,255,0.025);
        color: #fff;
        font-weight: 700;
        text-decoration: none;
    }
    .about-project-link:hover {
        border-color: rgba(251, 191, 36, 0.42);
        color: #fde68a;
        background: rgba(251, 191, 36, 0.06);
    }
    @media (max-width: 640px) {
        .about-project-links {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="glass-card animated-card" style="padding: 2.5rem; max-width: 1000px; margin: 0 auto; border-radius: 16px;">
    <img src="{{ asset('images/about_hero_1776457354755.png') }}" class="hero-image" alt="About AV8">

    <p class="mb-5 text-white" style="font-size: 1.15rem; line-height: 1.6; opacity: 0.9;">
        AV8 Capital — это больше, чем инструмент для ведения бизнеса или удобный кошелек. Это комплексная экосистема, объединяющая передовые финансовые технологии, профессиональный управленческий учет и реальные инструменты для заработка на глобальных рынках.
    </p>

    <div class="row mt-4">
        <div class="col-12 mb-4">
            <div class="hover-feature about-step-card">
                <div class="about-step-number">01</div>
                <div class="about-step-content">
                    <h4 style="color: #fff; font-size: 1.3rem; margin-bottom: 1rem;">Начать с тестирования и обучения</h4>
                    <p style="color: rgba(255, 255, 255, 0.75); font-size: 1.05rem; line-height: 1.7;">
                        Первый шаг — понять себя, свои сильные стороны, отношение к риску и готовность принимать финансовые решения. После тестирования пользователь может перейти к обучению в Академии AV8, закрыть пробелы в знаниях и двигаться дальше уже осознанно.
                    </p>
                    <a href="https://av8.fund/know-yourself" target="_blank" rel="noreferrer" class="about-step-link">Перейти к тестам и обучению →</a>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="hover-feature about-step-card">
                <div class="about-step-number">02</div>
                <div class="about-step-content">
                    <h4 style="color: #fff; font-size: 1.3rem; margin-bottom: 1rem;">Управленческий и финансовый учет</h4>
                    <p style="color: rgba(255, 255, 255, 0.75); font-size: 1.05rem; line-height: 1.7;">
                        Мы предоставляем <a href="https://av8capital.space" target="_blank" rel="noreferrer" class="about-step-link" style="margin-top:0;">ERP-инфраструктуру</a> для прозрачного учета всех процессов деятельности. Вы получаете детальный анализ финансовых потоков, контроль касс и расчетных счетов, отчеты P&L, Cash Flow, баланс, складской учет и аналитику для решений без догадок.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="hover-feature about-step-card">
                <div class="about-step-number">03</div>
                <div class="about-step-content">
                    <h4 style="color: #fff; font-size: 1.3rem; margin-bottom: 1rem;">Маркетплейс и электронная коммерция</h4>
                    <p style="color: rgba(255, 255, 255, 0.75); font-size: 1.05rem; line-height: 1.7;">
                        Развивайте каналы сбыта: инструменты системы позволяют размещать продукцию и услуги для онлайн-продаж, управлять ассортиментом, ценами, остатками и заказами в одном окне. <a href="https://autoagent.in.ua" target="_blank" rel="noreferrer" class="about-step-link" style="margin-top:0;">Маркетплейс</a> помогает проверить спрос и вывести бизнес к реальным клиентам.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-12 mb-2">
            <div class="hover-feature about-step-card">
                <div class="about-step-number">04</div>
                <div class="about-step-content">
                    <h4 style="color: #fff; font-size: 1.3rem; margin-bottom: 1rem;">Инвестиционная деятельность</h4>
                    <p style="color: rgba(255, 255, 255, 0.75); font-size: 1.05rem; line-height: 1.7;">
                        Когда учет и продажи дают понятный денежный поток, свободные средства можно превращать в капитал. <a href="https://av8.fund" target="_blank" rel="noreferrer" class="about-step-link" style="margin-top:0;">Наш банк</a> открывает доступ к инвестиционным направлениям, пулам, цифровым активам и прозрачной логике участия, где пользователь видит правила, риски и движение средств.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if(isset($projects) && $projects->isNotEmpty())
    <div class="row mt-5 pt-4" style="border-top: 1px solid rgba(255,255,255,0.05);">
        <div class="col-12 mb-4">
            <h3 class="mb-4 text-center" style="color: #fbbf24; font-weight: 700;">Проекты</h3>
        </div>

        @foreach($projects as $segment => $segmentProjects)
        <div class="col-12 about-project-segment">
            <h4 class="about-project-segment__title">{{ $segment }}</h4>
            <div class="about-project-links">
                @foreach($segmentProjects as $project)
                    @php
                        $projectUrl = trim((string) ($project->url ?? ''));
                        $projectHref = $projectUrl !== '' && !preg_match('/^https?:\/\//i', $projectUrl)
                            ? 'https://' . $projectUrl
                            : $projectUrl;
                    @endphp
                    @if($projectHref !== '')
                    <a href="{{ $projectHref }}" target="_blank" rel="noreferrer" class="about-project-link">{{ $project->name }}</a>
                    @endif
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
