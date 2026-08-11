@extends('home')

@section('title', 'Крипто-обмен')

@section('content')
<div class="money-swap-page">
    <div class="money-swap-page__header">
        <a href="{{ route('money.index') }}" class="btn btn-outline-secondary">← {{ __('money.btn_back') }}</a>
        <h3 class="money-swap-page__title">Крипто-обмен</h3>
    </div>

    <iframe
        class="money-swap-page__frame"
        src="{{ route('wallet.swap-window') }}"
        title="Крипто-обмен"
        loading="lazy"
    ></iframe>
</div>

<style>
    .money-swap-page {
        width: min(1180px, calc(100vw - 24px));
        margin: 0 auto;
        padding: 12px 0 28px;
    }

    .money-swap-page__header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 14px;
        flex-wrap: wrap;
    }

    .money-swap-page__title {
        margin: 0;
        color: #fff;
        font-size: 1.35rem;
        font-weight: 700;
    }

    .money-swap-page__frame {
        display: block;
        width: 100%;
        min-height: 780px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        background: #020617;
    }

    @media (max-width: 768px) {
        .money-swap-page {
            width: calc(100vw - 16px);
            padding-top: 8px;
        }

        .money-swap-page__frame {
            min-height: 860px;
        }
    }
</style>
@endsection
