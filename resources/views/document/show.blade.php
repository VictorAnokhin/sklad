@extends('home')

@section('content')
    @include('partials.panel')

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

        #goodsTable .goods-table-col-price .form-control,
        #goodsTable .goods-table-col-sum .form-control {
            min-width: 100%;
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
                width: 100vw;
                max-width: 100vw;
                margin: 0;
                min-height: 100vh;
            }

            #newClientModal .modal-content {
                min-height: 100vh;
                border-radius: 0;
                border: 0;
            }

            #newClientModal .modal-body {
                padding: 16px;
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
                gap: 12px;
            }

            #newClientModal .modal-client-grid .col-12,
            #newClientModal .modal-client-grid .col-md-6 {
                width: 100%;
            }
        }
    </style>

    <div class="ttable doc-page">

        {{-- Header --}}
        <div class="doc-header">
            <h2>{{ \App\Models\Document::typeName($doc) }} № {{ $document->num }}</h2>
        </div>

        <div class="mb-2 d-flex flex-wrap gap-2">
            <a href="{{ $documentIndexUrl }}" class="btn btn-outline-secondary btn-sm">
                ← До списку {{ \App\Models\Document::typeName($doc) }}
            </a>
            @if(!empty($parentDocumentUrl) && !empty($parentDocument))
                <a href="{{ $parentDocumentUrl }}" class="btn btn-outline-primary btn-sm">
                    ← До {{ \App\Models\Document::typeName($parentDocument->type) }} № {{ $parentDocument->num }}
                </a>
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

        {{-- Related icons strip (client_info) --}}
        @if(!empty($relatedIcons))
            <div class="alert alert-secondary py-2 related-icons-bar">
                <strong>Зв'язані:</strong> {!! $relatedIcons !!}
            </div>
        @endif

        <form action="{{ route('document.save') }}" method="post" class="compact-form" enctype="multipart/form-data">
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
                    @if(in_array($doc, ['RN', 'PN', 'WO1'], true))
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
                    @if(in_array($doc, ['ZOUT', 'ZIN'], true))
                        <div class="doc-form-row doc-form-row-two-cols">
                            <div class="col-f">
                                <label>ТТН Нова Пошта</label>
                                <input type="text" name="ttn" class="form-control text-white" value="{{ $document->ttn ?? '' }}">
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
                    @if(in_array($doc, ['PO', 'RO', 'ZP'], true))
                        <div class="doc-form-row doc-form-row-two-cols">
                            <div class="col-f">
                                <label>Каса</label>
                                <select name="oplata" class="form-select text-white">
                                    <option value="">— Оберіть касу —</option>
                                    @foreach(($oplataList ?? collect()) as $oplataOption)
                                        <option value="{{ $oplataOption->id }}" {{ (string) ($document->oplata ?? '') === (string) $oplataOption->id ? 'selected' : '' }}>
                                            {{ $oplataOption->name }}
                                        </option>
                                    @endforeach
                                </select>
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
                            @if($doc !== 'ZP')
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
                            data-status="{{ $client->idstatus ?? '' }}">
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

                    <!-- Сума field for PO/RO documents -->
                    @if(in_array($doc, ['PO', 'RO', 'ZP'], true))
                        <div class="doc-form-row-single">
                            <label>Сума</label>
                            <input type="text" name="summa" id="documentSummaInput" class="form-control form-control-number text-white"
                                value="{{ $document->summa ?? 0 }}">
                        </div>
                    @endif

                    <!-- RA: Multiple file upload block -->
                    @if($doc === 'RA')
                        <div class="ra-document-block" style="border: 2px solid #4a5568; padding: 16px; border-radius: 8px; background: rgba(0,0,0,0.2); margin-bottom: 20px;">
                            <div class="ra-title" style="font-weight: 600; font-size: 1.1rem; margin-bottom: 16px; color: #e0e7ff;">
                                📎 Завантажити файли
                            </div>

                            <div id="raFilesPreview" class="file-preview-container">
                                @php
                                    $existingRaFiles = [];
                                    if ($doc === 'RA' && !empty($document->docum)) {
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
                    @if(!in_array($doc, ['PO', 'RO', 'ZP', 'RA'], true))
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
                                                <input type="text" class="form-control form-control-sm text-white" value="{{ $item->name ?? '' }}"
                                                    readonly>
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
                                    class="form-control doc-sum-box-input text-end fs-6 text-white" value="{{ $document->summa ?? 0 }}">
                            </div>
                        </div>

                    @endif

                    <!-- Примечание field -->
                    <div class="doc-form-row-single">
                        <label>Примечание</label>
                        <textarea name="content" class="form-control text-white" rows="3" placeholder="Внесіть примітку до документа">{{ $document->content ?? '' }}</textarea>
                    </div>

                    {{-- Action buttons (inside form) --}}
                    <div class="doc-actions">
                        @if(in_array($doc, ['RN', 'PN', 'PO', 'RO', 'ZP', 'VN', 'AO', 'WO1'], true))
                            @if((int) ($document->provodka ?? 0) === 1)
                                <button type="button" 
                                    onclick="forceSubmitAction(this, '', '', '{{ route('document.provodka') }}')"
                                    ontouchstart="forceSubmitAction(this, '', '', '{{ route('document.provodka') }}'); event.preventDefault();"
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
                                    class="btn btn-primary {{ in_array($doc, ['PN', 'RN', 'PO', 'RO'], true) ? '' : 'w-100 mb-2' }}">💾 Зберегти</button>
                            @endif
                        @elseif($doc === 'RA')
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
                            <a href="{{ route('document.print', ['doc' => $doc, 'doc_id' => $document->id, 'num' => $document->num, 'year' => $year]) }}"
                                class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer">
                                Печать
                            </a>
                        @endif
                        @if(intval($document->provodka) === 0 && $doc !== 'RA')
                            <button type="button" class="btn btn-outline-danger"
                                onclick="if(confirm('Видалити документ{{ $doc === 'RA' ? '' : ' та всі товари' }}?')) { document.getElementById('deleteDocForm').submit(); }">🗑
                                Видалити
                            </button>
                        @endif
                    </div>
                </div>

                {{-- RIGHT: Related documents (client_info1) --}}
                @if(!empty($relatedDocs))
                    <div class="doc-related-col">
                        <div class="related-panel">
                            <h5>📋 Зв'язані документи</h5>
                            {!! $relatedDocs['html'] !!}
                        </div>
                    </div>
                @endif
            </div>
        </form>

        {{-- Hidden form for document deletion --}}
        <form id="deleteDocForm" action="{{ route('document.destroy') }}" method="post" class="delete-form">
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
                                <input type="text" class="form-control form-control-sm text-white" id="newClientCity">
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
            const teamOnlyClientSearch = @json($doc === 'ZP');
            const formatClientName = (user) => [user.secondname || '', user.name || ''].filter(Boolean).join(' ').trim();
            const formatClientDetailsHtml = (user) => {
                const regionPart = user.region ? user.region + ' | ' : '';
                const poshtaPart = user.poshta ? ' | ' + user.poshta : '';
                const orgnamePart = user.orgname ? `<strong>${user.orgname}</strong> | ` : '';
                const clientName = formatClientName(user);
                return `${orgnamePart}${clientName}<br><small>${user.phone || ''} | ${regionPart}${user.city || ''}${poshtaPart}</small>`;
            };

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

            if(newClientBtn) {
                newClientBtn.addEventListener('click', () => {
                    document.getElementById('newClientModalLabel').textContent = 'Новий клієнт';
                    newClientIdField.value = '0';
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
                    const orgname = orgnameField.value.trim();
                    const name = nameField.value.trim();
                    const secondname = secondnameField.value.trim();
                    const phone = phoneField.value.trim();
                    const city = cityField.value.trim();
                    const region = regionField.value.trim();
                    const poshta = poshtaField.value.trim();
                    const idstatus = statusField.value;
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
                        body: JSON.stringify({ id, orgname, name, secondname, phone, city, region, poshta, idstatus })
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

            const updateDocumentSum = () => {
                if (!documentSummaInput1 || !tableBody) {
                    return;
                }

                const total = Array.from(tableBody.querySelectorAll('.goods-sum'))
                    .reduce((carry, input) => carry + (parseFloat(input.value) || 0), 0);

                documentSummaInput1.value = total.toFixed(2);
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

            window.confirmAndSubmitItemDelete = function(btn) {
                if (!btn || !btn.value) {
                    return;
                }

                if (!confirm('Видалити цей товар?')) {
                    return;
                }

                forceSubmitAction(btn, 'bid', btn.value, "{{ route('document.body.delete') }}");
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
                    updateDocumentSum();
                };

                const updateRowPriceFromSum = () => {
                    const quantity = parseFloat(countInput.value) || 0;
                    const sum = parseFloat(sumInput.value) || 0;

                    if (quantity > 0) {
                        const price = sum / quantity;
                        priceInput.value = price.toFixed(2);
                        updateDocumentSum();
                    }
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
            };

            document.querySelectorAll('table tbody tr').forEach(tr => {
                if (tr.id === 'emptyGoodsRow') return;
                const cnt = tr.querySelector('.goods-count');
                const prc = tr.querySelector('.goods-price');
                const sum = tr.querySelector('.goods-sum');
                bindGoodsRowInputs(cnt, prc, sum);
            });

            updateDocumentSum();

            function performGoodsSearch() {
                const q = goodsSearchInput.value.trim();
                if (q.length < 2) { goodsResultsContainer.style.display = 'none'; return; }
                const docType = '{{ $doc }}';
                fetch("{{ route('goods.search') }}?q=" + encodeURIComponent(q) + "&doc=" + encodeURIComponent(docType))
                    .then(res => res.json())
                    .then(data => {
                        goodsResultsContainer.innerHTML = '';
                        if (data.length === 0) {
                            goodsResultsContainer.innerHTML = '<div class="list-group-item text-dark bg-white">Нічого не знайдено</div>';
                        } else {
                            data.forEach(good => {
                                const a = document.createElement('a');
                                a.href = '#'; a.className = 'list-group-item list-group-item-action py-2 bg-white text-dark';
                                a.innerHTML = `<strong>${good.pnum}</strong> - ${good.name || ''} <br><small class=" text-dark">Ціна (pay): ${good.priceCompPay} грн | Залишок: ${good.count || 0}</small>`;
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

                                    const tr = document.createElement('tr');
                                    tr.innerHTML = `
                                        <td class="goods-table-col-code" data-label="Код"><input type="hidden" name="id[]" value="0"><input type="hidden" name="pid[]" value="${good.id}"><input type="hidden" name="pnum[]" value="${good.pnum}"><input type="text" class="form-control form-control-sm text-dark text-white" value="${good.pnum}" readonly></td>
                                        <td class="goods-table-col-name" data-label="Найменування"><input type="hidden" name="name[]" value="${good.name || ''}"><input type="text" class="form-control form-control-sm text-dark text-white" value="${good.name || ''}" readonly></td>
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
