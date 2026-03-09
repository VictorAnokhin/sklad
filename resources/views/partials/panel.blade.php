@php
  $idstatus = (int)session('idstatus', 0);
  $cur      = session('doc', '');
  $num      = session('num', '0');

  $tab = function(string $doc, string $label, string $icon) use ($cur): string {
      $cls = $doc === $cur ? 'button_active' : 'button';
      $url = route('document.index', ['doc' => $doc, 'num' => 0]);
      return "<a href=\"{$url}\" title=\"" . h($label) . "\">
                <div class=\"{$cls}\" style=\"width:120px\">
                  <img src=\"/img/{$icon}\" alt=\"" . h($label) . "\"><br>" . h($label) . "
                </div>
              </a>";
  };

  $tabText = function(string $doc, string $label) use ($cur): string {
      $border = $doc === $cur ? '#CD5C5C' : '#ccc';
      $cls    = $doc === $cur ? 'atmenu' : 'tmenu';
      $url    = route('document.index', ['doc' => $doc, 'num' => 0]);
      return "<div class=\"tstr\" style=\"width:100px;border-bottom:3px solid {$border}\">
                <a href=\"{$url}\" class=\"{$cls}\">" . h($label) . "</a>
              </div>";
  };
@endphp

@if($num === '0')
  {{-- Panel 1: sales staff (idstatus ≠ 2) --}}
  @if($idstatus !== 2)
  <div class="tstr0"><div class="tstr1">
    {!! $tab('ZOUT', 'Замовлення',  'icon-order.png') !!}
    {!! $tab('CH',   'Рахунки',     'icon-invoice.png') !!}
    {!! $tab('RN',   'Накладні',    'icon-packing.png') !!}
    {!! $tab('PO',   'Гроші',       'icon-business.png') !!}
    {!! $tab('WO1',  'Наряди',      'icon-naryad.png') !!}
    {!! $tab('RA',   'Файли',       'icon-attach-file.png') !!}
  </div></div>
  @endif

  {{-- Panel 2: managers/admins (idstatus > 2) --}}
  @if($idstatus > 2)
  <div class="tstr0"><div class="tstr1">
    {!! $tab('ZIN', 'Закупки',        'icon-order.png') !!}
    {!! $tab('PN',  'Прихід товару',  'icon-packing.png') !!}
    {!! $tab('RO',  'Витрата грошей', 'icon-business.png') !!}
    {!! $tab('VN',  'Повернення',     'icon-naryad.png') !!}
  </div></div>
  @endif

  {{-- Panel 3: production workers (idstatus == 2) --}}
  @if($idstatus === 2)
  <div class="tstr0"><div class="tstr1">
    {!! $tabText('WO1', 'Наряди') !!}
    {!! $tabText('SP',  'Специфікації') !!}
  </div></div>
  @endif
@endif
