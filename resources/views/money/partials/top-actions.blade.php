<div class="ttable top-action-bar money-top-action-bar">
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
    <div class="top-action-create money-top-action-create">
        <form action="{{ route('money.show') }}" method="get" class="money-top-action-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="type" value="PO">
            <input type="hidden" name="return_q" value="{{ $returnFilters['q'] ?? '' }}">
            <input type="hidden" name="return_filter_type" value="{{ $returnFilters['type'] ?? '' }}">
            <input type="hidden" name="return_money" value="{{ $returnFilters['money'] ?? '' }}">
            <input type="hidden" name="return_reestr" value="{{ $returnFilters['reestr'] ?? '' }}">
            <input type="hidden" name="return_date_from" value="{{ $returnFilters['date_from'] ?? '' }}">
            <input type="hidden" name="return_date_to" value="{{ $returnFilters['date_to'] ?? '' }}">
            <input type="hidden" name="return_pos" value="{{ $returnFilters['pos'] ?? '' }}">
            <button type="submit" class="button top-action-create-btn">
                {{ __('money.add_income') }}
            </button>
        </form>
        <form action="{{ route('money.show') }}" method="get" class="money-top-action-form">
            <input type="hidden" name="id" value="0">
            <input type="hidden" name="type" value="RO">
            <input type="hidden" name="return_q" value="{{ $returnFilters['q'] ?? '' }}">
            <input type="hidden" name="return_filter_type" value="{{ $returnFilters['type'] ?? '' }}">
            <input type="hidden" name="return_money" value="{{ $returnFilters['money'] ?? '' }}">
            <input type="hidden" name="return_reestr" value="{{ $returnFilters['reestr'] ?? '' }}">
            <input type="hidden" name="return_date_from" value="{{ $returnFilters['date_from'] ?? '' }}">
            <input type="hidden" name="return_date_to" value="{{ $returnFilters['date_to'] ?? '' }}">
            <input type="hidden" name="return_pos" value="{{ $returnFilters['pos'] ?? '' }}">
            <button type="submit" class="button top-action-create-btn">
                {{ __('money.add_outcome') }}
            </button>
        </form>
    </div>
</div>
