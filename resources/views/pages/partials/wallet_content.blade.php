<div class="wallet-page" style="max-width: 900px; margin: 0 auto; padding-bottom: 3rem;">
    <div id="wallet-unconnected">
        <div class="glass-card animated-card" style="padding: 2.5rem; margin-bottom: 2rem; border-radius: 16px;">
            <h2 class="mb-4" style="color: #fbbf24; font-weight: 700;">Web3 Интеграция</h2>
            <p style="font-size: 1.1rem; color: rgba(255,255,255,0.85); line-height: 1.6; margin-bottom: 2rem;">
                Подключение Web3-кошелька EVM или Solana открывает доступ к передовым инвестиционным инструментам платформы AV8 Capital.
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

        <div class="glass-card web3-login-box" style="margin: 0 auto; max-width: 450px;">
            <div class="web3-login-copy text-center">
                <p class="web3-login-eyebrow">WEB3 ACCESS</p>
                <h2 class="web3-login-title" style="color: #fff; font-weight: 600;">Вход через кошелек</h2>
                <p class="web3-login-text" style="color: rgba(255,255,255,0.7); margin-bottom: 1.5rem;">Подключите MetaMask, Phantom или другой совместимый кошелек для авторизации в системе.</p>
            </div>
            <div class="web3-login-actions">
                <button type="button" id="web3-connect-btn" class="web3-connect-btn">Подключить Web3</button>
                <p id="web3-wallet-address" class="web3-wallet-address text-center" style="display:none; margin-top: 1rem; color: #fbbf24;"></p>
                <p id="web3-status" class="web3-status text-center" style="display:none; margin-top: 0.5rem;"></p>
            </div>
        </div>
    </div>

    <div id="wallet-connected" style="display: none; max-width: 420px; margin: 0 auto;">
        <div class="glass-card rabby-ui" style="border-radius: 20px; overflow: hidden; padding: 0; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
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

            <div id="wallet-main-view" class="rabby-tokens">
                <div style="padding: 1rem 1.5rem 0.25rem;">
                    <div id="profile-wallet-selector" style="margin-bottom: 1rem;">
                        <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; margin-bottom:0.75rem;" id="profile-wallet-list"></div>
                        <div style="color: rgba(255,255,255,0.58); font-size: 0.85rem;">Выберите кошелёк из профиля. Если кошельков несколько, переключайтесь между ними по очереди.</div>
                    </div>
                    <div class="wallet-network-panel">
                        <div>
                            <div class="wallet-network-panel__label">DeFi network</div>
                            <div class="wallet-network-panel__meta" id="wallet-network-meta">Выберите сеть для просмотра активов и DeFi</div>
                        </div>
                        <select id="wallet-network-select" class="wallet-network-select" aria-label="Select network for DeFi protocols">
                            <option value="0x1">Ethereum</option>
                            <option value="0x89">Polygon</option>
                            <option value="0xa4b1">Arbitrum</option>
                            <option value="0xa">Optimism</option>
                            <option value="0x2105">Base</option>
                            <option value="0xa86a">Avalanche</option>
                            <option value="0x38">BNB Chain</option>
                            <option value="solana">Solana</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem 0.75rem;">
                    <h4 style="font-size: 1.05rem; color: rgba(255,255,255,0.9); font-weight: 600; margin: 0;">Assets</h4>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <button id="btn-refresh-tokens" style="background: rgba(251, 191, 36, 0.15); border: 1px solid rgba(251, 191, 36, 0.3); color: #fbbf24; border-radius: 8px; padding: 0.5rem 1rem; font-size: 0.85rem; cursor: pointer; transition: all 0.2s;">Обновить</button>
                        <span style="font-size: 0.85rem; color: rgba(255,255,255,0.5);">Native + Network</span>
                    </div>
                </div>
                <div id="rabby-tokens-list" style="padding: 0 1rem 1rem;">
                    <div class="text-center py-4" style="color: rgba(255,255,255,0.5);">
                        Loading assets...
                    </div>
                </div>
                <div id="wallet-defi-sections" style="display:none; padding: 0 1rem 1rem;"></div>
            </div>

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
                    <select id="send-token-select" style="width: 100%; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: #1a1a1a; color: #fff; padding: 10px 12px; margin-bottom: 1rem; outline: none; appearance: none; cursor: pointer;"></select>

                    <label style="color: rgba(255,255,255,0.7); font-size: 0.85rem; margin-bottom: 0.5rem; display: block;">Сумма</label>
                    <input type="number" id="send-amount" placeholder="0.0" step="any" min="0" style="width: 100%; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; background: rgba(0,0,0,0.2); color: #fff; padding: 10px 12px; margin-bottom: 1.5rem; outline: none; font-size: 1.1rem;">

                    <button id="btn-submit-send" style="width: 100%; padding: 12px; border-radius: 8px; background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #111; border: none; font-weight: 600; font-size: 1.05rem; cursor: pointer; transition: transform 0.2s;">Одобрить транзакцию</button>
                    <p id="send-status" class="text-center" style="margin-top: 1rem; font-size: 0.9rem;"></p>
                </div>
            </div>

            <div id="wallet-receive-view" style="display:none; padding: 1.5rem; text-align: center; background: rgba(255,255,255,0.01);">
                <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                    <button id="btn-back-from-receive" style="background: none; border: none; color: #3b82f6; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; padding: 0;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                        Назад
                    </button>
                    <h4 style="margin: 0 auto; color: #fff; font-size: 1.1rem; transform: translateX(-20px);">Получить</h4>
                </div>

                <div style="background: #fff; padding: 1rem; border-radius: 12px; display: inline-block; margin-bottom: 1.5rem;">
                    <svg width="120" height="120" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><path d="M5 5v3h3V5H5zM16 5v3h3V5h-3zM5 16v3h3v-3H5z"></path></svg>
                </div>

                <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-bottom: 0.5rem;">Ваш адрес в EVM-сетях:</p>
                <div id="receive-address-display" style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.95rem; color: #fff; margin-bottom: 1rem; word-break: break-all;"></div>

                <button id="btn-copy-address" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #3b82f6; border-radius: 8px; padding: 8px 16px; cursor: pointer; transition: all 0.2s;">Копировать адрес</button>
            </div>
        </div>
    </div>
