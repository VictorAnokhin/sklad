@extends('layouts.app')

@section('title', 'Документи')

@section('contentbody')

{{-- New document button --}}
<div class="ttable">
  <form action="{{ route('document.save') }}" method="post" name="dataform">
    @csrf
    <input type="hidden" name="year_N" value="{{ session('year', date('Y')) }}">

    @php
    $btnLabel = match($doc) {
    'PO' => 'Отримання грошей',
    'RO' => 'Видача грошей',
    'PN' => 'Отримання товару',
    'RN' => 'Видача товару',
    'WO1' => 'На виготовлення',
    'CH' => 'Пропозиція',
    'SP' => 'На виготовлення',
    'RA' => 'Додати фото',
    'VN' => 'Видача товару',
    default => 'Новий документ',
    };
    @endphp

    <button type="submit" name="run" value="{{ $btnLabel }}" class="button" style="width:140px;margin:6px">
      + {{ $btnLabel }}
    </button>
  </form>
</div>

<div class="ttable">

  @if(empty($rows))
  <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
    Документи відсутні...
  </div>
  @else

  @php $total_sum = 0; @endphp

  @foreach($rows as $row)
  @php
  $statusId = $row->status ?? '';
  $conf = $confMap[$statusId] ?? null;
  $statusName = $conf ? h(convert_from_base($conf->name)) : '';
  $color = h($conf->color ?? '');
  $summa = (float)$row->summa;
  $total_sum += $summa;
  $summaFmt = number_format($summa, 2, ',', "'");
  $data = h($row->data ?? '');
  $time = h($row->time ?? '');
  $num = h($row->num ?? '');
  $docId = $row->id;
  $year = strlen((string)($row->data ?? '')) >= 10 ? substr((string)$row->data, 6, 4) : date('Y');
  $content = h(convert_from_base($row->content ?? ''));
  if ($row->ttn) $content .= '<br>(' . h($row->ttn) . ')';
  $orgname = h(convert_from_base($row->orgname ?? ''));
  $kod1 = h($row->kod1 ?? '');
  $org = $orgname ? "{$orgname}, {$kod1}<br>" : '';
  $fullName = h(trim(
  convert_from_base($row->secondname ?? '') . ' '
  . convert_from_base($row->name ?? '') . ' '
  . convert_from_base($row->fathername ?? '')
  ));
  $city = h(convert_from_base($row->city ?? ''));
  $poshta = $row->poshta ? 'НП ' . h($row->poshta) : '';
  $phone = h(formatPhone((string)($row->phone ?? '')));
  $manager = h(strtolower(convert_from_base($row->manager ?? '')));
  $top = (int)($row->top ?? 0);
  $topImg = $top >= 5 ? "⭐" : "[{$top}]";
  $signal = ($statusName === '' && $doc === 'ZOUT') ? "<a class='alink3'>new</a>" : '';
  $skladsConf = $confMap[$row->sklads ?? ''] ?? null;
  $skladsName = $skladsConf ? h(convert_from_base($skladsConf->name)) : '';
  $linkUrl = route('document.show', [
  'doc_id' => $docId, 'num' => $row->num, 'year' => $year, 'doc' => $doc,
  ]);
  @endphp

  <div class="txtbox-price-docs">
    <div class="numdoc-docs">
      <a href="{{ $linkUrl }}" title="відкрити">{{ $num }}</a>
    </div>
    <div class="status-docs4">{{ $data }}<br>{{ $time }}</div>
    <div class="captionbox-docs">
      <a href="{{ $linkUrl }}" class="title">
        {!! $org !!}{{ $fullName }}<br>
        <span class="city">{{ $city }} {{ $poshta }}</span><br>
        <span class="phone">{{ $phone }}</span>
      </a>
    </div>
    <div class="status-docs3" style="background:{{ $color }}">
      {{ $statusName }} {!! $signal !!}
    </div>
    <div class="pricebox-docs1">
      <span class="money">{{ $summaFmt }}</span>
    </div>
    <div class="captionbox-docs2">{!! $content !!}</div>
    <div class="status-docs2">{{ $skladsName }} {{ $manager }} {{ $topImg }}</div>
  </div>

  @endforeach

  {{-- Totals row --}}
  <div class="tstr" style="padding:6px;font-weight:bold">
    Разом: {{ count($rows) }} | Сума: {{ number_format($total_sum, 2, '.', '') }} грн
  </div>

  {{-- Pagination --}}
  @include('partials.navigator', [
  'pos' => $pos,
  'pos2' => 30,
  'max' => $total,
  'doc' => $doc,
  ])

  @endif
</div>

@endsection