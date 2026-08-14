@extends('home')

@section('title', $member ? 'Редактирование сотрудника' : 'Добавление сотрудника')

@section('content')
<div class="container mt-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="glass-card team-form-card">
        @include('team._form', ['inModal' => false])
    </div>
</div>

<style>
    .team-form-card { max-width: 980px; }
    .team-photo-preview { width: 100%; max-width: 220px; min-height: 140px; border: 1px solid rgba(255,255,255,.12); border-radius: 16px; background: rgba(255,255,255,.03); display: flex; align-items: center; justify-content: center; overflow: hidden; color: var(--muted-foreground); text-align: center; }
    .team-photo-preview img { width: 100%; height: 140px; object-fit: cover; }
    .team-company-role-list { display: grid; gap: .75rem; }
    .team-company-role-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(180px, 280px); gap: .75rem; align-items: center; }
    .team-company-role-row__company { margin-bottom: 0; }
    @media (max-width: 575.98px) {
        .team-company-role-row { grid-template-columns: 1fr; }
    }
</style>
@endsection
