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
  <div onclick="filterToggle()" class="{{ $btnCls }}" style="width:70px;height:42px;margin-top:-3px;cursor:pointer">
    <img src="/img/icon-category.png" alt="пошук" style="width:30px"><br>пошук
  </div>
  @endif
</div>

<div id="divfilter" class="filter_box_center" style="display:none">
  <div onclick="filterToggle()" class="filter_title" style="cursor:pointer">✕</div>

  <form action="{{ route('filter.apply') }}" method="post" name="filterform">
    @csrf
    <input type="hidden" name="filter" value="1">
    <input type="hidden" name="doc"    value="{{ $doc }}">

    @if(!in_array($doc, ['STAT','ZD','RO','PO','RPO','PP']))
    <div class="txtbox_startpage_str2">
      <input type="text" name="f_content" autocomplete="off"
             placeholder="номер або примітка"
             value="{{ $fd['fContent'] ?? '' }}">
    </div>
    @endif

    @if($doc !== 'STAT')
    <div class="txtbox_startpage_str2">
      <input type="text" name="f_name" autocomplete="off"
             placeholder="дані клієнта"
             value="{{ $fd['fName'] ?? '' }}">
    </div>
    @endif

    @if(!in_array($doc, ['STAT','ZD','RO','PO','RPO','PP']))
    <div class="txtbox_startpage_str2">
      <input type="text" name="f_operator"
             placeholder="оператор"
             value="{{ $fd['fOperator'] ?? '' }}">
    </div>
    @endif

    <div class="txtbox_startpage_str2">
      @if(in_array($doc, ['ZOUT','ZIN','RN','PN','WO1','STAT']))
        @include('partials.selects.reteil', ['selected' => $fd['fReteil'] ?? '', 'fid' => $fid])
      @endif

      @if(in_array($doc, ['PP','PO','RPO']))
        @include('partials.selects.oplata', ['selected' => $fd['fOplata'] ?? '', 'fid' => $fid])
      @elseif(in_array($doc, ['ZOUT','ZIN','WO1','PN','RN']))
        @include('partials.selects.sklads', ['selected' => $fd['fSklads'] ?? '', 'fid' => $fid])
      @endif

      @if($doc === 'ZOUT')
        @include('partials.selects.status', ['selected' => $fd['fStatus'] ?? '', 'fid' => $fid])
      @elseif(!in_array($doc, ['STAT','ZD','RO','PO','RPO','PP']))
        @include('partials.selects.status', ['selected' => $fd['fStatus'] ?? '', 'fid' => $fid])
      @endif

      @if(in_array($doc, ['WO1','ZD']))
        <label>
          <input type="checkbox" name="f_provodka" value="1"
                 {{ ($fd['fProvodka'] ?? '') ? 'checked' : '' }}>
          Показати всі
        </label>
      @endif

      @if(in_array($doc, ['PO','RO','RPO']))
        @include('partials.selects.reestr', ['selected' => $fd['fReestr'] ?? '', 'fid' => $fid])
      @endif
    </div>

    <div class="txtbox_startpage_str2">
      <label style="width:55px">Дата 1</label>
      <input type="date" name="fdata1" value="{{ $fd['fdata1'] ?? '' }}" style="width:60%"><br>
      <label style="width:55px">Дата 2</label>
      <input type="date" name="fdata2" value="{{ $fd['fdata2'] ?? '' }}" style="width:60%">
    </div>

    <div class="document">
      <button type="submit" class="button" style="width:70px">Знайти</button>
      <button type="submit" name="clear" value="1" class="button" style="width:70px">Скинути</button>
    </div>
  </form>
</div>

@push('scripts')
<script>
function filterToggle() {
  var d = document.getElementById('divfilter');
  d.style.display = d.style.display === 'block' ? 'none' : 'block';
}
</script>
@endpush
