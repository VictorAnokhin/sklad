@if(!empty($reportTabs) || !empty($investorReportTabs) || !empty($financialReportTabs) || !empty($strategicReportTabs))
<div class="doc-tabs-wrap report-tabs-wrap">
  @if(!empty($reportTabs))
  <div class="report-tabs-section">
    <div class="report-tabs-title">Операційні звіти</div>
    <div class="doc-tabs report-tabs">
      @foreach($reportTabs as $tab)
      @php
        $icon = match($tab['report'] ?? '') {
          'sales' => 'icon-business.png',
          'abcxyz' => 'icon-category.png',
          'inventory' => 'icon-packing.png',
          'turnover' => 'icon-wallet-income.png',
          'purchases' => 'icon-order.png',
          'stocks' => 'icon-packing.png',
          'finance' => 'icon-business.png',
          default => 'icon-category.png',
        };
      @endphp
      <a
        href="{{ $tab['url'] }}"
        title="{{ $tab['label'] }}"
        class="doc-tab report-tab{{ $tab['active'] ? ' is-active' : '' }}"
      >
        <img src="/img/{{ $icon }}" alt="" class="doc-tab__icon">
        <span class="doc-tab__label">{{ $tab['label'] }}</span>
      </a>
      @endforeach
    </div>
  </div>
  @endif

  @if(!empty($investorReportTabs))
  <div class="report-tabs-section report-tabs-section--secondary">
    <div class="report-tabs-title">Управлінська звітність</div>
    <div class="doc-tabs report-tabs">
      @foreach($investorReportTabs as $tab)
      @php
        $icon = match($tab['report'] ?? '') {
          'pnlsegments' => 'icon-category.png',
          'uniteconomics' => 'icon-business.png',
          'grossprofit' => 'icon-wallet-income.png',
          default => 'icon-category.png',
        };
      @endphp
      <a
        href="{{ $tab['url'] }}"
        title="{{ $tab['label'] }}"
        class="doc-tab report-tab{{ $tab['active'] ? ' is-active' : '' }}"
      >
        <img src="/img/{{ $icon }}" alt="" class="doc-tab__icon">
        <span class="doc-tab__label">{{ $tab['label'] }}</span>
      </a>
      @endforeach
    </div>
  </div>
  @endif

  @if(!empty($financialReportTabs))
  <div class="report-tabs-section report-tabs-section--tertiary">
    <div class="report-tabs-title">Фінансова звітність</div>
    <div class="doc-tabs report-tabs">
      @foreach($financialReportTabs as $tab)
      @php
        $icon = match($tab['report'] ?? '') {
          'financialpnl' => 'icon-business.png',
          'balancesheet' => 'icon-wallet-income.png',
          'cashflowstmt' => 'icon-invoice.png',
          'trialbalance' => 'icon-invoice.png',
          'journal' => 'icon-order.png',
          default => 'icon-category.png',
        };
      @endphp
      <a
        href="{{ $tab['url'] }}"
        title="{{ $tab['label'] }}"
        class="doc-tab report-tab{{ $tab['active'] ? ' is-active' : '' }}"
      >
        <img src="/img/{{ $icon }}" alt="" class="doc-tab__icon">
        <span class="doc-tab__label">{{ $tab['label'] }}</span>
      </a>
      @endforeach
    </div>
  </div>
  @endif

  @if(!empty($strategicReportTabs))
  <div class="report-tabs-section report-tabs-section--quaternary">
    <div class="report-tabs-title">Стратегічні звіти</div>
    <div class="doc-tabs report-tabs">
      @foreach($strategicReportTabs as $tab)
      @php
        $icon = match($tab['report'] ?? '') {
          'salesforecast' => 'icon-business.png',
          'purchaseplan' => 'icon-order.png',
          'profitplan' => 'icon-wallet-income.png',
          'demandtrends' => 'icon-category.png',
          default => 'icon-category.png',
        };
      @endphp
      <a
        href="{{ $tab['url'] }}"
        title="{{ $tab['label'] }}"
        class="doc-tab report-tab{{ $tab['active'] ? ' is-active' : '' }}"
      >
        <img src="/img/{{ $icon }}" alt="" class="doc-tab__icon">
        <span class="doc-tab__label">{{ $tab['label'] }}</span>
      </a>
      @endforeach
    </div>
  </div>
  @endif
</div>
@endif

<style>
  .report-tabs-wrap {
    margin-bottom: 0;
    padding-bottom: 0;
  }

  .report-tabs-section + .report-tabs-section {
    margin-top: 10px;
  }

  .report-tabs-title {
    margin-bottom: 8px;
    color: rgba(255, 255, 255, 0.78);
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .report-tabs {
    min-width: auto;
    border-bottom: 0;
    gap: 10px;
  }

  .report-tab {
    border: 1px solid rgba(251, 191, 36, 0.18);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.04);
    padding: 10px 14px;
    color: #ffffff;
    transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
  }

  .report-tab:hover,
  .report-tab:focus {
    background: rgba(251, 191, 36, 0.18);
    border-color: rgba(251, 191, 36, 0.55);
    box-shadow: 0 10px 24px rgba(251, 191, 36, 0.18);
    transform: translateY(-1px);
  }

  .report-tab.is-active {
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.28), rgba(245, 158, 11, 0.2));
    border-color: rgba(251, 191, 36, 0.75);
    box-shadow: 0 12px 28px rgba(251, 191, 36, 0.24);
    color: #ffffff;
  }

  .report-tab .doc-tab__icon {
    width: 16px;
    height: 16px;
    margin-right: 6px;
    filter: brightness(0) invert(1);
  }

  .report-tab:hover,
  .report-tab:focus,
  .report-tab .doc-tab__label {
    color: #ffffff;
  }
</style>
