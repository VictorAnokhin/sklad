@extends('home')

@section('title')
Кошелек
@endsection

@section('content')
<div class="wallet-page" style="max-width: 900px; margin: 0 auto; padding-bottom: 3rem;">
    <!-- Unconnected / Marketing Section -->
    <div id="wallet-unconnected">
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

<!-- Rabby-style Wallet UI (Hidden by default) -->
    <div id="wallet-connected" style="display: none; max-width: 420px; margin: 0 auto;">
        <div class="glass-card rabby-ui" style="border-radius: 20px; overflow: hidden; padding: 0; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <!-- Header: Address and Total Balance -->
            <div class="rabby-header" style="background: rgba(255,255,255,0.02); padding: 2rem 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); position: relative;">
                
                <button type="button" id="rabby-disconnect-btn" aria-label="Disconnect" style="position: absolute; right: 1rem; top: 1rem; background: none; border: none; color: rgba(255,255,255,0.4); cursor: pointer; font-size: 1.2rem; transition: color 0.2s;">
                    &times;
                </button>

                <div class="rabby-address mb-3" style="font-family: 'Geologica', monospace; font-size: 0.9rem; color: rgba(255,255,255,0.75); background: rgba(0,0,0,0.25); padding: 6px 14px; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="width: 8px; height: 8px; background-color: #10b981; border-radius: 50%; box-shadow: 0 0 8px #10b981;"></div>
                    <span id="rabby-address-text">0x...</span>
                </div>
                
                <div style="font-size: 0.95rem; color: rgba(255,255,255,0.5); margin-bottom: 0.25rem;">Total Net Worth</div>
                <h1 class="rabby-balance" style="font-size: 2.8rem; font-weight: 700; color: #fff; margin: 0; display: flex; justify-content: center; align-items: baseline; gap: 4px;">
                    <span style="font-size: 1.8rem; color: rgba(255,255,255,0.5);">$</span><span id="rabby-total-fiat">0.00</span>
                </h1>
            </div>

            <!-- Quick Actions -->
            <div class="rabby-actions" style="display: flex; justify-content: center; padding: 1.5rem 1rem; gap: 2rem; border-bottom: 1px solid rgba(255,255,255,0.05); background: rgba(255,255,255,0.01);">
                <div class="rabby-action-btn text-center">
                    <button style="width: 52px; height: 52px; border-radius: 16px; background: rgba(251, 191, 36, 0.12); border: 1px solid rgba(251, 191, 36, 0.2); color: #fbbf24; font-size: 1.4rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                    </button>
                    <div style="font-size: 0.85rem; margin-top: 0.5rem; color: rgba(255,255,255,0.7); font-weight: 500;">Send</div>
                </div>
                <div class="rabby-action-btn text-center">
                    <button style="width: 52px; height: 52px; border-radius: 16px; background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.2); color: #3b82f6; font-size: 1.4rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                    </button>
                    <div style="font-size: 0.85rem; margin-top: 0.5rem; color: rgba(255,255,255,0.7); font-weight: 500;">Receive</div>
                </div>
                <div class="rabby-action-btn text-center">
                    <button style="width: 52px; height: 52px; border-radius: 16px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; font-size: 1.4rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5"></path><path d="M4 20L21 3"></path><path d="M21 16v5h-5"></path><path d="M15 15l6 6"></path><path d="M4 4l5 5"></path></svg>
                    </button>
                    <div style="font-size: 0.85rem; margin-top: 0.5rem; color: rgba(255,255,255,0.7); font-weight: 500;">Swap</div>
                </div>
            </div>

            <!-- Tokens List -->
            <div class="rabby-tokens">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem 0.75rem;">
                    <h4 style="font-size: 1.05rem; color: rgba(255,255,255,0.9); font-weight: 600; margin: 0;">Assets</h4>
                    <span style="font-size: 0.85rem; color: rgba(255,255,255,0.5);">Native + Network</span>
                </div>
                <div id="rabby-tokens-list" style="padding: 0 1rem 1rem;">
                    <!-- Loading state -->
                    <div class="text-center py-4" style="color: rgba(255,255,255,0.5);">
                        Loading assets...
                    </div>
                </div>
            </div>
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

  const rabbyUi = document.getElementById('wallet-connected');
  const unconnectedUi = document.getElementById('wallet-unconnected');
  const rabbyAddressText = document.getElementById('rabby-address-text');
  const rabbyTotalFiat = document.getElementById('rabby-total-fiat');
  const rabbyTokensList = document.getElementById('rabby-tokens-list');
  const rabbyDisconnectBtn = document.getElementById('rabby-disconnect-btn');

  // Hardcoded prices for demonstration
  const PRICES = {
    'ETH': 3540.20,
    'USDT': 1.00,
    'USDC': 1.00,
    'AV8': 0.12 
  };

  function updateWalletState(address) {
    if (address) {
      unconnectedUi.style.display = 'none';
      rabbyUi.style.display = 'block';
      rabbyAddressText.textContent = shortenAddress(address);
      fetchBalances(address);
    } else {
      unconnectedUi.style.display = 'block';
      rabbyUi.style.display = 'none';
      rabbyTotalFiat.textContent = '0.00';
      rabbyTokensList.innerHTML = '';
    }
  }

  async function fetchBalances(address) {
    rabbyTokensList.innerHTML = '<div class="text-center py-4" style="color: rgba(255,255,255,0.5);">Scanning network...</div>';
    
    let ethBalance = 0;
    try {
      if (window.ethereum) {
        const balanceHex = await window.ethereum.request({ method: 'eth_getBalance', params: [address, 'latest'] });
        ethBalance = parseInt(balanceHex, 16) / 1e18;
      }
    } catch(e) {
      console.error('Failed to fetch balance', e);
    }

    // Prepare mock tokens + real ETH token
    const tokens = [
      {
        symbol: 'ETH',
        name: 'Ethereum',
        balance: ethBalance,
        price: PRICES.ETH,
        iconUrl: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
        color: '#627eea'
      },
      {
        symbol: 'USDT',
        name: 'Tether USD',
        balance: 1450.50, // Mocked balance
        price: PRICES.USDT,
        iconUrl: 'https://cryptologos.cc/logos/tether-usdt-logo.svg',
        color: '#26a17b'
      },
      {
        symbol: 'AV8',
        name: 'AV8 Capital',
        balance: 15400, // Mocked balance
        price: PRICES.AV8,
        iconUrl: 'https://cryptologos.cc/logos/avalanche-avax-logo.svg', // using avax logo as placeholder
        color: '#e84142'
      }
    ];

    let totalFiat = 0;
    let listHtml = '';

    tokens.forEach(t => {
      const fiatValue = t.balance * t.price;
      totalFiat += fiatValue;

      // Skip rendering if 0 and not native
      if (t.balance === 0 && t.symbol !== 'ETH') return;

      listHtml += `
        <div class="token-row" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0.5rem; border-radius: 12px; transition: background 0.2s; cursor: pointer;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #fff; padding: 4px; display: flex; justify-content: center; align-items: center;">
                    <img src="${t.iconUrl}" alt="${t.symbol}" style="max-width: 100%; max-height: 100%; border-radius: 50%;">
                </div>
                <div>
                    <div style="color: #fff; font-weight: 600; font-size: 1.05rem; line-height: 1.2;">${t.symbol}</div>
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">${t.name}</div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="color: #fff; font-weight: 600; font-size: 1.05rem; line-height: 1.2;">$${fiatValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">${t.balance.toLocaleString('en-US', {maximumFractionDigits: 6})} ${t.symbol}</div>
            </div>
        </div>
      `;
    });

    rabbyTokensList.innerHTML = listHtml;
    
    // Animate counter for total fiat
    let startTimestamp = null;
    const duration = 1000;
    const finalValue = totalFiat;
    
    const step = (timestamp) => {
      if (!startTimestamp) startTimestamp = timestamp;
      const progress = Math.min((timestamp - startTimestamp) / duration, 1);
      // easeOutExpo
      const easing = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
      const currentVal = finalValue * easing;
      rabbyTotalFiat.textContent = currentVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      if (progress < 1) {
        window.requestAnimationFrame(step);
      }
    };
    window.requestAnimationFrame(step);
  }

  // Check connection on load
  window.addEventListener('load', async () => {
    if (window.ethereum) {
      try {
        const accounts = await window.ethereum.request({ method: 'eth_accounts' });
        if (accounts && accounts.length > 0) {
          updateWalletState(accounts[0]);
        }
      } catch (e) {
        console.error(e);
      }
    }
  });

  if (rabbyDisconnectBtn) {
    rabbyDisconnectBtn.addEventListener('click', () => {
      // Metamask doesn't support programmatic disconnect on the frontend easily, 
      // but we can clear the UI state.
      updateWalletState(null);
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
        
        // Show the Rabby UI immediately upon connection
        updateWalletState(address);
        
        // Optionally run the challenge if the user is logging in on backend (skipped for pure visual connect)
        // const challenge = await postJson('...', { address });
        // ...
      } catch (error) {
        setWeb3Status(error.message || 'Не удалось выполнить подключение через Web3.', true);
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

  .rabby-action-btn button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
  }
  .token-row:hover {
    background: rgba(255,255,255,0.05);
  }
</style>
@endpush
