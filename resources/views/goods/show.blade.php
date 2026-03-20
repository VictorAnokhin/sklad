@extends('home')

@section('title')
{{ $pnum ? 'Редагування товару' : 'Новий товар' }}
@endsection

@section('content')
<div class="container mt-4">

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('goods.save') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $pnum ?? '0' }}">

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Найменування</label>
                <input type="text" name="name" class="form-control" value="{{ $good->name ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Код</label>
                <input type="text" name="kod" class="form-control" value="{{ $good->kod ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Штрихкод</label>
                <input type="text" name="barcode" class="form-control" value="{{ $good->barcode ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Од. виміру</label>
                <input type="text" name="edizm" class="form-control" value="{{ $good->edizm ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Вага</label>
                <input type="number" step="0.001" name="ves" class="form-control" value="{{ $good->ves ?? 0 }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Об'єм</label>
                <input type="number" step="0.001" name="obem" class="form-control" value="{{ $good->obem ?? 0 }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Ціна</label>
                <input type="number" step="0.01" name="price" class="form-control" value="{{ $good->price ?? 0 }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Ціна 2</label>
                <input type="number" step="0.01" name="price2" class="form-control" value="{{ $good->price2 ?? 0 }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Ціна 3</label>
                <input type="number" step="0.01" name="price3" class="form-control" value="{{ $good->price3 ?? 0 }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Група</label>
                <input type="text" name="group" class="form-control" value="{{ $good->group ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Виробник</label>
                <input type="text" name="proizv" class="form-control" value="{{ $good->proizv ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Артикул</label>
                <input type="text" name="articul" class="form-control" value="{{ $good->articul ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-12">
                <label class="form-label">Опис</label>
                <textarea name="opis" class="form-control" rows="3">{{ $good->opis ?? '' }}</textarea>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">💾 Зберегти</button>
            <a href="{{ route('goods.index') }}" class="btn btn-secondary">← Назад</a>
        </div>
    </form>
</div>
@endsection