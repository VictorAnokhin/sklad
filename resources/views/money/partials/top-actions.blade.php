@php
    $activeTab = ($tab ?? 'orders') === 'transfers' ? 'transfers' : 'orders';
    $baseFilters = $returnFilters ?? [];
    $indexRouteName = $indexRouteName ?? ($activeTab === 'transfers' ? 'money.transfers' : 'money.index');
    $showRouteName = $showRouteName ?? 'money.show';
@endphp

<div class="ttable top-action-bar money-top-action-bar" style="display:flex; gap:16px; align-items:flex-start; justify-content:space-between; flex-wrap:wrap;">
    <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
        @if($showMoneyFilter ?? false)
        <div class="top-action-filter">
            <div onclick="moneyFilterToggle()"
                class="{{ !empty($activeFilters ?? []) ? 'button_submit_start' : 'button_submit_start0' }}"
                style="width:70px;height:70px;margin-top:10px;cursor:pointer; background: linear-gradient(135deg, #fbbf24, #f59e0b); border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3); transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <img src="/img/icon-category.png" alt="{{ __('document.filter.icon_alt') }}" style="width:32px;filter: brightness(0);">
                <span style="font-size: 0.7rem; font-weight: 600; color: #000; margin-top: 4px;">{{ __('document.filter.search') }}</span>
            </div>
        </div>
        @endif
    </div>

    <div class="top-action-create money-top-action-create">
        @if($activeTab === 'transfers')
        <form action="{{ route($showRouteName) }}" method="get" class="money-top-action-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="tab" value="transfers">
            <input type="hidden" name="return_q" value="{{ $baseFilters['q'] ?? '' }}">
            <input type="hidden" name="return_money" value="{{ $baseFilters['money'] ?? '' }}">
            <input type="hidden" name="return_date_from" value="{{ $baseFilters['date_from'] ?? '' }}">
            <input type="hidden" name="return_date_to" value="{{ $baseFilters['date_to'] ?? '' }}">
            <input type="hidden" name="return_pos" value="{{ $baseFilters['pos'] ?? '' }}">
            <button type="submit" class="button top-action-create-btn">
                {{ __('money.add_transfer') }}
            </button>
        </form>
        @else
        <form action="{{ route($showRouteName) }}" method="get" class="money-top-action-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="type" value="PPO">
            <input type="hidden" name="tab" value="orders">
            <input type="hidden" name="return_q" value="{{ $baseFilters['q'] ?? '' }}">
            <input type="hidden" name="return_filter_type" value="{{ $baseFilters['type'] ?? '' }}">
            <input type="hidden" name="return_money" value="{{ $baseFilters['money'] ?? '' }}">
            <input type="hidden" name="return_reestr" value="{{ $baseFilters['reestr'] ?? '' }}">
            <input type="hidden" name="return_date_from" value="{{ $baseFilters['date_from'] ?? '' }}">
            <input type="hidden" name="return_date_to" value="{{ $baseFilters['date_to'] ?? '' }}">
            <input type="hidden" name="return_pos" value="{{ $baseFilters['pos'] ?? '' }}">
            <button type="submit" class="button top-action-create-btn">
                {{ __('money.add_income') }}
            </button>
        </form>
        <form action="{{ route($showRouteName) }}" method="get" class="money-top-action-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="type" value="PRO">
            <input type="hidden" name="tab" value="orders">
            <input type="hidden" name="return_q" value="{{ $baseFilters['q'] ?? '' }}">
            <input type="hidden" name="return_filter_type" value="{{ $baseFilters['type'] ?? '' }}">
            <input type="hidden" name="return_money" value="{{ $baseFilters['money'] ?? '' }}">
            <input type="hidden" name="return_reestr" value="{{ $baseFilters['reestr'] ?? '' }}">
            <input type="hidden" name="return_date_from" value="{{ $baseFilters['date_from'] ?? '' }}">
            <input type="hidden" name="return_date_to" value="{{ $baseFilters['date_to'] ?? '' }}">
            <input type="hidden" name="return_pos" value="{{ $baseFilters['pos'] ?? '' }}">
            <button type="submit" class="button top-action-create-btn">
                {{ __('money.add_outcome') }}
            </button>
        </form>
        <form action="{{ route($showRouteName) }}" method="get" class="money-top-action-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="type" value="PPP">
            <input type="hidden" name="tab" value="orders">
            <input type="hidden" name="return_q" value="{{ $baseFilters['q'] ?? '' }}">
            <input type="hidden" name="return_filter_type" value="{{ $baseFilters['type'] ?? '' }}">
            <input type="hidden" name="return_money" value="{{ $baseFilters['money'] ?? '' }}">
            <input type="hidden" name="return_reestr" value="{{ $baseFilters['reestr'] ?? '' }}">
            <input type="hidden" name="return_date_from" value="{{ $baseFilters['date_from'] ?? '' }}">
            <input type="hidden" name="return_date_to" value="{{ $baseFilters['date_to'] ?? '' }}">
            <input type="hidden" name="return_pos" value="{{ $baseFilters['pos'] ?? '' }}">
            <button type="submit" class="button top-action-create-btn">
                {{ __('money.add_exchange') }}
            </button>
        </form>
        @endif
    </div>
</div>
