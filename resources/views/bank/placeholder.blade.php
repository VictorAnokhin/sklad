@extends('home')

@section('title')
{{ $title }}
@endsection

@section('content')
<div class="bank-page">
    @include('bank.partials.nav')

    <section class="bank-panel bank-placeholder">
        <div class="bank-label">Банк / {{ $project->name }}</div>
        <h2>{{ $title }}</h2>
        <p>{{ $description }}</p>
        <div class="bank-placeholder-list">
            <div>Статус: страница добавлена в банковский раздел.</div>
            <div>Следующий этап: подключить таблицы операций, проводок и статусов обработки.</div>
        </div>
    </section>
</div>

@include('bank.partials.styles')
@endsection
