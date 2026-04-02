@extends('home')

@section('title', 'Налаштування')

@section('content')
<div class="ttable" style="padding: 16px;">
    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom:20px;">
        <h4>Налаштування компанії</h4>
        <a href="{{ route('settings.show') }}" class="btn btn-success">+ Додати налаштування</a>
    </div>

    {{-- Content placeholder --}}
    <table class="table table-bordered table-sm">
        <thead style="background:#efefef;">
            <tr>
                <th>Тип</th>
                <th>Назва</th>
                <th>Дія</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($conf) && count($conf) > 0)
            @foreach($conf as $c)
            <tr>
                <td>{{ $c->type ?? '' }}</td>
                <td>{{ $c->name ?? '' }}</td>
                <td>
                    <a href="{{ route('settings.show', ['id' => $c->id]) }}"
                        class="btn btn-sm btn-outline-primary">✏</a>
                </td>
            </tr>
            @endforeach
            @else
            <tr>
                <td colspan="3" class="text-center">Немає налаштувань</td>
            </tr>
            @endif
        </tbody>
    </table>

</div>
@endsection