@extends('home')

@section('title')
Кошелек
@endsection

@section('content')
<div class="wallet-page" style="max-width: 900px; margin: 0 auto; padding-bottom: 3rem;">
    <!-- Benefits Section -->
    <div class="glass-card animated-card" style="padding: 2.5rem; margin-bottom: 2rem; border-radius: 16px;">
        <h2 class="mb-4" style="color: #fbbf24; font-weight: 700;">Web3 Интеграция</h2>
        <p style="font-size: 1.1rem; color: rgba(255,255,255,0.85); line-height: 1.6; margin-bottom: 2rem;">
            Подключение Web3-кошелька (EVM-совместимого, например MetaMask) открывает доступ к передовым инвестиционным инструментам платформы AV8 Capital.
        </p>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="hover-feature" style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; height: 100%;">
                    <h4 style="color: #fff; font-size: 1.1rem; margin-bottom: 0.75rem;">Безопасный вход</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; margin-bottom: 0; line-height: 1.5;">Авторизация в системе в один клик без необходимости вводить логин и пароль. Ваш криптокошелек используется как надежный криптографический ключ.</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="hover-feature" style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; height: 100%;">
                    <h4 style="color: #fff; font-size: 1.1rem; margin-bottom: 0.75rem;">Прямые инвестиции</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; margin-bottom: 0; line-height: 1.5;">Абсолютно прозрачное участие в инвестиционных пулах и крипто-проектах с гарантией зачисления дивидендов смарт-контрактами.</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="hover-feature" style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; height: 100%;">
                    <h4 style="color: #fff; font-size: 1.1rem; margin-bottom: 0.75rem;">Прозрачные начисления</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; margin-bottom: 0; line-height: 1.5;">Все финансовые операции, связанные с цифровыми активами, проходят напрямую между вашим кошельком и инфраструктурой платформы.</p>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="hover-feature" style="background: rgba(255,255,255,0.03); padding: 1.5rem; border-radius: 12px; height: 100%;">
                    <h4 style="color: #fff; font-size: 1.1rem; margin-bottom: 0.75rem;">Web3 Экосистема</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem; margin-bottom: 0; line-height: 1.5;">Готовность к будущим децентрализованным функциям, токенизации активов и инструментам децентрализованных финансов (DeFi).</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Web3 Auth Section -->
    <div class="glass-card web3-login-box" style="margin: 0 auto; max-width: 450px;">
        <div class="web3-login-copy text-center">
            <p class="web3-login-eyebrow">WEB3 ACCESS</p>
            <h2 class="web3-login-title" style="color: #fff; font-weight: 600;">Вход через кошелек</h2>
            <p class="web3-login-text" style="color: rgba(255,255,255,0.7); margin-bottom: 1.5rem;">Подключите MetaMask или другой EVM-гаманець для авторизации в системе.</p>
        </div>
        <div class="web3-login-actions">
            <button type="button" id="web3-connect-btn" class="web3-connect-btn">Подключить Web3</button>
            <p id="web3-wallet-address" class="web3-wallet-address text-center" style="display:none; margin-top: 1rem; color: #fbbf24;"></p>
            <p id="web3-status" class="web3-status text-center" style="display:none; margin-top: 0.5rem;"></p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
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
    if (!web3WalletAddress) return;
    if (!address) {
      web3WalletAddress.style.display = 'none';
      web3WalletAddress.textContent = '';
      return;
    }
    web3WalletAddress.style.display = 'block';
    web3WalletAddress.textContent = 'Адрес: ' + shortenAddress(address);
  }

  function setWeb3Status(message, isError = false) {
    if (!web3Status) return;
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
  .animated-card {
      animation: fadeInScale 0.7s ease-out forwards;
  }
  .hover-feature {
      transition: all 0.3s ease;
      border: 1px solid transparent;
  }
  .hover-feature:hover {
      transform: translateY(-5px);
      background: rgba(255,255,255,0.04) !important;
      border-color: rgba(251, 191, 36, 0.3) !important;
      box-shadow: 0 10px 20px rgba(0,0,0,0.2);
  }
  @keyframes fadeInScale {
      from { opacity: 0; transform: scale(0.97) translateY(10px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
  }

  .web3-login-box {
    padding: 2.5rem 1.5rem;
    border: 1px solid rgba(251, 191, 36, 0.22);
    border-radius: 1rem;
    background:
      radial-gradient(circle at top right, rgba(251, 191, 36, 0.18), transparent 40%),
      linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.03));
  }

  .web3-login-eyebrow {
    margin-bottom: 0.5rem;
    color: #fbbf24;
    font-size: 0.85rem;
    font-weight: 700;
    letter-spacing: 0.2em;
  }

  .web3-login-title {
    margin-bottom: 0.5rem;
    font-size: 1.4rem;
  }

  .web3-login-text {
    margin-bottom: 1.5rem;
    color: rgba(255, 255, 255, 0.72);
  }

  .web3-connect-btn {
    width: 100%;
    min-height: 48px;
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: #111;
    font-weight: 600;
    font-size: 1.05rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 10px 24px rgba(245, 158, 11, 0.22);
  }

  .web3-connect-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(245, 158, 11, 0.3);
  }

  .web3-connect-btn:disabled {
    opacity: 0.75;
    cursor: not-allowed;
    transform: none;
  }

  .web3-wallet-address,
  .web3-status {
    margin-top: 0.75rem;
    margin-bottom: 0;
    font-size: 0.95rem;
  }

  .web3-wallet-address {
    color: rgba(255, 255, 255, 0.82);
    font-family: monospace;
  }
</style>
@endpush
