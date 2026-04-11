@php
    $tabGroups = [
        $salesTabs ?? [],
        $managerTabs ?? [],
        $productionTabs ?? [],
    ];
@endphp

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
