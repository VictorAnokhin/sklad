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
                    <button id="btn-show-send" style="width: 52px; height: 52px; border-radius: 16px; background: rgba(251, 191, 36, 0.12); border: 1px solid rgba(251, 191, 36, 0.2); color: #fbbf24; font-size: 1.4rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
                    </button>
                    <div style="font-size: 0.85rem; margin-top: 0.5rem; color: rgba(255,255,255,0.7); font-weight: 500;">Send</div>
                </div>
                <div class="rabby-action-btn text-center">
                    <button id="btn-show-receive" style="width: 52px; height: 52px; border-radius: 16px; background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.2); color: #3b82f6; font-size: 1.4rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
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

            <!-- Tokens List View (Main) -->
            <div id="wallet-main-view" class="rabby-tokens">
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
            
            <!-- Send Coins View -->
            <div id="wallet-send-view" style="display:none; padding: 1.5rem; background: rgba(255,255,255,0.01);">
                <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                    <button id="btn-back-from-send" style="background: none; border: none; color: #fbbf24; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; padding: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Назад
                    </button>
                    <h4 style="margin: 0 auto; color: #fff; font-size: 1.1rem; transform: translateX(-20px);">Отправить</h4>
                </div>
                <div>
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.5rem; display: block;">Адрес получателя</label>
                    <input type="text" id="send-to-address" placeholder="0x..." style="width: 100%; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: rgba(0,0,0,0.2); color: #fff; padding: 10px 12px; margin-bottom: 1rem; outline: none; font-family: monospace;">
                    
                    <label style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.5rem; display: block;">Выберите актив</label>
                    <select id="send-token-select" style="width: 100%; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: #1a1a1a; color: #fff; padding: 10px 12px; margin-bottom: 1rem; outline: none; appearance: none; cursor: pointer;">
                        <!-- Populated dynamically -->
                    </select>

                    <label style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.5rem; display: block;">Сумма</label>
                    <input type="number" id="send-amount" placeholder="0.0" step="any" min="0" style="width: 100%; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: rgba(0,0,0,0.2); color: #fff; padding: 10px 12px; margin-bottom: 1.5rem; outline: none; font-size: 1.1rem;">
                    
                    <button id="btn-submit-send" style="width: 100%; padding: 12px; border-radius: 8px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #111; border: none; font-weight: 600; font-size: 1.05rem; cursor: pointer; transition: transform 0.2s;">Одобрить транзакцию</button>
                    <p id="send-status" class="text-center" style="margin-top: 1rem; font-size: 0.9rem;"></p>
                </div>
            </div>

            <!-- Receive Coins View -->
            <div id="wallet-receive-view" style="display:none; padding: 1.5rem; text-align: center; background: rgba(255,255,255,0.01);">
                <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                    <button id="btn-back-from-receive" style="background: none; border: none; color: #3b82f6; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; padding: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Назад
                    </button>
                    <h4 style="margin: 0 auto; color: #fff; font-size: 1.1rem; transform: translateX(-20px);">Получить</h4>
                </div>
                
                <div style="background: #fff; padding: 1rem; border-radius: 12px; display: inline-block; margin-bottom: 1.5rem;">
                    <!-- Placeholder for QR code icon as we don't have a specific library loaded -->
                    <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><path d="M5 5v3h3V5H5zM16 5v3h3V5h-3zM5 16v3h3v-3H5z"></path></svg>
                </div>
                
                <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-bottom: 0.5rem;">Ваш адрес в EVM-сетях:</p>
                <div id="receive-address-display" style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.95rem; color: #fff; margin-bottom: 1rem; word-break: break-all;"></div>
                
                <button id="btn-copy-address" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #3b82f6; border-radius: 8px; padding: 8px 16px; cursor: pointer; transition: all 0.2s;">Копировать адрес</button>
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

  const topDisconnectBtn = document.getElementById('menu-disconnect-wallet');
  const mainLogoutBtn = document.getElementById('main-logout-btn');

  // Network configs for standard tokens to scan "Real Contents" via eth_call
  const COMMON_NETWORKS = {
    '0x1': {
      name: 'Ethereum Mainnet',
      native: { symbol: 'ETH', name: 'Ethereum', iconUrl: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg', price: 3500 },
      tokens: []
    },
    '0x38': {
      name: 'Binance Smart Chain',
      native: { symbol: 'BNB', name: 'BNB', iconUrl: 'https://cryptologos.cc/logos/bnb-bnb-logo.svg', price: 600 },
      tokens: []
    },
    '0x89': {
      name: 'Polygon',
      native: { symbol: 'POL', name: 'Polygon', iconUrl: 'https://cryptologos.cc/logos/polygon-matic-logo.svg', price: 0.8 },
      tokens: []
    },
    '0xa4b1': {
      name: 'Arbitrum One',
      native: { symbol: 'ETH', name: 'Ethereum', iconUrl: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg', price: 3500 },
      tokens: []
    },
    '0xa': {
      name: 'Optimism',
      native: { symbol: 'ETH', name: 'Ethereum', iconUrl: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg', price: 3500 },
      tokens: []
    },
    '0x2105': {
      name: 'Base',
      native: { symbol: 'ETH', name: 'Ethereum', iconUrl: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg', price: 3500 },
      tokens: []
    },
    '0xa86a': {
      name: 'Avalanche C-Chain',
      native: { symbol: 'AVAX', name: 'Avalanche', iconUrl: 'https://cryptologos.cc/logos/avalanche-avax-logo.svg', price: 35 },
      tokens: []
    }
  };

  function normalizeChainId(value) {
    if (value === null || value === undefined) return null;

    if (typeof value === 'number' && Number.isFinite(value)) {
      return '0x' + value.toString(16);
    }

    if (typeof value !== 'string') return null;

    const raw = value.trim().toLowerCase();
    if (!raw) return null;

    if (raw.startsWith('0x')) {
      const n = parseInt(raw, 16);
      if (Number.isFinite(n)) return '0x' + n.toString(16);
      return null;
    }

    // "56" or "38" (decimal)
    if (/^\d+$/.test(raw)) {
      const n = parseInt(raw, 10);
      if (Number.isFinite(n)) return '0x' + n.toString(16);
      return null;
    }

    // "a4b1" (hex w/o 0x)
    if (/^[0-9a-f]+$/.test(raw)) {
      const n = parseInt(raw, 16);
      if (Number.isFinite(n)) return '0x' + n.toString(16);
      return null;
    }

    return null;
  }

  function isEvmAddress(value) {
    if (typeof value !== 'string') return false;
    return /^0x[0-9a-fA-F]{40}$/.test(value.trim());
  }

  const dbTokens = {!! json_encode($web3Tokens ?? []) !!};
  dbTokens.forEach(t => {
      const chainId = normalizeChainId(t.vision);
      const address = (typeof t.color === 'string') ? t.color.trim() : '';
      if (!chainId || !COMMON_NETWORKS[chainId] || !isEvmAddress(address)) {
          return;
      }

      COMMON_NETWORKS[chainId].tokens.push({
          address,
          symbol: t.name,
          name: t.doc || t.name,
          decimals: parseInt(t.status) || 18,
          iconUrl: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg', // generic default icon
          price: 0, // default to 0, updated by Coingecko later
          cgId: (t.constanta && t.constanta !== '0') ? t.constanta : null
      });
  });

  // Collect Coingecko IDs
  const cgIdsToFetch = new Set();
  const nativeCoinMaps = {
    '0x1': 'ethereum',
    '0x38': 'binancecoin',
    '0x89': 'matic-network',
    '0xa4b1': 'ethereum',
    '0xa': 'ethereum',
    '0x2105': 'ethereum',
    '0xa86a': 'avalanche-2'
  };

  Object.values(nativeCoinMaps).forEach(id => cgIdsToFetch.add(id));
  dbTokens.forEach(t => {
      if (t.constanta && t.constanta !== '0') {
          cgIdsToFetch.add(t.constanta);
      }
  });

  if (cgIdsToFetch.size > 0) {
      const idsStr = Array.from(cgIdsToFetch).join(',');
      fetch(`https://api.coingecko.com/api/v3/simple/price?ids=${idsStr}&vs_currencies=usd`)
        .then(r => r.json())
        .then(prices => {
            // Apply native token prices
            Object.keys(nativeCoinMaps).forEach(chainId => {
                const cgId = nativeCoinMaps[chainId];
                if (prices[cgId] && prices[cgId].usd) {
                   if (COMMON_NETWORKS[chainId] && COMMON_NETWORKS[chainId].native) {
                       COMMON_NETWORKS[chainId].native.price = prices[cgId].usd;
                   }
                }
            });
            
            // Apply DB token prices
            Object.values(COMMON_NETWORKS).forEach(net => {
               if (net.tokens) {
                   net.tokens.forEach(tk => {
                      if (tk.cgId && prices[tk.cgId] && prices[tk.cgId].usd) {
                          tk.price = prices[tk.cgId].usd;
                      }
                   });
               }
            });
            
            // Refresh balances if a wallet is already loaded (could occur if price fetch is slow)
            if (currentWalletAddress && document.getElementById('wallet-main-view').style.display !== 'none') {
                fetchBalances(currentWalletAddress);
            }
        })
        .catch(e => console.error('Coingecko price fetch error:', e));
  }

  async function getErc20Balance(tokenAddress, decimals, walletAddress) {
    try {
      const data = '0x70a08231' + '000000000000000000000000' + walletAddress.substring(2);
      const balanceHex = await window.ethereum.request({
        method: 'eth_call',
        params: [{ to: tokenAddress, data: data }, 'latest']
      });
      if (!balanceHex || balanceHex === '0x') return 0;
      const balanceBigInt = BigInt(balanceHex);
      return Number(balanceBigInt) / Math.pow(10, decimals);
    } catch (e) {
      console.warn('Failed to fetch ERC20', tokenAddress, e);
      return 0;
    }
  }

  let currentWalletAddress = null;
  let currentWalletTokens = [];

  const desktopPublicLinks = document.getElementById('desktop-public-links');
  const mobilePublicLinks = document.getElementById('mobile-public-links');

  function updateWalletState(address) {
    if (address) {
      currentWalletAddress = address;
      unconnectedUi.style.display = 'none';
      rabbyUi.style.display = 'block';
      rabbyAddressText.textContent = shortenAddress(address);
      document.getElementById('receive-address-display').textContent = address;
      
      if (topDisconnectBtn) {
        topDisconnectBtn.style.display = 'inline-flex';
        const addressSpan = document.getElementById('menu-wallet-address');
        if (addressSpan) {
            addressSpan.textContent = address.slice(-4);
        }
      }
      
      if (desktopPublicLinks) desktopPublicLinks.style.display = 'none';
      if (mobilePublicLinks) mobilePublicLinks.style.display = 'none';

      showMainView();
      fetchBalances(address);
    } else {
      currentWalletAddress = null;
      unconnectedUi.style.display = 'block';
      rabbyUi.style.display = 'none';
      rabbyTotalFiat.textContent = '0.00';
      rabbyTokensList.innerHTML = '';
      currentWalletTokens = [];
      if (topDisconnectBtn) topDisconnectBtn.style.display = 'none';
      
      if (desktopPublicLinks) desktopPublicLinks.style.display = 'flex';
      if (mobilePublicLinks) mobilePublicLinks.style.display = 'block';
    }
  }

  const walletMainView = document.getElementById('wallet-main-view');
  const walletSendView = document.getElementById('wallet-send-view');
  const walletReceiveView = document.getElementById('wallet-receive-view');
  const btnShowSend = document.getElementById('btn-show-send');
  const btnShowReceive = document.getElementById('btn-show-receive');
  const btnBackFromSend = document.getElementById('btn-back-from-send');
  const btnBackFromReceive = document.getElementById('btn-back-from-receive');
  const btnSubmitSend = document.getElementById('btn-submit-send');
  const sendTokenSelect = document.getElementById('send-token-select');
  const sendToAddress = document.getElementById('send-to-address');
  const sendAmount = document.getElementById('send-amount');
  const sendStatus = document.getElementById('send-status');

  function showMainView() {
    walletMainView.style.display = 'block';
    walletSendView.style.display = 'none';
    walletReceiveView.style.display = 'none';
  }

  function showSendView() {
    walletMainView.style.display = 'none';
    walletReceiveView.style.display = 'none';
    walletSendView.style.display = 'block';
    sendStatus.textContent = '';
    
    // Populate dropdown
    sendTokenSelect.innerHTML = '';
    currentWalletTokens.forEach((t, i) => {
      const opt = document.createElement('option');
      opt.value = i;
      opt.textContent = `${t.symbol} (Баланс: ${t.balance.toLocaleString('en-US', {maximumFractionDigits: 6})})`;
      sendTokenSelect.appendChild(opt);
    });
  }

  function showReceiveView() {
    walletMainView.style.display = 'none';
    walletSendView.style.display = 'none';
    walletReceiveView.style.display = 'block';
  }

  if (btnShowSend) btnShowSend.addEventListener('click', showSendView);
  if (btnShowReceive) btnShowReceive.addEventListener('click', showReceiveView);
  if (btnBackFromSend) btnBackFromSend.addEventListener('click', showMainView);
  if (btnBackFromReceive) btnBackFromReceive.addEventListener('click', showMainView);

  document.getElementById('btn-copy-address')?.addEventListener('click', () => {
    if (currentWalletAddress) {
      navigator.clipboard.writeText(currentWalletAddress);
      const btn = document.getElementById('btn-copy-address');
      const old = btn.textContent;
      btn.textContent = 'Скопировано!';
      setTimeout(() => btn.textContent = old, 2000);
    }
  });

  // Handle Send Transaction
  if (btnSubmitSend) {
    btnSubmitSend.addEventListener('click', async () => {
      if (!window.ethereum || !currentWalletAddress) return;
      const toAddr = sendToAddress.value.trim();
      const amountVal = parseFloat(sendAmount.value);
      const tokenIdx = parseInt(sendTokenSelect.value);
      
      if (!toAddr || !amountVal || isNaN(tokenIdx)) {
        sendStatus.textContent = 'Заполните адрес и сумму корректно.';
        sendStatus.style.color = '#ff8e8e';
        return;
      }
      
      const token = currentWalletTokens[tokenIdx];
      
      sendStatus.textContent = 'Ожидание подтверждения в кошельке...';
      sendStatus.style.color = '#fbbf24';
      btnSubmitSend.disabled = true;

      try {
        let txHash;
        
        // Native vs ERC20
        // We know it's ERC20 if it has 'address' and 'decimals'
        if (token.address && token.decimals !== undefined) {
          // ERC20 Transfer
          // Function selector for transfer(address,uint256) is 0xa9059cbb
          // Pad address to 32 bytes (64 hex chars)
          const toParam = '000000000000000000000000' + toAddr.toLowerCase().substring(2);
          
          // Calculate amount in lowest denomination using BigInt to prevent precision loss
          // JS can't directly do Math.pow reliably for large uint256 strings
          let amountStr;
          try {
             // Basic multiplier for JS safe integers up to decimals 18
             // Using string manipulation or BigInt for real apps, simplified here:
             const multiplier = 10 ** token.decimals;
             const amountBigInt = BigInt(Math.floor(amountVal * multiplier));
             amountStr = amountBigInt.toString(16).padStart(64, '0');
          } catch(e) {
             console.error("Amount conversion error", e);
             throw new Error("Ошибка расчета суммы (слишком много нулей)");
          }

          const dataStr = '0xa9059cbb' + toParam + amountStr;

          txHash = await window.ethereum.request({
            method: 'eth_sendTransaction',
            params: [{
              from: currentWalletAddress,
              to: token.address,
              data: dataStr
            }]
          });

        } else {
          // Native Transfer
          const amountWeiHex = '0x' + BigInt(Math.floor(amountVal * 1e18)).toString(16);
          txHash = await window.ethereum.request({
            method: 'eth_sendTransaction',
            params: [{
              from: currentWalletAddress,
              to: toAddr,
              value: amountWeiHex
            }]
          });
        }
        
        sendStatus.textContent = 'Транзакция отправлена! Хэш: ' + shortenAddress(txHash);
        sendStatus.style.color = '#b9fbc0';
        sendToAddress.value = '';
        sendAmount.value = '';
        setTimeout(() => fetchBalances(currentWalletAddress), 5000); // refresh logic
      } catch (err) {
        console.error(err);
        sendStatus.textContent = 'Ошибка: ' + (err.message || 'Транзакция отклонена');
        sendStatus.style.color = '#ff8e8e';
      } finally {
        btnSubmitSend.disabled = false;
      }
    });
  }

  async function fetchBalances(address) {
    rabbyTokensList.innerHTML = '<div class="text-center py-4" style="color: rgba(255,255,255,0.5);">Scanning real network values...</div>';
    
    let nativeBalance = 0;
    let chainId = '0x1'; // default eth
    
    try {
      if (window.ethereum) {
        let rawChainId = await window.ethereum.request({ method: 'eth_chainId' });
        if (typeof rawChainId === 'string') {
          if (rawChainId.startsWith('0x')) {
            chainId = '0x' + parseInt(rawChainId, 16).toString(16);
          } else {
            chainId = '0x' + parseInt(rawChainId, 10).toString(16);
          }
        } else if (typeof rawChainId === 'number') {
          chainId = '0x' + rawChainId.toString(16);
        }
        const balanceHex = await window.ethereum.request({ method: 'eth_getBalance', params: [address, 'latest'] });
        nativeBalance = parseInt(balanceHex, 16) / 1e18;
      }
    } catch(e) {
      console.error('Failed to fetch native balance', e);
    }

    const networkCfg = COMMON_NETWORKS[chainId] || COMMON_NETWORKS['0x1'];
    
    // 1. Setup native token payload
    const tokensToShow = [];
    tokensToShow.push({
      symbol: networkCfg.native.symbol,
      name: networkCfg.native.name,
      balance: nativeBalance,
      price: networkCfg.native.price,
      iconUrl: networkCfg.native.iconUrl
    });

    // 2. Fetch real ERC-20 token balances for this network (show configured tokens even if balance is 0)
    for (const token of networkCfg.tokens) {
      const bal = await getErc20Balance(token.address, token.decimals, address);
      tokensToShow.push({
        symbol: token.symbol,
        name: token.name,
        balance: bal,
        price: token.price,
        iconUrl: token.iconUrl,
        address: token.address,
        decimals: token.decimals
      });
    }

    let totalFiat = 0;
    let listHtml = '';
    
    // Store globally for Send dropdown (omitting DeFi tokens so users don't accidentally send aTokens unless they mean to withdraw natively)
    currentWalletTokens = tokensToShow;

    tokensToShow.forEach(t => {
      const fiatValue = t.balance * t.price;
      totalFiat += fiatValue;

      // Skip native if 0 and we have other tokens
      if (t.balance === 0 && tokensToShow.length > 1 && t.symbol === networkCfg.native.symbol) return;

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
                <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">${t.balance.toLocaleString('en-US', {maximumFractionDigits: 4})} ${t.symbol}</div>
            </div>
        </div>
      `;
    });

    rabbyTokensList.innerHTML = listHtml;
    
    // 4. Fetch DeFi tokens if configured
    const defiPositions = [];
    if (networkCfg.defi && networkCfg.defi.length > 0) {
      for (const dToken of networkCfg.defi) {
        const bal = await getErc20Balance(dToken.address, dToken.decimals, address);
        if (bal > 0) {
          defiPositions.push({
            ...dToken,
            balance: bal
          });
        }
      }
    }
    
    // Append DeFi Positions UI
    if (defiPositions.length > 0) {
      let defiHtml = `
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem 0.5rem 0.75rem; border-top: 1px solid rgba(255,255,255,0.05); margin-top: 0.5rem;">
            <h4 style="font-size: 1.05rem; color: rgba(255,255,255,0.9); font-weight: 600; margin: 0;">DeFi Positions & Yield</h4>
        </div>
      `;
      
      defiPositions.forEach(dt => {
        const fiatValue = dt.balance * dt.price;
        totalFiat += fiatValue;
        
        defiHtml += `
          <div class="token-row" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0.5rem; border-radius: 12px; transition: background 0.2s; cursor: pointer; background: rgba(255,255,255,0.02); margin-bottom: 0.5rem;">
              <div style="display: flex; align-items: center; gap: 12px;">
                  <div style="width: 36px; height: 36px; border-radius: 50%; background: #1a1a1a; padding: 4px; display: flex; justify-content: center; align-items: center; position: relative;">
                      <img src="${dt.iconUrl}" alt="${dt.protocol}" style="max-width: 100%; max-height: 100%; border-radius: 50%;">
                      <div style="position: absolute; bottom: -4px; right: -4px; background: #fbbf24; color: #111; font-size: 0.6rem; font-weight: bold; border-radius: 4px; padding: 1px 3px;">${dt.protocol}</div>
                  </div>
                  <div>
                      <div style="color: #fff; font-weight: 600; font-size: 1.05rem; line-height: 1.2;">${dt.symbol}</div>
                      <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">Deposited / Yielding</div>
                  </div>
              </div>
              <div style="text-align: right;">
                  <div style="color: #4ade80; font-weight: 600; font-size: 1.05rem; line-height: 1.2;">$${fiatValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                  <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">${dt.balance.toLocaleString('en-US', {maximumFractionDigits: 4})} ${dt.symbol}</div>
              </div>
          </div>
        `;
      });
      rabbyTokensList.insertAdjacentHTML('beforeend', defiHtml);
    }
    
    // Animate counter for total fiat include DeFi
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
    if (window.ethereum && localStorage.getItem('walletDisconnectedExplicitly') !== 'true') {
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

  if (topDisconnectBtn) {
    topDisconnectBtn.addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.setItem('walletDisconnectedExplicitly', 'true');
      updateWalletState(null);
      // Initiate framework logout as well logic so session aligns
      const form = document.getElementById('logout-form');
      if (form) form.submit();
    });
  }

  if (rabbyDisconnectBtn) {
    rabbyDisconnectBtn.addEventListener('click', () => {
      localStorage.setItem('walletDisconnectedExplicitly', 'true');
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
        localStorage.removeItem('walletDisconnectedExplicitly');
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