</div>

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
  const walletDefiSections = document.getElementById('wallet-defi-sections');
  const rabbyDisconnectBtn = document.getElementById('rabby-disconnect-btn');
  const walletNetworkSelect = document.getElementById('wallet-network-select');
  const walletNetworkMeta = document.getElementById('wallet-network-meta');

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
    },
    'solana': {
      name: 'Solana',
      native: { symbol: 'SOL', name: 'Solana', iconUrl: 'https://cryptologos.cc/logos/solana-sol-logo.svg', price: 150 },
      tokens: []
    }
  };

  const PROTOCOL_NETWORKS = {
    '0x1': { aave: true, gmx: false },
    '0x89': { aave: true, gmx: false },
    '0xa4b1': { aave: true, gmx: true },
    '0xa': { aave: true, gmx: false },
    '0x2105': { aave: true, gmx: false },
    '0xa86a': { aave: true, gmx: true },
    '0x38': { aave: true, gmx: false }
  };

  function normalizeChainId(value) {
    if (value === null || value === undefined) return null;
    if (typeof value === 'number' && Number.isFinite(value)) return '0x' + value.toString(16);
    if (typeof value !== 'string') return null;

    const raw = value.trim().toLowerCase();
    if (!raw) return null;
    if (raw === 'solana') return 'solana';

    if (raw.startsWith('0x')) {
      const n = parseInt(raw, 16);
      return Number.isFinite(n) ? '0x' + n.toString(16) : null;
    }

    if (/^\d+$/.test(raw)) {
      const n = parseInt(raw, 10);
      return Number.isFinite(n) ? '0x' + n.toString(16) : null;
    }

    if (/^[0-9a-f]+$/.test(raw)) {
      const n = parseInt(raw, 16);
      return Number.isFinite(n) ? '0x' + n.toString(16) : null;
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
    if (!chainId || !COMMON_NETWORKS[chainId]) return;
    if (chainId !== 'solana' && !isEvmAddress(address)) return;

    COMMON_NETWORKS[chainId].tokens.push({
      address,
      symbol: t.name,
      name: t.doc || t.name,
      decimals: parseInt(t.status) || (chainId === 'solana' ? 9 : 18),
      iconUrl: chainId === 'solana'
        ? 'https://cryptologos.cc/logos/solana-sol-logo.svg'
        : 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
      price: 0,
      cgId: (t.constanta && t.constanta !== '0') ? t.constanta : null,
      chain_id: chainId,
    });
  });

  const cgIdsToFetch = new Set();
  const nativeCoinMaps = {
    '0x1': 'ethereum',
    '0x38': 'binancecoin',
    '0x89': 'matic-network',
    '0xa4b1': 'ethereum',
    '0xa': 'ethereum',
    '0x2105': 'ethereum',
    '0xa86a': 'avalanche-2',
    'solana': 'solana'
  };

  Object.values(nativeCoinMaps).forEach(id => cgIdsToFetch.add(id));
  dbTokens.forEach(t => {
    if (t.constanta && t.constanta !== '0') cgIdsToFetch.add(t.constanta);
  });

  if (cgIdsToFetch.size > 0) {
    const idsStr = Array.from(cgIdsToFetch).join(',');
    fetch(`https://api.coingecko.com/api/v3/simple/price?ids=${idsStr}&vs_currencies=usd`)
      .then(r => r.json())
      .then(prices => {
        Object.keys(nativeCoinMaps).forEach(chainId => {
          const cgId = nativeCoinMaps[chainId];
          if (prices[cgId] && prices[cgId].usd && COMMON_NETWORKS[chainId]?.native) {
            COMMON_NETWORKS[chainId].native.price = prices[cgId].usd;
          }
        });

        Object.values(COMMON_NETWORKS).forEach(net => {
          if (!net.tokens) return;
          net.tokens.forEach(tk => {
            if (tk.cgId && prices[tk.cgId] && prices[tk.cgId].usd) {
              tk.price = prices[tk.cgId].usd;
            }
          });
        });

        if (currentWalletAddress && document.getElementById('wallet-main-view').style.display !== 'none') {
          fetchBalances(currentWalletAddress, selectedProtocolChainId);
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

  const profileWallets = {!! json_encode($profileWallets ?? []) !!};
  const profileWallet = {!! json_encode($profileWallet ?? null) !!};
  let currentWalletIndex = 0;
  let currentWalletAddress = null;
  let currentWalletChainId = null;
  let currentWalletTokens = [];
  let connectedWalletAddress = null;
  let connectedWalletChainId = null;
  let selectedProtocolChainId = normalizeChainId(profileWallet?.chain_id || connectedWalletChainId || '0x1') || '0x1';

  function buildWalletLabel(wallet) {
    if (!wallet || !wallet.address) return 'Неизвестный кошелек';
    const networkName = COMMON_NETWORKS[normalizeChainId(wallet.chain_id) || '0x1']?.name || wallet.chain_id || 'Chain';
    return `${shortenAddress(wallet.address)} · ${networkName}`;
  }

  function renderProfileWalletList() {
    const listContainer = document.getElementById('profile-wallet-list');
    if (!listContainer) return;

    if (!Array.isArray(profileWallets) || profileWallets.length === 0) {
      listContainer.innerHTML = '<div style="color: rgba(255,255,255,0.65);">В профиле нет сохранённых кошельков.</div>';
      return;
    }

    listContainer.innerHTML = profileWallets.map((wallet, index) => {
      const isActive = index === currentWalletIndex;
      return `
        <button type="button" class="wallet-select-btn${isActive ? ' active' : ''}" data-index="${index}">
          ${buildWalletLabel(wallet)}
        </button>
      `;
    }).join('');

    listContainer.querySelectorAll('.wallet-select-btn').forEach((button) => {
      button.addEventListener('click', () => {
        const index = Number(button.dataset.index);
        if (!Number.isNaN(index)) {
          selectProfileWallet(index);
        }
      });
    });
  }

  function selectProfileWallet(index) {
    if (!Array.isArray(profileWallets) || profileWallets.length === 0) return;
    const normalizedIndex = Math.max(0, Math.min(index, profileWallets.length - 1));
    currentWalletIndex = normalizedIndex;
    const wallet = profileWallets[currentWalletIndex];
    if (!wallet) return;

    renderProfileWalletList();
    updateWalletState(wallet.address, { chainId: wallet.chain_id });

    // Dispatch update token data jobs
    fetch('/api/wallet/update-token-data', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
      },
      body: JSON.stringify({
        wallet_address: wallet.address,
        chain_id: wallet.chain_id || '0x1',
      }),
      credentials: 'same-origin',
    }).catch(e => console.error('Failed to update token data:', e));
  }

  function describeProtocolAvailability(chainId) {
    const support = PROTOCOL_NETWORKS[chainId] || { aave: false, gmx: false };
    if (support.aave && support.gmx) return 'Aave + GMX доступны';
    if (support.aave) return 'Aave доступен';
    if (support.gmx) return 'GMX доступен';
    if (chainId === 'solana') return 'DeFi протоколы недоступны';
    return 'Протоколы недоступны';
  }

  function syncNetworkSelector(chainId) {
    const normalized = normalizeChainId(chainId) || '0x1';
    selectedProtocolChainId = normalized;

    if (walletNetworkSelect) walletNetworkSelect.value = normalized;
    if (walletNetworkMeta) {
      const networkName = COMMON_NETWORKS[normalized]?.name || 'Unknown network';
      walletNetworkMeta.textContent = `${networkName} • ${describeProtocolAvailability(normalized)}`;
    }
  }

  function updateWalletState(address, options = {}) {
    const requestedChainId = normalizeChainId(options.chainId || selectedProtocolChainId || currentWalletChainId || profileWallet?.chain_id || '0x1') || '0x1';
    if (address) {
      currentWalletAddress = address;
      currentWalletChainId = requestedChainId;
      unconnectedUi.style.display = 'none';
      rabbyUi.style.display = 'block';
      rabbyAddressText.textContent = shortenAddress(address);
      document.getElementById('receive-address-display').textContent = address;
      syncNetworkSelector(requestedChainId);
      showMainView();
      fetchBalances(address, requestedChainId);
      return;
    }

    currentWalletAddress = null;
    currentWalletChainId = null;
    unconnectedUi.style.display = 'block';
    rabbyUi.style.display = 'none';
    rabbyTotalFiat.textContent = '0.00';
    rabbyTokensList.innerHTML = '';
    walletDefiSections.innerHTML = '';
    walletDefiSections.style.display = 'none';
    currentWalletTokens = [];
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
    sendStatus.style.color = 'rgba(255,255,255,0.65)';

    if (!connectedWalletAddress) {
      sendTokenSelect.innerHTML = '<option value="">Сначала подключите кошелек</option>';
      sendTokenSelect.disabled = true;
      sendToAddress.disabled = true;
      sendAmount.disabled = true;
      btnSubmitSend.disabled = false;
      sendStatus.textContent = 'Для отправки подключите кошелек кнопкой возле меню.';
      return;
    }

    if (normalizeChainId(currentWalletChainId) === 'solana') {
      sendTokenSelect.innerHTML = '<option value="">Отправка для Solana пока недоступна</option>';
      sendTokenSelect.disabled = true;
      sendToAddress.disabled = true;
      sendAmount.disabled = true;
      btnSubmitSend.disabled = true;
      sendStatus.textContent = 'Просмотр Solana активов доступен, отправка пока не реализована.';
      return;
    }

    sendTokenSelect.disabled = false;
    sendToAddress.disabled = false;
    sendAmount.disabled = false;
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

  if (btnSubmitSend) {
    btnSubmitSend.addEventListener('click', async () => {
      if (!connectedWalletAddress) {
        sendStatus.textContent = 'Для отправки сначала подключите кошелек.';
        sendStatus.style.color = '#ff8e8e';
        await connectWalletProvider();
        return;
      }

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

        if (token.address && token.decimals !== undefined) {
          const toParam = '000000000000000000000000' + toAddr.toLowerCase().substring(2);
          let amountStr;
          try {
            const multiplier = 10 ** token.decimals;
            const amountBigInt = BigInt(Math.floor(amountVal * multiplier));
            amountStr = amountBigInt.toString(16).padStart(64, '0');
          } catch (e) {
            console.error('Amount conversion error', e);
            throw new Error('Ошибка расчета суммы (слишком много нулей)');
          }

          const dataStr = '0xa9059cbb' + toParam + amountStr;
          txHash = await window.ethereum.request({
            method: 'eth_sendTransaction',
            params: [{
              from: connectedWalletAddress,
              to: token.address,
              data: dataStr
            }]
          });
        } else {
          const amountWeiHex = '0x' + BigInt(Math.floor(amountVal * 1e18)).toString(16);
          txHash = await window.ethereum.request({
            method: 'eth_sendTransaction',
            params: [{
              from: connectedWalletAddress,
              to: toAddr,
              value: amountWeiHex
            }]
          });
        }

        sendStatus.textContent = 'Транзакция отправлена! Хэш: ' + shortenAddress(txHash);
        sendStatus.style.color = '#b9fbc0';
        sendToAddress.value = '';
        sendAmount.value = '';
        setTimeout(() => fetchBalances(currentWalletAddress, selectedProtocolChainId), 5000);
      } catch (err) {
        console.error(err);
        sendStatus.textContent = 'Ошибка: ' + (err.message || 'Транзакция отклонена');
        sendStatus.style.color = '#ff8e8e';
      } finally {
        btnSubmitSend.disabled = false;
      }
    });
  }

  async function fetchBalances(address, preferredChainId = null) {
    rabbyTokensList.innerHTML = '<div class="text-center py-4" style="color: rgba(255,255,255,0.5);">Scanning real network values...</div>';
    walletDefiSections.innerHTML = '';
    walletDefiSections.style.display = 'none';
    const overview = await fetchWalletOverview(address, preferredChainId);
    if (!overview || !overview.assets || !overview.assets.available) {
      rabbyTokensList.innerHTML = '<div class="text-center py-4" style="color:#fca5a5;">Не удалось загрузить активы кошелька.</div>';
      return;
    }

    const chainId = normalizeChainId(preferredChainId || overview.wallet?.chain_id || overview.assets.chain_id || '0x1') || '0x1';
    currentWalletChainId = chainId;
    syncNetworkSelector(chainId);
    const networkCfg = COMMON_NETWORKS[chainId] || COMMON_NETWORKS['0x1'];
    const assets = Array.isArray(overview.assets.assets) ? overview.assets.assets : [];
    const tokensToShow = assets.map((asset) => {
      const configuredToken = asset.address
        ? networkCfg.tokens.find((token) => chainId === 'solana'
            ? String(token.address) === String(asset.address)
            : String(token.address).toLowerCase() === String(asset.address).toLowerCase())
        : null;
      const nativeToken = asset.is_native ? networkCfg.native : null;

      return {
        symbol: asset.symbol || configuredToken?.symbol || nativeToken?.symbol || 'TOKEN',
        name: asset.name || configuredToken?.name || nativeToken?.name || 'Token',
        balance: Number(asset.balance || 0),
        price: configuredToken?.price || nativeToken?.price || 0,
        iconUrl: configuredToken?.iconUrl || nativeToken?.iconUrl || 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
        address: asset.address || configuredToken?.address || null,
        decimals: asset.decimals ?? configuredToken?.decimals ?? 18,
        isNative: Boolean(asset.is_native)
      };
    });

    let totalFiat = 0;
    let listHtml = '';
    currentWalletTokens = tokensToShow;

    tokensToShow.forEach(t => {
      const fiatValue = t.balance * (t.price || 0);
      totalFiat += fiatValue;
      if (t.balance === 0 && tokensToShow.length > 1 && t.isNative) return;

      const priceText = t.price ? `$${t.price.toFixed(4)}` : 'Цена не загружена';
      listHtml += `
        <div class="token-row" style="display: flex; justify-content: space-between; align-items: center; padding: 1rem 0.5rem; border-radius: 12px; transition: background 0.2s; cursor: pointer;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #fff; padding: 4px; display: flex; justify-content: center; align-items: center;">
                    <img src="${t.iconUrl}" alt="${t.symbol}" style="max-width: 100%; max-height: 100%; border-radius: 50%;">
                </div>
                <div>
                    <div style="color: #fff; font-weight: 600; font-size: 1.05rem; line-height: 1.2;">${t.symbol}</div>
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">${t.name}</div>
                    <div style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">${priceText}</div>
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
    renderProtocolSections(chainId === 'solana' ? null : (overview.protocols || null));

    let startTimestamp = null;
    const duration = 1000;
    const finalValue = totalFiat;
    const step = (timestamp) => {
      if (!startTimestamp) startTimestamp = timestamp;
      const progress = Math.min((timestamp - startTimestamp) / duration, 1);
      const easing = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
      const currentVal = finalValue * easing;
      rabbyTotalFiat.textContent = currentVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      if (progress < 1) window.requestAnimationFrame(step);
    };
    window.requestAnimationFrame(step);
  }

  async function fetchWalletOverview(address, chainId) {
    try {
      const params = new URLSearchParams({ address });
      if (chainId) params.set('chain_id', chainId);

      const response = await fetch(`/api/wallet/overview?${params.toString()}`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      if (!response.ok) throw new Error('Failed to load wallet overview');
      return await response.json();
    } catch (error) {
      console.error('Wallet overview error:', error);
      return null;
    }
  }

  async function fetchProtocolData(address, chainId) {
    try {
      const params = new URLSearchParams({ address, chain_id: chainId });
      const response = await fetch(`/api/wallet/protocols?${params.toString()}`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      if (!response.ok) throw new Error('Failed to load protocol data');
      return await response.json();
    } catch (error) {
      console.error('Protocol data error:', error);
      return null;
    }
  }

  function renderProtocolSections(protocols) {
    if (!walletDefiSections) return;
    if (!protocols || typeof protocols !== 'object') {
      walletDefiSections.innerHTML = '';
      walletDefiSections.style.display = 'none';
      return;
    }

    const protocolOrder = ['aave', 'gmx'];
    const sections = protocolOrder
      .map((key) => renderProtocolSection(protocols[key]))
      .filter(Boolean)
      .join('');

    walletDefiSections.innerHTML = sections;
    walletDefiSections.style.display = sections ? 'block' : 'none';
  }

  function renderProtocolSection(protocol) {
    if (!protocol || (!protocol.available && !protocol.error)) return '';

    const tokens = Array.isArray(protocol.tokens) ? protocol.tokens : [];
    const loans = Array.isArray(protocol.loans) ? protocol.loans : [];
    const pools = Array.isArray(protocol.pools) ? protocol.pools : [];

    if (!tokens.length && !loans.length && !pools.length && !protocol.error) return '';

    return `
      <div style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
          <h4 style="font-size:1.05rem; color:rgba(255,255,255,0.9); font-weight:600; margin:0;">${escapeHtml(protocol.name || 'Protocol')}</h4>
          <span style="font-size:0.8rem; color:${protocol.available ? '#4ade80' : '#fca5a5'};">${protocol.available ? 'API connected' : 'API unavailable'}</span>
        </div>
        ${protocol.error ? `<div style="color:#fca5a5; font-size:0.85rem; margin-bottom:0.75rem;">${escapeHtml(protocol.error)}</div>` : ''}
        ${renderProtocolGroup('Tokens', tokens, renderProtocolTokenRow)}
        ${renderProtocolGroup('Loans', loans, renderProtocolLoanRow)}
        ${renderProtocolGroup('Pools', pools, renderProtocolPoolRow)}
      </div>
    `;
  }

  function renderProtocolGroup(title, items, rowRenderer) {
    if (!items.length) return '';
    return `
      <div style="margin-bottom:0.85rem;">
        <div style="color:rgba(255,255,255,0.52); font-size:0.8rem; margin-bottom:0.4rem; text-transform:uppercase; letter-spacing:0.04em;">${title}</div>
        <div style="display:flex; flex-direction:column; gap:0.45rem;">
          ${items.map(rowRenderer).join('')}
        </div>
      </div>
    `;
  }

  function renderProtocolTokenRow(item) {
    const value = formatUsd(item.usd_value);
    const balance = formatAmount(item.balance);
    const apy = item.apy ? ` • APY ${formatPercent(item.apy)}` : '';
    const collateral = item.collateral ? ' • collateral' : '';
    return renderInfoRow(item.symbol || item.name, `${balance} ${item.symbol || ''}`, `${value}${apy}${collateral}`, '#4ade80');
  }

  function renderProtocolLoanRow(item) {
    const value = formatUsd(item.usd_value);
    const balance = formatAmount(item.balance);
    const side = item.side ? ` • ${escapeHtml(item.side)}` : '';
    const apy = item.apy ? ` • APR ${formatPercent(item.apy)}` : '';
    const pnl = item.pnl_usd ? ` • PnL ${formatUsd(item.pnl_usd)}` : '';
    return renderInfoRow(item.symbol || item.name, `${balance} ${item.symbol || ''}`, `${value}${side}${apy}${pnl}`, '#fca5a5');
  }

  function renderProtocolPoolRow(item) {
    const metrics = [];
    if (item.tvl_usd) metrics.push(`TVL ${formatUsd(item.tvl_usd)}`);
    if (item.total_liquidity) metrics.push(`liq ${formatAmount(item.total_liquidity)}`);
    if (item.total_borrowed) metrics.push(`borrowed ${formatAmount(item.total_borrowed)}`);
    if (item.apy) metrics.push(`APY ${formatPercent(item.apy)}`);
    if (item.supply_apy) metrics.push(`supply ${formatPercent(item.supply_apy)}`);
    if (item.borrow_apy) metrics.push(`borrow ${formatPercent(item.borrow_apy)}`);
    if (item.long_token || item.short_token) metrics.push(`${escapeHtml(item.long_token || '-')} / ${escapeHtml(item.short_token || '-')}`);
    return renderInfoRow(item.symbol || item.name, item.name || item.symbol || '', metrics.join(' • '), '#93c5fd');
  }

  function renderInfoRow(title, subtitle, meta, accent) {
    return `
      <div style="display:flex; justify-content:space-between; gap:0.75rem; padding:0.75rem; border-radius:12px; background:rgba(255,255,255,0.02);">
        <div>
          <div style="color:#fff; font-weight:600; font-size:0.96rem;">${escapeHtml(title || '')}</div>
          <div style="color:rgba(255,255,255,0.5); font-size:0.82rem;">${escapeHtml(subtitle || '')}</div>
        </div>
        <div style="text-align:right; color:${accent}; font-size:0.82rem; line-height:1.45;">${escapeHtml(meta || '')}</div>
      </div>
    `;
  }

  function formatAmount(value) {
    const number = Number(value || 0);
    return number.toLocaleString('en-US', { maximumFractionDigits: 4 });
  }

  function formatUsd(value) {
    const number = Number(value || 0);
    return '$' + number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function formatPercent(value) {
    const number = Number(value || 0);
    return number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '%';
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  async function connectWalletProvider() {
    if (!window.appWallet || typeof window.appWallet.openModal !== 'function') {
      setWeb3Status('Web3 modal недоступен. Обновите страницу.', true);
      sendStatus.textContent = 'Web3 modal недоступен.';
      sendStatus.style.color = '#ff8e8e';
      return false;
    }

    if (web3Button) {
      web3Button.disabled = true;
      web3Button.textContent = 'Подключаем...';
    }

    setWeb3Status('Открываем модальное окно подключения...');
    window.appWallet.openModal({
      onConnected(session) {
        connectedWalletAddress = session.address;
        connectedWalletChainId = session.chainId;
        setWalletAddress(session.address);
        updateWalletState(session.address, { chainId: session.chainId });
        setWeb3Status(session.linked ? 'Кошелек привязан. Выполняем вход...' : 'Кошелек подключен для работы с DeFi.');

        if (walletSendView.style.display !== 'none') {
          showSendView();
        }
      },
    });

    setTimeout(() => {
      if (web3Button) {
        web3Button.disabled = false;
        web3Button.textContent = 'Подключить Web3';
      }
    }, 300);

    return false;
  }

  function disconnectWalletProvider() {
    if (window.appWallet && typeof window.appWallet.disconnect === 'function') {
      window.appWallet.disconnect();
    }
  }

  window.addEventListener('load', async () => {
    renderProfileWalletList();

    if (window.appWallet && typeof window.appWallet.subscribe === 'function') {
      window.appWallet.subscribe((state) => {
        if (state.connected && state.address) {
          connectedWalletAddress = state.address;
          connectedWalletChainId = state.chainId;
          setWalletAddress(state.address);
          updateWalletState(state.address, { chainId: state.chainId });
          setWeb3Status(state.linked ? 'Кошелек привязан к аккаунту.' : 'Кошелек подключен для работы с DeFi.');
          return;
        }

        connectedWalletAddress = null;
        connectedWalletChainId = null;

        if (profileWallets.length > 0) {
          selectProfileWallet(0);
          setWeb3Status('Показан первый кошелек из профиля.');
        } else if (profileWallet?.address) {
          setWalletAddress(profileWallet.address);
          updateWalletState(profileWallet.address, { chainId: profileWallet.chain_id });
          setWeb3Status('Показан кошелек из профиля.');
        } else {
          setWalletAddress(null);
          updateWalletState(null);
          setWeb3Status('Кошелек не подключен.');
        }
      });
    } else if (profileWallets.length > 0) {
      selectProfileWallet(0);
      setWeb3Status('Показан первый кошелек из профиля.');
    } else if (profileWallet?.address) {
      setWalletAddress(profileWallet.address);
      updateWalletState(profileWallet.address, { chainId: profileWallet.chain_id });
      setWeb3Status('Показан кошелек из профиля.');
    } else {
      updateWalletState(null);
    }
  });

  if (rabbyDisconnectBtn) {
    rabbyDisconnectBtn.addEventListener('click', () => {
      disconnectWalletProvider();
    });
  }

  if (walletNetworkSelect) {
    walletNetworkSelect.addEventListener('change', () => {
      const nextChainId = normalizeChainId(walletNetworkSelect.value) || '0x1';
      syncNetworkSelector(nextChainId);
      if (currentWalletAddress) fetchBalances(currentWalletAddress, nextChainId);
    });
  }

  if (web3Button) {
    web3Button.addEventListener('click', connectWalletProvider);
  }

  const btnRefreshTokens = document.getElementById('btn-refresh-tokens');
  if (btnRefreshTokens) {
    btnRefreshTokens.addEventListener('click', async () => {
      if (!currentWalletAddress) {
        alert('Сначала выберите кошелёк.');
        return;
      }

      btnRefreshTokens.disabled = true;
      btnRefreshTokens.textContent = 'Обновление...';

      try {
        const response = await fetch('/api/wallet/update-token-data', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
          },
          body: JSON.stringify({
            wallet_address: currentWalletAddress,
            chain_id: currentWalletChainId || '0x1',
          }),
          credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('Failed to update token data');

        // Reload assets after a short delay
        setTimeout(() => fetchBalances(currentWalletAddress, selectedProtocolChainId), 2000);
      } catch (e) {
        console.error('Update token data error:', e);
        alert('Ошибка обновления данных токенов.');
      } finally {
        btnRefreshTokens.disabled = false;
        btnRefreshTokens.textContent = 'Обновить';
      }
    });
  }

  syncNetworkSelector(selectedProtocolChainId);
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

  #btn-refresh-tokens:hover:not(:disabled) {
    background: rgba(251, 191, 36, 0.25);
    border-color: rgba(251, 191, 36, 0.5);
    transform: translateY(-1px);
  }

  #btn-refresh-tokens:disabled {
    opacity: 0.6;
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

  .wallet-select-btn {
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 999px;
    padding: 0.5rem 0.9rem;
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.9);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .wallet-select-btn:hover,
  .wallet-select-btn.active {
    border-color: rgba(251,191,36,0.4);
    background: rgba(251,191,36,0.12);
    color: #fbbf24;
  }

  .wallet-network-panel {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.85rem 1rem;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    background:
      linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.015)),
      radial-gradient(circle at top left, rgba(251, 191, 36, 0.12), transparent 45%);
  }

  .wallet-network-panel__label {
    color: rgba(255,255,255,0.92);
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .wallet-network-panel__meta {
    color: rgba(255,255,255,0.58);
    font-size: 0.85rem;
    margin-top: 0.2rem;
  }

  .wallet-network-select {
    min-width: 170px;
    border: 1px solid rgba(251, 191, 36, 0.25);
    border-radius: 10px;
    background: rgba(10, 10, 10, 0.7);
    color: #fff;
    padding: 0.65rem 0.85rem;
    font-size: 0.95rem;
    outline: none;
    cursor: pointer;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
  }

  @media (max-width: 576px) {
    .wallet-network-panel {
      flex-direction: column;
      align-items: stretch;
    }

    .wallet-network-select {
      width: 100%;
      min-width: 0;
    }
  }
</style>
@endpush
