@extends('home')

@section('title', \App\Models\Document::typeName($doc))

@section('content')
@php
$documentRoutes = $documentRoutePrefix ?? 'document';
$isLoanDocuments = $documentRoutes === 'bank.loanDocs';
$isProductionDocuments = in_array($doc, ['WO1', 'SP'], true);
$productionBtnLabel = $doc === 'SP' ? 'Спецификация' : 'Наряд';
$btnLabel = match($doc) {
'PO' => \App\Models\Document::typeName('PO'),
'RO' => \App\Models\Document::typeName('RO'),
'ZP' => 'Выдать ЗП',
'PP' => \App\Models\Document::typeName('PP'),
'PN' => \App\Models\Document::typeName('PN'),
'RN' => \App\Models\Document::typeName('RN'),
'WO1' => \App\Models\Document::typeName('WO1'),
'CH' => \App\Models\Document::typeName('CH'),
'SP' => \App\Models\Document::typeName('SP'),
'RA' => \App\Models\Document::typeName('RA'),
'VN' => \App\Models\Document::typeName('VN'),
default => __('document.create_new', ['name' => \App\Models\Document::typeName($doc)]),
};
@endphp

<div class="ttable top-action-bar">
  <div class="top-action-filter">
    @include('partials.filter', $isLoanDocuments ? ['filterButtonLabel' => 'Фильтр'] : [])
  </div>
  @if($isProductionDocuments)
    <form action="{{ route($documentRoutes . '.save') }}" method="post" name="dataform" class="top-action-create">
      @csrf
      <input type="hidden" name="year_N" value="{{ session('year', date('Y')) }}">
      <input type="hidden" name="create_doc_type" value="{{ $doc }}">

      <button type="submit" name="run" value="{{ $productionBtnLabel }}" class="button top-action-create-btn">
        + {{ $productionBtnLabel }}
      </button>
    </form>
  @elseif($isLoanDocuments)
    <div class="top-action-create">
      <a href="{{ route('bank.loanDocs.index', ['action' => 'create']) }}" class="button top-action-create-btn">
        + Создать заявку
      </a>
    </div>
  @else
    <form action="{{ route($documentRoutes . '.save') }}" method="post" name="dataform" class="top-action-create">
      @csrf
      <input type="hidden" name="year_N" value="{{ session('year', date('Y')) }}">
      <input type="hidden" name="create_doc_type" value="{{ $doc }}">

      <button type="submit" name="run" value="{{ $btnLabel }}" class="button top-action-create-btn">
        + {{ $btnLabel }}
      </button>
    </form>
  @endif
  <div class="top-action-panel">
    @if($isLoanDocuments)
      <div class="doc-tabs-wrap">
        <nav class="doc-tabs" aria-label="Меню кредитов">
          <a href="{{ route('bank.loanDocs.index') }}" class="doc-tab">
            <span class="doc-tab__label">Заявки (CRDT)</span>
          </a>
          <a href="{{ route('bank.loanDocs.index', ['doc' => 'CRO']) }}"
            class="doc-tab {{ $doc === 'CRO' ? 'is-active' : '' }}">
            <span class="doc-tab__label">Кредиты (CRO)</span>
          </a>
          <a href="{{ route('bank.loanDocs.index', ['doc' => 'CPO']) }}"
            class="doc-tab {{ $doc === 'CPO' ? 'is-active' : '' }}">
            <span class="doc-tab__label">Выплаты (CPO)</span>
          </a>
        </nav>
      </div>
    @else
      @include('partials.panel')
    @endif
  </div>
</div>

<div class="ttable document-compact-wrap">

  @if(empty($items))
  <div style="text-align:center;padding:20px;color:#CC0000;font-size:1.2em">
    {{ __('document.empty') }}
  </div>
  @else

  <div class="document-compact-list">
  @foreach($items as $item)
  <div class="txtbox-price-docs">
    <div class="order-card__header">
      <div class="numdoc-docs">
        <a href="{{ $item['linkUrl'] }}" title="{{ __('document.open') }}">{{ $item['num'] }}</a>
      </div>
      <div class="status-docs-icons--mobile">
        {!! $item['signalIcons'] !!}
      </div>
      <div class="status-docs4 compact-date">
        <span class="compact-date-line">{{ $item['data'] }}</span>
        <span class="compact-date-line">{{ $item['time'] }}</span>
      </div>
    </div>
    <div class="captionbox-docs">
      <a href="{{ $item['linkUrl'] }}" class="title">
        <span class="compact-client-line compact-main">{!! $item['org'] !!}{{ $item['fullName'] }}</span>
        <span class="compact-client-line city">{{ $item['city'] }} {{ $item['poshta'] }}</span>
        @if(in_array($doc, ['PO', 'RO', 'PP', 'ZP']))
          <span class="compact-client-line city text-muted">
            <strong>{{ __('money.filter_cashbox') }}:</strong> {{ $item['moneyName'] ?: '—' }}
            | 
            <strong>{{ __('money.filter_payment_type') }}:</strong> {{ $item['reestrName'] ?: '—' }}
          </span>
        @endif
        <span class="phone">{{ $item['phone'] }}</span>
      </a>
    </div>
    <div class="status-docs3" style="background:{{ $item['color'] }}">
      {{ $item['statusName'] }}
    </div>
    <div class="pricebox-docs1">
      <span class="money">{{ $item['summaFmt'] }}</span>
    </div>
    <div class="captionbox-docs2">{!! $item['content'] !!}</div>
    <div class="status-docs-icons">
      {!! $item['signalIcons'] !!}
    </div>
  </div>

  @endforeach
  </div>

  {{-- Totals row --}}
  <div class="tstr" style="padding:6px;font-weight:bold">
    {{ __('document.total') }}: {{ count($items) }} | {{ __('document.sum') }}: {{ number_format($total_sum, 2, '.', '') }} {{ __('document.currency') }}
  </div>

  {{-- Pagination --}}
  @include('partials.navigator', [
  'pos' => $pos,
  'pos2' => 30,
  'max' => $total,
  'doc' => $doc,
  'routeName' => $documentRoutes . '.index',
  ])

  @endif
</div>

@endsection
