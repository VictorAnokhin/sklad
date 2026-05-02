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

    <div class="login-grid">
      <form action="{{ route('login.post') }}" method="post" class="login-form login-panel">
        @csrf
        @if(!empty($authFid))
        <input type="hidden" name="fid" value="{{ $authFid }}">
        @endif

        <h2 class="login-panel-title">Авторизация по email</h2>

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

        <div class="login-secondary">
          <div class="login-register-row">
            <span>Ще немає акаунта?</span>
            <a href="{{ !empty($authFid) ? route('register', ['fid' => $authFid]) : route('register') }}">Зареєструватися</a>
          </div>
        </div>
      </form>

      <form class="login-form login-panel" id="phone-login-form">
        @csrf

        <h2 class="login-panel-title">Авторизация по телефону</h2>

        <div class="login-field">
          <label for="phone">Телефон</label>
          <input
            type="tel"
            id="phone"
            name="phone"
            value="+38"
            placeholder="+38 (0XX) XXX-XX-XX"
            autocomplete="tel"
            maxlength="19"
          >
        </div>

        <div class="login-phone-row">
          <button type="button" id="phone-send-code" class="button-secondary">Отримати SMS-код</button>
        </div>

        <div class="login-field">
          <label for="phone_code">Код з SMS</label>
          <input
            type="text"
            id="phone_code"
            name="phone_code"
            placeholder="6 цифр"
            inputmode="numeric"
            autocomplete="one-time-code"
            maxlength="6"
          >
        </div>

        <div class="login-phone-hint" id="phone-auth-hint">Отримайте SMS-код і підтвердіть номер.</div>
        <div class="alert alert-error" id="phone-auth-error" style="display:none"></div>
        <div class="alert alert-success" id="phone-auth-success" style="display:none"></div>

        <div>
          <button type="submit" id="phone-login-submit" style="width:100%">Увійти по телефону</button>
        </div>
      </form>
    </div>

    <div class="login-social-grid">
      @if(!empty($googleClientId))
      <div class="login-social-card">
        <h2 class="login-panel-title">Вход через Google</h2>
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

      <div class="login-social-card">
        <h2 class="login-panel-title">Вход через Web3 кошелек</h2>
        <button type="button" id="web3-login-btn" class="button-secondary web3-login-btn">Подключить Web3</button>
        <div class="login-phone-hint" id="web3-auth-hint">MetaMask или другой совместимый EVM-кошелек.</div>
        <div class="alert alert-error" id="web3-auth-error" style="display:none"></div>
        <div class="alert alert-success" id="web3-auth-success" style="display:none"></div>
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
  const authFid = @json($authFid ?? '');
  const dashboardUrl = @json(route('dashboard'));
  const csrfToken = @json(csrf_token());
  const btnMenu = document.getElementById('btn_login');
  const Menu = document.getElementById('menu_content_login');
  const phoneInput = document.getElementById('phone');
  const phoneCodeInput = document.getElementById('phone_code');
  const phoneLoginForm = document.getElementById('phone-login-form');
  const phoneSendCodeButton = document.getElementById('phone-send-code');
  const phoneLoginSubmitButton = document.getElementById('phone-login-submit');
  const phoneAuthHint = document.getElementById('phone-auth-hint');
  const phoneAuthError = document.getElementById('phone-auth-error');
  const phoneAuthSuccess = document.getElementById('phone-auth-success');
  const web3LoginButton = document.getElementById('web3-login-btn');
  const web3AuthError = document.getElementById('web3-auth-error');
  const web3AuthSuccess = document.getElementById('web3-auth-success');

  function setPhoneMessage(target, message) {
    if (!target) {
      return;
    }

    target.textContent = message;
    target.style.display = message ? 'block' : 'none';
  }

  function clearPhoneMessages() {
    setPhoneMessage(phoneAuthError, '');
    setPhoneMessage(phoneAuthSuccess, '');
  }

  function clearWeb3Messages() {
    setPhoneMessage(web3AuthError, '');
    setPhoneMessage(web3AuthSuccess, '');
  }

  function formatPhone(value) {
    let digits = value.replace(/\D/g, '');

    if (digits.startsWith('380') && digits.length > 3) {
      digits = digits.slice(0, 12);
    } else if (digits.startsWith('0') && digits.length > 0) {
      digits = `38${digits}`.slice(0, 12);
    } else if (digits.startsWith('38') && digits.length > 2) {
      digits = digits.slice(0, 12);
    } else if (digits.length === 0) {
      digits = '38';
    } else {
      digits = `38${digits}`.slice(0, 12);
    }

    const local = digits.slice(2);
    let formatted = '+38';

    if (local.length > 0) {
      formatted += ` (${local.slice(0, 3)}`;
      if (local.length >= 3) formatted += ')';
      if (local.length > 3) formatted += ` ${local.slice(3, 6)}`;
      if (local.length > 6) formatted += `-${local.slice(6, 8)}`;
      if (local.length > 8) formatted += `-${local.slice(8, 10)}`;
    }

    return formatted;
  }

  function normalizePhone(value) {
    const digits = value.replace(/\D/g, '');

    if (digits.startsWith('38') && digits.length === 12) {
      return `+${digits}`;
    }

    const padded = digits.startsWith('38') ? digits.slice(0, 12) : `38${digits}`.slice(0, 12);
    return `+${padded}`;
  }

  function isValidPhone(value) {
    return /^\+380\d{9}$/.test(normalizePhone(value));
  }

  async function phoneAuthRequest(url, payload) {
    const requestUrl = authFid ? `${url}?fid=${encodeURIComponent(authFid)}` : url;
    const response = await fetch(requestUrl, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(typeof data.message === 'string' ? data.message : `Request failed: ${response.status}`);
    }

    return data;
  }

  async function postJson(url, payload) {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      credentials: 'same-origin',
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(typeof data.message === 'string' ? data.message : `Request failed: ${response.status}`);
    }

    return data;
  }

  if (btnMenu && Menu) {
    btnMenu.addEventListener('click', function () {
      if (Menu.style.display == 'block')
        Menu.style.display = 'none';
      else
        Menu.style.display = 'block';
    });
  }

  if (phoneInput) {
    phoneInput.addEventListener('input', function (event) {
      event.target.value = formatPhone(event.target.value);
    });
  }

  if (phoneCodeInput) {
    phoneCodeInput.addEventListener('input', function (event) {
      event.target.value = event.target.value.replace(/\D/g, '').slice(0, 6);
    });
  }

  if (phoneSendCodeButton) {
    phoneSendCodeButton.addEventListener('click', async function () {
      clearPhoneMessages();

      if (!phoneInput || !isValidPhone(phoneInput.value)) {
        setPhoneMessage(phoneAuthError, 'Введіть коректний номер телефону у форматі +380XXXXXXXXX');
        return;
      }

      phoneSendCodeButton.disabled = true;
      phoneSendCodeButton.textContent = 'Надсилаємо код...';

      try {
        const data = await phoneAuthRequest('/auth/phone/send-code', {
          phone: normalizePhone(phoneInput.value),
        });

        setPhoneMessage(phoneAuthSuccess, typeof data.message === 'string' ? data.message : 'SMS-код відправлено.');
      } catch (error) {
        setPhoneMessage(phoneAuthError, error instanceof Error ? error.message : 'Не вдалося відправити SMS-код.');
      } finally {
        phoneSendCodeButton.disabled = false;
        phoneSendCodeButton.textContent = 'Отримати SMS-код';
      }
    });
  }

  if (phoneLoginForm) {
    phoneLoginForm.addEventListener('submit', async function (event) {
      event.preventDefault();
      clearPhoneMessages();

      if (!phoneInput || !phoneCodeInput || !isValidPhone(phoneInput.value)) {
        setPhoneMessage(phoneAuthError, 'Введіть коректний номер телефону у форматі +380XXXXXXXXX');
        return;
      }

      if (phoneCodeInput.value.trim().length !== 6) {
        setPhoneMessage(phoneAuthError, 'Введіть 6-значний код з SMS.');
        return;
      }

      phoneLoginSubmitButton.disabled = true;
      phoneLoginSubmitButton.textContent = 'Підтверджуємо...';

      try {
        const data = await phoneAuthRequest('/auth/phone/verify', {
          phone: normalizePhone(phoneInput.value),
          code: phoneCodeInput.value.trim(),
        });

        setPhoneMessage(phoneAuthSuccess, typeof data.message === 'string' ? data.message : 'Телефон підтверджено.');
        window.location.href = dashboardUrl;
      } catch (error) {
        setPhoneMessage(phoneAuthError, error instanceof Error ? error.message : 'Не вдалося підтвердити код.');
      } finally {
        phoneLoginSubmitButton.disabled = false;
        phoneLoginSubmitButton.textContent = 'Увійти по телефону';
      }
    });
  }

  fetch(authFid ? `/api/auth/config?fid=${encodeURIComponent(authFid)}` : '/api/auth/config', {
    headers: {
      'Accept': 'application/json',
    },
    credentials: 'same-origin',
  })
    .then(function (response) {
      return response.json().then(function (data) {
        return { ok: response.ok, data: data };
      });
    })
    .then(function (result) {
      if (!result.ok || !result.data || result.data.phoneAuthEnabled !== true) {
        if (phoneSendCodeButton) {
          phoneSendCodeButton.disabled = true;
        }
        if (phoneLoginSubmitButton) {
          phoneLoginSubmitButton.disabled = true;
        }
        if (phoneAuthHint) {
          phoneAuthHint.textContent = 'Поле телефону доступне, але SMS-авторизація зараз не налаштована на сервері.';
        }
      }
    })
    .catch(function () {
      if (phoneSendCodeButton) {
        phoneSendCodeButton.disabled = true;
      }
      if (phoneLoginSubmitButton) {
        phoneLoginSubmitButton.disabled = true;
      }
      if (phoneAuthHint) {
        phoneAuthHint.textContent = 'Не вдалося перевірити доступність SMS-авторизації.';
      }
    });

  if (web3LoginButton) {
    web3LoginButton.addEventListener('click', async function () {
      clearWeb3Messages();

      if (!window.ethereum || !window.ethereum.request) {
        setPhoneMessage(web3AuthError, 'В этом браузере не найден Web3-кошелек.');
        return;
      }

      web3LoginButton.disabled = true;
      web3LoginButton.textContent = 'Подключаем кошелек...';

      try {
        const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
        const address = Array.isArray(accounts) ? accounts[0] : null;

        if (!address) {
          throw new Error('Кошелек не вернул адрес.');
        }

        const challenge = await postJson('{{ route('web3.challenge') }}', {
          address: address,
          wallet_type: 'evm',
        });

        web3LoginButton.textContent = 'Подтвердите подпись...';

        const signature = await window.ethereum.request({
          method: 'personal_sign',
          params: [challenge.message, address],
        });

        await postJson('{{ route('web3.login') }}', {
          address: address,
          signature: signature,
          wallet_type: 'evm',
        });

        setPhoneMessage(web3AuthSuccess, 'Вхід через Web3 виконано.');
        window.location.href = dashboardUrl;
      } catch (error) {
        setPhoneMessage(web3AuthError, error instanceof Error ? error.message : 'Не вдалося виконати Web3-вхід.');
      } finally {
        web3LoginButton.disabled = false;
        web3LoginButton.textContent = 'Подключить Web3';
      }
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
    max-width: 980px;
    margin: 0 auto;
  }

  .login-card {
    padding: 1.35rem;
  }

  .login-grid,
  .login-social-grid {
    display: grid;
    gap: 1rem;
  }

  .login-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .login-social-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-top: 1rem;
  }

  .login-form {
    display: grid;
    gap: 0.85rem;
  }

  .login-panel,
  .login-social-card {
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

  .login-phone-row {
    display: flex;
  }

  .login-phone-hint {
    color: rgba(255, 255, 255, 0.58);
    font-size: 0.84rem;
  }

  .button-secondary {
    width: 100%;
    background: rgba(255, 255, 255, 0.06);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.14);
  }

  .button-secondary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
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

  .web3-login-btn {
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

  @media (max-width: 860px) {
    .login-grid,
    .login-social-grid {
      grid-template-columns: 1fr;
    }
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
