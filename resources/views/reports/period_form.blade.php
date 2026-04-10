@php
    $periodFormAction = $periodFormAction ?? route('reports.index');
    $periodResetUrl = $periodResetUrl ?? route('reports.index');
    $periodResetLabel = $periodResetLabel ?? 'Поточний місяць';
    $periodHiddenFields = $periodHiddenFields ?? [];
@endphp

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="{{ $periodFormAction }}" class="row g-3 align-items-end">
            @foreach($periodHiddenFields as $fieldName => $fieldValue)
            <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
            @endforeach
            <div class="col-md-4">
                <label for="date_from" class="form-label">Період з</label>
                <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label for="date_to" class="form-label">Період по</label>
                <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}" class="form-control">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Показати</button>
                <a href="{{ $periodResetUrl }}" class="btn btn-outline-secondary">{{ $periodResetLabel }}</a>
            </div>
        </form>
    </div>
</div>
