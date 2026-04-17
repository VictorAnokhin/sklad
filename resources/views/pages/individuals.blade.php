@extends('home')

@section('title')
Для физических лиц
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
        background: linear-gradient(180deg, rgba(255,255,255,0.02) 0%, rgba(255,255,255,0) 100%);
    }
    .hover-feature:hover {
        transform: translateY(-5px) scale(1.02);
        background: rgba(255,255,255,0.04);
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
        animation: floatImg 6s ease-in-out infinite;
    }
    @keyframes floatImg {
        0% { transform: translateY(0px); box-shadow: 0 15px 35px rgba(251, 191, 36, 0.1); }
        50% { transform: translateY(-10px); box-shadow: 0 25px 45px rgba(251, 191, 36, 0.2); }
        100% { transform: translateY(0px); box-shadow: 0 15px 35px rgba(251, 191, 36, 0.1); }
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
    <img src="{{ asset('images/individuals_hero_1776457220958.png') }}" class="hero-image" alt="Individuals">

    <h2 class="mb-4" style="color: #fbbf24; font-weight: 700; border-bottom: 1px solid rgba(251, 191, 36, 0.2); padding-bottom: 1rem;">AV8 для частных инвесторов и клиентов</h2>
    
    <p class="mb-4 text-white" style="font-size: 1.15rem; line-height: 1.6; opacity: 0.9;">
        Откройте новые возможности контроля личных финансов и прозрачных операций с помощью передовых технологий. Вы получаете безопасную и интуитивно понятную инфраструктуру для управления своими активами.
    </p>

    <div class="row mt-5">
        <div class="col-md-6 mb-4">
            <div class="hover-feature">
                <h4 class="text-white mb-3" style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #fbbf24; font-size: 1.5rem;">⌬</span> Web3 Интеграция
                </h4>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; line-height: 1.5;">Подключайте свой EVM-кошелек (например, MetaMask) для безопасной и быстрой авторизации в один клик. Передовая криптографическая защита доступа.</p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="hover-feature">
                <h4 class="text-white mb-3" style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #fbbf24; font-size: 1.5rem;">⌬</span> Депозиты и кошельки
                </h4>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; line-height: 1.5;">Отслеживайте историю операций и легко управляйте своими депозитами в личном кабинете. Абсолютная прозрачность каждого движения средств.</p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="hover-feature">
                <h4 class="text-white mb-3" style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #fbbf24; font-size: 1.5rem;">⌬</span> Комфортный заказ
                </h4>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; line-height: 1.5;">Оформляйте заказы как частный клиент, отслеживайте статусы доставки в реальном времени и получайте сервис премиум-уровня.</p>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="hover-feature">
                <h4 class="text-white mb-3" style="display: flex; align-items: center; gap: 8px;">
                    <span style="color: #fbbf24; font-size: 1.5rem;">⌬</span> Приватность
                </h4>
                <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; line-height: 1.5;">Ваши данные надежно защищены. Мы используем современные стандарты шифрования для обеспечения сохранности личной и финансовой информации.</p>
            </div>
        </div>
    </div>

    <div class="mt-4 pt-4 text-center" style="border-top: 1px solid rgba(255,255,255,0.05);">
        <a href="{{ route('register') }}" class="btn btn-animated" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #111; font-weight: 700; font-size: 1.1rem; padding: 0.85rem 3rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.2);">Создать аккаунт</a>
    </div>
</div>
@endsection
