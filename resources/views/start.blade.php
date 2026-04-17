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

    <div class="web3-login-box">
      <div class="web3-login-copy">
        <p class="web3-login-eyebrow">WEB3 ACCESS</p>
        <h2 class="web3-login-title">Підключення гаманця</h2>
        <p class="web3-login-text">Увійдіть через MetaMask або інший EVM-сумісний гаманець, якщо адреса вже прив'язана до вашого акаунта.</p>
      </div>
      <div class="web3-login-actions">
        <button type="button" id="web3-connect-btn" class="web3-connect-btn">Подключить Web3</button>
        <p id="web3-wallet-address" class="web3-wallet-address" style="display:none;"></p>
        <p id="web3-status" class="web3-status" style="display:none;"></p>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const btnMenu = document.getElementById('btn_login');
  const Menu = document.getElementById('menu_content_login');
  const web3Button = document.getElementById('web3-connect-btn');
  const web3Status = document.getElementById('web3-status');
  const web3WalletAddress = document.getElementById('web3-wallet-address');

  function shortenAddress(address) {
    if (!address || address.length < 10) {
      return address || '';
    }

    return address.slice(0, 6) + '...' + address.slice(-4);
  }

  function setWalletAddress(address) {
    if (!web3WalletAddress) {
      return;
    }

    if (!address) {
      web3WalletAddress.style.display = 'none';
      web3WalletAddress.textContent = '';
      return;
    }

    web3WalletAddress.style.display = 'block';
    web3WalletAddress.textContent = 'Адрес: ' + shortenAddress(address);
  }

  function setWeb3Status(message, isError = false) {
    if (!web3Status) {
      return;
    }

    web3Status.style.display = 'block';
    web3Status.textContent = message;
    web3Status.style.color = isError ? '#ff8e8e' : '#b9fbc0';
  }

  async function postJson(url, payload) {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
      },
      body: JSON.stringify(payload),
      credentials: 'same-origin',
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      throw new Error(data.message || 'Web3 request failed.');
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

  if (web3Button) {
    web3Button.addEventListener('click', async function () {
      if (!window.ethereum) {
        setWeb3Status('Ethereum-кошелек не найден. Откройте страницу в браузере с MetaMask.', true);
        return;
      }

      web3Button.disabled = true;
      web3Button.textContent = 'Подключаем...';
      setWeb3Status('Подключаем кошелек...');

      try {
        const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
        const address = accounts && accounts[0];

        if (!address) {
          throw new Error('Кошелек не вернул адрес.');
        }

        setWalletAddress(address);
        const challenge = await postJson('{{ route('web3.challenge') }}', { address });
        const signature = await window.ethereum.request({
          method: 'personal_sign',
          params: [challenge.message, address],
        });

        await postJson('{{ route('web3.login') }}', {
          address,
          signature,
        });

        setWeb3Status('Кошелек подключен. Выполняется вход...');
        window.location.href = '{{ route('document.index') }}';
      } catch (error) {
        setWeb3Status(error.message || 'Не удалось выполнить вход через Web3.', true);
      } finally {
        web3Button.disabled = false;
        web3Button.textContent = 'Подключить Web3';
      }
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

  .web3-login-box {
    margin-top: 1rem;
    padding: 1rem;
    border: 1px solid rgba(251, 191, 36, 0.22);
    border-radius: 1rem;
    background:
      radial-gradient(circle at top right, rgba(251, 191, 36, 0.18), transparent 40%),
      linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.03));
  }

  .web3-login-eyebrow {
    margin-bottom: 0.35rem;
    color: #fbbf24;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.16em;
  }

  .web3-login-title {
    margin-bottom: 0.35rem;
    font-size: 1.2rem;
  }

  .web3-login-text {
    margin-bottom: 1rem;
    color: rgba(255, 255, 255, 0.72);
  }

  .web3-connect-btn {
    width: 100%;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #111;
    font-weight: 600;
    box-shadow: 0 10px 24px rgba(245, 158, 11, 0.22);
  }

  .web3-connect-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(245, 158, 11, 0.3);
  }

  .web3-connect-btn:disabled {
    opacity: 0.75;
  }

  .web3-wallet-address,
  .web3-status {
    margin-top: 0.75rem;
    margin-bottom: 0;
    font-size: 0.95rem;
  }

  .web3-wallet-address {
    color: rgba(255, 255, 255, 0.82);
    font-family: "Geologica", sans-serif;
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
