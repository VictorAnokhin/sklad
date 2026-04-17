@php
  $pos  = (int)($pos  ?? 0);
  $pos2 = (int)($pos2 ?? 30);
  $max  = (int)($max  ?? 0);

  // Universal route support: pass routeName and routeParams from the caller
  $navRoute  = $routeName ?? 'document.index';
  $navParams = $routeParams ?? [];

  // Legacy support: if 'doc' is passed and no routeParams, add it
  if (empty($navParams) && isset($doc) && $doc !== '' && $doc !== 'money') {
      $navParams['doc'] = $doc;
  }
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

<div class="navigator d-flex align-items-center justify-content-center gap-2 flex-wrap py-2">
  @if($isFirst)
    <span class="btn btn-outline-secondary disabled" style="opacity: 0.5; pointer-events: none;">«</span>
    <span class="btn btn-outline-secondary disabled" style="opacity: 0.5; pointer-events: none;">‹</span>
  @else
    <a href="{{ route($navRoute, array_merge($navParams, ['pos' => $firstPos])) }}" class="btn btn-outline-secondary">«</a>
    <a href="{{ route($navRoute, array_merge($navParams, ['pos' => $prevPos])) }}" class="btn btn-outline-secondary">‹</a>
  @endif

  <span class="px-3" style="font-size: 0.9em; color: #fbbf24; font-weight: 500;">
    {{ __('document.navigator.from_to_total', ['from' => $pos + 1, 'to' => min($pos + $pos2, $max), 'total' => $max]) }} | {{ __('document.navigator.page', ['current' => $cur + 1, 'pages' => $pages]) }}
  </span>

  @if($isLast)
    <span class="btn btn-outline-secondary disabled" style="opacity: 0.5; pointer-events: none;">›</span>
    <span class="btn btn-outline-secondary disabled" style="opacity: 0.5; pointer-events: none;">»</span>
  @else
    <a href="{{ route($navRoute, array_merge($navParams, ['pos' => $nextPos])) }}" class="btn btn-outline-secondary">›</a>
    <a href="{{ route($navRoute, array_merge($navParams, ['pos' => $lastPos])) }}" class="btn btn-outline-secondary">»</a>
  @endif
</div>
@endif
