<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Друк {{ $docTitle ?? 'документа' }} № {{ $document->num }}</title>
    <style>
        body {
            margin: 0;
            padding: 18px;
            font-family: Arial, sans-serif;
            color: #111827;
            background: #ffffff;
            font-size: 13px;
            line-height: 1.3;
        }

        .print-page {
            max-width: 960px;
            margin: 0 auto;
        }

        .print-header {
            margin-bottom: 14px;
        }

        .print-title {
            margin: 0 0 8px;
            font-size: 26px;
            line-height: 1.1;
        }

        .print-subtitle {
            margin: 0;
            color: #4b5563;
        }

        .print-card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 10px;
        }

        .print-card h3 {
            margin: 0 0 6px;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 5px 7px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        td.num, th.num {
            text-align: right;
            white-space: nowrap;
        }

        .compact-table td {
            width: 50%;
            padding: 4px 6px;
        }

        .compact-table strong {
            font-weight: 700;
        }

        .totals {
            margin-top: 8px;
            display: flex;
            justify-content: flex-end;
        }

        .table-meta {
            margin-top: 8px;
            font-size: 12px;
        }

        .totals-box {
            min-width: 280px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 8px 10px;
        }

        .totals-line {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 4px;
        }

        .totals-line:last-child {
            margin-bottom: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .print-note {
            margin-top: 10px;
            color: #4b5563;
        }

        .signature-block {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
        }

        .signature-box {
            width: 320px;
        }

        .signature-line {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }

        .signature-label {
            white-space: nowrap;
        }

        .signature-space {
            flex: 1;
            min-height: 28px;
            border-bottom: 1px solid #111827;
        }

        .signature-image {
            max-width: 170px;
            max-height: 70px;
            object-fit: contain;
            display: block;
        }

        .stamp-image {
            margin-top: 10px;
            max-width: 120px;
            max-height: 120px;
            object-fit: contain;
            margin-left: auto;
            display: block;
        }

        .signature-name {
            margin-top: 4px;
            text-align: right;
            font-size: 12px;
            color: #4b5563;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-page {
                max-width: none;
            }
        }

    </style>
</head>
<body>
    @php
        $signatureImage = $firma ? \App\Models\Firma::resolveMediaUrl((string) ($firma->pidpys ?? '')) : null;
        $stampImage = $firma ? \App\Models\Firma::resolveMediaUrl((string) ($firma->pechat ?? '')) : null;
        $signatureName = $firma ? trim((string) ($firma->direktor ?? '')) : '';
    @endphp
    <div class="print-page">
        <div class="print-header">
            <div>
                <h1 class="print-title">{{ $docTitle }} № {{ $document->num }}</h1>
                <p class="print-subtitle">Дата: {{ $document->data ?: '—' }}</p>
            </div>
        </div>

        @if($firma)
            <div class="print-card">
                <h3>Постачальник</h3>
                <table class="compact-table">
                    <tbody>
                        <tr>
                            <td><strong>Назва компанії:</strong> {{ $firma->name ?: '—' }}</td>
                            <td><strong>ЄДРПОУ:</strong> {{ $firma->regnum ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td><strong>ІПН:</strong> {{ $firma->inn ?: '—' }}</td>
                            <td><strong>Р/р:</strong> {{ $firma->schet ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Банк:</strong> {{ $firma->bank ?: '—' }}</td>
                            <td><strong>МФО:</strong> {{ $firma->mfo ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Адреса:</strong> {{ $firma->address ?: '—' }}</td>
                            <td><strong>Телефон:</strong> {{ $firma->phone ?: '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <div class="print-card">
            <h3>Покупець</h3>
            @if($client)
                @php
                    $clientName = trim((string) ($client->orgname ?? ''));
                    $clientEdrpou = trim((string) ($client->kod1 ?? ''));
                    $clientPhone = trim((string) ($client->phone ?? ''));
                    $clientDetails = array_filter([
                        $clientName !== '' ? $clientName : '—',
                        $clientEdrpou !== '' ? 'ЄДРПОУ: ' . $clientEdrpou : null,
                        $clientPhone !== '' ? 'Телефон: ' . $clientPhone : null,
                    ]);
                @endphp
                <div>{{ implode(' | ', $clientDetails) }}</div>
            @else
                <div>Клієнта не вказано.</div>
            @endif
        </div>

        @if(!empty($document->content))
            <div class="print-card">
                <h3>Коментар</h3>
                <div>{{ $document->content }}</div>
            </div>
        @endif

        <div class="print-card">
            <h3>{{ $itemsTitle ?? 'Позиції документа' }}</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 48px;">№</th>
                        <th>Найменування</th>
                        <th class="num" style="width: 72px;">К-ть</th>
                        <th class="num" style="width: 102px;">Ціна</th>
                        <th class="num" style="width: 112px;">Сума</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lineItems as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->name ?: '—' }}</td>
                            <td class="num">{{ number_format((float) ($item->pcount ?? 0), 3, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) ($item->pprice ?? 0), 2, '.', ' ') }}</td>
                            <td class="num">{{ number_format((float) ($item->psumma ?? 0), 2, '.', ' ') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">У документі немає товарних позицій.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="totals">
                <div class="totals-box">
                    @if((float) ($document->discount ?? 0) > 0)
                        <div class="totals-line">
                            <span>Знижка</span>
                            <span>{{ number_format((float) $document->discount, 2, '.', ' ') }} грн</span>
                        </div>
                    @endif
                    <div class="totals-line">
                        <span>Разом</span>
                        <span>{{ number_format((float) ($document->summa ?? 0), 2, '.', ' ') }} грн</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="print-note">
            Документ сформовано {{ now()->format('d-m-Y H:i') }}.
        </div>

        @if(!empty($skladName))
            <div class="table-meta">
                <strong>Склад:</strong> {{ $skladName }}
            </div>
        @endif

        <div class="signature-block">
            <div class="signature-box">
                <div class="signature-line">
                    <div class="signature-label">Підпис</div>
                    @if($signatureImage)
                        <img src="{{ $signatureImage }}" alt="Підпис" class="signature-image">
                    @else
                        <div class="signature-space"></div>
                    @endif
                </div>
                @if(!empty($signatureName))
                    <div class="signature-name">
                        {{ $signatureName }}
                    </div>
                @endif
                @if($stampImage)
                    <img src="{{ $stampImage }}" alt="Печатка" class="stamp-image">
                @endif
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
