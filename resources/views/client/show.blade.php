@extends('home')

@section('title')
{{ $client ? 'Редагування клієнта' : 'Новий клієнт' }}
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

    <form method="POST" action="{{ route('client.save') }}">
        @csrf
        <input type="hidden" name="id" value="{{ $client->id ?? '0' }}">

        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Організація</label>
                <input type="text" name="orgname" class="form-control" value="{{ $client->orgname ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">ЄДРПОУ (kod1)</label>
                <input type="text" name="kod1" class="form-control" value="{{ $client->kod1 ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Контактна особа (name2)</label>
                <input type="text" name="name2" class="form-control" value="{{ $client->name2 ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Прізвище</label>
                <input type="text" name="secondname" class="form-control" value="{{ $client->secondname ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Ім'я</label>
                <input type="text" name="name" class="form-control" value="{{ $client->name ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">По батькові</label>
                <input type="text" name="fathername" class="form-control" value="{{ $client->fathername ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Логін</label>
                <input type="text" name="login" class="form-control" value="{{ $client->login ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Телефон</label>
                <input type="text" name="phone" class="form-control" value="{{ $client->phone ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Телефон 2</label>
                <input type="text" name="phone1" class="form-control" value="{{ $client->phone1 ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">День народження</label>
                <input type="text" name="hbd" class="form-control" value="{{ $client->hbd ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ $client->email ?? '' }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Місто</label>
                <input type="text" name="city" class="form-control" value="{{ $client->city ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Область</label>
                <input type="text" name="region" class="form-control" value="{{ $client->region ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Відділення НП</label>
                <input type="text" name="poshta" class="form-control" value="{{ $client->poshta ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Пароль</label>
                <input type="password" name="pass" class="form-control" value="" placeholder="{{ $client ? 'Залиште порожнім, щоб не змінювати' : '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Статус / роль контрагента</label>
                <select name="idstatus" class="form-select">
                    @foreach($statuses as $s)
                        <option value="{{ $s->id }}" {{ (string)($client->idstatus ?? $client->ustype ?? '') === (string)$s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Рейтинг (Top)</label>
                <input type="number" name="top" class="form-control" value="{{ $client->top ?? 1 }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Бонус</label>
                <input type="number" step="0.01" name="bonus" class="form-control" value="{{ $client->bonus ?? 0 }}">
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">💾 Зберегти</button>
            <a href="{{ route('client.index') }}" class="btn btn-secondary">← Назад</a>
            @if($client && !empty($client->id))
            <button
                type="submit"
                class="btn btn-danger"
                formaction="{{ route('client.destroy') }}"
                formmethod="POST"
                formnovalidate
                onclick="return confirm('Ви впевнені, що хочете видалити цього клієнта?');"
            >🗑 Видалити</button>
            @endif
        </div>
    </form>
</div>
@endsection
