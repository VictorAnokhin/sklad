@php
  $pos  = (int)($pos  ?? 0);
  $pos2 = (int)($pos2 ?? 30);
  $max  = (int)($max  ?? 0);
  $doc  = $doc ?? session('doc', '');
@endphp

@if($max > $pos2)
<div class="navigator" style="display:flex;gap:6px;padding:6px">
  @if($pos > 0)
    <a href="{{ route('document.index', ['doc' => $doc, 'pos' => max(0, $pos - $pos2)]) }}"
       class="button" style="width:60px">← Назад</a>
  @endif

  @php $pages = ceil($max / $pos2); $cur = (int)($pos / $pos2); @endphp
  @for($p = 0; $p < $pages; $p++)
    @php $pPos = $p * $pos2; $cls = $p === $cur ? 'button_active' : 'button'; @endphp
    <a href="{{ route('document.index', ['doc' => $doc, 'pos' => $pPos]) }}"
       class="{{ $cls }}" style="width:30px;text-align:center">{{ $p + 1 }}</a>
  @endfor

  @if($pos + $pos2 < $max)
    <a href="{{ route('document.index', ['doc' => $doc, 'pos' => $pos + $pos2]) }}"
       class="button" style="width:60px">Вперед →</a>
  @endif

  <span style="line-height:32px;font-size:0.85em;color:#666">
    {{ $pos + 1 }}–{{ min($pos + $pos2, $max) }} з {{ $max }}
  </span>
</div>
@endif
