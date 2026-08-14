@extends('home')

@section('title', 'Цены')

@section('content')
@php
    $purchasedPlans = collect($purchasedPlans ?? [])->map(fn ($plan) => (string) $plan)->all();
@endphp
<main class="pricing-page">
    <section class="pricing-plans" aria-label="Тарифы">
        @if(session('success'))
        <div class="pricing-alert pricing-alert--success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="pricing-alert pricing-alert--danger">{{ $errors->first() }}</div>
        @endif

        <div class="pricing-plans__grid" style="--pricing-columns: {{ max(1, min(count($plans), 4)) }};">
            @foreach($plans as $plan)
            <article class="pricing-card {{ !empty($plan['featured']) ? 'pricing-card--featured' : '' }}">
                <div>
                    <div class="pricing-card__topline">
                        <h2>{{ $plan['name'] ?? '' }}</h2>
                        @if(!empty($plan['featured']))
                        <span>Рекомендуем</span>
                        @endif
                    </div>
                    <p class="pricing-card__description">{{ $plan['subtitle'] ?? '' }}</p>
                    <div class="pricing-card__price">
                        <strong>{{ $plan['price'] ?? '' }}</strong>
                        <span>{{ $plan['period'] ?? 'в месяц' }}</span>
                    </div>
                </div>

                <div class="pricing-card__features">
                    {!! $plan['description'] ?? '' !!}
                </div>

                @if(auth()->check() && in_array((string) ($plan['id'] ?? ''), $purchasedPlans, true))
                    <button type="button" class="pricing-card__button pricing-card__button--muted" disabled>Используется</button>
                @else
                    <button
                        type="button"
                        class="pricing-card__button"
                        data-price-order-open
                        data-plan-id="{{ $plan['id'] ?? '' }}"
                        data-plan-name="{{ $plan['name'] ?? '' }}"
                        data-plan-price="{{ $plan['price'] ?? '' }}"
                    >Заказать</button>
                @endif
            </article>
            @endforeach
        </div>
    </section>

    <div class="pricing-order-modal" id="pricing-order-modal" aria-hidden="true">
        <div class="pricing-order-modal__backdrop" data-price-order-close></div>
        <div class="pricing-order-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="pricing-order-title">
            <button type="button" class="pricing-order-modal__close" aria-label="Закрыть" data-price-order-close>×</button>
            <h2 id="pricing-order-title">Заказать тариф</h2>
            <p class="pricing-order-modal__subtitle">
                Выбранный тариф: <strong id="pricing-order-plan-label"></strong>
            </p>

            <form action="{{ route('price.order') }}" method="POST" class="pricing-order-form">
                @csrf
                <input type="hidden" name="plan_id" id="pricing-order-plan-id-input" value="{{ old('plan_id') }}">
                <input type="hidden" name="plan" id="pricing-order-plan-input" value="{{ old('plan') }}">

                <div class="pricing-order-form__grid">
                    <label>
                        <span>Имя</span>
                        <input type="text" name="customer_name" value="{{ old('customer_name', auth()->check() ? trim(implode(' ', array_filter([auth()->user()->secondname ?? '', auth()->user()->name ?? '', auth()->user()->fathername ?? '']))) : '') }}" maxlength="120" {{ auth()->check() ? '' : 'required' }}>
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" maxlength="255" {{ auth()->check() ? '' : 'required' }}>
                    </label>
                    <label>
                        <span>Телефон</span>
                        <input type="text" name="customer_phone" value="{{ old('customer_phone', auth()->user()->phone ?? '') }}" maxlength="50">
                    </label>
                </div>

                <label>
                    <span>Комментарий</span>
                    <textarea name="customer_comment" rows="4" maxlength="1000">{{ old('customer_comment') }}</textarea>
                </label>

                <button type="submit" class="pricing-card__button">Отправить заявку</button>
            </form>
        </div>
    </div>
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

    .pricing-plans {
        width: min(1120px, 100%);
        margin: 0 auto;
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
        padding-top: 0;
    }

    .pricing-alert {
        margin-bottom: 16px;
        padding: 13px 16px;
        border-radius: 8px;
        font-weight: 700;
    }

    .pricing-alert--success {
        border: 1px solid rgba(34, 197, 94, 0.36);
        background: rgba(34, 197, 94, 0.14);
        color: #bbf7d0;
    }

    .pricing-alert--danger {
        border: 1px solid rgba(248, 113, 113, 0.42);
        background: rgba(248, 113, 113, 0.14);
        color: #fecaca;
    }

    .pricing-plans__grid {
        display: grid;
        grid-template-columns: repeat(var(--pricing-columns), minmax(0, 1fr));
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
        margin: 0;
        padding: 0;
        line-height: 1.45;
        white-space: pre-line;
    }

    .pricing-card__features ul,
    .pricing-card__features ol {
        display: grid;
        gap: 12px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .pricing-card__features p,
    .pricing-card__features div {
        margin: 0 0 12px;
    }

    .pricing-card__features p:last-child,
    .pricing-card__features div:last-child {
        margin-bottom: 0;
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

    .pricing-card__button--muted {
        border-color: rgba(148, 163, 184, 0.34);
        background: rgba(148, 163, 184, 0.18);
        color: rgba(248, 250, 252, 0.74);
        cursor: not-allowed;
    }

    .pricing-card__form {
        margin: 0;
    }

    .pricing-order-modal {
        position: fixed;
        inset: 0;
        z-index: 1100;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 18px;
    }

    .pricing-order-modal.is-open {
        display: flex;
    }

    .pricing-order-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.72);
        backdrop-filter: blur(8px);
    }

    .pricing-order-modal__dialog {
        position: relative;
        z-index: 1;
        width: min(620px, 100%);
        max-height: calc(100vh - 36px);
        overflow: auto;
        padding: 26px;
        border: 1px solid rgba(251, 191, 36, 0.34);
        border-radius: 8px;
        background: rgba(7, 16, 25, 0.96);
        box-shadow: 0 28px 80px rgba(0, 0, 0, 0.52);
    }

    .pricing-order-modal__close {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 36px;
        height: 36px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
        font-size: 1.35rem;
        line-height: 1;
    }

    .pricing-order-modal h2 {
        margin: 0;
        color: #fff;
        font-size: 1.5rem;
    }

    .pricing-order-modal__subtitle {
        margin: 10px 0 22px;
        color: var(--pricing-muted);
    }

    .pricing-order-form {
        display: grid;
        gap: 16px;
    }

    .pricing-order-form__grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .pricing-order-form label {
        display: grid;
        gap: 7px;
        color: rgba(255, 255, 255, 0.78);
        font-weight: 700;
    }

    .pricing-order-form input,
    .pricing-order-form textarea {
        width: 100%;
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.06);
        color: #fff;
        padding: 0.72rem 0.82rem;
    }

    .pricing-order-form textarea {
        resize: vertical;
    }

    @media (max-width: 900px) {
        .pricing-plans__grid {
            grid-template-columns: 1fr;
        }

        .pricing-card {
            min-height: 0;
        }

        .pricing-order-form__grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('pricing-order-modal');
        const planIdInput = document.getElementById('pricing-order-plan-id-input');
        const planInput = document.getElementById('pricing-order-plan-input');
        const planLabel = document.getElementById('pricing-order-plan-label');

        if (!modal || !planIdInput || !planInput || !planLabel) {
            return;
        }

        function openModal(planId, planName, planPrice) {
            planIdInput.value = planId || '';
            planInput.value = planName || '';
            planLabel.textContent = [planName, planPrice].filter(Boolean).join(' · ');
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-price-order-open]').forEach((button) => {
            button.addEventListener('click', function () {
                openModal(button.dataset.planId || '', button.dataset.planName || '', button.dataset.planPrice || '');
            });
        });

        document.querySelectorAll('[data-price-order-close]').forEach((button) => {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });

        @if($errors->any() && old('plan_id'))
            openModal(@json(old('plan_id')), @json(old('plan')), '');
        @endif
    });
</script>
@endsection
