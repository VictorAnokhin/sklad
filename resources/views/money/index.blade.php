@extends('home')

@section('title', 'Гроші')

@section('content')
@include('partials.panel')

<div class="ttable" style="padding: 16px;">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Зведення --}}
    <div style="display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap;">
        <div style="padding:12px 24px; background:#d4edda; border-radius:8px; font-weight:bold; font-size:1.1em;">
            📥 Приход (PO): {{ number_format($sumPO, 2, '.', ' ') }} грн
        </div>
        <div style="padding:12px 24px; background:#f8d7da; border-radius:8px; font-weight:bold; font-size:1.1em;">
            📤 Видача (RO): {{ number_format($sumRO, 2, '.', ' ') }} грн
        </div>
        <div style="padding:12px 24px; background:#fff3cd; border-radius:8px; font-weight:bold; font-size:1.1em;">
            💰 Баланс: {{ number_format($sumPO - $sumRO, 2, '.', ' ') }} грн
        </div>
    </div>

    {{-- Кнопки додавання --}}
    <div style="display:flex; gap:10px; margin-bottom:16px;">
        <a href="{{ route('money.show', ['id' => 0, 'type' => 'PO']) }}" class="btn btn-success">+ Прихід (PO)</a>
        <a href="{{ route('money.show', ['id' => 0, 'type' => 'RO']) }}" class="btn btn-danger">+ Видача (RO)</a>
    </div>

    {{-- Список документів --}}
    <h4>Документи (PO / RO)</h4>

    @if($documents->isEmpty())
        <div style="text-align:center; padding:20px; color:#CC0000;">Документи відсутні...</div>
    @else
        <table class="table table-bordered table-sm">
            <thead style="background:#efefef;">
                <tr>
                    <th>#</th>
                    <th>Тип</th>
                    <th>Дата</th>
                    <th>Клієнт</th>
                    <th>Каса</th>
                    <th>Сума (грн)</th>
                    <th>Коментар</th>
                    <th>Пров.</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($documents as $doc)
                <tr>
                    <td>{{ $doc->num }}</td>
                    <td>
                        @if($doc->type === 'PO')
                            <span style="color:green; font-weight:bold;">📥 PO</span>
                        @else
                            <span style="color:red; font-weight:bold;">📤 RO</span>
                        @endif
                    </td>
                    <td>{{ $doc->data ?? '—' }}</td>
                    <td style="font-size:0.9em;">
                        {{ $doc->orgname ?? '' }}
                        {{ trim(($doc->secondname ?? '') . ' ' . ($doc->name ?? '') . ' ' . ($doc->name2 ?? '')) }}
                        @if($doc->phone)<br><small>{{ $doc->phone }}</small>@endif
                    </td>
                    <td>{{ $kassasMap[$doc->money ?? ''] ?? ($doc->money ?: '—') }}</td>
                    <td style="font-weight:bold; color:{{ $doc->type === 'PO' ? 'green' : 'red' }};">
                        {{ number_format($doc->summa ?? 0, 2, '.', ' ') }}
                    </td>
                    <td style="font-size:0.9em;">{{ $doc->content ?? '' }}</td>
                    <td style="text-align:center;">{{ $doc->provodka ? '✅' : '' }}</td>
                    <td>
                        <a href="{{ route('money.show', ['id' => $doc->id]) }}" class="btn btn-sm btn-outline-primary">✏</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Пагінація --}}
        @include('partials.navigator', [
            'pos'  => $pos,
            'pos2' => 30,
            'max'  => $total,
            'doc'  => 'PO',
        ])
    @endif

</div>
@endsection
