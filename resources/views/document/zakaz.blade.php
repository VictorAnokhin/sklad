@extends('layouts.app')

@section('contentbody')

{{-- New document + year selector --}}
<div class="ttable">
  <form action="{{ route('document.save') }}" method="post" name="dataform">
    @csrf
    <input type="hidden" name="doc" value="{{ $doc }}">

    <button type="submit" name="run" value="{{ $doc === 'ZOUT' ? 'Новий замовлення' : 'Нова закупівля' }}"
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

    @php
        $listData = \App\Models\Document::showDocumentList($rows, $confMap, $doc);
        $items = $listData['items'];
        $total_sum = $listData['total_sum'];
    @endphp

    @foreach ($items as $item)
    <div class="txtbox-price-docs">
      <div class="numdoc-docs">
        <input type="checkbox" name="ids[]" value="{{ $item['id'] }}" style="margin:2px">
        <a href="{!! $item['linkUrl'] !!}">{!! $item['num'] !!}</a>
      </div>
      <div class="status-docs4">{!! $item['data'] !!}<br>{!! $item['time'] !!}</div>
      <div class="captionbox-docs">
        <a href="{!! $item['linkUrl'] !!}" class="title">
          {!! $item['org'] !!}<br>{!! $item['fullName'] !!}<br>
          <span class="city">{!! $item['city'] !!} {!! $item['poshta'] !!}</span><br>
          <span class="phone">{!! $item['phone'] !!}</span>
        </a>
      </div>
      <div class="status-docs3" style="background:{!! $item['color'] !!}">
        {!! $item['statusName'] !!} {!! $item['signal'] !!}
      </div>
      <div class="pricebox-docs1">
        <span class="money">{!! $item['summaFmt'] !!}</span>
      </div>
      <div class="captionbox-docs2">{!! $item['content'] !!}</div>
      <div class="status-docs2">{!! $item['manager'] !!}</div>
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