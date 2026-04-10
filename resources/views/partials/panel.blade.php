@php
    $tabGroups = [
        $salesTabs ?? [],
        $managerTabs ?? [],
        $productionTabs ?? [],
    ];
@endphp

<style>
  .doc-tabs-wrap {
    overflow-x: auto;
    margin-bottom: 12px;
    padding-bottom: 2px;
  }

  .doc-tabs {
    display: inline-flex;
    align-items: stretch;
    gap: 8px;
    min-width: 100%;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
  }

  .doc-tab {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    text-decoration: none;
    color: var(--muted-foreground, #94a3b8);
    border-bottom: 3px solid transparent;
    white-space: nowrap;
    transition: color 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
    border-radius: 12px 12px 0 0;
  }

  .doc-tab:hover {
    color: var(--foreground, #ffffff);
    background: rgba(255, 255, 255, 0.04);
  }

  .doc-tab.is-active {
    color: #fbbf24;
    border-bottom-color: #fbbf24;
    background: rgba(251, 191, 36, 0.08);
  }

  .doc-tab__icon {
    width: 18px;
    height: 18px;
    object-fit: contain;
    flex: 0 0 auto;
  }

  .doc-tab__label {
    font-weight: 600;
    line-height: 1.2;
  }
</style>

@foreach($tabGroups as $tabs)
@if(!empty($tabs))
<div class="doc-tabs-wrap">
  <div class="doc-tabs">
    @foreach($tabs as $tab)
    <a
      href="{{ $tab['url'] }}"
      title="{{ $tab['label'] }}"
      class="doc-tab{{ $tab['active'] ? ' is-active' : '' }}"
    >
      @if(!empty($tab['icon']))
      <img src="/img/{{ $tab['icon'] }}" alt="" class="doc-tab__icon">
      @endif
      <span class="doc-tab__label">{{ $tab['label'] }}</span>
    </a>
    @endforeach
  </div>
</div>
@endif
@endforeach
