@extends('home')

@section('title', \App\Models\Document::typeName($doc))

@push('styles')
  <style>
    .signal-badge {
      display: inline-flex;
      font-size: 1rem;
      line-height: 1;
      margin-right: 2px;
      opacity: 0.18;
      cursor: default;
    }

    .signal-badge--ok {
      opacity: 0.95;
    }
  </style>
@endpush

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

      <button type="submit" name="run" value="{{ $docLabel }}" class="button top-action-create-btn">
        + {{ $docLabel }}
      </button>
    </form>
    <div class="top-action-panel">
      @include('partials.panel')
    </div>
  </div>

  {{-- Bulk-status form --}}
  <div class="ttable document-compact-wrap zakaz">
    <form action="{{ route('document.bulkStatus') }}" method="post" id="bulkForm">
      @csrf
      <input type="hidden" name="doc" value="{{ $doc }}">

      @if(empty($items))
        <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
          {{ __('document.empty') }}
        </div>
      @else

        <!-- Desktop Table View -->
        <div class="desktop-table d-none d-md-block">
          <div class="document-compact-list">
                @foreach ($items as $item)
                  <div class="txtbox-price-docs">
                    <div class="order-card__header">
                      <div class="numdoc-docs">
                        <a href="{!! $item['linkUrl'] !!}">{!! $item['num'] !!}</a>
                    </div>
                    <div class="status-docs4 compact-date">
                      <span class="compact-date-line">{!! $item['data'] !!}</span>
                        <span class="compact-date-line">{!! $item['time'] !!}</span>
                    </div>
                    <div class="captionbox-docs">
                      <span class="compact-client-line compact-main">{!! $item['org'] !!} {!! $item['fullName'] !!}</span>
                      @if($item['city'] || $item['poshta'] || $item['phone'])
                        <span class="client-meta">
                          @if($item['city'] || $item['poshta'])
                            <span class="compact-client-line city">
                              <span class="meta-icon">📍</span> {!! $item['city'] !!} {!! $item['poshta'] !!}
                            </span>
                          @endif
                          @if($item['phone'])
                            <span class="client-meta-item">
                              <span class="phone">📞 {!! $item['phone'] !!}</span>
                            </span>
                          @endif
                        </span>
                      @endif
                    </div>
                    <div class="status-docs3" style="background:{{ $item['color'] }}">
                      {!! $item['statusName'] !!}
                    </div>
                    <div class="pricebox-docs1">
                      <span class="money">{!! $item['summaFmt'] !!}</span>
                    </div>
                    <div class="captionbox-docs2">{!! $item['content'] !!}</div>
                  </div>
                  <div class="status-docs-icons">
                    {!! $item['signalIcons'] !!}
                  </div>
                </div>
                @endforeach
        </div>
        </div>
        <!-- Mobile Cards View -->
        <div class="mobile-cards">
          <div class="document-compact-list">
            @foreach ($items as $item)
              <div class="txtbox-price-docs">
                <div class="order-card__header">
                  <div class="numdoc-docs">
                    <a href="{!! $item['linkUrl'] !!}">{!! $item['num'] !!}</a>
                  </div>
                  <div class="status-docs-icons--mobile d-md-none">
                    {!! $item['signalIcons'] !!}
                  </div>
                  <div class="status-docs4 compact-date">
                    <span class="compact-date-line">{!! $item['data'] !!}</span>
                    <span class="compact-date-line">{!! $item['time'] !!}</span>
                  </div>
                </div>
                <div class="captionbox-docs">
                  <a href="{!! $item['linkUrl'] !!}" class="title">
                    <span class="compact-client-line compact-main">{!! $item['org'] !!} {!! $item['fullName'] !!}</span>
                    @if($item['city'] || $item['poshta'] || $item['phone'])
                      <span class="client-meta">
                        @if($item['city'] || $item['poshta'])
                          <span class="client-meta-item">
                            <span class="meta-icon">📍</span> {!! $item['city'] !!} {!! $item['poshta'] !!}
                          </span>
                        @endif
                        @if($item['phone'])
                          <span class="client-meta-item">
                            <span class="meta-icon">📞</span> {!! $item['phone'] !!}
                          </span>
                        @endif
                      </span>
                    @endif
                  </a>
                </div>

                <div class="captionbox-docs2 note">{!! $item['content'] !!}</div>

                <div class="order-card-footer">
                  <div class="pricebox-docs1">
                    <span class="money">{!! $item['summaFmt'] !!}</span>
                  </div>
                  <div class="status-docs3 text-truncate" style="background:{!! $item['color'] !!};">
                    {!! $item['statusName'] !!}
                  </div>
                </div>

                <div class="status-docs4 client-info" style="min-width:110px;">
                  {!! $item['clientInfoHtml'] ?? '' !!}
                </div>
              </div>
            @endforeach
          </div>
        </div>

        <div class="tstr" style="padding:6px;font-weight:bold">
          {{ __('document.total') }}: {{ count($items) }} | {{ __('document.sum') }}:
          {{ number_format($total_sum, 2, '.', '') }} {{ __('document.currency') }}
        </div>

        @include('partials.navigator', ['pos' => $pos, 'pos2' => 30, 'max' => $total, 'doc' => $doc])

      @endif

    </form>
  </div>

@endsection