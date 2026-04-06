@extends('home')

@section('title')
Реєстрація нового клієнта
@endsection

@section('content')
<div class="glass-card">
    <form action="{{ route('register.post') }}" method="post">
        @csrf

        @if ($errors->any())
        <div class="alert alert-error" style="margin-bottom:1rem">
            {{ $errors->first() }}
        </div>
        @endif

        <div style="margin-bottom:1rem">
            <label for="name">Ім'я</label>
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Ваше ім'я" required
                autocomplete="name">
        </div>

        <div style="margin-bottom:1rem">
            <label for="surname">Прізвище</label>
            <input type="text" name="surname" value="{{ old('surname') }}" placeholder="Ваше прізвище"
                autocomplete="family-name">
        </div>

        <div style="margin-bottom:1rem">
            <label for="phone">Телефон</label>
            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+380..."
                autocomplete="tel">
        </div>

        <div style="margin-bottom:1rem">
            <label for="email">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required>
        </div>

        @if (\App\Models\User::hasUsersColumn('login'))
        <div style="margin-bottom:1rem">
            <label for="login">Логін</label>
            <input type="text" name="login" value="{{ old('login') }}" placeholder="Логін" autocomplete="username" required>
        </div>
        @endif

        <div style="margin-bottom:1rem">
            <label for="pass">Пароль</label>
            <input type="password" name="pass" placeholder="Пароль" autocomplete="new-password" required>
        </div>

        <div style="margin-bottom:1rem">
            <label for="pass_confirmation">Підтвердження пароля</label>
            <input type="password" name="pass_confirmation" placeholder="Повторіть пароль" autocomplete="new-password" required>
        </div>

        <div>
            <button type="submit" style="width:100%">Зареєструватися</button>
        </div>
    </form>
    <div style="text-align:center;margin-top:1rem;color:#aeb6d3;font-size:.95rem">
        Для нового клієнта буде автоматично створено наступне значення <strong>firma</strong>.
    </div>
    <div style="text-align:center;margin-top:1.5rem">
        <a href="{{ route('login') }}">Вже маєте акаунт? Увійти</a>
    </div>
</div>
@endsection
