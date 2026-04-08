@php
  $pos  = (int)($pos  ?? 0);
  $pos2 = (int)($pos2 ?? 30);
  $max  = (int)($max  ?? 0);
  $doc  = $doc ?? session('doc', '');
@endphp

@if($max > $pos2)
@php
  $pages = (int) ceil($max / $pos2);
  $cur = (int) floor($pos / $pos2);
  $firstPos = 0;
  $prevPos = max(0, $pos - $pos2);
  $nextPos = min(max(0, ($pages - 1) * $pos2), $pos + $pos2);
  $lastPos = max(0, ($pages - 1) * $pos2);
  $isFirst = $cur <= 0;
  $isLast = $cur >= ($pages - 1);
@endphp

<div class="navigator" style="display:flex;gap:6px;padding:6px;align-items:center;flex-wrap:wrap">
  @if($isFirst)
    <span class="button" style="width:52px;pointer-events:none;opacity:.55;text-align:center">« 1</span>
    <span class="button" style="width:60px;pointer-events:none;opacity:.55;text-align:center">←</span>
  @else
    <a href="{{ route('document.index', ['doc' => $doc, 'pos' => $firstPos]) }}"
       class="button" style="width:52px;text-align:center">« 1</a>
    <a href="{{ route('document.index', ['doc' => $doc, 'pos' => $prevPos]) }}"
       class="button" style="width:60px;text-align:center">←</a>
  @endif

  @if($isLast)
    <span class="button" style="width:60px;pointer-events:none;opacity:.55;text-align:center">→</span>
    <span class="button" style="width:52px;pointer-events:none;opacity:.55;text-align:center">{{ $pages }} »</span>
  @else
    <a href="{{ route('document.index', ['doc' => $doc, 'pos' => $nextPos]) }}"
       class="button" style="width:60px;text-align:center">→</a>
    <a href="{{ route('document.index', ['doc' => $doc, 'pos' => $lastPos]) }}"
       class="button" style="width:52px;text-align:center">{{ $pages }} »</a>
  @endif

  <span style="line-height:32px;font-size:0.85em;color:#666">
    {{ $pos + 1 }}–{{ min($pos + $pos2, $max) }} з {{ $max }} | стор. {{ $cur + 1 }} / {{ $pages }}
  </span>
</div>
@endif
