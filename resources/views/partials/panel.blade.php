@if($num === '0')
{{-- Panel 1: sales staff (idstatus ≠ 2) --}}
@if(!empty($salesTabs))
<div class="tstr0">
  <div class="tstr1">
    @foreach($salesTabs as $tab)
    <a href="{{ $tab['url'] }}" title="{{ $tab['label'] }}">
      <div class="{{ $tab['active'] ? 'button_active' : 'button' }}" style="width:120px">
        <img src="/img/{{ $tab['icon'] }}" alt="{{ $tab['label'] }}"><br>{{ $tab['label'] }}
      </div>
    </a>
    @endforeach
  </div>
</div>
@endif

{{-- Panel 2: managers/admins (idstatus > 2) --}}
@if(!empty($managerTabs))
<div class="tstr0">
  <div class="tstr1">
    @foreach($managerTabs as $tab)
    <a href="{{ $tab['url'] }}" title="{{ $tab['label'] }}">
      <div class="{{ $tab['active'] ? 'button_active' : 'button' }}" style="width:120px">
        <img src="/img/{{ $tab['icon'] }}" alt="{{ $tab['label'] }}"><br>{{ $tab['label'] }}
      </div>
    </a>
    @endforeach
  </div>
</div>
@endif

{{-- Panel 3: production workers (idstatus == 2) --}}
@if(!empty($productionTabs))
<div class="tstr0">
  <div class="tstr1">
    @foreach($productionTabs as $tab)
    <div class="tstr" style="width:100px;border-bottom:3px solid {{ $tab['active'] ? '#CD5C5C' : '#ccc' }}">
      <a href="{{ $tab['url'] }}" class="{{ $tab['active'] ? 'atmenu' : 'tmenu' }}">{{ $tab['label'] }}</a>
    </div>
    @endforeach
  </div>
</div>
@endif
@endif