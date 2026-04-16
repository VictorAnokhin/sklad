@php
  $doc    = session('doc', '');
  $fid    = session('fid', '');
  $num    = session('num', '0');
  $fd     = $fd ?? app(\App\Services\FilterService::class)->resolve($doc, $fid);
  $active = !($fd['isDefault'] ?? true);
  $btnCls = $active ? 'button_submit_start' : 'button_submit_start0';
@endphp

<div style="position:relative;margin-top:13px">
  @if($num === '0')
  <div onclick="filterToggle()" class="{{ $btnCls }}" style="width:70px;height:70px;margin-top:-3px;cursor:pointer; background: linear-gradient(135deg, #fbbf24, #f59e0b); border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3); transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; justify-content: center;">
    <img src="/img/icon-category.png" alt="{{ __('document.filter.icon_alt') }}" style="width:32px;filter: brightness(0);">
    <span style="font-size: 0.7rem; font-weight: 600; color: #000; margin-top: 4px;">{{ __('document.filter.search') }}</span>
  </div>
  @endif
</div>

<div id="filterModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.7); backdrop-filter:blur(8px); z-index:9999; justify-content:center; align-items:center;">
  <div class="glass-card" style="width:700px; max-width:90vw; max-height:80vh; overflow-y:auto; position:relative; margin:0 auto; padding:24px;">
    <div onclick="filterToggle()" style="position:absolute; top:12px; right:16px; cursor:pointer; font-size:1.5rem; color:var(--muted-foreground); transition:color 0.2s; z-index:10;">✕</div>

    <h3 style="margin:0 0 16px 0; color:var(--foreground); font-family:var(--header); font-size:1.25rem;">🔍 {{ __('document.filter.title') }}</h3>

    <form action="{{ route('filter.apply') }}" method="post" name="filterform">
      @csrf
      <input type="hidden" name="filter" value="1">
      <input type="hidden" name="doc"    value="{{ $doc }}">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        @if(!in_array($doc, ['STAT','ZD','RO','PO','RPO','PP']))
        <div>
          <label style="display:block; margin-bottom:4px; font-size:0.85rem;">{{ __('document.filter.number_or_note') }}</label>
          <input type="text" name="f_content" autocomplete="off"
                 placeholder="{{ __('document.filter.number_or_note_placeholder') }}"
                 value="{{ $fd['fContent'] ?? '' }}" style="width:100%; padding:8px 12px; font-size:0.9rem;">
        </div>
        @endif

        @if($doc !== 'STAT')
        <div>
          <label style="display:block; margin-bottom:4px; font-size:0.85rem;">{{ __('document.filter.client_data') }}</label>
          <input type="text" name="f_name" autocomplete="off"
                 placeholder="{{ __('document.filter.client_data_placeholder') }}"
                 value="{{ $fd['fName'] ?? '' }}" style="width:100%; padding:8px 12px; font-size:0.9rem;">
        </div>
        @endif

        @if(in_array($doc, ['PP','PO','RPO']))
        <div>
          <label style="display:block; margin-bottom:4px; font-size:0.85rem;">{{ __('document.filter.payment') }}</label>
          <div style="width:100%;">@include('partials.selects.oplata', ['selected' => $fd['fOplata'] ?? '', 'fid' => $fid])</div>
        </div>
        @elseif(in_array($doc, ['WO1','PN','RN']))
        <div>
          <label style="display:block; margin-bottom:4px; font-size:0.85rem;">{{ __('document.filter.warehouse') }}</label>
          <div style="width:100%;">@include('partials.selects.sklads', ['selected' => $fd['fSklads'] ?? '', 'fid' => $fid])</div>
        </div>
        @endif

        @if(in_array($doc, ['ZOUT','ZIN']))
        <div>
          <label style="display:block; margin-bottom:4px; font-size:0.85rem;">{{ __('document.filter.status') }}</label>
          <div style="width:100%;">@include('partials.selects.status', ['selected' => $fd['fStatus'] ?? '', 'fid' => $fid])</div>
        </div>
        @endif

        @if(in_array($doc, ['WO1','ZD']))
        <div style="display:flex; align-items:flex-end; padding-bottom:8px;">
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:0.9rem;">
            <input type="checkbox" name="f_provodka" value="1"
                   {{ ($fd['fProvodka'] ?? '') ? 'checked' : '' }} style="width:auto;">
            {{ __('document.filter.show_all') }}
          </label>
        </div>
        @endif

        @if(in_array($doc, ['PO','RO','RPO']))
        <div>
          <label style="display:block; margin-bottom:4px; font-size:0.85rem;">{{ __('document.filter.registry') }}</label>
          <div style="width:100%;">@include('partials.selects.reestr', ['selected' => $fd['fReestr'] ?? '', 'fid' => $fid])</div>
        </div>
        @endif

        <div>
          <label style="display:block; margin-bottom:4px; font-size:0.85rem;">{{ __('document.filter.date_from') }}</label>
          <input type="date" name="fdata1" value="{{ $fd['fdata1'] ?? '' }}" style="width:100%; padding:8px 12px; font-size:0.9rem;">
        </div>

        <div>
          <label style="display:block; margin-bottom:4px; font-size:0.85rem;">{{ __('document.filter.date_to') }}</label>
          <input type="date" name="fdata2" value="{{ $fd['fdata2'] ?? '' }}" style="width:100%; padding:8px 12px; font-size:0.9rem;">
        </div>
      </div>

      <div style="display: flex; gap: 10px; margin-top: 20px;">
        <button type="submit" style="flex: 1; padding: 10px 16px; background: linear-gradient(135deg, #fbbf24, #f59e0b); border: none; border-radius: 8px; box-shadow: 0 4px 12px rgba(251, 191, 36, 0.3); color: #000; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 6px;">
          <span>🔍</span> {{ __('document.filter.find') }}
        </button>
        <button type="submit" name="clear" value="1" style="flex: 1; padding: 10px 16px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; color: var(--foreground); font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 6px;">
          <span>✕</span> {{ __('document.filter.reset') }}
        </button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
function filterToggle() {
  var d = document.getElementById('filterModal');
  if (d.style.display === 'none' || d.style.display === '') {
    d.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  } else {
    d.style.display = 'none';
    document.body.style.overflow = '';
  }
}

// Закрити при кліку на фон
document.getElementById('filterModal').addEventListener('click', function(e) {
  if (e.target === this) {
    filterToggle();
  }
});

// Закрити при натисканні Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    var d = document.getElementById('filterModal');
    if (d.style.display === 'flex') {
      filterToggle();
    }
  }
});
</script>
@endpush
