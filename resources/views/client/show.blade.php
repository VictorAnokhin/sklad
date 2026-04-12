@extends('home')

@section('title')
{{ $client ? __('client.edit_title') : __('client.create_title') }}
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
                <label class="form-label">{{ __('client.field_organization') }}</label>
                <input type="text" name="orgname" class="form-control" value="{{ $client->orgname ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('client.field_edrpou') }}</label>
                <input type="text" name="kod1" class="form-control" value="{{ $client->kod1 ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('client.field_contact') }}</label>
                <input type="text" name="name2" class="form-control" value="{{ $client->name2 ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_lastname') }}</label>
                <input type="text" name="secondname" class="form-control" value="{{ $client->secondname ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_firstname') }}</label>
                <input type="text" name="name" class="form-control" value="{{ $client->name ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_middlename') }}</label>
                <input type="text" name="fathername" class="form-control" value="{{ $client->fathername ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_login') }}</label>
                <input type="text" name="login" class="form-control" value="{{ $client->login ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_phone') }}</label>
                <input type="text" name="phone" class="form-control" value="{{ $client->phone ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_phone2') }}</label>
                <input type="text" name="phone1" class="form-control" value="{{ $client->phone1 ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_birthday') }}</label>
                <input type="text" name="hbd" class="form-control" value="{{ $client->hbd ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_email') }}</label>
                <input type="email" name="email" class="form-control" value="{{ $client->email ?? '' }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_city') }}</label>
                <input type="text" name="city" class="form-control" value="{{ $client->city ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_region') }}</label>
                <input type="text" name="region" class="form-control" value="{{ $client->region ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_nova_poshta') }}</label>
                <input type="text" name="poshta" class="form-control" value="{{ $client->poshta ?? '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">{{ __('client.field_password') }}</label>
                <input type="password" name="pass" class="form-control" value="" placeholder="{{ $client ? __('client.field_password_hint') : '' }}">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('client.field_status') }}</label>
                <select name="idstatus" class="form-select">
                    @foreach($statuses as $s)
                        <option value="{{ $s->id }}" {{ (string)($client->idstatus ?? $client->ustype ?? '') === (string)$s->id ? 'selected' : '' }}>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('client.field_rating') }}</label>
                <input type="number" name="top" class="form-control" value="{{ $client->top ?? 1 }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('client.field_bonus') }}</label>
                <input type="number" step="0.01" name="bonus" class="form-control" value="{{ $client->bonus ?? 0 }}">
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">💾 {{ __('client.btn_save') }}</button>
            <a href="{{ route('client.index') }}" class="btn btn-secondary">← {{ __('client.btn_back') }}</a>
            @if($client && !empty($client->id))
            <button
                type="submit"
                class="btn btn-danger"
                formaction="{{ route('client.destroy') }}"
                formmethod="POST"
                formnovalidate
                onclick="return confirm('{{ __('client.confirm_delete') }}');"
            >🗑 {{ __('client.btn_delete') }}</button>
            @endif
        </div>
    </form>
</div>
@endsection
