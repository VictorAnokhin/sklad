@extends('home')

@section('title', __('document.title'))

@section('content')
@php
  $docLabel = \App\Models\Document::typeName($doc);
@endphp

<div class="ttable top-action-bar">
  <div class="top-action-filter">
    @include('partials.filter')
  </div>
  <form action="{{ route('document.save') }}" method="post" name="dataform" class="top-action-create">
    @csrf
    <input type="hidden" name="doc" value="{{ $doc }}">
    <input type="hidden" name="create_doc_type" value="{{ $doc }}">

    <button type="submit" name="run" value="{{ $docLabel }}"
      class="button top-action-create-btn">
      + {{ $docLabel }}
    </button>
  </form>
  <div class="top-action-panel">
    @include('partials.panel')
  </div>
</div>

{{-- Bulk-status form --}}
<div class="ttable document-compact-wrap">
  <form action="{{ route('document.bulkStatus') }}" method="post" id="bulkForm">
    @csrf
    <input type="hidden" name="doc" value="{{ $doc }}">

    @if(empty($items))
    <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
      {{ __('document.empty') }}
    </div>
    @else

    <div class="document-compact-list">
    @foreach ($items as $item)
    <div class="txtbox-price-docs">
      <div class="numdoc-docs">
        <a href="{!! $item['linkUrl'] !!}">{!! $item['num'] !!}</a>
      </div>
      <div class="status-docs4 compact-date">
        <span class="compact-date-line">{!! $item['data'] !!}</span>
        <span class="compact-date-line">{!! $item['time'] !!}</span>
      </div>
      <div class="captionbox-docs">
        <a href="{!! $item['linkUrl'] !!}" class="title">
          <span class="compact-client-line compact-main">{!! $item['org'] !!} {!! $item['fullName'] !!}</span>
          <span class="compact-client-line city">{!! $item['city'] !!} {!! $item['poshta'] !!}</span>
          <span class="phone">{!! $item['phone'] !!}</span>
        </a>
      </div>
      <div class="status-docs3" style="background:{!! $item['color'] !!}">
        {!! $item['statusName'] !!} {!! $item['signal'] !!}
      </div>
      @if(!empty($item['clientInfoHtml']))
    <div class="status-docs4" style="font-size:0.85em; min-width:120px;">
      {!! $item['clientInfoHtml'] !!}
    </div>
    @endif
      <div class="pricebox-docs1">
        <span class="money">{!! $item['summaFmt'] !!}</span>
      </div>
      <div class="captionbox-docs2">{!! $item['content'] !!}</div>
    </div>
    @endforeach
    </div>

    <div class="tstr" style="padding:6px;font-weight:bold">
      {{ __('document.total') }}: {{ count($items) }} | {{ __('document.sum') }}: {{ number_format($total_sum, 2, '.', '') }} {{ __('document.currency') }}
    </div>

    @include('partials.navigator', ['pos' => $pos, 'pos2' => 30, 'max' => $total, 'doc' => $doc])

    @endif

  </form>
</div>

@endsection
