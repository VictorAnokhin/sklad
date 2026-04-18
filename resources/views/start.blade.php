@extends('home')

@section('title')
Вхід в систему
@endsection

@section('content')
<div class="login-shell">
  <div class="glass-card login-card">
    @if(session('success'))
    <div class="alert alert-success" style="margin-bottom:1rem">{{ session('success') }}</div>
    @endif

    <form action="{{ route('login.post') }}" method="post" class="login-form">
      @csrf

      @error('email')
      <div class="alert alert-error" style="margin-bottom:1rem">{{ $message }}</div>
      @enderror
      @error('login')
      <div class="alert alert-error" style="margin-bottom:1rem">{{ $message }}</div>
      @enderror

      <div class="login-field">
        <label for="email">Email</label>
        <input type="email" name="email" value="{{ old('email', old('login')) }}" placeholder="you@example.com" required
          autocomplete="username">
      </div>

      <div class="login-field">
        <label for="pass">Пароль</label>
        <input type="password" name="pass" placeholder="Пароль" autocomplete="current-password" required>
      </div>

      <div>
        <button type="submit" style="width:100%">Увійти</button>
      </div>
    </form>

    <div class="login-secondary">
      <form action="{{ route('password.forgot') }}" method="post" class="login-recovery-form">
        @csrf
        <div class="login-secondary-head">
          <strong>Забули пароль?</strong>
          <span>Введіть email і ми надішлемо новий пароль.</span>
        </div>
        <div class="login-recovery-row">
          <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required>
          <button type="submit">Відновити</button>
        </div>
      </form>

      <div class="login-register-row">
        <span>Ще немає акаунта?</span>
        <a href="{{ route('register') }}">Зареєструватися</a>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
  const btnMenu = document.getElementById('btn_login');
  const Menu = document.getElementById('menu_content_login');

  if (btnMenu && Menu) {
    btnMenu.addEventListener('click', function () {
      if (Menu.style.display == 'block')
        Menu.style.display = 'none';
      else
        Menu.style.display = 'block';
    });
  }
</script>
<style>
  .login-shell {
    max-width: 420px;
    margin: 0 auto;
  }

  .login-card {
    padding: 1.35rem;
  }

  .login-form {
    display: grid;
    gap: 0.85rem;
  }

  .login-field {
    display: grid;
    gap: 0.35rem;
  }

  .login-field label {
    font-size: 0.92rem;
    color: rgba(255, 255, 255, 0.86);
  }

  .login-field input,
  .login-recovery-row input {
    min-height: 42px;
  }

  .login-secondary {
    margin-top: 0.95rem;
    padding-top: 0.95rem;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
  }

  .login-secondary-head {
    display: grid;
    gap: 0.2rem;
    margin-bottom: 0.7rem;
  }

  .login-secondary-head strong {
    font-size: 0.95rem;
  }

  .login-secondary-head span {
    color: rgba(255, 255, 255, 0.64);
    font-size: 0.86rem;
  }

  .login-recovery-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.55rem;
    align-items: center;
  }

  .login-recovery-row button {
    white-space: nowrap;
    min-height: 42px;
  }

  .login-register-row {
    margin-top: 0.85rem;
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: center;
    font-size: 0.92rem;
  }

  @media (max-width: 520px) {
    .login-shell {
      max-width: 100%;
    }

    .login-card {
      padding: 1rem;
    }

    .login-recovery-row {
      grid-template-columns: 1fr;
    }

    .login-register-row {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>
@endpush
