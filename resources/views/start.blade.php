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
      @if(!empty($authFid))
      <input type="hidden" name="fid" value="{{ $authFid }}">
      @endif

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

    @if(!empty($googleClientId))
    <div class="login-social">
      <div class="login-divider"><span>або</span></div>
      <div id="google-signin-button" class="google-signin-slot"></div>
      <form id="google-login-form" action="{{ route('login.google') }}" method="post" style="display:none">
        @csrf
        @if(!empty($authFid))
        <input type="hidden" name="fid" value="{{ $authFid }}">
        @endif
        <input type="hidden" name="credential" id="google-login-credential">
      </form>
    </div>
    @endif

    <div class="login-secondary">
      <form action="{{ route('password.forgot') }}" method="post" class="login-recovery-form">
        @csrf
        @if(!empty($authFid))
        <input type="hidden" name="fid" value="{{ $authFid }}">
        @endif
        <div class="login-secondary-head">
          <strong>Забули пароль?</strong>
          <span>Введіть email і ми надішлемо новий пароль.</span>
        </div>
        @error('recovery_email')
        <div class="alert alert-error" style="margin-bottom:0.7rem">{{ $message }}</div>
        @enderror
        @if(session('recovery_warning'))
        <div class="alert alert-warning" style="margin-bottom:0.7rem">{{ session('recovery_warning') }}</div>
        @endif
        @if(session('temporary_password'))
        <div class="alert alert-success" style="margin-bottom:0.7rem">
          <strong>Тимчасовий пароль:</strong> <code>{{ session('temporary_password') }}</code>
        </div>
        @endif
        <div class="login-recovery-row">
          <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email" required>
          <button type="submit">Відновити</button>
        </div>
      </form>

      <div class="login-register-row">
        <span>Ще немає акаунта?</span>
        <a href="{{ !empty($authFid) ? route('register', ['fid' => $authFid]) : route('register') }}">Зареєструватися</a>
      </div>
    </div>

  </div>
</div>
@endsection

@push('scripts')
@if(!empty($googleClientId))
<script src="https://accounts.google.com/gsi/client" async defer></script>
@endif
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

  @if(!empty($googleClientId))
  window.handleGoogleLogin = function (response) {
    const credentialField = document.getElementById('google-login-credential');
    const googleForm = document.getElementById('google-login-form');

    if (!credentialField || !googleForm || !response || !response.credential) {
      return;
    }

    credentialField.value = response.credential;
    googleForm.submit();
  };

  window.addEventListener('load', function () {
    const buttonTarget = document.getElementById('google-signin-button');

    if (!buttonTarget || !window.google || !window.google.accounts || !window.google.accounts.id) {
      return;
    }

    window.google.accounts.id.initialize({
      client_id: @json($googleClientId),
      callback: window.handleGoogleLogin,
      ux_mode: 'popup'
    });

    window.google.accounts.id.renderButton(buttonTarget, {
      theme: 'outline',
      size: 'large',
      type: 'standard',
      text: 'signin_with',
      shape: 'pill',
      width: buttonTarget.offsetWidth > 0 ? buttonTarget.offsetWidth : 320
    });
  });
  @endif
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

  .login-social {
    margin-top: 0.95rem;
  }

  .login-divider {
    position: relative;
    margin: 0.2rem 0 0.85rem;
    text-align: center;
  }

  .login-divider::before {
    content: "";
    position: absolute;
    inset: 50% 0 auto;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    transform: translateY(-50%);
  }

  .login-divider span {
    position: relative;
    display: inline-block;
    padding: 0 0.7rem;
    background: rgba(14, 16, 28, 0.92);
    color: rgba(255, 255, 255, 0.58);
    font-size: 0.84rem;
  }

  .google-signin-slot {
    display: flex;
    justify-content: center;
    min-height: 44px;
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
