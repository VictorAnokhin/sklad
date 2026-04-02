@extends('home')

@section('title', 'Редагування налаштування')

@section('content')
<div class="ttable" style="padding: 20px; max-width: 700px; margin: 0 auto; background: #fff; border-radius: 8px;">
    <h3>@if(isset($setting) && $setting->id) Редагувати @else Створити @endif Налаштування</h3>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('settings.save') }}" method="post">
        @csrf
        <input type="hidden" name="run" value="save_conf">
        <input type="hidden" name="id" value="{{ $setting->id ?? '' }}">

        <div class="mb-3">
            <label>Тип</label>
            <input type="text" name="type" class="form-control" value="{{ $setting->type ?? '' }}" required>
        </div>

        <div class="mb-3">
            <label>Назва</label>
            <input type="text" name="name" class="form-control" value="{{ $setting->name ?? '' }}" required>
        </div>

        <div class="mb-3">
            <label>Колір</label>
            <input type="color" name="color" class="form-control form-control-color"
                value="{{ $setting->color ?? '#000000' }}" title="Оберіть колір">
        </div>

        <div style="display:flex; justify-content: space-between; align-items: center; margin-top:20px;">
            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">💾 Зберегти</button>
                <a href="{{ route('settings.index') }}" class="btn btn-secondary">← Назад</a>
            </div>
        </div>
    </form>

    @if(isset($setting) && $setting->id)
    <form action="{{ route('settings.save') }}" method="post" style="margin-top: -38px; text-align: right;"
        onsubmit="return confirm('Дійсно видалити це налаштування?');">
        @csrf
        <input type="hidden" name="run" value="del_conf">
        <input type="hidden" name="id" value="{{ $setting->id }}">
        <button type="submit" class="btn btn-danger">🗑</button>
    </form>
    @endif

</div>
@endsection