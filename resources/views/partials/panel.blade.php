@if($num === '0')
{{-- Panel 1: sales staff (idstatus ≠ 2) --}}
@if(!empty($salesTabs))
<div class="tstr0" style="overflow-x: auto;">
  <div class="tstr1" style="display: flex; gap: 8px; flex-wrap: nowrap; align-items: center;">
    @foreach($salesTabs as $tab)
    <a href="{{ $tab['url'] }}" title="{{ $tab['label'] }}" class="text-decoration-none">
      <div class="{{ $tab['active'] ? 'button_active' : 'button' }}" style="min-width: 120px; {{ $tab['active'] ? 'border: 2px solid #fbbf24; box-shadow: 0 0 12px rgba(251, 191, 36, 0.3);' : '' }}">
        <img src="/img/{{ $tab['icon'] }}" alt="{{ $tab['label'] }}" style="width: 24px; height: 24px;"><br>
        <span style="{{ $tab['active'] ? 'color: #fbbf24; font-weight: 600;' : 'color: var(--foreground);' }}">{{ $tab['label'] }}</span>
      </div>
    </a>
    @endforeach
  </div>
</div>
@endif

{{-- Panel 2: managers/admins (idstatus > 2) --}}
@if(!empty($managerTabs))
<div class="tstr0" style="overflow-x: auto;">
  <div class="tstr1" style="display: flex; gap: 8px; flex-wrap: nowrap; align-items: center;">
    @foreach($managerTabs as $tab)
    <a href="{{ $tab['url'] }}" title="{{ $tab['label'] }}" class="text-decoration-none">
      <div class="{{ $tab['active'] ? 'button_active' : 'button' }}" style="min-width: 120px; {{ $tab['active'] ? 'border: 2px solid #fbbf24; box-shadow: 0 0 12px rgba(251, 191, 36, 0.3);' : '' }}">
        <img src="/img/{{ $tab['icon'] }}" alt="{{ $tab['label'] }}" style="width: 24px; height: 24px;"><br>
        <span style="{{ $tab['active'] ? 'color: #fbbf24; font-weight: 600;' : 'color: var(--foreground);' }}">{{ $tab['label'] }}</span>
      </div>
    </a>
    @endforeach
  </div>
</div>
@endif

{{-- Panel 3: production workers (idstatus == 2) --}}
@if(!empty($productionTabs))
<div class="tstr0" style="overflow-x: auto;">
  <div class="tstr1" style="display: flex; gap: 8px; flex-wrap: nowrap; align-items: center;">
    @foreach($productionTabs as $tab)
    <div style="{{ $tab['active'] ? 'border-bottom: 3px solid #fbbf24; padding-bottom: 4px;' : 'border-bottom: 3px solid var(--border); padding-bottom: 4px;' }}">
      <a href="{{ $tab['url'] }}" class="{{ $tab['active'] ? 'atmenu' : 'tmenu' }}" style="{{ $tab['active'] ? 'color: #fbbf24; font-weight: 600;' : 'color: var(--muted-foreground);' }}">{{ $tab['label'] }}</a>
    </div>
    @endforeach
  </div>
</div>
@endif
@endif