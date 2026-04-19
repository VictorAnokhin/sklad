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
</style>

<div class="glass-card animated-card" style="padding: 2.5rem; max-width: 1000px; margin: 0 auto; border-radius: 16px;">
    <img src="{{ asset('images/about_hero_1776457354755.png') }}" class="hero-image" alt="About AV8">

    <h2 class="mb-4" style="color: #fbbf24; font-weight: 700; border-bottom: 1px solid rgba(251, 191, 36, 0.2); padding-bottom: 1rem;">
        О платформе AV8 Capital
    </h2>
    
    <p class="mb-5 text-white" style="font-size: 1.15rem; line-height: 1.6; opacity: 0.9;">
        AV8 Capital — это больше, чем инструмент для ведения бизнеса или удобный кошелек. Это комплексная экосистема, объединяющая передовые финансовые технологии, профессиональный управленческий учет и реальные инструменты для заработка на глобальных рынках.
    </p>

    <div class="row mt-4">
        <div class="col-12 mb-4">
            <div class="hover-feature">
                <h4 style="color: #fff; font-size: 1.3rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="color: #fbbf24; display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #fbbf24; box-shadow: 0 0 10px #fbbf24;"></span>
                    Управленческий и финансовый учет
                </h4>
                <p style="color: rgba(255, 255, 255, 0.75); font-size: 1.05rem; line-height: 1.7;">
                    Мы предоставляем полноформатную ERP-инфраструктуру для прозрачного учета всех процессов вашей деятельности. Вы получаете возможность детально анализировать финансовые потоки, контролировать кассы и расчетные счета, формировать отчеты о прибылях и убытках (P&L, Cash Flow), балансовые отчеты и глубокую аналитику. Встроенный учет товаров автоматически связывает закупки и продажи, исключая любые расхождения в документации.
                </p>
            </div>
        </div>

        <div class="col-12 mb-4">
            <div class="hover-feature">
                <h4 style="color: #fff; font-size: 1.3rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="color: #fbbf24; display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #fbbf24; box-shadow: 0 0 10px #fbbf24;"></span>
                    Маркетплейс и электронная коммерция
                </h4>
                <p style="color: rgba(255, 255, 255, 0.75); font-size: 1.05rem; line-height: 1.7;">
                    Развивайте новые каналы сбыта: инструменты системы позволяют легко выгружать и размещать вашу продукцию и услуги для онлайн-продаж. Вы можете выставлять свои товары на нашем централизованном маркетплейсе, повышая узнаваемость бренда, или бесшовно интегрировать ассортимент на свой личный сайт. При этом управление ценами, остатками и статусами заказов происходит в едином удобном окне кабинета.
                </p>
            </div>
        </div>

        <div class="col-12 mb-2">
            <div class="hover-feature">
                <h4 style="color: #fff; font-size: 1.3rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="color: #fbbf24; display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #fbbf24; box-shadow: 0 0 10px #fbbf24;"></span>
                    Инвестиционная деятельность
                </h4>
                <p style="color: rgba(255, 255, 255, 0.75); font-size: 1.05rem; line-height: 1.7;">
                    Капитализируйте свои свободные средства. С помощью платформы пользователи получают прямой доступ к уникальным инвестиционным продуктам. Мы предлагаем возможность участвовать вместе с инвестиционными пулами нашей компании в профильных сделках и получать доход на традиционном фондовом рынке, а также на высоколиквидном рынке криптовалют. Интеграция Web3-кошельков гарантирует абсолютную безопасность и прозрачность начислений.
                </p>
            </div>
        </div>
    </div>

    @if(isset($projects) && $projects->isNotEmpty())
    <div class="row mt-5 pt-4" style="border-top: 1px solid rgba(255,255,255,0.05);">
        <div class="col-12 mb-4">
            <h3 class="mb-4 text-center" style="color: #fbbf24; font-weight: 700;">Проекты</h3>
        </div>
        
        @foreach($projects as $project)
        <div class="col-md-6 mb-4">
            <div class="hover-feature" style="background: rgba(255,255,255,0.02); height: 100%;">
                <h4 style="color: #fff; font-size: 1.25rem; margin-bottom: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span style="color: #fbbf24; display: inline-block; width: 6px; height: 6px; border-radius: 50%; background-color: #fbbf24;"></span>
                    {{ $project->name }}
                </h4>
                @if($project->description)
                <p style="color: rgba(255, 255, 255, 0.65); font-size: 0.95rem; line-height: 1.6; margin-bottom: 0;">
                    {!! nl2br(e($project->description)) !!}
                </p>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<div style="margin-top: 3rem;">
    @include('pages.partials.wallet_content')
</div>
@endsection
