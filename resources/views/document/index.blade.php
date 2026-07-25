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
'ZV' => 'Выдать ЗП',
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
  @elseif($doc === 'ZV')
    <div class="top-action-create">
      <button type="button" class="button top-action-create-btn" data-zv-create data-doc="ZV">
        + Выдать ЗП
      </button>
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

  @if($doc === 'ZV' && ($unassignedSalaryDocuments ?? collect())->isNotEmpty())
    <div class="mb-3">
      <h3 class="h6 mb-2">Документы ZP без платежной ведомости</h3>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Документ</th>
              <th>Дата</th>
              <th>Сотрудник</th>
              <th class="text-end">Сумма</th>
              <th>Статус</th>
            </tr>
          </thead>
          <tbody>
            @foreach($unassignedSalaryDocuments as $salaryDocument)
              <tr>
                <td>
                  <a href="{{ route($documentRoutes . '.show', [
                    'doc' => 'ZP',
                    'doc_id' => $salaryDocument->id,
                    'num' => $salaryDocument->num,
                    'year' => strlen((string) $salaryDocument->data) >= 10
                      ? substr((string) $salaryDocument->data, 6, 4)
                      : session('year', date('Y')),
                  ]) }}">
                    ZP №{{ $salaryDocument->num }}
                  </a>
                </td>
                <td>{{ $salaryDocument->data }}</td>
                <td>{{ $salaryDocument->employee_name }}</td>
                <td class="text-end">{{ number_format((float) $salaryDocument->summa, 2, '.', ' ') }} грн</td>
                <td>{{ (int) $salaryDocument->provodka === 1 ? 'Проведен' : 'Не проведен' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

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
        <a href="{{ $item['linkUrl'] }}" title="{{ __('document.open') }}"
          @if($doc === 'ZV') data-zv-open data-statement-id="{{ $item['id'] }}" @endif>{{ $item['num'] }}</a>
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
      <a href="{{ $item['linkUrl'] }}" class="title"
        @if($doc === 'ZV') data-zv-open data-statement-id="{{ $item['id'] }}" @endif>
        @if($doc === 'ZV')
          <span class="compact-client-line compact-main">Платежная ведомость №{{ $item['num'] }}</span>
        @endif
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

@if($doc === 'ZV')
  @include('document.salary_statement_modal', [
    'salaryEmployees' => $salaryEmployees ?? collect(),
    'salaryCashboxes' => $salaryCashboxes ?? collect(),
    'salaryPaymentTypes' => $salaryPaymentTypes ?? collect(),
  ])
@endif

@endsection
