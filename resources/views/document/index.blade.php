@extends('home')

@section('title', 'Документи')

@section('content')
<div class="row">
    <div class="col-3">
        @include('partials.filter')
    </div>

    <div class="col-9">
        @include('partials.panel')
    </div>
</div>

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

  @if(empty($items))
  <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
    Документи відсутні...
  </div>
  @else

  @foreach($items as $item)
  <div class="txtbox-price-docs">
    <div class="numdoc-docs">
      <a href="{{ $item['linkUrl'] }}" title="відкрити">{{ $item['num'] }}</a>
    </div>
    <div class="status-docs4">{{ $item['data'] }}<br>{{ $item['time'] }}</div>
    <div class="captionbox-docs">
      <a href="{{ $item['linkUrl'] }}" class="title">
        {!! $item['org'] !!}{{ $item['fullName'] }}<br>
        <span class="city">{{ $item['city'] }} {{ $item['poshta'] }}</span><br>
        <span class="phone">{{ $item['phone'] }}</span>
      </a>
    </div>
    <div class="status-docs3" style="background:{{ $item['color'] }}">
      {{ $item['statusName'] }} {!! $item['signal'] !!}
    </div>
    <div class="pricebox-docs1">
      <span class="money">{{ $item['summaFmt'] }}</span>
    </div>
    <div class="captionbox-docs2">{!! $item['content'] !!}</div>
    <div class="status-docs2">{{ $item['skladsName'] }} {{ $item['manager'] }} {{ $item['topImg'] }}</div>
  </div>

  @endforeach

  {{-- Totals row --}}
  <div class="tstr" style="padding:6px;font-weight:bold">
    Разом: {{ count($items) }} | Сума: {{ number_format($total_sum, 2, '.', '') }} грн
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