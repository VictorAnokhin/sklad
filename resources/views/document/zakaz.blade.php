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

@push('scripts')
<style>
  .top-action-bar {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    flex-wrap: nowrap;
    overflow-x: auto;
  }

  .top-action-filter {
    flex: 0 0 auto;
  }

  .top-action-create {
    flex: 0 0 auto;
    margin: 0;
  }

  .top-action-create-btn {
    width: auto !important;
    min-width: 150px;
    margin: 6px 0 !important;
    white-space: nowrap;
  }

  .top-action-panel {
    flex: 1 1 auto;
    min-width: 0;
  }

  .top-action-panel .tstr0 {
    margin-bottom: 0;
  }

  .document-compact-wrap {
    overflow: hidden;
  }

  .document-compact-list {
    display: grid;
    gap: 0.55rem;
    overflow-x: auto;
    padding-bottom: 0.2rem;
  }

  .document-compact-wrap .txtbox-price-docs {
    min-width: 1120px;
    grid-template-columns: 42px 104px minmax(220px, 1fr) 150px minmax(130px, 0.72fr) 104px minmax(320px, 1.55fr);
    gap: 0.5rem;
    padding: 0.72rem 0.8rem;
    margin-bottom: 0;
  }

  .document-compact-wrap .numdoc-docs,
  .document-compact-wrap .status-docs4,
  .document-compact-wrap .captionbox-docs,
  .document-compact-wrap .status-docs3,
  .document-compact-wrap .pricebox-docs1,
  .document-compact-wrap .captionbox-docs2 {
    min-width: 0;
    width: auto;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .document-compact-wrap .captionbox-docs a,
  .document-compact-wrap .captionbox-docs2,
  .document-compact-wrap .compact-date,
  .document-compact-wrap .status-docs4 {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .document-compact-wrap .compact-date {
    white-space: normal;
    line-height: 1.1;
  }

  .document-compact-wrap .compact-date-line {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .document-compact-wrap .captionbox-docs a br,
  .document-compact-wrap .captionbox-docs2 br,
  .document-compact-wrap .status-docs4 br {
    display: none;
  }

  .document-compact-wrap .status-docs3 {
    padding: 0.45rem 0.65rem;
    height: auto;
    margin-top: 0;
  }

  .document-compact-wrap .pricebox-docs1 {
    text-align: right;
  }

  .document-compact-wrap .captionbox-docs2 {
    font-size: 0.76rem;
    color: rgba(255, 255, 255, 0.78);
    white-space: normal;
    overflow: visible;
    text-overflow: unset;
    line-height: 1.18;
    word-break: break-word;
  }

  .document-compact-wrap .compact-sep {
    color: rgba(255, 255, 255, 0.32);
    margin: 0 0.35rem;
  }

  .document-compact-wrap .compact-main {
    font-weight: 600;
  }

  .document-compact-wrap .captionbox-docs a {
    white-space: normal;
    line-height: 1.15;
  }

  .document-compact-wrap .compact-client-line,
  .document-compact-wrap .phone {
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .document-compact-wrap .compact-client-line + .compact-client-line,
  .document-compact-wrap .compact-client-line + .phone {
    margin-top: 0.12rem;
  }

  @media (max-width: 721px) {
    .top-action-bar {
      flex-wrap: wrap;
    }

    .document-compact-wrap .txtbox-price-docs {
      display: grid;
      flex-wrap: nowrap;
    }
  }
</style>
@endpush
