@extends('home')

@section('content')
    @php
        $documentRoutes = $documentRoutePrefix ?? 'document';
        $errors = $errors ?? new \Illuminate\Support\ViewErrorBag;
        $isLoanDocument = in_array($documentRoutes, ['loan', 'bank.loanDocs'], true);
        $showLoanRelatedMenu = ! $isLoanDocument || in_array($doc, ['CRDT', 'CPLAN', 'CRO', 'CPO', 'CDOC'], true);
        $showLoanRepaymentSchedule = $isLoanDocument && $doc === 'CPLAN';
        $hideGoodsSection = $isLoanDocument && in_array($doc, ['CRDT', 'CPLAN'], true);
    @endphp

    @if($isLoanDocument)
        @if(in_array($doc, ['CRDT', 'CRO', 'CPO'], true))
            <div class="doc-tabs-wrap">
                <nav class="doc-tabs" aria-label="Кредитные документы">
                <a href="{{ route('bank.loanDocs.index') }}"
                    class="doc-tab {{ $doc === 'CRDT' ? 'is-active' : '' }}">
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
        @endif
    @else
        @include('partials.panel')
    @endif

    <style>

        .goods-table-col-code,
        .goods-table-col-qty,
        .goods-table-col-price,
        .goods-table-col-sum,
        .goods-table-col-actions {
            white-space: nowrap;
        }

        .goods-table-col-actions {
            width: 1%;
            text-align: center;
        }

        .goods-table-col-actions .remove-btn {
            min-width: 42px;
        }

        #goodsTable {
            table-layout: fixed;
        }

        #goodsTable .goods-table-col-qty {
            width: 150px;
        }

        #goodsTable .goods-table-col-price,
        #goodsTable .goods-table-col-sum {
            width: 152px;
        }

        #goodsTable .goods-table-col-name {
            width: 32%;
        }

        .goods-name-map-wrap {
            display: flex;
            gap: 6px;
            align-items: center;
        }

        .goods-name-map-wrap input {
            flex: 1 1 auto;
        }

        .product-map-btn {
            flex: 0 0 auto;
            min-width: 44px;
            padding-left: 8px;
            padding-right: 8px;
            font-weight: 700;
        }

        .product-map-btn.is-mapped {
            border-color: #22c55e;
            color: #22c55e;
        }

        .mapping-product-row {
            cursor: pointer;
        }

        .mapping-product-row:hover {
            background: rgba(13, 110, 253, 0.08);
        }

        #goodsTable .goods-table-col-price .form-control,
        #goodsTable .goods-table-col-sum .form-control {
            min-width: 100%;
        }

        #goodsTable tbody tr.goods-zero-sum {
            outline: 2px solid #fbbf24;
            outline-offset: -2px;
        }

        #goodsTable tbody tr.goods-zero-sum .goods-sum {
            border-color: #fbbf24;
            box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.22);
        }

        #goodsTable .goods-table-col-qty .input-group {
            flex-wrap: nowrap;
            min-width: 0;
        }

        #goodsTable .goods-table-col-qty .goods-count {
            flex: 0 1 58px;
            min-width: 48px;
            max-width: 64px;
            text-align: center;
        }

        #goodsTable .goods-table-col-qty .btn {
            flex: 0 0 34px;
            min-width: 34px;
            padding-left: 0;
            padding-right: 0;
        }

        .goods-table-col-name input,
        .goods-table-col-price input,
        .goods-table-col-sum input,
        .goods-table-col-code input,
        .goods-table-col-qty input {
            min-width: 0;
        }

        .file-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .file-preview-card {
            width: 120px;
            min-height: 120px;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            background: rgba(15, 23, 42, 0.85);
            color: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            font-size: 0.78rem;
        }

        .file-preview-card img {
            width: 100%;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .file-preview-card .file-preview-name {
            width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-bottom: 6px;
        }

        .file-preview-card a {
            color: #93c5fd;
            text-decoration: none;
            word-break: break-all;
            font-size: 0.75rem;
        }

        .file-preview-card .file-preview-icon {
            width: 100%;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 2rem;
        }

        .client-modal-field .form-control,
        .client-modal-field .form-select {
            min-height: 42px;
        }

        @media (max-width: 767.98px) {
            #goodsTable, 
            #goodsTable tbody, 
            #goodsTable tr, 
            #goodsTable td {
                display: block !important;
                box-sizing: border-box;
            }

            #goodsTable {
                border: 0 !important;
                background: transparent !important;
            }

            #goodsTable .goods-table-header {
                display: none !important;
            }

            #goodsTable tbody {
                display: flex !important;
                flex-direction: column;
                gap: 12px;
            }

            #goodsTable tbody tr {
                border-left: 0 !important;
                border-right: 0 !important;
                border-top: 1px solid rgba(148, 163, 184, 0.35);
                border-bottom: 1px solid rgba(148, 163, 184, 0.35);
                border-radius: 0;
                padding: 16px 12px;
                background: rgba(15, 23, 42, 0.22);
                box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
                margin-left: -12px;
                margin-right: -12px;
                width: calc(100% + 24px) !important;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) {
                display: grid !important;
                grid-template-columns: repeat(10, minmax(0, 1fr));
                gap: 8px;
                align-items: start;
            }

            #goodsTable tbody td {
                border: 0;
                padding: 0;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) td + td {
                margin-top: 0;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-name {
                grid-column: 1 / -1;
                order: 1;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-actions {
                grid-column: 1 / -1;
                order: 7;
                margin-top: 4px;
                text-align: center;
                border-top: 1px solid rgba(148, 163, 184, 0.1);
                padding-top: 10px;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-code {
                grid-column: 1 / -1;
                order: 3;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-qty {
                grid-column: 1 / -1;
                order: 4;
                width: 100% !important;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-price {
                grid-column: 1 / span 5;
                order: 5;
                width: 100% !important;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-sum {
                grid-column: 6 / span 5;
                order: 6;
                width: 100% !important;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-name,
            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-code,
            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-qty,
            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-actions {
                width: 100% !important;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-price,
            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-sum {
                display: block;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 4px;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                color: #94a3b8;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-name input[readonly],
            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-code input[readonly] {
                background: transparent !important;
                border: none !important;
                padding: 0 !important;
                box-shadow: none !important;
                min-height: auto;
                white-space: normal;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-name input[readonly] {
                font-weight: 600;
                font-size: 1.05rem;
                color: #f8fafc !important;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-code input[readonly] {
                font-size: 0.85rem;
                color: #94a3b8 !important;
                margin-top: -4px;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-actions::before {
                display: none;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-actions .remove-btn {
                width: auto;
                min-width: 100px;
                min-height: 32px;
                padding: 4px 12px;
                font-size: 0.85rem;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .form-control,
            #goodsTable tbody tr:not(#emptyGoodsRow) .input-group {
                width: 100% !important;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-qty .input-group {
                flex-wrap: nowrap;
            }

            #goodsTable tbody tr:not(#emptyGoodsRow) .goods-table-col-qty .btn {
                min-width: 50px;
                font-size: 1.2rem;
            }

            #goodsTable tbody tr#emptyGoodsRow td {
                border: 1px dashed rgba(148, 163, 184, 0.35);
                border-radius: 14px;
                padding: 16px 12px;
                background: rgba(15, 23, 42, 0.12);
            }

            .doc-sum-box {
                justify-content: stretch !important;
            }

            .doc-sum-box-inner {
                width: 100%;
            }

            .doc-actions {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(15, 23, 42, 0.9);
                padding: 12px 16px;
                display: flex;
                flex-direction: column;
                gap: 8px;
                z-index: 1050;
                box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.5);
                border-top: 1px solid rgba(148, 163, 184, 0.2);
                backdrop-filter: blur(10px);
            }

            .doc-actions .btn {
                width: 100% !important;
                margin: 0 !important;
                padding: 12px;
                font-weight: 600;
            }

            .doc-actions .post-checkbox {
                margin-bottom: 4px !important;
            }

            .doc-page {
                padding-bottom: 240px !important;
            }

            #newClientModal .modal-dialog {
                width: calc(100vw - 24px);
                max-width: calc(100vw - 24px);
                height: auto;
                min-height: 0;
                margin: 12px auto;
            }

            #newClientModal .modal-content {
                height: auto;
                min-height: 0;
                max-height: calc(100dvh - 24px);
                border-radius: 10px;
                border: 0;
                overflow: hidden;
            }

            #newClientModal .modal-body {
                max-height: calc(100dvh - 150px);
                overflow-y: auto;
                padding: 8px 12px;
            }

            #newClientModal .modal-footer {
                padding: 12px 16px calc(12px + env(safe-area-inset-bottom));
            }

            #newClientModal .modal-footer .btn {
                flex: 1 1 100%;
            }

            #newClientModal .modal-client-grid {
                display: flex;
                flex-direction: column;
                gap: 0;
            }

            #newClientModal .modal-client-grid .col-12,
            #newClientModal .modal-client-grid .col-md-6 {
                width: 100%;
            }

            #newClientModal .client-modal-field {
                margin: 0;
                padding-top: 0;
                padding-bottom: 0;
            }

            #newClientModal .client-modal-field .form-label {
                margin: 0;
                line-height: 1.1;
            }

            #newClientModal .client-modal-field .form-control,
            #newClientModal .client-modal-field .form-select {
                font-size: 17px;
                line-height: 1.2;
                min-height: 44px;
                padding-top: 8px;
                padding-bottom: 8px;
                margin-top: 0;
                margin-bottom: 0;
            }
        }
    </style>

    <div class="ttable doc-page">

        {{-- Header --}}
        <div class="doc-header">
            <h2>
                @if($isLoanDocument)
                    {{ \App\Models\Document::typeName($doc) }} № {{ $document->num }}
                @else
                    {{ \App\Models\Document::typeName($doc) }} № {{ $document->num }}
                @endif
            </h2>
        </div>

        <div class="mb-2 d-flex flex-wrap gap-2">
            @if($isLoanDocument)
                <a href="{{ route('bank.loanDocs.index') }}" class="btn btn-outline-secondary btn-sm">← Назад в кредиты</a>
            @else
                <a href="{{ $documentIndexUrl }}" class="btn btn-outline-secondary btn-sm">
                    ← До списку {{ \App\Models\Document::typeName($doc) }}
                </a>
                @if(!empty($parentDocumentUrl) && !empty($parentDocument))
                    <a href="{{ $parentDocumentUrl }}" class="btn btn-outline-primary btn-sm">
                        ← До {{ \App\Models\Document::typeName($parentDocument->type) }} № {{ $parentDocument->num }}
                    </a>
                @endif
            @endif
        </div>

        @if(session('error'))
            <div class="alert alert-danger py-2">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger py-2">
                Увага: Форма містить помилки (перевірте поля, що виділені червоним).
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success py-2">
                {{ session('success') }}
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning py-2">
                {{ session('warning') }}
            </div>
        @endif

        {{-- Related icons strip (client_info) --}}
        @if(! $isLoanDocument && !empty($relatedIcons))
            <div class="alert alert-secondary py-2 related-icons-bar">
                <strong>Зв'язані:</strong> {!! $relatedIcons !!}
            </div>
        @endif

        <form action="{{ route($documentRoutes . '.save') }}" method="post" class="compact-form" enctype="multipart/form-data">
            @csrf
            @php
                $documentDateValue = (string) ($document->data ?? '');
                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $documentDateValue) === 1) {
                    $documentDateValue = \DateTimeImmutable::createFromFormat('d-m-Y', $documentDateValue)?->format('Y-m-d') ?? '';
                }
            @endphp
            <input type="hidden" name="doc_id" value="{{ $document->id }}">
            <input type="hidden" name="doc" value="{{ $doc }}">

            <div class="doc-layout">
                {{-- LEFT: Document form --}}
                <div class="doc-form-col">
                    <!-- Row 1: Номер | Дата | Время -->
                    <div class="doc-form-row doc-form-row-three-cols">
                        <div class="col-f">
                            <label>Номер</label>
                            <input type="text" name="num" class="form-control text-white" value="{{ $document->num ?? '' }}"
                                placeholder="Номер документа">
                        </div>
                        <div class="col-f">
                            <label>Дата</label>
                            <input type="date" name="data" class="form-control text-white" value="{{ $documentDateValue }}">
                        </div>
                        <div class="col-f">
                            <label>Время</label>
                            <input type="time" name="time" class="form-control text-white" value="{{ $document->time ?? '' }}">
                        </div>
                    </div>

                    <!-- Row 2: Склад (only for RN, PN, WO1) -->
                    @if(!$showLoanRepaymentSchedule && in_array($doc, ['RN', 'CPLAN', 'PN', 'WO1'], true))
                        <div class="doc-form-row-single">
                            <label>Склад</label>
                            <select name="sklads" class="form-select text-white">
                                <option value="">— Оберіть склад —</option>
                                @foreach(($skladsList ?? collect()) as $skladOption)
                                    <option value="{{ $skladOption->id }}" {{ (string) ($document->sklads ?? '') === (string) $skladOption->id ? 'selected' : '' }}>
                                        {{ $skladOption->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('sklads')
                                <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <!-- Row 3: ТТН | Статус (only for ZOUT and ZIN) -->
                    @if(in_array($doc, ['ZOUT', 'CRDT', 'ZIN'], true))
                        <div class="doc-form-row doc-form-row-two-cols">
                            <div class="col-f">
                                <label>ТТН Нова Пошта</label>
                                <input type="text" name="ttn" class="form-control text-white" value="{{ $document->ttn ?? '' }}">
                                @if($doc === 'ZOUT')
                                    <div class="form-check d-flex align-items-center mt-2">
                                        <input type="hidden" name="sms_flag" value="0">
                                        <input type="checkbox" class="form-check-input" id="send_order_sms" name="sms_flag" value="1" {{ (string) ($document->sms_flag ?? '0') === '1' ? 'checked' : '' }}>
                                        <label class="form-check-label ms-2" for="send_order_sms">SMS</label>
                                    </div>
                                @endif
                            </div>
                            <div class="col-f">
                                <label>Статус</label>
                                <select name="status" class="form-select text-white">
                                    <option value="">— Оберіть статус —</option>
                                    <option value="0" {{ (string) $document->status === '0' ? 'selected' : '' }}>0 - Новий
                                    </option>
                                    @foreach(($statusList ?? collect()) as $statusOption)
                                        <option value="{{ $statusOption->id }}" {{ (string) $document->status === (string) $statusOption->id ? 'selected' : '' }}>
                                            {{ $statusOption->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <!-- Row 3b: Каса | Вид платежу (only for PO/RO) -->
                    @if(in_array($doc, ['PO', 'CPO', 'RO', 'CRO', 'ZP'], true))
                        <div class="doc-form-row doc-form-row-two-cols">
                            <div class="col-f">
                                <label>Каса</label>
                                <select name="oplata" id="documentCashboxSelect" class="form-select text-white">
                                    <option value="">— Оберіть касу —</option>
                                    @foreach(($oplataList ?? collect()) as $oplataOption)
                                        @php
                                            $cashboxCurrency = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($oplataOption->currency ?? ''))) ?: 'UAH';
                                        @endphp
                                        <option value="{{ $oplataOption->id }}" data-currency="{{ $cashboxCurrency }}" {{ (string) ($document->oplata ?? '') === (string) $oplataOption->id ? 'selected' : '' }}>
                                            {{ $oplataOption->name }} ({{ $cashboxCurrency }})
                                        </option>
                                    @endforeach
                                </select>
                                @if($doc === 'ZP')
                                    <div class="form-text text-muted">Валюта ЗП береться з обраної каси.</div>
                                @endif
                                @error('oplata')
                                    <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-f">
                                <label>Вид платежу</label>
                                <select name="reestr" class="form-select text-white">
                                    <option value="">— Оберіть вид платежу —</option>
                                    @foreach(($reestrList ?? collect()) as $reestrOption)
                                        <option value="{{ $reestrOption->id }}" {{ (string) ($document->reestr ?? '') === (string) $reestrOption->id ? 'selected' : '' }}>
                                            {{ $reestrOption->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('reestr')
                                    <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <!-- Row 2: Клієнт -->
                    <div class="doc-form-row-single">
                        <label>{{ $doc === 'ZP' ? 'Сотрудник' : 'Клієнт' }}</label>
                        <div class="client-search-row d-flex gap-1">
                            <input type="text" id="clientSearchInput" class="form-control flex-grow-1 text-white" placeholder="{{ $doc === 'ZP' ? 'Пошук співробітника...' : 'Пошук клієнта...' }}"
                                autocomplete="off">
                            @if($doc !== 'ZP' && !$showLoanRepaymentSchedule)
                                <button type="button" id="editClientBtn" class="btn btn-outline-secondary text-white" data-bs-toggle="modal"
                                    data-bs-target="#newClientModal" style="{{ $client ? '' : 'display:none;' }}">Изменить</button>
                                <button type="button" class="btn btn-outline-primary text-white" id="newClientBtn" data-bs-toggle="modal"
                                    data-bs-target="#newClientModal">Новий</button>
                            @endif
                        </div>
                        <div id="clientSearchResults" class="list-group client-search-results">
                        </div>
                        <input type="hidden" name="client1" id="client1_id"
                            value="{{ old('client1', $client ? $client->id : '') }}"
                            data-orgname="{{ $client->orgname ?? '' }}"
                            data-name="{{ $client->name ?? '' }}"
                            data-secondname="{{ $client->secondname ?? '' }}"
                            data-phone="{{ $client->phone ?? '' }}"
                            data-city="{{ $client->city ?? '' }}"
                            data-region="{{ $client->region ?? '' }}"
                            data-poshta="{{ $client->poshta ?? '' }}"
                            data-status="{{ $client->idstatus ?? '' }}"
                            data-usergroup="{{ $client->usergroup ?? '' }}"
                            data-usergroup-name="{{ optional(($clientGroups ?? collect())->firstWhere('id', $client->usergroup ?? null))->name ?? '' }}">
                        <div id="selectedClientDetails"
                            class="alert {{ $client ? 'alert-secondary' : 'alert-warning' }} py-1 mt-1 selected-client-details {{ $client ? 'selected-client-details--filled' : 'selected-client-details--empty' }}">
                            @if($client)
                                @if($client->orgname)
                                    <strong>{{ $client->orgname }}</strong> |
                                @endif
                                {{ trim(($client->secondname ?? '') . ' ' . ($client->name ?? '')) }}<br>
                                {{ $client->phone }} |
                                {{ $client->region ? $client->region . ' | ' : '' }}{{ $client->city }}{{ $client->poshta ? ' | ' . $client->poshta : '' }}
                            @else
                                {{ $doc === 'ZP' ? 'Сотрудник не выбран' : 'Клієнт не обраний' }}
                            @endif
                        </div>
                        @error('client1')
                            <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($hideGoodsSection)
                        <div class="loan-request-fields">
                            <div class="doc-form-row doc-form-row-two-cols">
                                <div class="col-f">
                                    <label>Тип залога</label>
                                    <input type="text" name="collateral_type" value="{{ old('collateral_type', $loanMeta['collateral_type'] ?? 'Автомобиль') }}"
                                        class="form-control text-white" list="loanCollateralOptions" required>
                                    <datalist id="loanCollateralOptions">
                                        @foreach(($loanCollateralOptions ?? collect()) as $collateralOption)
                                            <option value="{{ $collateralOption }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('collateral_type')
                                        <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-f">
                                    <label>Рыночная стоимость</label>
                                    <input type="number" step="0.01" min="0" name="market_value"
                                        value="{{ old('market_value', $loanMeta['market_value'] ?? '') }}"
                                        class="form-control text-white" data-loan-market-value required>
                                    @error('market_value')
                                        <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="doc-form-row doc-form-row-two-cols">
                                <div class="col-f">
                                    <label>LTV сделки</label>
                                    <select name="ltv" class="form-select text-white" data-loan-ltv required>
                                        @foreach([40, 50, 60, 70, 80, 90, 100] as $ltv)
                                            <option value="{{ $ltv }}" {{ (string) old('ltv', $loanMeta['ltv'] ?? '70') === (string) $ltv ? 'selected' : '' }}>{{ $ltv }}%</option>
                                        @endforeach
                                    </select>
                                    @error('ltv')
                                        <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-f">
                                    <label>Сумма кредита</label>
                                    <input type="number" step="0.01" min="0" name="loan_amount" id="loanAmountInput"
                                        value="{{ old('loan_amount', $loanMeta['loan_amount'] ?? $document->summa ?? '') }}"
                                        class="form-control text-white" data-loan-amount-input required>
                                    @error('loan_amount')
                                        <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="doc-form-row doc-form-row-two-cols">
                                <div class="col-f">
                                    <label>Процентная ставка</label>
                                    <input type="number" step="0.01" min="0" max="100" name="interest_rate"
                                        value="{{ old('interest_rate', $loanMeta['interest_rate'] ?? '') }}"
                                        class="form-control text-white" required>
                                    @error('interest_rate')
                                        <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-f">
                                    <label>Срок кредита</label>
                                    <select name="loan_term_months" class="form-select text-white" required>
                                        <option value="1" {{ (string) old('loan_term_months', $loanMeta['loan_term_months'] ?? '12') === '1' ? 'selected' : '' }}>1 мес</option>
                                        <option value="3" {{ (string) old('loan_term_months', $loanMeta['loan_term_months'] ?? '12') === '3' ? 'selected' : '' }}>3 мес</option>
                                        <option value="6" {{ (string) old('loan_term_months', $loanMeta['loan_term_months'] ?? '12') === '6' ? 'selected' : '' }}>6 мес</option>
                                        <option value="9" {{ (string) old('loan_term_months', $loanMeta['loan_term_months'] ?? '12') === '9' ? 'selected' : '' }}>9 мес</option>
                                        <option value="12" {{ (string) old('loan_term_months', $loanMeta['loan_term_months'] ?? '12') === '12' ? 'selected' : '' }}>1 год</option>
                                        <option value="24" {{ (string) old('loan_term_months', $loanMeta['loan_term_months'] ?? '12') === '24' ? 'selected' : '' }}>2 года</option>
                                        <option value="36" {{ (string) old('loan_term_months', $loanMeta['loan_term_months'] ?? '12') === '36' ? 'selected' : '' }}>3 года</option>
                                    </select>
                                    @error('loan_term_months')
                                        <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="doc-form-row doc-form-row-two-cols">
                                <div class="col-f">
                                    <label>Доходность для инвесторов</label>
                                    <input type="number" step="0.01" min="0" max="100" name="investor_yield"
                                        value="{{ old('investor_yield', $loanMeta['investor_yield'] ?? '') }}"
                                        class="form-control text-white" required>
                                    @error('investor_yield')
                                        <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-f">
                                    <label>Дедлайн</label>
                                    <select name="deadline_days" class="form-select text-white" required>
                                        <option value="0" {{ (string) old('deadline_days', $loanMeta['deadline_days'] ?? '7') === '0' ? 'selected' : '' }}>Сразу</option>
                                        <option value="1" {{ (string) old('deadline_days', $loanMeta['deadline_days'] ?? '7') === '1' ? 'selected' : '' }}>1 день</option>
                                        @foreach([3, 7, 14, 21] as $days)
                                            <option value="{{ $days }}" {{ (string) old('deadline_days', $loanMeta['deadline_days'] ?? '7') === (string) $days ? 'selected' : '' }}>{{ $days }} дней</option>
                                        @endforeach
                                    </select>
                                    @error('deadline_days')
                                        <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="doc-form-row-single">
                                <label>Комментарий риск-менеджера</label>
                                <textarea name="comment" class="form-control text-white" rows="3"
                                    placeholder="Описание залога, VIN/госномер, условия удержания, примечания скоринга">{{ old('comment', $loanMeta['comment'] ?? '') }}</textarea>
                                @error('comment')
                                    <div class="text-danger small mt-1 text-red">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    @if($showLoanRepaymentSchedule)
                        @php
                            $schedule = $loanRepaymentSchedule ?? null;
                        @endphp
                        <div class="loan-repayment-panel">
                            <div class="doc-form-row doc-form-row-three-cols">
                                <div class="col-f">
                                    <label>Тело кредита</label>
                                    <div class="form-control text-dark bg-light">{{ number_format((float) ($schedule['principal'] ?? 0), 2, '.', ' ') }}</div>
                                </div>
                                <div class="col-f">
                                    <label>Оплачено</label>
                                    <div class="form-control text-dark bg-light">{{ number_format((float) ($schedule['paid_total'] ?? 0), 2, '.', ' ') }}</div>
                                </div>
                                <div class="col-f">
                                    <label>Остаток по займу</label>
                                    <div class="form-control text-dark bg-light">{{ number_format((float) ($schedule['remaining_total'] ?? 0), 2, '.', ' ') }}</div>
                                </div>
                            </div>

                            <h5 class="goods-title">График выплат</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm align-middle">
                                    <thead class="goods-table-header">
                                        <tr>
                                            <th style="width: 80px;">№</th>
                                            <th>Дата</th>
                                            <th class="text-end">К оплате</th>
                                            <th class="text-end">Оплачено</th>
                                            <th class="text-end">Остаток</th>
                                            <th>Статус</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse(($schedule['rows'] ?? []) as $paymentRow)
                                            @php
                                                $paymentStatus = $paymentRow['status'] ?? 'pending';
                                                $paymentStatusLabel = match($paymentStatus) {
                                                    'paid' => 'Оплачено',
                                                    'partial' => 'Частично',
                                                    default => 'Ожидает',
                                                };
                                                $paymentStatusClass = match($paymentStatus) {
                                                    'paid' => 'bg-success',
                                                    'partial' => 'bg-warning text-dark',
                                                    default => 'bg-secondary',
                                                };
                                            @endphp
                                            <tr>
                                                <td class="bank-mono">#{{ $paymentRow['number'] ?? '' }}</td>
                                                <td>{{ $paymentRow['due_date'] ?? '' }}</td>
                                                <td class="text-end">{{ number_format((float) ($paymentRow['amount'] ?? 0), 2, '.', ' ') }}</td>
                                                <td class="text-end">{{ number_format((float) ($paymentRow['paid'] ?? 0), 2, '.', ' ') }}</td>
                                                <td class="text-end">{{ number_format((float) ($paymentRow['remaining'] ?? 0), 2, '.', ' ') }}</td>
                                                <td>
                                                    <span class="badge {{ $paymentStatusClass }}">
                                                        {{ $paymentStatusLabel }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-white">График выплат не сформирован</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if(!empty($schedule['overpaid']))
                                <div class="alert alert-warning py-2">
                                    Переплата: {{ number_format((float) $schedule['overpaid'], 2, '.', ' ') }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Сума field for PO/RO documents -->
                    @if(in_array($doc, ['PO', 'CPO', 'RO', 'CRO', 'ZP'], true))
                        <div class="doc-form-row-single">
                            <label>Сума</label>
                            <input type="text" name="summa" id="documentSummaInput" class="form-control form-control-number text-white"
                                value="{{ $document->summa ?? 0 }}" inputmode="numeric" autocomplete="off">
                        </div>
                    @endif

                    <!-- RA: Multiple file upload block -->
                    @if(in_array($doc, ['RA', 'CDOC'], true))
                        <div class="ra-document-block" style="border: 2px solid #4a5568; padding: 16px; border-radius: 8px; background: rgba(0,0,0,0.2); margin-bottom: 20px;">
                            <div class="ra-title" style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #e0e7ff;">
                                📎 Завантажити файли
                            </div>

                            <div id="raFilesPreview" class="file-preview-container">
                                @php
                                    $existingRaFiles = [];
                                    if (in_array($doc, ['RA', 'CDOC'], true) && !empty($document->docum)) {
                                        $existingRaFiles = array_values(array_filter(array_map('trim', explode(';', (string) $document->docum))));
                                    }
                                    $imageExtensions = '/\.(jpe?g|png|gif|webp|bmp)(\?.*)?$/i';
                                    $publicRaFileUrl = static function (string $path): string {
                                        $path = trim($path);
                                        if ($path === '') {
                                            return '';
                                        }
                                        if (preg_match('#^https?://#i', $path) === 1) {
                                            return $path;
                                        }
                                        $normalizedPath = str_replace('\\', '/', $path);
                                        if (str_starts_with($normalizedPath, '/storage/')) {
                                            return $normalizedPath;
                                        }
                                        if (str_starts_with($normalizedPath, 'storage/')) {
                                            return '/' . $normalizedPath;
                                        }
                                        if (str_starts_with($normalizedPath, 'documents/')) {
                                            return asset('storage/' . ltrim($normalizedPath, '/'));
                                        }
                                        return asset(ltrim($normalizedPath, '/'));
                                    };
                                @endphp
                                @foreach($existingRaFiles as $existingFile)
                                    @php
                                        $existingFileUrl = $publicRaFileUrl($existingFile);
                                    @endphp
                                    <div class="file-preview-card existing-file-card" data-file-url="{{ $existingFile }}">
                                        @if($existingFileUrl !== '' && preg_match($imageExtensions, $existingFile))
                                            <img src="{{ $existingFileUrl }}" alt="file preview">
                                        @else
                                            <div class="file-preview-icon">📎</div>
                                        @endif
                                        <div class="file-preview-name">{{ basename($existingFile) }}</div>
                                        @if($existingFileUrl !== '')
                                            <a href="{{ $existingFileUrl }}" target="_blank" rel="noopener noreferrer">Скачати</a>
                                        @endif
                                        <button type="button" class="file-preview-remove btn btn-sm btn-outline-danger" data-file-url="{{ $existingFile }}">×</button>
                                        <input type="hidden" name="existing_docum[]" value="{{ $existingFile }}">
                                    </div>
                                @endforeach
                            </div>

                            <div id="raFilesContainer" class="ra-files-container" style="margin-top: 12px;">
                                <!-- File upload fields will be dynamically added here -->
                                <div class="ra-file-item" data-index="1" style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                                    <label style="display: block; margin-bottom: 6px; font-size: 0.95rem;">📎 Файл 1:</label>
                                    <input type="file" name="docum[]" class="form-control text-white ra-file-input" 
                                           data-index="1"
                                           {{ (int)($document->provodka ?? 0) === 1 ? 'disabled' : '' }}>
                                </div>
                            </div>
                            <div style="margin-top: 12px; font-size: 0.9rem; color: #cbd5e1;">
                                Додаткові поля з'являться при заповненні
                            </div>
                        </div>
                    @endif

                    <!-- Goods add — hidden for PO/RO (payment types) and RA (file documents) -->
                    @if(!$hideGoodsSection && !in_array($doc, ['PO', 'CPO', 'RO', 'CRO', 'ZP', 'RA', 'CDOC'], true))
                        <div class="goods-search-container">
                            <div class="goods-search-row">
                                <input type="text" id="goodsSearchInput" class="form-control text-white" placeholder="Поиск товара..."
                                    autocomplete="off">
                                <button type="button" id="searchGoodsBtn"
                                    class="btn btn-outline-secondary btn-sm">Шукати</button>
                            </div>
                            <div id="goodsSearchResults" class="list-group goods-search-results">
                            </div>
                        </div>

                        <!-- Goods table -->
                        <h5 class="goods-title">Товари</h5>
                        <table class="table table-bordered table-sm " id="goodsTable">
                            <thead class="goods-table-header">
                                <tr>
                                    <th class="goods-table-col-code">Код</th>
                                    <th class="goods-table-col-name">Найменування</th>
                                    <th class="goods-table-col-qty">К-ть</th>
                                    <th class="goods-table-col-price">Ціна</th>
                                    <th class="goods-table-col-sum">Сума</th>
                                    <th class="goods-table-col-actions"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(count($lineItems) > 0)
                                    @foreach($lineItems as $item)
                                        <tr
                                            data-price-comp-pay="{{ (float) ($item->comp_pay ?? 0) }}"
                                            data-price-comp-pay1="{{ (float) ($item->comp_pay1 ?? 0) }}"
                                            data-price-base="{{ (float) ($item->price_pay ?? 0) }}"
                                            data-price-wholesale="{{ (float) ($item->price_pay1 ?? 0) }}"
                                            data-wholesale-from="{{ (int) ($item->price_count ?? 0) }}"
                                            data-doc-type="{{ $doc }}"
                                        >
                                            <td class="goods-table-col-code" data-label="Код">
                                                <input type="hidden" name="id[]" value="{{ $item->id }}">
                                                <input type="hidden" name="pid[]" value="{{ $item->pid }}">
                                                <input type="hidden" name="pnum[]" value="{{ $item->pnum }}">
                                                <input type="text" class="form-control form-control-sm text-white" value="{{ $item->pnum }}"
                                                    readonly>
                                            </td>
                                            <td class="goods-table-col-name" data-label="Найменування">
                                                <input type="hidden" name="name[]" value="{{ $item->name ?? '' }}">
                                                <div class="goods-name-map-wrap">
                                                    <input type="text" class="form-control form-control-sm text-white" value="{{ $item->name ?? '' }}"
                                                        readonly>
                                                    @if($mappingTargetProjectId)
                                                        <button type="button"
                                                            class="btn btn-sm btn-outline-secondary product-map-btn {{ !empty($item->mapped_product_id) ? 'is-mapped' : '' }}"
                                                            data-source-product-id="{{ $item->pnum }}"
                                                            data-source-product-name="{{ $item->name ?? '' }}"
                                                            data-target-product-id="{{ $item->mapped_product_id ?? '' }}"
                                                            title="Маппінг товару проекту {{ $mappingTargetProjectId }}">
                                                            {{ !empty($item->mapped_product_id) ? $item->mapped_product_id : '...' }}
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="goods-table-col-qty" data-label="К-ть">
                                                <div class="input-group input-group-sm">
                                                    <button type="button" class="btn btn-outline-secondary btn-qty-decrease">−</button>
                                                    <input type="text" name="pcount[]"
                                                        class="form-control form-control-sm goods-count text-white" value="{{ $item->pcount }}">
                                                    <button type="button" class="btn btn-outline-secondary btn-qty-increase">+</button>
                                                </div>
                                            </td>
                                            <td class="goods-table-col-price" data-label="Ціна"><input type="text" name="pprice[]"
                                                    class="form-control form-control-sm goods-price text-end text-white"
                                                    value="{{ $item->pprice }}"></td>
                                            <td class="goods-table-col-sum" data-label="Сума"><input type="text" name="psumma[]"
                                                    class="form-control form-control-sm goods-sum text-end text-white" value="{{ $item->psumma }}">
                                            </td>
                                            <td class="goods-table-col-actions">
                                                @if(intval($document->provodka) === 0)
                                                    <button type="button" value="{{ $item->id }}"
                                                        class="btn btn-sm btn-outline-danger remove-btn" title="Видалити"
                                                        onclick="confirmAndSubmitItemDelete(this)"
                                                        ontouchstart="confirmAndSubmitItemDelete(this); event.preventDefault();">❌</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr id="emptyGoodsRow">
                                        <td colspan="6" class="text-center text-white">Немає товарів</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>

                        <!-- Сума field below goods table, right-aligned 30% width -->
                        <div class="d-flex justify-content-end mb-3 doc-sum-box">
                            <div class="doc-sum-box-inner">
                                <label class="doc-sum-box-label">💰 Сума</label>
                                <input type="text" name="summa" id="documentSummaInput"
                                    class="form-control doc-sum-box-input text-end fs-6 text-white" value="{{ $document->summa ?? 0 }}"
                                    inputmode="numeric" autocomplete="off">
                            </div>
                        </div>

                    @endif

                    <!-- Примечание field -->
                    @if(!$hideGoodsSection)
                        <div class="doc-form-row-single">
                            <label>Примечание</label>
                            <textarea name="content" class="form-control text-white" rows="3" placeholder="Внесіть примітку до документа">{{ $document->content ?? '' }}</textarea>
                        </div>
                    @endif

                    {{-- Action buttons (inside form) --}}
                    @if(!$showLoanRepaymentSchedule)
                        <div class="doc-actions">
                            @if(in_array($doc, ['RN', 'CPLAN', 'PN', 'PO', 'CPO', 'RO', 'CRO', 'ZP', 'VN', 'AO', 'WO1'], true))
                                @if((int) ($document->provodka ?? 0) === 1)
                                    <button type="button" 
                                        onclick="forceSubmitAction(this, '', '', '{{ route($documentRoutes . '.provodka') }}')"
                                        ontouchstart="forceSubmitAction(this, '', '', '{{ route($documentRoutes . '.provodka') }}'); event.preventDefault();"
                                        class="btn btn-success">
                                        ↺ Скасувати проводку
                                    </button>
                                @else
                                    <div class="form-check d-flex align-items-center post-checkbox">
                                        <!-- <input type="hidden" name="post_after_save" value="0"> -->
                                        <input type="checkbox" class="form-check-input" id="post_after_save" name="post_after_save"
                                            value="1" checked>
                                        <label class="form-check-label ms-2 post-checkbox-label" for="post_after_save">
                                            Провести документ
                                        </label>
                                    </div>
                                    <button type="button" 
                                        onclick="forceSubmitAction(this, 'run', 'Зберегти')"
                                        ontouchstart="forceSubmitAction(this, 'run', 'Зберегти'); event.preventDefault();"
                                        class="btn btn-primary {{ in_array($doc, ['PN', 'RN', 'CPLAN', 'PO', 'CPO', 'RO', 'CRO'], true) ? '' : 'w-100 mb-2' }}">💾 Зберегти</button>
                                @endif
                            @elseif(in_array($doc, ['RA', 'CDOC'], true))
                                <button type="button" 
                                    onclick="forceSubmitAction(this, 'run', 'Зберегти')"
                                    ontouchstart="forceSubmitAction(this, 'run', 'Зберегти'); event.preventDefault();"
                                    class="btn btn-primary">💾 Зберегти файл</button>
                            @else
                                <button type="button" 
                                    onclick="forceSubmitAction(this, 'run', 'Зберегти')"
                                    ontouchstart="forceSubmitAction(this, 'run', 'Зберегти'); event.preventDefault();"
                                    class="btn btn-primary">💾 Зберегти</button>
                            @endif
                            @if(in_array($doc, ['CH', 'RN'], true))
                                <a href="{{ route($documentRoutes . '.print', ['doc' => $doc, 'doc_id' => $document->id, 'num' => $document->num, 'year' => $year]) }}"
                                    class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                                    Печать
                                </a>
                            @endif
                            @if(intval($document->provodka) === 0 && !in_array($doc, ['RA', 'CDOC'], true))
                                <button type="button" class="btn btn-outline-danger"
                                    onclick="if(confirm('Видалити документ{{ $doc === 'RA' ? '' : ' та всі товари' }}?')) { document.getElementById('deleteDocForm').submit(); }">🗑
                                    Видалити
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- RIGHT: Related documents (client_info1) --}}
                @if($showLoanRelatedMenu && (!empty($relatedDocs) || !empty($loanRoUrl)))
                    <div class="doc-related-col">
                        <div class="related-panel">
                            <h5>{{ $isLoanDocument ? '📋 Документы кредита' : "📋 Зв'язані документи" }}</h5>
                            @if(!empty($loanRoUrl))
                                <a href="{{ $loanRoUrl }}" class="btn {{ !empty($loanRoIsIssued) ? 'btn-success' : 'btn-primary' }} w-100 mb-3">
                                    {{ !empty($loanRoIsIssued) ? 'Кредит выдан' : 'Выдача кредита' }}
                                </a>
                            @endif
                            @if(!empty($relatedDocs))
                                {!! $relatedDocs['html'] !!}
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </form>

        {{-- Hidden form for document deletion --}}
        <form id="deleteDocForm" action="{{ route($documentRoutes . '.destroy') }}" method="post" class="delete-form">
            @csrf
            <input type="hidden" name="doc_id" value="{{ $document->id }}">
            <input type="hidden" name="doc" value="{{ $doc }}">
        </form>
    </div>

    <!-- Modal: New Client -->
    @if($doc !== 'ZP')
        <div class="modal fade" id="newClientModal" tabindex="-1" aria-labelledby="newClientModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="newClientModalLabel">Новий клієнт</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                    </div>
                    <div class="modal-body py-2">
                        <input type="hidden" id="newClientId" value="0">
                        <div class="row g-2 modal-client-grid">
                            <div class="col-12 client-modal-field">
                                <label class="form-label small mb-0">Группа</label>
                                <input type="text" class="form-control form-control-sm text-white" id="newClientGroupName"
                                    list="newClientGroupList" autocomplete="off" placeholder="Почніть вводити назву групи">
                                <input type="hidden" id="newClientGroupId" value="">
                                <datalist id="newClientGroupList">
                                    @foreach(($clientGroups ?? collect()) as $group)
                                        <option value="{{ $group->name }}" data-id="{{ $group->id }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-12 client-modal-field">
                                <label class="form-label small mb-0">Організація</label>
                                <input type="text" class="form-control form-control-sm text-white" id="newClientOrgname">
                            </div>
                            <div class="col-12 col-md-6 client-modal-field">
                                <label class="form-label small mb-0">Прізвище</label>
                                <input type="text" class="form-control form-control-sm text-white" id="newClientSecondname">
                            </div>
                            <div class="col-12 col-md-6 client-modal-field">
                                <label class="form-label small mb-0">Ім'я</label>
                                <input type="text" class="form-control form-control-sm text-white" id="newClientName">
                            </div>
                            <div class="col-12 col-md-6 client-modal-field">
                                <label class="form-label small mb-0">Телефон</label>
                                <input type="text" class="form-control form-control-sm text-white" id="newClientPhone"
                                    placeholder="+38 (000) 00-00-000" maxlength="19" inputmode="tel">
                            </div>
                            <div class="col-12 col-md-6 client-modal-field">
                                <label class="form-label small mb-0">Місто</label>
                                <input type="text" class="form-control form-control-sm text-white" id="newClientCity"
                                    list="newClientCityList" autocomplete="off">
                                <datalist id="newClientCityList"></datalist>
                            </div>
                            <div class="col-12 col-md-6 client-modal-field">
                                <label class="form-label small mb-0">Область</label>
                                <input type="text" class="form-control form-control-sm text-white" id="newClientRegion">
                            </div>
                            <div class="col-12 col-md-6 client-modal-field">
                                <label class="form-label small mb-0">Отделение НP</label>
                                <input type="text" class="form-control form-control-sm text-white" id="newClientPoshta">
                            </div>
                            <div class="col-12 client-modal-field">
                                <label class="form-label small mb-0">Статус клієнта</label>
                                <select class="form-select form-select-sm text-white" id="newClientStatus">
                                    <option value="">Оберіть статус</option>
                                    @foreach(($clientStatuses ?? collect()) as $statusOption)
                                        <option value="{{ $statusOption->id }}">{{ $statusOption->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div id="newClientError" class="text-danger small" style="display: none;"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Скасувати</button>
                        <button type="button" class="btn btn-primary" id="saveNewClientBtn">Зберегти</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(!$hideGoodsSection)
        <div class="modal fade" id="productMappingModal" tabindex="-1" aria-labelledby="productMappingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productMappingModalLabel">Маппінг товару</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2 small text-muted" id="productMappingSourceInfo"></div>
                        <div class="input-group input-group-sm mb-3">
                            <input type="text" class="form-control text-white" id="productMappingSearchInput"
                                placeholder="Пошук товару в проекті {{ $mappingTargetProjectId ?: 'контрагента' }}...">
                            <button type="button" class="btn btn-outline-secondary" id="productMappingSearchBtn">Шукати</button>
                        </div>
                        <div id="productMappingError" class="alert alert-danger py-2" style="display:none;"></div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 90px;">ID</th>
                                        <th>Товар</th>
                                        <th style="width: 120px;">Залишок</th>
                                        <th style="width: 120px;">Ціна</th>
                                    </tr>
                                </thead>
                                <tbody id="productMappingResults">
                                    <tr>
                                        <td colspan="4" class="text-muted">Введіть назву або код товару.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрити</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.add('text-dark'));
            const documentForm = document.querySelector('form.compact-form');
            const searchInput = document.getElementById('clientSearchInput');
            const editClientBtn = document.getElementById('editClientBtn');
            const resultsContainer = document.getElementById('clientSearchResults');
            const client1Id = document.getElementById('client1_id');
            const clientDetails = document.getElementById('selectedClientDetails');
            const documentSummaInput1 = document.getElementById('documentSummaInput');
            const loanMarketValueInput = document.querySelector('[data-loan-market-value]');
            const loanLtvSelect = document.querySelector('[data-loan-ltv]');
            const loanAmountInput = document.querySelector('[data-loan-amount-input]');
            const teamOnlyClientSearch = @json($doc === 'ZP');
            const formatClientName = (user) => [user.secondname || '', user.name || ''].filter(Boolean).join(' ').trim();
            const formatClientDetailsHtml = (user) => {
                const regionPart = user.region ? user.region + ' | ' : '';
                const poshtaPart = user.poshta ? ' | ' + user.poshta : '';
                const orgnamePart = user.orgname ? `<strong>${user.orgname}</strong> | ` : '';
                const clientName = formatClientName(user);
                return `${orgnamePart}${clientName}<br><small>${user.phone || ''} | ${regionPart}${user.city || ''}${poshtaPart}</small>`;
            };

            const formatTerminalAmount = (cents) => (Math.max(0, cents) / 100).toFixed(2);
            const parseAmountToCents = (value) => {
                const normalized = String(value || '').replace(/\s/g, '').replace(',', '.');
                const amount = parseFloat(normalized);

                return Number.isFinite(amount) ? Math.round(amount * 100) : 0;
            };
            const bindTerminalAmountInput = (input) => {
                if (!input) {
                    return;
                }

                const syncValue = (cents) => {
                    input.dataset.terminalAmountCents = String(cents);
                    input.value = formatTerminalAmount(cents);
                };
                const getDigits = () => {
                    const cents = parseInt(input.dataset.terminalAmountCents || '0', 10) || 0;

                    return String(cents);
                };
                const appendDigit = (digit) => {
                    const currentDigits = input.dataset.terminalAmountFresh === '1' ? '' : getDigits();
                    const nextDigits = (currentDigits + digit).replace(/^0+(?=\d)/, '');

                    syncValue(parseInt(nextDigits || '0', 10));
                    input.dataset.terminalAmountFresh = '0';
                };
                const removeLastDigit = () => {
                    const nextDigits = getDigits().slice(0, -1);

                    syncValue(parseInt(nextDigits || '0', 10));
                    input.dataset.terminalAmountFresh = '0';
                };

                syncValue(parseAmountToCents(input.value));

                input.addEventListener('focus', () => {
                    input.dataset.terminalAmountFresh = '1';
                    syncValue(parseAmountToCents(input.value));
                    input.select();
                });
                input.addEventListener('beforeinput', (event) => {
                    if (event.inputType === 'insertText' && /^\d$/.test(event.data || '')) {
                        event.preventDefault();
                        appendDigit(event.data);
                        return;
                    }

                    if (event.inputType === 'deleteContentBackward') {
                        event.preventDefault();
                        removeLastDigit();
                        return;
                    }

                    if (event.inputType === 'deleteContentForward') {
                        event.preventDefault();
                        syncValue(0);
                        input.dataset.terminalAmountFresh = '0';
                    }
                });
                input.addEventListener('keydown', (event) => {
                    if (event.ctrlKey || event.metaKey || event.altKey) {
                        return;
                    }

                    if (/^\d$/.test(event.key)) {
                        event.preventDefault();
                        appendDigit(event.key);
                        return;
                    }

                    if (event.key === 'Backspace') {
                        event.preventDefault();
                        removeLastDigit();
                        return;
                    }

                    if (event.key === 'Delete') {
                        event.preventDefault();
                        syncValue(0);
                        input.dataset.terminalAmountFresh = '0';
                        return;
                    }

                    if (event.key === 'Enter') {
                        event.preventDefault();
                        submitDocumentSave();
                    }
                });
                input.addEventListener('paste', (event) => {
                    event.preventDefault();
                    const text = event.clipboardData?.getData('text') || '';
                    const digits = text.replace(/\D/g, '');

                    syncValue(parseInt(digits || '0', 10));
                    input.dataset.terminalAmountFresh = '0';
                });
                input.addEventListener('input', () => {
                    syncValue(parseAmountToCents(input.value));
                    input.dataset.terminalAmountFresh = '0';
                });
            };

            document.querySelectorAll('input[name="summa"]').forEach(bindTerminalAmountInput);

            function performSearch() {
                const q = searchInput.value.trim();
                if (q.length < 2) { resultsContainer.style.display = 'none'; return; }
                const params = new URLSearchParams({ q });
                if (teamOnlyClientSearch) {
                    params.set('team_only', '1');
                }
                fetch("{{ route('client.search') }}?" + params.toString())
                    .then(res => res.json())
                    .then(data => {
                        resultsContainer.innerHTML = '';
                        if (data.length === 0) {
                            resultsContainer.innerHTML = '<div class="list-group-item text-dark bg-white">Нічого не знайдено</div>';
                        } else {
                            data.forEach(user => {
                                const a = document.createElement('a');
                                a.href = '#'; a.className = 'list-group-item list-group-item-action bg-white text-dark';
                                a.innerHTML = formatClientDetailsHtml(user);
                                a.addEventListener('click', function (e) {
                                    e.preventDefault();
                                    const selectedLabel = [user.orgname || '', formatClientName(user)].filter(Boolean).join(' ').trim();
                                    client1Id.value = user.id;
                                    client1Id.dataset.orgname = user.orgname || '';
                                    client1Id.dataset.name = user.name || '';
                                    client1Id.dataset.secondname = user.secondname || '';
                                    client1Id.dataset.phone = user.phone || '';
                                    client1Id.dataset.city = user.city || '';
                                    client1Id.dataset.region = user.region || '';
                                    client1Id.dataset.poshta = user.poshta || '';
                                    client1Id.dataset.status = user.idstatus || '';
                                    client1Id.dataset.usergroup = user.usergroup || '';
                                    client1Id.dataset.usergroupName = user.usergroup_name || '';

                                    if (editClientBtn) {
                                        editClientBtn.style.display = 'inline-block';
                                    }
                                    
                                    clientDetails.className = 'alert alert-secondary py-1 mt-1';
                                    clientDetails.style.background = '#f8f9fa';
                                    clientDetails.style.border = '1px solid #ddd';
                                    clientDetails.style.fontSize = '0.85rem';
                                    clientDetails.innerHTML = formatClientDetailsHtml(user);
                                    resultsContainer.style.display = 'none';
                                    searchInput.value = selectedLabel;
                                });
                                resultsContainer.appendChild(a);
                            });
                        }
                        resultsContainer.style.display = 'block';
                    })
                    .catch(err => console.error('Search failed:', err));
            }
            let clientSearchTimeout = null;
            searchInput.addEventListener('input', function (e) {
                clearTimeout(clientSearchTimeout);
                clientSearchTimeout = setTimeout(performSearch, 400);
            });
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); performSearch(); }
            });
            document.addEventListener('click', function (e) {
                if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.style.display = 'none';
                }
            });

            // ================= NEW/EDIT CLIENT MODAL =================
            const newClientBtn = document.getElementById('newClientBtn');
            const saveNewClientBtn = document.getElementById('saveNewClientBtn');
            const newClientPhoneField = document.getElementById('newClientPhone');
            const newClientIdField = document.getElementById('newClientId');
            const newClientOrgnameField = document.getElementById('newClientOrgname');
            const newClientGroupNameField = document.getElementById('newClientGroupName');
            const newClientGroupIdField = document.getElementById('newClientGroupId');
            const newClientGroupList = document.getElementById('newClientGroupList');
            const newClientCityField = document.getElementById('newClientCity');
            const newClientRegionField = document.getElementById('newClientRegion');
            const newClientCityList = document.getElementById('newClientCityList');
            let clientGroupsCache = @json(collect($clientGroups ?? [])->map(fn ($group) => ['id' => (int) $group->id, 'name' => (string) $group->name])->values());
            let clientCitiesCache = [];
            let clientCitiesLoadPromise = null;

            const normalizeGroupName = (value) => String(value || '').trim().toLowerCase();
            const normalizeLocationName = (value) => String(value || '').trim().toLowerCase();
            const capitalizeTextWords = (value) => String(value || '').replace(
                /(^|[\s\-'"`(])([a-zа-яёіїєґ])/giu,
                (match, prefix, letter) => `${prefix}${letter.toUpperCase()}`
            );
            const bindCapitalizedInput = (field) => {
                if (!field) {
                    return;
                }

                field.addEventListener('input', () => {
                    const selectionStart = field.selectionStart;
                    const selectionEnd = field.selectionEnd;
                    const nextValue = capitalizeTextWords(field.value);

                    if (nextValue === field.value) {
                        return;
                    }

                    field.value = nextValue;
                    if (selectionStart !== null && selectionEnd !== null) {
                        field.setSelectionRange(selectionStart, selectionEnd);
                    }
                });
            };
            const applyCapitalizedValue = (field) => {
                if (field) {
                    field.value = capitalizeTextWords(field.value);
                }
            };
            [
                newClientGroupNameField,
                newClientOrgnameField,
                document.getElementById('newClientSecondname'),
                document.getElementById('newClientName'),
                newClientCityField,
                newClientRegionField,
                document.getElementById('newClientPoshta'),
            ].forEach(bindCapitalizedInput);
            const regionDisplayName = (region) => region?.name || region?.name_ua || region?.name_ru || region?.name_en || '';
            const cityDisplayName = (city) => city?.val || city?.valru || city?.valen || '';
            const cityMatchesQuery = (city, query) => {
                if (!query) {
                    return true;
                }

                return [
                    city.name,
                    city.valru,
                    city.valen,
                    city.region_name,
                ].some((value) => normalizeLocationName(value).includes(query));
            };
            const syncClientRegionFromCity = () => {
                if (!newClientCityField || !newClientRegionField) {
                    return;
                }

                const cityName = normalizeLocationName(newClientCityField.value);
                if (!cityName) {
                    return;
                }

                const selectedCity = clientCitiesCache.find((city) => normalizeLocationName(city.name) === cityName);
                if (selectedCity) {
                    newClientRegionField.value = selectedCity.region_name || newClientRegionField.value;
                }
            };
            const renderClientCityOptions = () => {
                if (!newClientCityList) {
                    return;
                }

                const query = normalizeLocationName(newClientCityField?.value || '');
                const fragment = document.createDocumentFragment();
                clientCitiesCache
                    .filter((city) => cityMatchesQuery(city, query))
                    .slice(0, 200)
                    .forEach((city) => {
                        const option = document.createElement('option');
                        option.value = city.name;
                        option.label = city.region_name ? `${city.region_name} | ${city.name}` : city.name;
                        option.dataset.regionId = city.region_id;
                        option.dataset.regionName = city.region_name;
                        fragment.appendChild(option);
                    });

                newClientCityList.innerHTML = '';
                newClientCityList.appendChild(fragment);
                syncClientRegionFromCity();
            };
            const loadClientCities = () => {
                if (!newClientCityField || !newClientCityList) {
                    return Promise.resolve();
                }

                if (clientCitiesLoadPromise) {
                    return clientCitiesLoadPromise;
                }

                const regionsUrl = new URL("{{ route('settings.fields.index') }}", window.location.origin);
                regionsUrl.searchParams.set('keyfield', 'city');
                regionsUrl.searchParams.set('parent_id', '0');

                clientCitiesLoadPromise = fetch(regionsUrl.toString(), { headers: { Accept: 'application/json' } })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(data.message || 'Не вдалося завантажити регіони');
                        }

                        const regions = data.items || [];

                        return Promise.all(regions.map(async (region) => {
                            const citiesUrl = new URL("{{ route('settings.regionCities.index') }}", window.location.origin);
                            citiesUrl.searchParams.set('region_id', region.id);

                            const citiesResponse = await fetch(citiesUrl.toString(), { headers: { Accept: 'application/json' } });
                            const citiesData = await citiesResponse.json().catch(() => ({}));
                            if (!citiesResponse.ok) {
                                return [];
                            }

                            const regionName = regionDisplayName(region);

                            return (citiesData.items || []).map((city) => ({
                                ...city,
                                name: cityDisplayName(city),
                                region_name: regionName,
                            }));
                        }));
                    })
                    .then((groups) => {
                        clientCitiesCache = groups
                            .flat()
                            .filter((city) => city.name)
                            .sort((a, b) => String(a.name).localeCompare(String(b.name)));
                        renderClientCityOptions();
                    })
                    .catch((error) => {
                        console.error('City search load failed:', error);
                        clientCitiesLoadPromise = null;
                    });

                return clientCitiesLoadPromise;
            };
            const syncClientGroupIdFromName = () => {
                if (!newClientGroupNameField || !newClientGroupIdField) {
                    return;
                }

                const normalized = normalizeGroupName(newClientGroupNameField.value);
                const match = clientGroupsCache.find((group) => normalizeGroupName(group.name) === normalized);
                newClientGroupIdField.value = match ? String(match.id) : '';
            };
            const renderClientGroupOptions = (items) => {
                if (!newClientGroupList) {
                    return;
                }

                const known = new Map(clientGroupsCache.map((group) => [String(group.id), group]));
                (items || []).forEach((group) => {
                    known.set(String(group.id), { id: group.id, name: group.name });
                });
                clientGroupsCache = Array.from(known.values()).sort((a, b) => String(a.name).localeCompare(String(b.name)));
                newClientGroupList.innerHTML = '';
                clientGroupsCache.forEach((group) => {
                    const option = document.createElement('option');
                    option.value = group.name || '';
                    option.dataset.id = group.id;
                    newClientGroupList.appendChild(option);
                });
                syncClientGroupIdFromName();
            };
            const searchClientGroups = () => {
                if (!newClientGroupNameField) {
                    return;
                }

                const q = newClientGroupNameField.value.trim();
                fetch("{{ route('client.groups.index') }}?" + new URLSearchParams({ q }).toString(), {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(res => res.json())
                    .then(payload => renderClientGroupOptions(payload.items || []))
                    .catch(() => {});
            };
            let clientGroupSearchTimeout = null;
            if (newClientGroupNameField) {
                newClientGroupNameField.addEventListener('input', () => {
                    syncClientGroupIdFromName();
                    clearTimeout(clientGroupSearchTimeout);
                    clientGroupSearchTimeout = setTimeout(searchClientGroups, 250);
                });
                newClientGroupNameField.addEventListener('change', syncClientGroupIdFromName);
            }

            let clientCitySearchTimeout = null;
            if (newClientCityField) {
                newClientCityField.addEventListener('focus', () => {
                    loadClientCities();
                });
                newClientCityField.addEventListener('input', () => {
                    clearTimeout(clientCitySearchTimeout);
                    clientCitySearchTimeout = setTimeout(() => {
                        loadClientCities().then(renderClientCityOptions);
                    }, 150);
                });
                newClientCityField.addEventListener('change', syncClientRegionFromCity);
            }

            if(newClientBtn) {
                newClientBtn.addEventListener('click', () => {
                    document.getElementById('newClientModalLabel').textContent = 'Новий клієнт';
                    newClientIdField.value = '0';
                    if (newClientGroupNameField) newClientGroupNameField.value = '';
                    if (newClientGroupIdField) newClientGroupIdField.value = '';
                    newClientOrgnameField.value = '';
                    document.getElementById('newClientName').value = '';
                    document.getElementById('newClientSecondname').value = '';
                    newClientPhoneField.value = '';
                    document.getElementById('newClientCity').value = '';
                    document.getElementById('newClientRegion').value = '';
                    document.getElementById('newClientPoshta').value = '';
                    document.getElementById('newClientStatus').value = '';
                    document.getElementById('newClientError').style.display = 'none';
                });
            }

            if(editClientBtn) {
                editClientBtn.addEventListener('click', () => {
                    document.getElementById('newClientModalLabel').textContent = 'Змінити клієнта';
                    newClientIdField.value = client1Id.value || '0';
                    if (newClientGroupNameField) newClientGroupNameField.value = client1Id.dataset.usergroupName || '';
                    if (newClientGroupIdField) newClientGroupIdField.value = client1Id.dataset.usergroup || '';
                    newClientOrgnameField.value = client1Id.dataset.orgname || '';
                    document.getElementById('newClientName').value = client1Id.dataset.name || '';
                    document.getElementById('newClientSecondname').value = client1Id.dataset.secondname || '';
                    newClientPhoneField.value = client1Id.dataset.phone || '';
                    document.getElementById('newClientCity').value = client1Id.dataset.city || '';
                    document.getElementById('newClientRegion').value = client1Id.dataset.region || '';
                    document.getElementById('newClientPoshta').value = client1Id.dataset.poshta || '';
                    document.getElementById('newClientStatus').value = client1Id.dataset.status || '';
                    document.getElementById('newClientError').style.display = 'none';
                    // Trigger format
                    newClientPhoneField.dispatchEvent(new Event('input'));
                });
            }

            const formatPhoneInput = (value) => {
                const digits = value.replace(/\D/g, '').slice(0, 12);
                if (digits.length === 0) {
                    return '';
                }

                if (digits.length <= 3) {
                    return `+${digits}`;
                }

                if (digits.length <= 5) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3)}`;
                }

                if (digits.length <= 8) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5)}`;
                }

                if (digits.length <= 10) {
                    return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5, 8)}-${digits.slice(8)}`;
                }

                return `+${digits.slice(0, 3)} (${digits.slice(3, 5)}) ${digits.slice(5, 8)}-${digits.slice(8, 10)}-${digits.slice(10)}`;
            };

            if (newClientPhoneField) {
                newClientPhoneField.addEventListener('input', function () {
                    this.value = formatPhoneInput(this.value);
                });
            }

            if (saveNewClientBtn && newClientPhoneField && newClientIdField && newClientOrgnameField) {
                saveNewClientBtn.addEventListener('click', function () {
                    const id = newClientIdField.value || '0';
                    const orgnameField = newClientOrgnameField;
                    const nameField = document.getElementById('newClientName');
                    const secondnameField = document.getElementById('newClientSecondname');
                    const phoneField = newClientPhoneField;
                    const cityField = document.getElementById('newClientCity');
                    const regionField = document.getElementById('newClientRegion');
                    const poshtaField = document.getElementById('newClientPoshta');
                    const statusField = document.getElementById('newClientStatus');
                    const groupNameField = newClientGroupNameField;
                    const groupIdField = newClientGroupIdField;
                    [orgnameField, nameField, secondnameField, cityField, regionField, poshtaField, groupNameField].forEach(applyCapitalizedValue);
                    const orgname = orgnameField.value.trim();
                    const name = nameField.value.trim();
                    const secondname = secondnameField.value.trim();
                    const phone = phoneField.value.trim();
                    const city = cityField.value.trim();
                    const region = regionField.value.trim();
                    const poshta = poshtaField.value.trim();
                    const idstatus = statusField.value;
                    const usergroupName = groupNameField ? groupNameField.value.trim() : '';
                    syncClientGroupIdFromName();
                    const usergroup = groupIdField ? groupIdField.value : '';
                    const errorDiv = document.getElementById('newClientError');
                    [nameField, secondnameField, phoneField, statusField].forEach(field => field.classList.remove('is-invalid'));
                    if (!name && !secondname && !phone) {
                        nameField.classList.add('is-invalid');
                        secondnameField.classList.add('is-invalid');
                        phoneField.classList.add('is-invalid');
                        errorDiv.textContent = 'Заповніть хоча б одне поле: імʼя, прізвище або телефон';
                        errorDiv.style.display = 'block';
                        return;
                    }
                    if (!idstatus) {
                        statusField.classList.add('is-invalid');
                        errorDiv.textContent = 'Оберіть статус клієнта';
                        errorDiv.style.display = 'block';
                        return;
                    }
                    errorDiv.style.display = 'none';
                    saveNewClientBtn.disabled = true;
                    saveNewClientBtn.textContent = 'Зберігаємо...';
                    fetch("{{ route('client.quickStore') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ id, orgname, name, secondname, phone, city, region, poshta, idstatus, usergroup, usergroup_name: usergroupName })
                    })
                        .then(async res => {
                            const payload = await res.json().catch(() => ({}));
                            if (!res.ok) {
                                throw new Error(payload.message || 'Не вдалося зберегти клієнта');
                            }
                            return payload;
                        })
                        .then(user => {
                            client1Id.value = user.id;
                            client1Id.dataset.orgname = user.orgname || '';
                            client1Id.dataset.name = user.name || '';
                            client1Id.dataset.secondname = user.secondname || '';
                            client1Id.dataset.phone = user.phone || '';
                            client1Id.dataset.city = user.city || '';
                            client1Id.dataset.region = user.region || '';
                            client1Id.dataset.poshta = user.poshta || '';
                            client1Id.dataset.status = user.idstatus || '';
                            client1Id.dataset.usergroup = user.usergroup || '';
                            client1Id.dataset.usergroupName = user.usergroup_name || '';
                            if (user.usergroup && user.usergroup_name) {
                                renderClientGroupOptions([{ id: user.usergroup, name: user.usergroup_name }]);
                            }
                            
                            if (editClientBtn) {
                                editClientBtn.style.display = 'inline-block';
                            }
                            
                            clientDetails.className = 'alert alert-secondary py-1 mt-1';
                            clientDetails.style.background = '#f8f9fa';
                            clientDetails.style.border = '1px solid #ddd';
                            clientDetails.style.fontSize = '0.85rem';
                            clientDetails.innerHTML = formatClientDetailsHtml(user);
                            
                            const modal = bootstrap.Modal.getInstance(document.getElementById('newClientModal'));
                            modal.hide();
                            orgnameField.value = '';
                            nameField.value = '';
                            secondnameField.value = '';
                            phoneField.value = '';
                            cityField.value = '';
                            regionField.value = '';
                            poshtaField.value = '';
                            statusField.value = '';
                            if (groupNameField) groupNameField.value = '';
                            if (groupIdField) groupIdField.value = '';
                        })
                        .catch(err => { errorDiv.textContent = 'Помилка: ' + err.message; errorDiv.style.display = 'block'; })
                        .finally(() => { saveNewClientBtn.disabled = false; saveNewClientBtn.textContent = 'Зберегти'; });
                });
            }

            // ================= GOODS SEARCH =================
            const goodsSearchInput = document.getElementById('goodsSearchInput');
            const searchGoodsBtn = document.getElementById('searchGoodsBtn');
            const goodsResultsContainer = document.getElementById('goodsSearchResults');
            const tableBody = document.querySelector('#goodsTable tbody');
            const mappingTargetProjectId = @json($mappingTargetProjectId);
            const productMappingModalEl = document.getElementById('productMappingModal');
            const productMappingModal = productMappingModalEl ? new bootstrap.Modal(productMappingModalEl) : null;
            const productMappingSourceInfo = document.getElementById('productMappingSourceInfo');
            const productMappingSearchInput = document.getElementById('productMappingSearchInput');
            const productMappingSearchBtn = document.getElementById('productMappingSearchBtn');
            const productMappingResults = document.getElementById('productMappingResults');
            const productMappingError = document.getElementById('productMappingError');
            let activeProductMappingButton = null;
            let productMappingSearchTimeout = null;

            const escapeHtml = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            const showProductMappingError = (message) => {
                if (!productMappingError) return;
                productMappingError.textContent = message;
                productMappingError.style.display = 'block';
            };

            const hideProductMappingError = () => {
                if (!productMappingError) return;
                productMappingError.textContent = '';
                productMappingError.style.display = 'none';
            };

            const renderProductMappingRows = (items) => {
                if (!productMappingResults) return;

                if (!items || items.length === 0) {
                    productMappingResults.innerHTML = '<tr><td colspan="4" class="text-muted">Нічого не знайдено.</td></tr>';
                    return;
                }

                productMappingResults.innerHTML = items.map((item) => `
                    <tr class="mapping-product-row" data-target-product-id="${escapeHtml(item.id)}">
                        <td><strong>${escapeHtml(item.id)}</strong></td>
                        <td>
                            <div class="fw-semibold">${escapeHtml(item.name || '')}</div>
                            <div class="small text-muted">${escapeHtml(item.code || '')}</div>
                        </td>
                        <td>${escapeHtml(item.stock_count ?? 0)}</td>
                        <td>${Number(item.price || 0).toFixed(2)}</td>
                    </tr>
                `).join('');
            };

            const searchProductMappingTargets = () => {
                if (!activeProductMappingButton || !productMappingSearchInput || !productMappingResults) return;

                const params = new URLSearchParams({
                    doc: @json($doc),
                    doc_id: @json((string) $document->id),
                    source_product_id: activeProductMappingButton.dataset.sourceProductId || '',
                    counterparty_user_id: client1Id?.value || '',
                    q: productMappingSearchInput.value.trim(),
                });

                productMappingResults.innerHTML = '<tr><td colspan="4" class="text-muted">Завантаження...</td></tr>';
                hideProductMappingError();

                fetch("{{ route($documentRoutes . '.productMapping.search') }}?" + params.toString(), {
                    headers: { 'Accept': 'application/json' },
                })
                    .then(async (res) => {
                        const payload = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            throw new Error(payload.message || 'Не вдалося завантажити товари.');
                        }
                        return payload;
                    })
                    .then((payload) => renderProductMappingRows(payload.items || []))
                    .catch((error) => {
                        productMappingResults.innerHTML = '<tr><td colspan="4" class="text-muted">Помилка завантаження.</td></tr>';
                        showProductMappingError(error.message);
                    });
            };

            const saveProductMapping = (targetProductId) => {
                if (!activeProductMappingButton || !targetProductId) return;

                const sourceProductId = activeProductMappingButton.dataset.sourceProductId || '';
                hideProductMappingError();

                fetch("{{ route($documentRoutes . '.productMapping.save') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        doc: @json($doc),
                        doc_id: @json((string) $document->id),
                        counterparty_user_id: client1Id?.value || '',
                        source_product_id: sourceProductId,
                        target_product_id: targetProductId,
                    }),
                })
                    .then(async (res) => {
                        const payload = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            throw new Error(payload.message || 'Не вдалося зберегти маппінг.');
                        }
                        return payload;
                    })
                    .then((payload) => {
                        activeProductMappingButton.textContent = payload.target_product_id;
                        activeProductMappingButton.dataset.targetProductId = payload.target_product_id;
                        activeProductMappingButton.classList.add('is-mapped');
                        productMappingModal?.hide();
                    })
                    .catch((error) => showProductMappingError(error.message));
            };

            document.addEventListener('click', function (event) {
                const mapButton = event.target.closest('.product-map-btn');
                if (mapButton) {
                    event.preventDefault();

                    if (!client1Id?.value) {
                        alert('Спочатку виберіть клієнта/продавця документа.');
                        return;
                    }

                    activeProductMappingButton = mapButton;
                    if (productMappingSourceInfo) {
                        productMappingSourceInfo.textContent = `Товар документа: ${mapButton.dataset.sourceProductId || ''} — ${mapButton.dataset.sourceProductName || ''}. Цільовий проект визначається по продавцю.`;
                    }
                    if (productMappingSearchInput) {
                        productMappingSearchInput.value = mapButton.dataset.sourceProductName || '';
                    }
                    hideProductMappingError();
                    renderProductMappingRows([]);
                    productMappingModal?.show();
                    searchProductMappingTargets();
                    return;
                }

                const mappingRow = event.target.closest('.mapping-product-row');
                if (mappingRow) {
                    event.preventDefault();
                    saveProductMapping(mappingRow.dataset.targetProductId || '');
                }
            });

            if (productMappingSearchBtn) {
                productMappingSearchBtn.addEventListener('click', searchProductMappingTargets);
            }
            if (productMappingSearchInput) {
                productMappingSearchInput.addEventListener('input', () => {
                    clearTimeout(productMappingSearchTimeout);
                    productMappingSearchTimeout = setTimeout(searchProductMappingTargets, 350);
                });
                productMappingSearchInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        searchProductMappingTargets();
                    }
                });
            }

            const updateDocumentSum = () => {
                if (!documentSummaInput1 || !tableBody) {
                    return;
                }

                const total = Array.from(tableBody.querySelectorAll('.goods-sum'))
                    .reduce((carry, input) => carry + (parseFloat(input.value) || 0), 0);

                documentSummaInput1.value = total.toFixed(2);
            };

            const updateZeroSumHighlight = (tr) => {
                if (!tr || tr.id === 'emptyGoodsRow') {
                    return;
                }

                const sumInput = tr.querySelector('.goods-sum');
                const sum = parseFloat(sumInput?.value || '0') || 0;
                tr.classList.toggle('goods-zero-sum', Math.abs(sum) < 0.005);
            };

            window.submitDocumentSave = () => {
                if (!documentForm) return;
                const runInput = document.createElement('input');
                runInput.type = 'hidden';
                runInput.name = 'run';
                runInput.value = 'Зберегти';
                documentForm.appendChild(runInput);
                if (documentForm.requestSubmit) {
                    documentForm.requestSubmit();
                } else {
                    documentForm.submit();
                }
            };

            window.forceSubmitAction = function(btn, name, value, actionUrl = null) {
                if (btn.dataset.submitting) return;
                btn.dataset.submitting = '1';
                setTimeout(() => btn.dataset.submitting = '', 2000); // на случай возврата назад
                
                if (name && value) {
                    documentForm.querySelectorAll(`[data-temp-submit-field="${name}"]`).forEach((input) => input.remove());
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    input.dataset.tempSubmitField = name;
                    documentForm.appendChild(input);
                }
                
                if (actionUrl) {
                    documentForm.action = actionUrl;
                }
                
                if (documentForm.requestSubmit) {
                    documentForm.requestSubmit();
                } else {
                    documentForm.submit();
                }
            };

            if (loanMarketValueInput && loanLtvSelect && loanAmountInput) {
                const updateLoanAmount = () => {
                    const marketValue = parseFloat(loanMarketValueInput.value || '0') || 0;
                    const ltv = parseFloat(loanLtvSelect.value || '0') || 0;
                    const calculated = marketValue * ltv / 100;
                    loanAmountInput.value = calculated > 0 ? calculated.toFixed(2) : '';
                };

                loanMarketValueInput.addEventListener('input', updateLoanAmount);
                loanLtvSelect.addEventListener('change', updateLoanAmount);
            }

            window.confirmAndSubmitItemDelete = function(btn) {
                if (!btn || !btn.value) {
                    return;
                }

                if (!confirm('Видалити цей товар?')) {
                    return;
                }

                forceSubmitAction(btn, 'bid', btn.value, "{{ route($documentRoutes . '.body.delete') }}");
            };

            const bindGoodsRowInputs = (countInput, priceInput, sumInput) => {
                if (!countInput || !priceInput || !sumInput) {
                    return;
                }

                const tr = countInput.closest('tr');
                const docType = tr?.dataset.docType || '';

                const updateRowSum = (e) => {
                    const quantity = parseFloat(countInput.value) || 0;
                    let price = parseFloat(priceInput.value) || 0;

                    // Автоматически обновляем цену по каталогу/опту только если пользователь меняет количество
                    if (tr && e && e.target === countInput) {
                        const priceCompPay1 = parseFloat(tr.dataset.priceCompPay1) || 0;
                        const priceBase = parseFloat(tr.dataset.priceBase) || parseFloat(tr.dataset.priceCompPay) || 0;
                        const priceWholesale = parseFloat(tr.dataset.priceWholesale) || 0;
                        const wholesaleFrom = parseInt(tr.dataset.wholesaleFrom) || 0;

                        if (docType === 'ZIN' || docType === 'PN') {
                            if (priceCompPay1 > 0) {
                                price = priceCompPay1;
                                priceInput.value = price.toFixed(2);
                            }
                        } else {
                            if (wholesaleFrom > 0 && priceWholesale > 0 && quantity >= wholesaleFrom) {
                                priceInput.value = priceWholesale.toFixed(2);
                                price = priceWholesale;
                            } else if (priceBase > 0) {
                                priceInput.value = priceBase.toFixed(2);
                                price = priceBase;
                            }
                        }
                    }

                    sumInput.value = (quantity * price).toFixed(2);
                    updateZeroSumHighlight(tr);
                    updateDocumentSum();
                };

                const updateRowPriceFromSum = () => {
                    const quantity = parseFloat(countInput.value) || 0;
                    const sum = parseFloat(sumInput.value) || 0;

                    if (quantity > 0) {
                        const price = sum / quantity;
                        priceInput.value = price.toFixed(2);
                    }

                    updateZeroSumHighlight(tr);
                    updateDocumentSum();
                };

                const handleSaveOnEnter = (event) => {
                    if (event.key !== 'Enter') {
                        return;
                    }

                    event.preventDefault();
                    updateRowSum();
                    submitDocumentSave();
                };

                countInput.addEventListener('input', updateRowSum);
                priceInput.addEventListener('input', updateRowSum);
                sumInput.addEventListener('input', updateRowPriceFromSum);
                countInput.addEventListener('keydown', handleSaveOnEnter);
                priceInput.addEventListener('keydown', handleSaveOnEnter);
                sumInput.addEventListener('keydown', handleSaveOnEnter);
                updateZeroSumHighlight(tr);
            };

            document.querySelectorAll('#goodsTable tbody tr').forEach(tr => {
                if (tr.id === 'emptyGoodsRow') return;
                const cnt = tr.querySelector('.goods-count');
                const prc = tr.querySelector('.goods-price');
                const sum = tr.querySelector('.goods-sum');
                bindGoodsRowInputs(cnt, prc, sum);
            });

            updateDocumentSum();

            if (goodsSearchInput && searchGoodsBtn && goodsResultsContainer && tableBody) {
                function performGoodsSearch() {
                    const q = goodsSearchInput.value.trim();
                    if (q.length < 2) { goodsResultsContainer.style.display = 'none'; return; }
                    const docType = '{{ $doc }}';
                    const goodsSearchParams = new URLSearchParams({
                        q,
                        doc: docType,
                        counterparty_user_id: client1Id?.value || '',
                    });
                    fetch("{{ route('goods.search') }}?" + goodsSearchParams.toString())
                        .then(res => res.json())
                        .then(data => {
                            goodsResultsContainer.innerHTML = '';
                            if (data.length === 0) {
                                goodsResultsContainer.innerHTML = '<div class="list-group-item text-dark bg-white">Нічого не знайдено</div>';
                            } else {
    	                            data.forEach(good => {
    	                                const a = document.createElement('a');
    	                                const imageUrl = good.image_thumb || good.image || '';
    	                                const imageHtml = imageUrl
    	                                    ? `<img src="${imageUrl}" alt="" style="width:48px;height:48px;object-fit:contain;border-radius:6px;background:#f1f5f9;flex:0 0 48px;">`
    	                                    : `<div style="width:48px;height:48px;border-radius:6px;background:#e5e7eb;flex:0 0 48px;"></div>`;
    	                                a.href = '#'; a.className = 'list-group-item list-group-item-action py-2 bg-white text-dark';
    	                                a.innerHTML = `<div style="display:flex;gap:10px;align-items:center;">${imageHtml}<div><strong>${good.pnum}</strong> - ${good.name || ''} <br><small class="text-dark">Ціна (pay): ${good.priceCompPay} грн</small></div></div>`;
    	                                a.addEventListener('click', function (e) {
                                        e.preventDefault();
                                        const emptyRow = document.getElementById('emptyGoodsRow');
                                        if (emptyRow) emptyRow.remove();
                                        const quantity = 1;
                                        const wholesaleFrom = parseInt(good.wholesaleFrom || 0, 10);
                                        const pay = parseFloat(good.priceCompPay || 0);
                                        const pay1 = parseFloat(good.priceCompPay1 || 0);
                                        const priceBase = parseFloat(good.priceBase || 0) || pay;
                                        const priceWholesale = parseFloat(good.priceWholesale || 0) || parseFloat(good.pay1 || 0);
                                        let initialPrice = (docType === 'ZIN' || docType === 'PN') ? pay1 : priceBase;
                                        if ((docType === 'ZOUT' || docType === 'RN') && wholesaleFrom > 0 && quantity >= wholesaleFrom && priceWholesale > 0) {
                                            initialPrice = priceWholesale;
                                        } else if ((docType === 'ZOUT' || docType === 'RN') && priceBase > 0) {
                                            initialPrice = priceBase;
                                        } else if ((docType === 'ZIN' || docType === 'PN') && pay1 <= 0) {
                                            initialPrice = 0;
                                        } else if (initialPrice <= 0) {
                                            initialPrice = pay1;
                                        }
                                        const mappedProductId = String(good.mappedProductId || '');
                                        const mapButtonClass = mappedProductId ? 'btn btn-sm btn-outline-secondary product-map-btn is-mapped' : 'btn btn-sm btn-outline-secondary product-map-btn';
                                        const mapButtonText = mappedProductId || '...';

                                        const tr = document.createElement('tr');
                                        tr.innerHTML = `
                                            <td class="goods-table-col-code" data-label="Код"><input type="hidden" name="id[]" value="0"><input type="hidden" name="pid[]" value="${good.id}"><input type="hidden" name="pnum[]" value="${good.pnum}"><input type="text" class="form-control form-control-sm text-dark text-white" value="${good.pnum}" readonly></td>
                                            <td class="goods-table-col-name" data-label="Найменування">
                                                <input type="hidden" name="name[]" value="${escapeHtml(good.name || '')}">
                                                <div class="goods-name-map-wrap">
                                                    <input type="text" class="form-control form-control-sm text-dark text-white" value="${escapeHtml(good.name || '')}" readonly>
                                                    <button type="button" class="${mapButtonClass}" data-source-product-id="${escapeHtml(good.pnum)}" data-source-product-name="${escapeHtml(good.name || '')}" data-target-product-id="${escapeHtml(mappedProductId)}" title="Маппінг товару проекту ${escapeHtml(mappingTargetProjectId || 'не визначено')}">${escapeHtml(mapButtonText)}</button>
                                                </div>
                                            </td>
                                            <td class="goods-table-col-qty" data-label="К-ть">
                                                <div class="input-group input-group-sm">
                                                    <button type="button" class="btn btn-outline-secondary btn-qty-decrease">−</button>
                                                    <input type="number" step="1" name="pcount[]" class="form-control form-control-sm goods-count text-dark text-white" value="1">
                                                    <button type="button" class="btn btn-outline-secondary btn-qty-increase">+</button>
                                                </div>
                                            </td>
                                            <td class="goods-table-col-price" data-label="Ціна"><input type="text" name="pprice[]" class="form-control form-control-sm goods-price text-dark text-white" value="${initialPrice.toFixed(2)}"></td>
                                            <td class="goods-table-col-sum" data-label="Сума"><input type="text" name="psumma[]" class="form-control form-control-sm goods-sum text-dark text-white" value="${initialPrice.toFixed(2)}"></td>
                                            <td class="goods-table-col-actions text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-new-row remove-btn">❌</button></td>`;
                                        tr.dataset.priceCompPay = good.priceCompPay || 0;
                                        tr.dataset.priceCompPay1 = good.priceCompPay1 || 0;
                                        tr.dataset.priceBase = good.priceBase || 0;
                                        tr.dataset.priceWholesale = good.priceWholesale || 0;
                                        tr.dataset.wholesaleFrom = good.wholesaleFrom || 0;
                                        tr.dataset.docType = '{{ $doc }}';
                                        tableBody.appendChild(tr);
                                        const pcount = tr.querySelector('.goods-count');
                                        const pprice = tr.querySelector('.goods-price');
                                        const psum = tr.querySelector('.goods-sum');
                                        bindGoodsRowInputs(pcount, pprice, psum);
                                        updateZeroSumHighlight(tr);
                                        tr.querySelector('.remove-new-row').addEventListener('click', () => {
                                            tr.remove();
                                            if (tableBody.querySelectorAll('tr').length === 0) {
                                                tableBody.innerHTML = '<tr id="emptyGoodsRow"><td colspan="6" class="text-center text-dark">Немає товарів</td></tr>';
                                            }
                                            updateDocumentSum();
                                        });
                                        updateDocumentSum();
                                        goodsResultsContainer.style.display = 'none';
                                        goodsSearchInput.value = '';
                                    });
                                    goodsResultsContainer.appendChild(a);
                                });
                            }
                            goodsResultsContainer.style.display = 'block';
                        })
                        .catch(err => console.error('Goods search failed:', err));
                }
                searchGoodsBtn.addEventListener('click', performGoodsSearch);
                let goodsSearchTimeout = null;
                goodsSearchInput.addEventListener('input', function (e) {
                    clearTimeout(goodsSearchTimeout);
                    goodsSearchTimeout = setTimeout(performGoodsSearch, 400);
                });
                goodsSearchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') { e.preventDefault(); performGoodsSearch(); }
                });
                document.addEventListener('click', function (e) {
                    if (!goodsSearchInput.contains(e.target) && !goodsResultsContainer.contains(e.target) && !searchGoodsBtn.contains(e.target)) {
                        goodsResultsContainer.style.display = 'none';
                    }
                });
            }

            // ================= QUANTITY +/- BUTTONS =================
            document.addEventListener('click', function (e) {
                const decreaseBtn = e.target.closest('.btn-qty-decrease');
                const increaseBtn = e.target.closest('.btn-qty-increase');

                if (decreaseBtn) {
                    const inputGroup = decreaseBtn.closest('.input-group');
                    const countInput = inputGroup?.querySelector('.goods-count');
                    if (countInput) {
                        const currentVal = parseFloat(countInput.value) || 0;
                        const step = parseFloat(countInput.step) || 1;
                        countInput.value = Math.max(0, currentVal - step).toFixed(3).replace(/\.?0+$/, '');
                        countInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }

                if (increaseBtn) {
                    const inputGroup = increaseBtn.closest('.input-group');
                    const countInput = inputGroup?.querySelector('.goods-count');
                    if (countInput) {
                        const currentVal = parseFloat(countInput.value) || 0;
                        const step = parseFloat(countInput.step) || 1;
                        countInput.value = (currentVal + step).toFixed(3).replace(/\.?0+$/, '');
                        countInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            });
        });

        // ================= RA MULTIPLE FILE UPLOADS =================
        (function () {
            function initRaUploads() {
                console.log('🔍 initRaUploads called');
                const raFilesContainer = document.getElementById('raFilesContainer');
                const raFilesPreview = document.getElementById('raFilesPreview');
                
                console.log('raFilesContainer:', raFilesContainer);
                console.log('raFilesPreview:', raFilesPreview);

                if (!raFilesContainer) {
                    console.log('❌ raFilesContainer not found - not an RA document');
                    return;
                }

                console.log('✅ RA upload block found, initializing...');

                function getFileIcon(type) {
                    if (type.startsWith('image/')) {
                        return null;
                    }
                    if (type === 'application/pdf') {
                        return '📄';
                    }
                    if (type.startsWith('video/')) {
                        return '🎬';
                    }
                    if (type.startsWith('audio/')) {
                        return '🎧';
                    }
                    return '📎';
                }

                function createPreviewCard(name, url, type, isImage, generated = false, inputIndex = null) {
                    const card = document.createElement('div');
                    card.className = 'file-preview-card' + (generated ? ' generated-preview' : '');

                    if (inputIndex) {
                        card.dataset.inputIndex = inputIndex;
                    }

                    if (isImage) {
                        const img = document.createElement('img');
                        img.src = url;
                        card.appendChild(img);
                    } else {
                        const icon = document.createElement('div');
                        icon.className = 'file-preview-icon';
                        icon.textContent = getFileIcon(type);
                        card.appendChild(icon);
                    }

                    const nameEl = document.createElement('div');
                    nameEl.className = 'file-preview-name';
                    nameEl.textContent = name;
                    card.appendChild(nameEl);

                    if (url) {
                        const link = document.createElement('a');
                        link.href = url;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.textContent = 'Скачати';
                        card.appendChild(link);
                    }

                    if (generated) {
                        const removeButton = document.createElement('button');
                        removeButton.type = 'button';
                        removeButton.className = 'file-preview-remove btn btn-sm btn-outline-danger';
                        removeButton.textContent = '×';
                        card.appendChild(removeButton);
                    }

                    return card;
                }

                function renderFilesPreview(files, container) {
                    container.innerHTML = '';
                    if (!files || files.length === 0) {
                        return;
                    }
                    Array.from(files).forEach(file => {
                        const isImage = file.type.startsWith('image/');
                        if (isImage) {
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                container.appendChild(createPreviewCard(file.name, e.target.result, file.type, true));
                            };
                            reader.readAsDataURL(file);
                        } else {
                            container.appendChild(createPreviewCard(file.name, '', file.type, false));
                        }
                    });
                }

                function renderRaPreviews() {
                    if (!raFilesPreview) {
                        console.log('❌ raFilesPreview not found');
                        return;
                    }
                    console.log('🖼️ Rendering RA previews...');

                    const existingGenerated = raFilesPreview.querySelectorAll('.generated-preview');
                    existingGenerated.forEach(card => card.remove());

                    const inputs = document.querySelectorAll('.ra-file-input');
                    console.log('📎 Total RA inputs found:', inputs.length);

                    let hasSelectedFiles = false;
                    inputs.forEach((input, idx) => {
                        if (!input.files || input.files.length === 0) {
                            console.log('   Input ' + idx + ': empty');
                            return;
                        }
                        hasSelectedFiles = true;
                        const file = input.files[0];
                        console.log('   Input ' + idx + ': ' + file.name + ' (' + file.type + ')');
                        const isImage = file.type.startsWith('image/');
                        if (isImage) {
                            const reader = new FileReader();
                            reader.onload = function (e) {
                                raFilesPreview.appendChild(createPreviewCard(file.name, e.target.result, file.type, true, true, input.dataset.index));
                            };
                            reader.readAsDataURL(file);
                        } else {
                            raFilesPreview.appendChild(createPreviewCard(file.name, '', file.type, false, true, input.dataset.index));
                        }
                    });

                    if (!hasSelectedFiles) {
                        console.log('   No selected files found, preserving existing saved previews');
                    }
                }

                function addRaInputListener(input) {
                    input.addEventListener('change', function () {
                        console.log('🔄 RA file input changed, updating fields', {
                            filename: this.files[0]?.name || 'no file',
                            index: this.dataset.index
                        });
                        updateRaFields();
                        renderRaPreviews();
                    });
                }

                function addRaFileField(index) {
                    const container = document.getElementById('raFilesContainer');
                    if (!container) {
                        console.log('❌ raFilesContainer not found when adding field');
                        return;
                    }
                    console.log('➕ Adding new RA file field:', index);
                    const newItem = document.createElement('div');
                    newItem.className = 'ra-file-item';
                    newItem.setAttribute('data-index', index);
                    newItem.style.cssText = 'margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.1);';
                    newItem.innerHTML = `
                        <label style="display: block; margin-bottom: 6px; font-size: 0.95rem;">📎 Файл ${index}:</label>
                        <input type="file" name="docum[]" class="form-control text-white ra-file-input" data-index="${index}">
                    `;
                    container.appendChild(newItem);
                    const newInput = newItem.querySelector('.ra-file-input');
                    if (newInput) {
                        console.log('✅ New input field created and listener added');
                        addRaInputListener(newInput);
                    }
                }

                function removeRaFieldsAfter(index) {
                    const container = document.getElementById('raFilesContainer');
                    if (!container) {
                        return;
                    }
                    const items = container.querySelectorAll('.ra-file-item');
                    items.forEach(item => {
                        const itemIndex = parseInt(item.getAttribute('data-index'));
                        if (itemIndex > index) {
                            item.remove();
                        }
                    });
                }

                function updateRaFields() {
                    const container = document.getElementById('raFilesContainer');
                    if (!container) {
                        console.log('❌ raFilesContainer not found in updateRaFields');
                        return;
                    }
                    console.log('🔄 Updating RA fields...');
                    const items = container.querySelectorAll('.ra-file-item');
                    console.log('📦 Current items count:', items.length);

                    if (items.length === 0) {
                        addRaFileField(1);
                        return;
                    }

                    items.forEach((item, idx) => {
                        const input = item.querySelector('.ra-file-input');
                        const index = parseInt(input.dataset.index) || (idx + 1);
                        const hasFiles = input.files && input.files.length > 0;
                        console.log('   Item ' + idx + ' (index=' + index + '): ' + (hasFiles ? 'HAS FILES' : 'empty'));
                        if (hasFiles) {
                            if (!container.querySelector(`.ra-file-item[data-index="${index + 1}"]`)) {
                                console.log('   → Need to add field for index ' + (index + 1));
                                addRaFileField(index + 1);
                            }
                        } else {
                            removeRaFieldsAfter(index);
                        }
                    });
                }

                function attachRaPreviewDeleteHandler() {
                    if (!raFilesPreview) {
                        return;
                    }

                    raFilesPreview.addEventListener('click', function (event) {
                        const button = event.target.closest('.file-preview-remove');
                        if (!button) {
                            return;
                        }

                        const card = button.closest('.file-preview-card');
                        if (!card) {
                            return;
                        }

                        const inputIndex = card.dataset.inputIndex;
                        if (inputIndex) {
                            const inputItem = document.querySelector(`.ra-file-item[data-index="${inputIndex}"]`);
                            if (inputItem) {
                                inputItem.remove();
                            }
                        }

                        card.remove();
                        updateRaFields();
                    });
                }

                if (raFilesContainer) {
                    console.log('✅ Adding listeners to existing RA inputs');
                    console.log('✅ Adding listeners to existing RA inputs');
                    const existingInputs = raFilesContainer.querySelectorAll('.ra-file-input');
                    console.log('📎 Found ' + existingInputs.length + ' existing RA inputs');
                    existingInputs.forEach(addRaInputListener);
                    attachRaPreviewDeleteHandler();
                    updateRaFields();
                    console.log('✅ RA initialization complete');
                }
            }

            if (document.readyState === 'loading') {
                console.log('⏳ Document still loading, waiting for DOMContentLoaded');
                document.addEventListener('DOMContentLoaded', initRaUploads);
            } else {
                console.log('✅ Document already loaded, initializing RA uploads immediately');
                initRaUploads();
            }
        })();

    </script>
@endsection
