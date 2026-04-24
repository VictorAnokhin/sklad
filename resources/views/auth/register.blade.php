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
    @if (!empty(config('services.google.client_id')))
    <div style="margin-top:1rem">
        <div style="position:relative;margin:0.2rem 0 0.85rem;text-align:center">
            <div style="position:absolute;left:0;right:0;top:50%;border-top:1px solid rgba(255,255,255,0.12);transform:translateY(-50%)"></div>
            <span style="position:relative;display:inline-block;padding:0 0.7rem;background:rgba(14,16,28,0.92);color:rgba(255,255,255,0.58);font-size:0.84rem">або</span>
        </div>
        <div id="google-register-button" style="display:flex;justify-content:center;min-height:44px"></div>
        <form id="google-register-form" action="{{ route('login.google') }}" method="post" style="display:none">
            @csrf
            <input type="hidden" name="credential" id="google-register-credential">
        </form>
    </div>
    @endif
    <div style="text-align:center;margin-top:1rem;color:#aeb6d3;font-size:.95rem">
        Для нового клієнта буде автоматично створено наступне значення <strong>firma</strong>.
    </div>
    <div style="text-align:center;margin-top:1.5rem">
        <a href="{{ route('login') }}">Вже маєте акаунт? Увійти</a>
    </div>
</div>
@endsection

@push('scripts')
@if (!empty(config('services.google.client_id')))
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
  window.handleGoogleRegister = function (response) {
    const credentialField = document.getElementById('google-register-credential');
    const googleForm = document.getElementById('google-register-form');

    if (!credentialField || !googleForm || !response || !response.credential) {
      return;
    }

    credentialField.value = response.credential;
    googleForm.submit();
  };

  window.addEventListener('load', function () {
    const buttonTarget = document.getElementById('google-register-button');

    if (!buttonTarget || !window.google || !window.google.accounts || !window.google.accounts.id) {
      return;
    }

    window.google.accounts.id.initialize({
      client_id: @json(config('services.google.client_id')),
      callback: window.handleGoogleRegister,
      ux_mode: 'popup'
    });

    window.google.accounts.id.renderButton(buttonTarget, {
      theme: 'outline',
      size: 'large',
      type: 'standard',
      text: 'signup_with',
      shape: 'pill',
      width: buttonTarget.offsetWidth > 0 ? buttonTarget.offsetWidth : 320
    });
  });
</script>
@endif
@endpush
