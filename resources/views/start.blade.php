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

    <div class="login-card-head">
      <span class="login-eyebrow">AV8 Capital</span>
      <h1>Вхід в систему</h1>
      <p>Авторизуйтесь через Google або використайте email і пароль.</p>
    </div>

    @if(!empty($googleClientId))
    <div class="login-google-panel">
      <div class="login-google-icon" aria-hidden="true">G</div>
      <div class="login-google-copy">
        <h2>Вхід через Google</h2>
        <p>Швидкий доступ до особистого кабінету.</p>
      </div>
      <div id="google-signin-button" class="google-signin-slot"></div>
      <form id="google-login-form" action="{{ route('login.google') }}" method="post" style="display:none">
        @csrf
        @if(!empty($authFid))
        <input type="hidden" name="fid" value="{{ $authFid }}">
        @endif
        <input type="hidden" name="credential" id="google-login-credential">
      </form>
    </div>

    <div class="login-divider"><span>або</span></div>
    @endif

    <form action="{{ route('login.post') }}" method="post" class="login-form login-panel">
      @csrf
      @if(!empty($authFid))
      <input type="hidden" name="fid" value="{{ $authFid }}">
      @endif

      <h2 class="login-panel-title">Авторизація по email</h2>

      @error('email')
      <div class="alert alert-error" style="margin-bottom:1rem">{{ $message }}</div>
      @enderror
      @error('login')
      <div class="alert alert-error" style="margin-bottom:1rem">{{ $message }}</div>
      @enderror

      <div class="login-field">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', old('login')) }}" placeholder="you@example.com" required autocomplete="username">
      </div>

      <div class="login-field">
        <label for="pass">Пароль</label>
        <input id="pass" type="password" name="pass" placeholder="Пароль" autocomplete="current-password" required>
      </div>

      <div>
        <button type="submit" class="login-submit">Увійти</button>
      </div>

      <div class="login-secondary">
        <div class="login-register-row">
          <span>Ще немає акаунта?</span>
          <a href="https://av8.fund/about">Дізнатися про AV8 Fund</a>
        </div>
      </div>
    </form>
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
      theme: 'filled_blue',
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
    min-height: calc(100vh - 180px);
    display: flex;
    align-items: center;
    justify-content: center;
    max-width: 100%;
    padding: 2rem 1rem;
  }

  .login-card {
    width: min(100%, 520px);
    padding: 1.5rem;
  }

  .login-card-head {
    margin-bottom: 1.25rem;
    text-align: center;
  }

  .login-eyebrow {
    display: inline-flex;
    margin-bottom: 0.65rem;
    border: 1px solid rgba(45, 212, 191, 0.26);
    border-radius: 999px;
    background: rgba(45, 212, 191, 0.1);
    padding: 0.35rem 0.75rem;
    color: #8ff5e5;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
  }

  .login-card-head h1 {
    margin: 0;
    color: #fff;
    font-size: clamp(1.8rem, 4vw, 2.5rem);
    font-weight: 700;
  }

  .login-card-head p {
    margin: 0.65rem auto 0;
    max-width: 28rem;
    color: rgba(255, 255, 255, 0.66);
    font-size: 0.95rem;
    line-height: 1.55;
  }

  .login-google-panel {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 0.85rem;
    align-items: center;
    padding: 1rem;
    border: 1px solid rgba(45, 212, 191, 0.22);
    border-radius: 22px;
    background:
      radial-gradient(circle at top left, rgba(45, 212, 191, 0.18), transparent 42%),
      rgba(255, 255, 255, 0.045);
    box-shadow: 0 18px 42px rgba(0, 0, 0, 0.22);
  }

  .login-google-icon {
    display: grid;
    width: 48px;
    height: 48px;
    place-items: center;
    border-radius: 16px;
    background: #fff;
    color: #1f2937;
    font-size: 1.35rem;
    font-weight: 800;
  }

  .login-google-copy h2 {
    margin: 0;
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
  }

  .login-google-copy p {
    margin: 0.25rem 0 0;
    color: rgba(255, 255, 255, 0.62);
    font-size: 0.84rem;
  }

  .google-signin-slot {
    grid-column: 1 / -1;
    display: flex;
    justify-content: center;
    min-height: 44px;
  }

  .login-divider {
    position: relative;
    margin: 1rem 0;
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

  .login-form {
    display: grid;
    gap: 0.85rem;
  }

  .login-panel {
    padding: 1.1rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.03);
  }

  .login-panel-title {
    margin: 0 0 0.2rem;
    color: #fff;
    font-size: 1.15rem;
    font-weight: 600;
  }

  .login-field {
    display: grid;
    gap: 0.35rem;
  }

  .login-field label {
    font-size: 0.92rem;
    color: rgba(255, 255, 255, 0.86);
  }

  .login-field input {
    min-height: 44px;
  }

  .login-submit {
    width: 100%;
    min-height: 46px;
  }

  .login-secondary {
    margin-top: 0.95rem;
    padding-top: 0.95rem;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
  }

  .login-register-row {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: center;
    font-size: 0.92rem;
  }

  @media (max-width: 520px) {
    .login-shell {
      min-height: auto;
      padding: 1rem 0.75rem;
    }

    .login-card {
      padding: 1rem;
    }

    .login-google-panel {
      grid-template-columns: 1fr;
      text-align: center;
    }

    .login-google-icon {
      margin: 0 auto;
    }

    .login-register-row {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>
@endpush
