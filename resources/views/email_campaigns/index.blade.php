@extends('home')

@section('title', 'Email рассылка')

@section('content')
<div class="container py-4 email-campaign-page">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Email рассылка</h1>
            <div class="text-muted">Отправка писем через внешний провайдер API.</div>
        </div>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-warning">Настройки</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-card h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Провайдер</h2>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted">Статус</span>
                        <span class="badge {{ ($providerSettings['configured'] ?? false) ? 'bg-success' : 'bg-secondary' }}">
                            {{ ($providerSettings['configured'] ?? false) ? 'Активно' : 'Не настроено' }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted">Сервис</span>
                        <strong>{{ strtoupper($providerSettings['provider'] ?? 'resend') }}</strong>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted">Отправитель</span>
                        <span class="text-end">{{ $providerSettings['from_email'] ?: 'не задан' }}</span>
                    </div>
                    <a href="{{ route('settings.index') }}" class="btn btn-sm btn-outline-info mt-3">Изменить настройки</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <form action="{{ route('email-campaigns.send') }}" method="post" class="glass-card">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="segment">Получатели</label>
                            <select class="form-select" id="segment" name="segment" required>
                                <option value="test" @selected(old('segment') === 'test')>Тестовое письмо</option>
                                <option value="inactive" @selected(old('segment') === 'inactive')>Неактивные 30+ дней: {{ $inactiveCount }}</option>
                                <option value="clients" @selected(old('segment') === 'clients')>Клиенты: {{ $recipientCount }}</option>
                                <option value="subscribers" @selected(old('segment') === 'subscribers')>Активные подписки: {{ $subscribersCount }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="test_email">Email для теста</label>
                            <input type="email" class="form-control" id="test_email" name="test_email" value="{{ old('test_email') }}" maxlength="255" autocomplete="email">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="subject">Тема</label>
                            <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject') }}" maxlength="180" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="body">Текст письма</label>
                            <textarea class="form-control" id="body" name="body" rows="12" maxlength="8000" required>{{ old('body') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-warning" @disabled(!($providerSettings['configured'] ?? false))>Отправить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .email-campaign-page .glass-card {
        background: rgba(10, 14, 20, 0.88);
        border: 1px solid rgba(245, 179, 1, 0.28);
        border-radius: 8px;
        color: #f8fafc;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.28);
    }
    .email-campaign-page .card-body,
    .email-campaign-page .card-footer {
        padding: 1.25rem;
    }
    .email-campaign-page .card-footer {
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.02);
    }
</style>
@endsection
