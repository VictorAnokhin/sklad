@extends('home')

@section('title')
Для микро-бизнеса
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
        height: 100%;
    }
    .hover-feature:hover {
        transform: translateY(-5px) scale(1.02);
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(251, 191, 36, 0.25);
        box-shadow: 0 10px 30px rgba(251, 191, 36, 0.15);
    }
    @keyframes fadeInScale {
        from { opacity: 0; transform: scale(0.97) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .hero-image {
        width: 100%;
        max-height: 350px;
        object-fit: cover;
        border-radius: 12px;
        margin-bottom: 2rem;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        border: 1px solid rgba(255,255,255,0.05);
        animation: glowPulse 4s infinite alternate;
    }
    @keyframes glowPulse {
        from { box-shadow: 0 15px 35px rgba(251, 191, 36, 0.1); }
        to { box-shadow: 0 15px 45px rgba(251, 191, 36, 0.25); }
    }
    .btn-animated {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .btn-animated:hover {
        transform: scale(1.05) translateY(-2px);
        box-shadow: 0 15px 30px rgba(245, 158, 11, 0.4) !important;
    }
</style>

<div class="glass-card animated-card" style="padding: 2.5rem; max-width: 900px; margin: 0 auto; border-radius: 16px;">
    <img src="{{ asset('images/micro_business_hero_1776457020084.png') }}" class="hero-image" alt="Micro Business">

    <h2 class="mb-4" style="color: #fbbf24; font-weight: 700; border-bottom: 1px solid rgba(251, 191, 36, 0.2); padding-bottom: 1rem;">Современные решения для микро-бизнеса</h2>
    
    <p class="mb-4 text-white" style="font-size: 1.15rem; line-height: 1.6; opacity: 0.9;">
        Мы предлагаем комплексный набор инструментов, разработанный специально для удовлетворения потребностей малого и микро-бизнеса. Управляйте финансами, контролируйте запасы и автоматизируйте учет в едином удобном кабинете.
    </p>

    <div class="row mt-5">
        <div class="col-md-6 mb-4">
            <div class="hover-feature">
                <h4 class="text-white mb-3" style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #fbbf24; font-size: 1.5rem;">❖</span> Управление заказами
                </h4>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; line-height: 1.5;">Отслеживайте новые заявки, контролируйте статусы закупок и продаж. Автоматизируйте создание документов и снизьте рутину до минимума.</p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="hover-feature">
                <h4 class="text-white mb-3" style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #fbbf24; font-size: 1.5rem;">❖</span> Финансовый учет
                </h4>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; line-height: 1.5;">Следите за ежедневной выручкой, формируйте отчеты о прибылях и убытках, контролируйте кассы и расчетные счета прямо из дашборда.</p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="hover-feature">
                <h4 class="text-white mb-3" style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #fbbf24; font-size: 1.5rem;">❖</span> Склад и номенклатура
                </h4>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; line-height: 1.5;">Встроенная система учета товаров позволяет видеть актуальные остатки, управлять резервами и мгновенно реагировать на изменения спроса.</p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="hover-feature">
                <h4 class="text-white mb-3" style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #fbbf24; font-size: 1.5rem;">❖</span> Глубокая аналитика
                </h4>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; line-height: 1.5;">Получайте подробные ABC/XYZ отчеты, анализ рентабельности и другие профессиональные данные для принятия правильных бизнес-решений.</p>
            </div>
        </div>
    </div>

    <div class="mt-4 pt-4 text-center" style="border-top: 1px solid rgba(255,255,255,0.05);">
        <a href="{{ route('register') }}" class="btn btn-animated" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #111; font-weight: 700; font-size: 1.1rem; padding: 0.85rem 3rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.2);">Регистрация для бизнеса</a>
    </div>
</div>
@endsection
