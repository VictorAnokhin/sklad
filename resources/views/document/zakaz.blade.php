@extends('layouts.app')

@section('title', $doc === 'ZOUT' ? 'Замовлення' : 'Закупки')

@section('content')

{{-- New document + year selector --}}
<div class="ttable">
  <form action="{{ route('document.save') }}" method="post" name="dataform">
    @csrf
    <input type="hidden" name="doc" value="{{ $doc }}">

    <select name="year_N" style="width:80px;margin:4px">
      @for($y = (int)date('Y'); $y >= 2020; $y--)
        <option value="{{ $y }}" {{ $y == session('year', date('Y')) ? 'selected' : '' }}>{{ $y }}</option>
      @endfor
    </select>

    <button type="submit"
            name="run"
            value="{{ $doc === 'ZOUT' ? 'Новий замовлення' : 'Нова закупівля' }}"
            class="button" style="width:140px;margin:4px">
      + {{ $doc === 'ZOUT' ? 'Нове замовлення' : 'Нова закупівля' }}
    </button>
  </form>
</div>

{{-- Bulk-status form --}}
<div class="ttable">
<form action="{{ route('document.bulkStatus') }}" method="post" id="bulkForm">
  @csrf
  <input type="hidden" name="doc" value="{{ $doc }}">

@if(empty($rows))
  <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
    Документи відсутні...
  </div>
@else

@php $total_sum = 0; @endphp

@foreach($rows as $row)
@php
  $statusId   = $row->status ?? '';
  $conf       = $confMap[$statusId] ?? null;
  $statusName = $conf ? h(convert_from_base($conf->name)) : '';
  $color      = h($conf->color ?? '');
  $summa      = (float)$row->summa;
  $total_sum += $summa;
  $summaFmt   = number_format($summa, 2, ',', "'");
  $year       = strlen((string)($row->data ?? '')) >= 10 ? substr((string)$row->data, 6, 4) : date('Y');
  $content    = h(convert_from_base($row->content ?? ''));
  if ($row->ttn) $content .= '<br>НП:' . h($row->ttn);
  $orgname    = h(convert_from_base($row->orgname ?? ''));
  $kod1       = h($row->kod1 ?? '');
  $org        = $orgname ? "{$orgname}, {$kod1}" : '';
  $fullName   = h(trim(
      convert_from_base($row->secondname ?? '') . ' '
    . convert_from_base($row->name ?? '') . ' '
    . convert_from_base($row->fathername ?? '')
  ));
  $city    = h(convert_from_base($row->city ?? ''));
  $poshta  = $row->poshta ? 'НП ' . h($row->poshta) : '';
  $phone   = h(formatPhone((string)($row->phone ?? '')));
  $manager = h(strtolower(convert_from_base($row->manager ?? '')));
  $signal  = ($statusName === '' && $doc === 'ZOUT') ? "<span class='alink3'>new</span>" : '';
  $linkUrl = route('document.show', [
      'doc_id' => $row->id, 'num' => $row->num, 'year' => $year, 'doc' => $doc,
  ]);
@endphp

<div class="txtbox-price-docs">
  <div class="numdoc-docs">
    <input type="checkbox" name="ids[]" value="{{ $row->id }}" style="margin:2px">
    <a href="{{ $linkUrl }}">{{ h($row->num) }}</a>
  </div>
  <div class="status-docs4">{{ h($row->data) }}<br>{{ h($row->time) }}</div>
  <div class="captionbox-docs">
    <a href="{{ $linkUrl }}" class="title">
      {{ $org }}<br>{{ $fullName }}<br>
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
  <div class="status-docs2">{{ $manager }}</div>
</div>

@endforeach

<div class="tstr" style="padding:6px;font-weight:bold">
  Разом: {{ count($rows) }} | Сума: {{ number_format($total_sum, 2, '.', '') }} грн
</div>

@include('partials.navigator', ['pos' => $pos, 'pos2' => 30, 'max' => $total, 'doc' => $doc])

@endif

</form>
</div>

@endsection
