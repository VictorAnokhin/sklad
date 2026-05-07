@php
    $initialWalletAddress = '';
    $initialWalletChainId = '';
    $initialWalletShort = '0x...';
@endphp

<div class="wallet-page" style="max-width: 1240px; margin: 0 auto; padding-bottom: 3rem;">
    <div id="wallet-unconnected">
        <div class="glass-card web3-login-box" style="margin: 0 auto; max-width: 450px;">
            <div class="web3-login-copy text-center">
                <p class="web3-login-eyebrow">WALLET ACCESS</p>
                <h2 class="web3-login-title" style="color: #fff; font-weight: 600;">Подключите кошелек</h2>
                <p class="web3-login-text" style="color: rgba(255,255,255,0.7); margin-bottom: 1.5rem;">MetaMask, Rabby или другой совместимый Web3 кошелек.</p>
            </div>
            <div class="web3-login-actions">
                <button type="button" id="web3-connect-btn" class="web3-connect-btn">Подключить кошелек</button>
                <p id="web3-wallet-address" class="web3-wallet-address text-center" style="display:none; margin-top: 1rem; color: #fbbf24;"></p>
                <p id="web3-status" class="web3-status text-center" style="display:none; margin-top: 0.5rem;"></p>
            </div>
        </div>
    </div>

    <div id="wallet-connected" style="display:none; max-width: 860px; margin: 0 auto;">
        <div class="glass-card rabby-ui" style="border-radius: 20px; overflow: hidden; padding: 0; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
            <div class="rabby-header" style="background: rgba(255,255,255,0.02); padding: 2rem 1.5rem; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.05); position: relative;">
                <button type="button" id="rabby-disconnect-btn" aria-label="Disconnect" style="position: absolute; right: 1rem; top: 1rem; background: none; border: none; color: rgba(255,255,255,0.4); cursor: pointer; font-size: 1.2rem; transition: color 0.2s;">
                    &times;
                </button>

                <div class="rabby-address mb-3" style="font-family: 'Geologica', monospace; font-size: 0.9rem; color: rgba(255,255,255,0.75); background: rgba(0,0,0,0.25); padding: 6px 14px; border-radius: 12px; display: inline-flex; align-items: center; gap: 8px; border: 1px solid rgba(255,255,255,0.05);">
                    <div style="width: 8px; height: 8px; background-color: #10b981; border-radius: 50%; box-shadow: 0 0 8px #10b981;"></div>
                    <span id="rabby-address-text">{{ $initialWalletShort }}</span>
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
                    <button id="btn-open-swap-window" style="width: 52px; height: 52px; border-radius: 16px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; font-size: 1.4rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 3h5v5"></path><path d="M4 20L21 3"></path><path d="M21 16v5h-5"></path><path d="M15 15l6 6"></path><path d="M4 4l5 5"></path></svg>
                    </button>
                    <div style="font-size: 0.85rem; margin-top: 0.5rem; color: rgba(255,255,255,0.7); font-weight: 500;">Swap</div>
                </div>
            </div>

            <div id="wallet-main-view" class="rabby-tokens">
                <div style="padding: 1rem 1.5rem 0.25rem;">
                    <div id="profile-wallet-selector" style="margin-bottom: 1rem;">
                        <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; margin-bottom:0.75rem;">
                            <div id="profile-wallet-list" style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center; flex:1 1 auto; min-width:0;"></div>
                            <button type="button" id="web3-connect-btn-inline" class="web3-connect-btn web3-connect-btn--inline">Подключить кошелек</button>
                        </div>
                    </div>
                    <div class="wallet-network-panel">
                        <div>
                            <div class="wallet-network-panel__meta" id="wallet-network-meta"></div>
                        </div>
                        <select id="wallet-network-select" class="wallet-network-select" aria-label="Select network for DeFi protocols">
                            <option value="">Loading networks...</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem 0.75rem;">
                    <h4 style="font-size: 1.05rem; color: rgba(255,255,255,0.9); font-weight: 600; margin: 0;">Assets</h4>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <button id="btn-refresh-tokens" style="background: rgba(251, 191, 36, 0.15); border: 1px solid rgba(251, 191, 36, 0.3); color: #fbbf24; border-radius: 8px; padding: 0.5rem 1rem; font-size: 0.85rem; cursor: pointer; transition: all 0.2s;">Обновить</button>
                        <button id="btn-open-token-settings" style="background: rgba(59, 130, 246, 0.12); border: 1px solid rgba(59, 130, 246, 0.25); color: #93c5fd; border-radius: 8px; padding: 0.5rem 1rem; font-size: 0.85rem; cursor: pointer; transition: all 0.2s;">Настройка</button>
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
                <div id="receive-address-display" style="background: rgba(0,0,0,0.3); padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.95rem; color: #fff; margin-bottom: 1rem; word-break: break-all;">{{ $initialWalletAddress }}</div>

                <button id="btn-copy-address" style="background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); color: #3b82f6; border-radius: 8px; padding: 8px 16px; cursor: pointer; transition: all 0.2s;">Копировать адрес</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="walletSwapModal" tabindex="-1" aria-labelledby="walletSwapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content wallet-swap-modal">
            <div class="modal-header wallet-swap-modal__header">
                <div>
                    <div class="wallet-swap-modal__eyebrow">1inch route</div>
                    <h5 class="modal-title" id="walletSwapModalLabel">Swap</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body wallet-swap-modal__body">
                <div class="wallet-swap-grid">
                    <div class="wallet-swap-field">
                        <label for="wallet-swap-network">Network</label>
                        <select id="wallet-swap-network"></select>
                    </div>

                    <div class="wallet-swap-field">
                        <label for="wallet-swap-sell-token">You pay</label>
                        <select id="wallet-swap-sell-token"></select>
                    </div>

                    <div class="wallet-swap-field">
                        <label for="wallet-swap-buy-token">You receive</label>
                        <select id="wallet-swap-buy-token"></select>
                    </div>

                    <div class="wallet-swap-field">
                        <label for="wallet-swap-amount">Amount</label>
                        <input id="wallet-swap-amount" type="number" min="0" step="any" placeholder="0.0">
                    </div>
                </div>

                <div class="wallet-swap-quote">
                    <div class="wallet-swap-quote__amount" id="wallet-swap-buy-amount">--</div>
                    <div class="wallet-swap-quote__hint" id="wallet-swap-quote-hint">Enter an amount to preview the current quote.</div>
                    <div class="wallet-swap-quote__meta">
                        <div><span>Commission</span><strong id="wallet-swap-commission">0%</strong></div>
                        <div><span>Network fee</span><strong id="wallet-swap-network-fee">--</strong></div>
                    </div>
                </div>

                <div class="wallet-swap-status" id="wallet-swap-status">Ready.</div>

                <div class="wallet-swap-actions">
                    <button type="button" class="wallet-swap-btn wallet-swap-btn--muted" id="wallet-swap-connect-btn">Connect wallet</button>
                    <button type="button" class="wallet-swap-btn wallet-swap-btn--primary" id="wallet-swap-submit-btn">Approve and swap</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="walletTokenSettingsModal" tabindex="-1" aria-labelledby="walletTokenSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content wallet-settings-modal">
            <div class="modal-header wallet-settings-modal__header">
                <div>
                    <div class="wallet-settings-modal__eyebrow">Wallet tokens</div>
                    <h5 class="modal-title" id="walletTokenSettingsModalLabel">Настройка токенов</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body wallet-settings-modal__body">
                <div id="wallet-token-settings-status" class="wallet-settings-status">Загрузка списка токенов...</div>
                <div class="wallet-settings-search">
                    <input type="text" id="wallet-token-settings-search" placeholder="Поиск по адресу токена в списке или через Alchemy">
                </div>
                <div id="wallet-token-settings-list" class="wallet-settings-list"></div>
                <div class="wallet-settings-actions">
                    <button type="button" class="wallet-settings-btn wallet-settings-btn--primary" id="btn-save-token-settings">Сохранить</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
  const web3Button = document.getElementById('web3-connect-btn');
  const web3ConnectBtnInline = document.getElementById('web3-connect-btn-inline');
  const web3Status = document.getElementById('web3-status');
  const web3WalletAddress = document.getElementById('web3-wallet-address');
  const isAuthenticated = @json(\Illuminate\Support\Facades\Auth::check());
  const walletLinkChallengeUrl = '{{ route('wallet.challenge') }}';
  const walletLinkUrl = '{{ route('wallet.link') }}';
  const walletUnlinkUrl = '{{ route('wallet.unlink') }}';

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

  const fallbackNetworkMeta = {
    '0x1': { name: 'Ethereum', native_symbol: 'ETH', native_name: 'Ethereum', icon_url: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg', supports_swap: true, supports_protocols: true },
    '0x38': { name: 'BNB Chain', native_symbol: 'BNB', native_name: 'BNB', icon_url: 'https://cryptologos.cc/logos/bnb-bnb-logo.svg', supports_swap: true, supports_protocols: true },
    '0x89': { name: 'Polygon', native_symbol: 'POL', native_name: 'Polygon', icon_url: 'https://cryptologos.cc/logos/polygon-matic-logo.svg', supports_swap: true, supports_protocols: true },
    '0xa': { name: 'Optimism', native_symbol: 'ETH', native_name: 'Ethereum', icon_url: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg', supports_swap: true, supports_protocols: true },
    '0x2105': { name: 'Base', native_symbol: 'ETH', native_name: 'Ethereum', icon_url: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg', supports_swap: true, supports_protocols: true },
    '0xa4b1': { name: 'Arbitrum', native_symbol: 'ETH', native_name: 'Ethereum', icon_url: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg', supports_swap: true, supports_protocols: true },
    '0xa86a': { name: 'Avalanche', native_symbol: 'AVAX', native_name: 'Avalanche', icon_url: 'https://cryptologos.cc/logos/avalanche-avax-logo.svg', supports_swap: true, supports_protocols: true },
    'solana': { name: 'Solana', native_symbol: 'SOL', native_name: 'Solana', icon_url: 'https://cryptologos.cc/logos/solana-sol-logo.svg', supports_swap: false, supports_protocols: false },
  };

  const initialWeb3Catalog = {!! json_encode($web3Catalog ?? ['items' => [], 'networks' => []]) !!};
  let dbTokens = [];
  let COMMON_NETWORKS = {};

  function buildNetworkConfig(network) {
    const chainId = normalizeChainId(network?.chain_id);
    if (!chainId) return null;

    const fallback = fallbackNetworkMeta[chainId] || {
      name: String(network?.name || chainId).toUpperCase(),
      native_symbol: 'TOKEN',
      native_name: 'Token',
      icon_url: 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
      supports_swap: chainId !== 'solana',
      supports_protocols: chainId !== 'solana',
    };

    return {
      name: network?.name || fallback.name,
      native: {
        symbol: network?.native_symbol || fallback.native_symbol,
        name: network?.native_name || fallback.native_name,
        iconUrl: network?.icon_url || fallback.icon_url,
        price: 0,
      },
      iconUrl: network?.icon_url || fallback.icon_url,
      supports_swap: Boolean(network?.supports_swap ?? fallback.supports_swap),
      supports_protocols: Boolean(network?.supports_protocols ?? fallback.supports_protocols),
      tokens: [],
    };
  }

  function setWeb3Catalog(catalog) {
    const nextTokens = Array.isArray(catalog?.items) ? catalog.items : [];
    const nextNetworks = Array.isArray(catalog?.networks) ? catalog.networks : [];
    const nextMap = {};

    nextNetworks.forEach((network) => {
      const chainId = normalizeChainId(network?.chain_id);
      const config = buildNetworkConfig(network);
      if (!chainId || !config) return;
      nextMap[chainId] = config;
    });

    nextTokens.forEach((token) => {
      const chainId = normalizeChainId(token?.chain_id);
      const address = typeof token?.address === 'string' ? token.address.trim() : '';
      if (!chainId) return;

      if (!nextMap[chainId]) {
        const fallbackConfig = buildNetworkConfig({ chain_id: chainId });
        if (fallbackConfig) {
          nextMap[chainId] = fallbackConfig;
        }
      }

      if (!nextMap[chainId]) return;
      if (chainId !== 'solana' && address !== '' && !isEvmAddress(address)) return;

      nextMap[chainId].tokens.push({
        address,
        symbol: token.symbol,
        name: token.name || token.symbol,
        decimals: parseInt(token.decimals, 10) || (chainId === 'solana' ? 9 : 18),
        iconUrl: nextMap[chainId].iconUrl || fallbackNetworkMeta[chainId]?.icon_url || 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
        price: 0,
        chain_id: chainId,
        commission: Number(token.commission || 0),
      });
    });

    dbTokens = nextTokens.map((token) => ({
      ...token,
      chain_id: normalizeChainId(token.chain_id),
      address: typeof token.address === 'string' ? token.address.toLowerCase() : '',
      commission: Number(token.commission || 0),
    }));
    COMMON_NETWORKS = nextMap;
  }

  function configuredChainIds() {
    return Object.keys(COMMON_NETWORKS);
  }

  function configuredNetworkEntries() {
    return Object.entries(COMMON_NETWORKS);
  }

  function renderWalletNetworkOptions() {
    if (!walletNetworkSelect) return;

    const entries = configuredNetworkEntries();
    if (!entries.length) {
      walletNetworkSelect.innerHTML = '<option value="">No settings/web3 networks</option>';
      walletNetworkSelect.disabled = true;
      return;
    }

    walletNetworkSelect.disabled = false;
    walletNetworkSelect.innerHTML = entries
      .map(([chainId, config]) => `<option value="${escapeHtml(chainId)}">${escapeHtml(config.name)}</option>`)
      .join('');

    const preferredChainId = configuredChainIds().includes(selectedProtocolChainId)
      ? selectedProtocolChainId
      : configuredChainIds()[0];

    if (preferredChainId) {
      walletNetworkSelect.value = preferredChainId;
      selectedProtocolChainId = preferredChainId;
    }
  }

  async function loadWeb3Catalog(force = false) {
    if (!force && dbTokens.length && configuredChainIds().length) {
      renderWalletNetworkOptions();
      return;
    }

    const response = await fetch('/api/wallet/tokens', {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(payload?.message || 'Failed to load settings/web3 catalog.');
    }

    setWeb3Catalog(payload);
    renderWalletNetworkOptions();
  }

  setWeb3Catalog(initialWeb3Catalog);

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
  let selectedProtocolChainId = normalizeChainId(profileWallet?.chain_id || connectedWalletChainId || configuredChainIds()[0] || '0x1') || '0x1';
  let activeBalanceFetchKey = null;
  let activeBalanceFetchId = 0;
  let totalFiatAnimationFrameId = null;

  function buildWalletLabel(wallet) {
    if (!wallet || !wallet.address) return 'Неизвестный кошелек';
    const walletChainId = normalizeChainId(wallet.chain_id || wallet.network);
    const networkName = COMMON_NETWORKS[walletChainId || '']?.name || walletChainId || wallet.network || 'Chain';
    return `${shortenAddress(wallet.address)} · ${networkName}`;
  }

  function replaceProfileWallets(wallets) {
    const nextWallets = Array.isArray(wallets) ? wallets : [];
    profileWallets.splice(0, profileWallets.length, ...nextWallets.map((wallet) => ({
      ...wallet,
      chain_id: normalizeChainId(wallet?.chain_id || wallet?.network),
    })));
    renderProfileWalletList();
  }

  function upsertProfileWallet(address, chainId, walletType = 'evm') {
    if (!address) return;

    const normalizedAddress = String(address).toLowerCase();
    const normalizedChainId = normalizeChainId(chainId) || (walletType === 'solana' ? 'solana' : '0x1');
    const existingIndex = profileWallets.findIndex((wallet) => String(wallet?.address || '').toLowerCase() === normalizedAddress);
    const nextWallet = {
      address: normalizedAddress,
      network: normalizedChainId,
      chain_id: normalizedChainId,
      connected_at: new Date().toISOString(),
    };

    if (existingIndex >= 0) {
      profileWallets.splice(existingIndex, 1);
    }

    profileWallets.unshift(nextWallet);
    currentWalletIndex = 0;
    renderProfileWalletList();
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
          <span class="wallet-select-btn__label">${buildWalletLabel(wallet)}</span>
          <span class="wallet-select-remove" data-remove-index="${index}" aria-label="Отвязать кошелек" role="button" tabindex="0">
            &times;
          </span>
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

    listContainer.querySelectorAll('.wallet-select-remove').forEach((button) => {
      button.addEventListener('click', async (event) => {
        event.preventDefault();
        event.stopPropagation();
        const index = Number(button.dataset.removeIndex);
        if (!Number.isNaN(index)) {
          await unlinkProfileWallet(index);
        }
      });

      button.addEventListener('keydown', async (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
          return;
        }

        event.preventDefault();
        event.stopPropagation();
        const index = Number(button.dataset.removeIndex);
        if (!Number.isNaN(index)) {
          await unlinkProfileWallet(index);
        }
      });
    });
  }

  async function unlinkProfileWallet(index) {
    if (!isAuthenticated) {
      alert('Отвязка доступна только для авторизованного пользователя.');
      return;
    }

    const wallet = profileWallets[index];
    if (!wallet?.address) {
      return;
    }

    const confirmed = window.confirm(`Отвязать кошелек ${shortenAddress(wallet.address)}?`);
    if (!confirmed) {
      return;
    }

    const response = await fetch(walletUnlinkUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
      },
      body: JSON.stringify({
        address: wallet.address,
      }),
      credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
      alert(payload?.message || 'Не удалось отвязать кошелек.');
      return;
    }

    profileWallets.splice(index, 1);

    if (profileWallets.length === 0) {
      currentWalletIndex = 0;
      if (connectedWalletAddress && String(connectedWalletAddress).toLowerCase() !== String(wallet.address).toLowerCase()) {
        updateWalletState(connectedWalletAddress, { chainId: connectedWalletChainId, forceReload: true });
      } else {
        updateWalletState(null);
      }
      renderProfileWalletList();
      return;
    }

    if (index < currentWalletIndex) {
      currentWalletIndex -= 1;
    }

    if (currentWalletIndex >= profileWallets.length) {
      currentWalletIndex = profileWallets.length - 1;
    }

    renderProfileWalletList();

    const activeWallet = profileWallets[currentWalletIndex];
    if (activeWallet?.address) {
      selectProfileWallet(currentWalletIndex);
    }
  }

  function selectProfileWallet(index) {
    if (!Array.isArray(profileWallets) || profileWallets.length === 0) return;
    const normalizedIndex = Math.max(0, Math.min(index, profileWallets.length - 1));
    currentWalletIndex = normalizedIndex;
    const wallet = profileWallets[currentWalletIndex];
    if (!wallet) return;
    const walletChainId = normalizeChainId(wallet.chain_id || wallet.network) || selectedProtocolChainId || configuredChainIds()[0] || '0x1';

    renderProfileWalletList();
    updateWalletState(wallet.address, { chainId: walletChainId });

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
        chain_id: walletChainId,
      }),
      credentials: 'same-origin',
    }).catch(e => console.error('Failed to update token data:', e));
  }

  function preferredProfileWalletView() {
    if (!Array.isArray(profileWallets) || profileWallets.length === 0) {
      return null;
    }

    const wallet = profileWallets[Math.max(0, Math.min(currentWalletIndex, profileWallets.length - 1))] || profileWallets[0];
    if (!wallet?.address) {
      return null;
    }

    return {
      address: wallet.address,
      chainId: normalizeChainId(wallet.chain_id || wallet.network) || selectedProtocolChainId || configuredChainIds()[0] || '0x1',
    };
  }

  function describeProtocolAvailability(chainId) {
    const network = COMMON_NETWORKS[chainId];
    if (!network) return 'Сеть не настроена в settings/web3';
    if (network.supports_protocols) return 'Tokens from Alchemy + DeFi from Zerion';
    if (chainId === 'solana') return 'Configured network';
    return 'Tokens only';
  }

  const ALCHEMY_CHAIN_BY_HEX = {
    '0x1': 'eth',
    '0xa4b1': 'arbitrum',
    '0x89': 'polygon',
    '0x38': 'bsc',
  };

  function syncNetworkSelector(chainId) {
    const allowedChainIds = configuredChainIds();
    const normalized = normalizeChainId(chainId);
    const resolved = normalized && allowedChainIds.includes(normalized)
      ? normalized
      : allowedChainIds[0] || normalized || '0x1';
    selectedProtocolChainId = resolved;

    if (walletNetworkSelect && walletNetworkSelect.value !== resolved) walletNetworkSelect.value = resolved;
    if (walletNetworkMeta) {
      const networkName = COMMON_NETWORKS[resolved]?.name || 'Unknown network';
      walletNetworkMeta.textContent = `${networkName} • ${describeProtocolAvailability(resolved)}`;
    }
  }

  function updateWalletState(address, options = {}) {
    const previousAddress = currentWalletAddress;
    const previousChainId = currentWalletChainId;
    const requestedChainId = normalizeChainId(
      options.chainId
      || selectedProtocolChainId
      || currentWalletChainId
      || profileWallet?.chain_id
      || profileWallet?.network
      || configuredChainIds()[0]
      || '0x1'
    ) || '0x1';
    if (address) {
      currentWalletAddress = address;
      currentWalletChainId = requestedChainId;
      unconnectedUi.style.display = 'none';
      rabbyUi.style.display = 'block';
      rabbyAddressText.textContent = shortenAddress(address);
      document.getElementById('receive-address-display').textContent = address;
      syncNetworkSelector(requestedChainId);
      showMainView();
      if (previousAddress !== address || previousChainId !== requestedChainId || options.forceReload === true) {
        fetchBalances(address, requestedChainId);
      }
      return;
    }

    currentWalletAddress = null;
    currentWalletChainId = null;
    activeBalanceFetchKey = null;
    activeBalanceFetchId += 1;
    if (totalFiatAnimationFrameId) {
      window.cancelAnimationFrame(totalFiatAnimationFrameId);
      totalFiatAnimationFrameId = null;
    }
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
  const btnOpenSwapWindow = document.getElementById('btn-open-swap-window');
  const btnSubmitSend = document.getElementById('btn-submit-send');
  const sendTokenSelect = document.getElementById('send-token-select');
  const sendToAddress = document.getElementById('send-to-address');
  const sendAmount = document.getElementById('send-amount');
  const sendStatus = document.getElementById('send-status');
  const walletSwapModalElement = document.getElementById('walletSwapModal');
  const walletSwapNetworkSelect = document.getElementById('wallet-swap-network');
  const walletSwapSellTokenSelect = document.getElementById('wallet-swap-sell-token');
  const walletSwapBuyTokenSelect = document.getElementById('wallet-swap-buy-token');
  const walletSwapAmountInput = document.getElementById('wallet-swap-amount');
  const walletSwapBuyAmount = document.getElementById('wallet-swap-buy-amount');
  const walletSwapQuoteHint = document.getElementById('wallet-swap-quote-hint');
  const walletSwapCommission = document.getElementById('wallet-swap-commission');
  const walletSwapNetworkFee = document.getElementById('wallet-swap-network-fee');
  const walletSwapStatus = document.getElementById('wallet-swap-status');
  const walletSwapConnectBtn = document.getElementById('wallet-swap-connect-btn');
  const walletSwapSubmitBtn = document.getElementById('wallet-swap-submit-btn');
  const walletSwapModal = walletSwapModalElement && window.bootstrap ? new bootstrap.Modal(walletSwapModalElement) : null;
  const btnOpenTokenSettings = document.getElementById('btn-open-token-settings');
  const btnSaveTokenSettings = document.getElementById('btn-save-token-settings');
  const walletTokenSettingsModalElement = document.getElementById('walletTokenSettingsModal');
  const walletTokenSettingsModal = walletTokenSettingsModalElement && window.bootstrap ? new bootstrap.Modal(walletTokenSettingsModalElement) : null;
  const walletTokenSettingsStatus = document.getElementById('wallet-token-settings-status');
  const walletTokenSettingsList = document.getElementById('wallet-token-settings-list');
  const walletTokenSettingsSearch = document.getElementById('wallet-token-settings-search');
  let walletSwapTokens = [];
  let walletSwapSellTokens = [];
  let walletSwapPricePreview = null;
  let walletSwapQuotePreview = null;
  let walletSwapPreviewTimeoutId = null;
  let walletTokenSettingsItems = [];
  let walletTokenSettingsQuery = '';
  let walletTokenSettingsSearchTimeoutId = null;
  let walletTokenSettingsSearchRequestId = 0;

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
  if (btnOpenSwapWindow) {
    btnOpenSwapWindow.addEventListener('click', () => {
      const chainId = normalizeChainId(selectedProtocolChainId || currentWalletChainId || '0xa4b1') || '0xa4b1';
      if (chainId === 'solana') {
        alert('Swap currently supports EVM networks only.');
        return;
      }

      if (!walletSwapModal) {
        alert('Swap modal is unavailable. Refresh the page.');
        return;
      }

      walletSwapModal.show();
      if (walletSwapNetworkSelect) {
        walletSwapNetworkSelect.value = chainId;
      }
      void initializeWalletSwapModal(chainId);
    });
  }

  function setWalletSwapStatus(message, tone = 'neutral') {
    if (!walletSwapStatus) return;
    walletSwapStatus.textContent = message;
    walletSwapStatus.className = tone === 'error'
      ? 'wallet-swap-status is-error'
      : tone === 'success'
        ? 'wallet-swap-status is-success'
        : 'wallet-swap-status';
  }

  function walletSwapActiveAddress() {
    return connectedWalletAddress || currentWalletAddress || '';
  }

  function walletSwapActiveChainId() {
    const swapChainIds = [...new Set(walletSwapTokens.map((token) => normalizeChainId(token.chain_id)).filter(Boolean))];
    const normalized = normalizeChainId(walletSwapNetworkSelect?.value || selectedProtocolChainId || currentWalletChainId || swapChainIds[0] || configuredChainIds()[0] || '0xa4b1');
    return swapChainIds.includes(normalized)
      ? normalized
      : (swapChainIds[0] || configuredChainIds()[0] || normalized || '0xa4b1');
  }

  function walletSwapChainName(chainId) {
    return COMMON_NETWORKS[chainId]?.name || chainId || 'Unknown';
  }

  function walletSwapNativeSymbol(chainId) {
    return COMMON_NETWORKS[chainId]?.native?.symbol || 'ETH';
  }

  function walletSwapSelectedSellToken() {
    return walletSwapSellTokens.find((token) => token.address === walletSwapSellTokenSelect?.value) || null;
  }

  function walletSwapSelectedBuyToken() {
    return walletSwapTokens.find((token) => token.address === walletSwapBuyTokenSelect?.value && token.chain_id === walletSwapActiveChainId()) || null;
  }

  function toBaseUnitsDecimal(value, decimals) {
    const normalized = String(value || '').trim().replace(',', '.');
    if (!/^\d+(\.\d+)?$/.test(normalized)) {
      throw new Error('Введите корректную сумму.');
    }

    const [whole, fraction = ''] = normalized.split('.');
    const paddedFraction = (fraction + '0'.repeat(decimals)).slice(0, decimals);
    return (whole + paddedFraction).replace(/^0+/, '') || '0';
  }

  function formatUnitsDecimal(rawAmount, decimals, precision = 8) {
    const value = Number(rawAmount || 0) / Math.pow(10, decimals);
    if (!Number.isFinite(value)) {
      return '--';
    }

    return value.toLocaleString('en-US', {
      maximumFractionDigits: Math.min(decimals, precision),
    });
  }

  function resetWalletSwapPreview() {
    walletSwapPricePreview = null;
    walletSwapQuotePreview = null;
    if (walletSwapBuyAmount) walletSwapBuyAmount.textContent = '--';
    if (walletSwapQuoteHint) walletSwapQuoteHint.textContent = 'Enter an amount to preview the current quote.';
    if (walletSwapNetworkFee) walletSwapNetworkFee.textContent = '--';
  }

  function renderWalletSwapNetworkOptions() {
    if (!walletSwapNetworkSelect) return;
    const chainIds = [...new Set(walletSwapTokens.map((token) => normalizeChainId(token.chain_id)).filter(Boolean))];
    walletSwapNetworkSelect.innerHTML = chainIds
      .map((chainId) => `<option value="${escapeHtml(chainId)}">${escapeHtml(walletSwapChainName(chainId))}</option>`)
      .join('');

    const preferredChainId = walletSwapActiveChainId();
    if (chainIds.includes(preferredChainId)) {
      walletSwapNetworkSelect.value = preferredChainId;
    } else if (chainIds[0]) {
      walletSwapNetworkSelect.value = chainIds[0];
    }
  }

  async function loadWalletSwapTokens(chainId) {
    const targetWallet = walletSwapActiveAddress();
    if (!targetWallet) {
      walletSwapTokens = [];
      renderWalletSwapNetworkOptions();
      return;
    }

    const payload = await fetchWalletPortfolioTokens(targetWallet, false);
    if (!payload || !Array.isArray(payload.result)) {
      throw new Error('Failed to load selected wallet tokens for swap.');
    }

    walletSwapTokens = payload.result
      .filter((token) => Boolean(token.token_address))
      .map((token) => ({
        address: String(token.token_address || '').toLowerCase(),
        symbol: token.symbol || 'TOKEN',
        name: token.name || token.symbol || 'Token',
        decimals: Number(token.decimals || 18),
        chain_id: Object.entries(ALCHEMY_CHAIN_BY_HEX).find(([, slug]) => slug === String(token.chain || '').toLowerCase())?.[0] || null,
        commission: Number(token.commission || 0),
      }))
      .filter((token) => token.chain_id);

    if (walletSwapNetworkSelect) {
      const normalized = normalizeChainId(chainId);
      const swapChainIds = walletSwapTokens.map((token) => token.chain_id);
      if (normalized && swapChainIds.includes(normalized)) {
        walletSwapNetworkSelect.value = normalized;
      }
    }
    renderWalletSwapNetworkOptions();
  }

  async function loadWalletSwapSellTokens(chainId) {
    const targetWallet = walletSwapActiveAddress();
    if (!targetWallet) {
      walletSwapSellTokens = [];
      if (walletSwapSellTokenSelect) {
        walletSwapSellTokenSelect.innerHTML = '<option value="">Connect wallet first</option>';
      }
      return;
    }

    const payload = await fetchWalletPortfolioTokens(targetWallet, false);
    if (!payload || !Array.isArray(payload.result)) {
      throw new Error('Failed to load wallet assets for swap.');
    }

    const targetChain = String(ALCHEMY_CHAIN_BY_HEX[chainId] || '').toLowerCase();
    walletSwapSellTokens = payload.result
      .filter((asset) => asset.token_address && String(asset.chain || '').toLowerCase() === targetChain && Number(asset.balance || 0) > 0)
      .map((asset) => {
        const normalizedAddress = String(asset.token_address).toLowerCase();
        const configuredToken = walletSwapTokens.find((token) => token.chain_id === chainId && token.address === normalizedAddress);

        return {
          address: normalizedAddress,
          symbol: asset.symbol,
          name: asset.name,
          decimals: Number(asset.decimals || configuredToken?.decimals || 18),
          balance: Number(asset.balance || 0),
          commission: Number(asset.commission ?? (configuredToken?.commission || 0)),
        };
      });

    if (walletSwapSellTokenSelect) {
      walletSwapSellTokenSelect.innerHTML = walletSwapSellTokens.length
        ? walletSwapSellTokens.map((token) => (
            `<option value="${escapeHtml(token.address)}">${escapeHtml(token.symbol)} · ${escapeHtml(formatAmount(token.balance))}</option>`
          )).join('')
        : '<option value="">No available sell tokens</option>';
    }
  }

  function syncWalletSwapBuyTokens() {
    const chainId = walletSwapActiveChainId();
    const buyTokens = walletSwapTokens.filter((token) => token.chain_id === chainId);
    const selectedSellToken = walletSwapSelectedSellToken();

    if (!walletSwapBuyTokenSelect) return;

    walletSwapBuyTokenSelect.innerHTML = buyTokens.length
      ? buyTokens.map((token) => `<option value="${escapeHtml(token.address)}">${escapeHtml(token.symbol)} · ${escapeHtml(token.name)}</option>`).join('')
      : '<option value="">No buy tokens configured</option>';

    const nextBuyToken = buyTokens.find((token) => token.address !== selectedSellToken?.address) || buyTokens[0] || null;
    walletSwapBuyTokenSelect.value = nextBuyToken?.address || '';
  }

  function syncWalletSwapCommission() {
    const selectedSellToken = walletSwapSelectedSellToken();
    if (walletSwapCommission) {
      walletSwapCommission.textContent = `${Number(selectedSellToken?.commission || 0).toFixed(4)}%`;
    }
  }

  async function refreshWalletSwapPreview() {
    const selectedSellToken = walletSwapSelectedSellToken();
    const selectedBuyToken = walletSwapSelectedBuyToken();

    if (!selectedSellToken || !selectedBuyToken) {
      resetWalletSwapPreview();
      syncWalletSwapCommission();
      return;
    }

    syncWalletSwapCommission();

    const amountValue = String(walletSwapAmountInput?.value || '').trim();
    if (!amountValue) {
      resetWalletSwapPreview();
      setWalletSwapStatus('Ready.');
      return;
    }

    let sellAmount;
    try {
      sellAmount = toBaseUnitsDecimal(amountValue, Number(selectedSellToken.decimals || 18));
    } catch (error) {
      resetWalletSwapPreview();
      setWalletSwapStatus(error instanceof Error ? error.message : 'Invalid amount.', 'error');
      return;
    }

    if (sellAmount === '0') {
      resetWalletSwapPreview();
      return;
    }

    try {
      setWalletSwapStatus('Fetching 1inch quote...');
      const price = await postJson('/api/wallet/swap/price', {
        chain_id: walletSwapActiveChainId(),
        sell_token: selectedSellToken.address,
        buy_token: selectedBuyToken.address,
        sell_amount: sellAmount,
        address: walletSwapActiveAddress(),
      });

      walletSwapPricePreview = price;
      const dstAmount = price.dstAmount ?? price.toTokenAmount ?? '0';
      if (walletSwapBuyAmount) {
        walletSwapBuyAmount.textContent = `${formatUnitsDecimal(dstAmount, Number(selectedBuyToken.decimals || 18))} ${selectedBuyToken.symbol}`;
      }
      if (walletSwapQuoteHint) {
        walletSwapQuoteHint.textContent = `${walletSwapChainName(walletSwapActiveChainId())} · live preview via 1inch`;
      }
      if (walletSwapCommission) {
        walletSwapCommission.textContent = `${Number(price.meta?.commission_percent ?? selectedSellToken.commission ?? 0).toFixed(4)}%`;
      }
      if (walletSwapNetworkFee) {
        walletSwapNetworkFee.textContent = '--';
      }
      setWalletSwapStatus('Quote is ready.', 'success');
    } catch (error) {
      resetWalletSwapPreview();
      setWalletSwapStatus(error instanceof Error ? error.message : 'Failed to fetch swap quote.', 'error');
    }
  }

  function scheduleWalletSwapPreview() {
    if (walletSwapPreviewTimeoutId) {
      clearTimeout(walletSwapPreviewTimeoutId);
    }

    walletSwapPreviewTimeoutId = setTimeout(() => {
      void refreshWalletSwapPreview();
    }, 350);
  }

  async function initializeWalletSwapModal(preferredChainId = null) {
    const chainId = normalizeChainId(preferredChainId || selectedProtocolChainId || currentWalletChainId || configuredChainIds()[0] || '0xa4b1') || '0xa4b1';

    try {
      setWalletSwapStatus('Loading swap configuration...');
      await loadWalletSwapTokens(chainId);
      if (walletSwapNetworkSelect) {
        walletSwapNetworkSelect.value = chainId;
      }
      await loadWalletSwapSellTokens(walletSwapActiveChainId());
      syncWalletSwapBuyTokens();
      syncWalletSwapCommission();
      resetWalletSwapPreview();
      setWalletSwapStatus(walletSwapActiveAddress() ? 'Ready.' : 'Connect wallet to swap.');
    } catch (error) {
      setWalletSwapStatus(error instanceof Error ? error.message : 'Failed to initialize swap.', 'error');
    }
  }

  async function submitWalletSwap() {
    if (!connectedWalletAddress) {
      const connected = await connectWalletProvider();
      if (!connectedWalletAddress && connected === false) {
        throw new Error('Connect wallet first.');
      }
    }

    if (!window.ethereum || !connectedWalletAddress) {
      throw new Error('Injected EVM wallet is unavailable.');
    }

    const selectedSellToken = walletSwapSelectedSellToken();
    const selectedBuyToken = walletSwapSelectedBuyToken();

    if (!selectedSellToken || !selectedBuyToken) {
      throw new Error('Choose both sell and buy tokens.');
    }

    const sellAmount = toBaseUnitsDecimal(walletSwapAmountInput?.value || '', Number(selectedSellToken.decimals || 18));
    const chainId = walletSwapActiveChainId();
    const quotePayload = {
      chain_id: chainId,
      sell_token: selectedSellToken.address,
      buy_token: selectedBuyToken.address,
      sell_amount: sellAmount,
      taker: connectedWalletAddress,
      slippage_bps: 100,
    };

    setWalletSwapStatus('Preparing executable quote...');
    let quote = await postJson('/api/wallet/swap/quote', quotePayload);

    walletSwapQuotePreview = quote;
    if (walletSwapNetworkFee) {
      walletSwapNetworkFee.textContent = quote.totalNetworkFee
        ? `${formatUnitsDecimal(quote.totalNetworkFee, 18, 6)} ${walletSwapNativeSymbol(chainId)}`
        : '--';
    }

    const normalizedCurrentChain = normalizeChainId(await window.ethereum.request({ method: 'eth_chainId' }));
    if (normalizedCurrentChain !== chainId) {
      await window.ethereum.request({
        method: 'wallet_switchEthereumChain',
        params: [{ chainId }],
      });
    }

    if (quote.approval_required && quote.approve_tx?.to && quote.approve_tx?.data) {
      setWalletSwapStatus('Confirm approve transaction in wallet...');
      const approveHash = await window.ethereum.request({
        method: 'eth_sendTransaction',
        params: [{
          from: connectedWalletAddress,
          to: quote.approve_tx.to,
          data: quote.approve_tx.data,
          value: quote.approve_tx.value && quote.approve_tx.value !== '0'
            ? '0x' + BigInt(quote.approve_tx.value).toString(16)
            : undefined,
          gas: quote.approve_tx.gas ? '0x' + BigInt(quote.approve_tx.gas).toString(16) : undefined,
          gasPrice: quote.approve_tx.gasPrice ? '0x' + BigInt(quote.approve_tx.gasPrice).toString(16) : undefined,
        }],
      });

      if (approveHash) {
        await waitForReceipt(approveHash);
      }

      setWalletSwapStatus('Refreshing executable quote after approval...');
      quote = await postJson('/api/wallet/swap/quote', quotePayload);
      walletSwapQuotePreview = quote;
    }

    if (!quote.tx?.to || !quote.tx?.data) {
      throw new Error('Swap transaction payload is missing.');
    }

    setWalletSwapStatus('Confirm swap transaction in wallet...');
    const txHash = await window.ethereum.request({
      method: 'eth_sendTransaction',
      params: [{
        from: connectedWalletAddress,
        to: quote.tx.to,
        data: quote.tx.data,
        value: quote.tx.value && quote.tx.value !== '0'
          ? '0x' + BigInt(quote.tx.value).toString(16)
          : undefined,
        gas: quote.tx.gas ? '0x' + BigInt(quote.tx.gas).toString(16) : undefined,
        gasPrice: quote.tx.gasPrice ? '0x' + BigInt(quote.tx.gasPrice).toString(16) : undefined,
      }],
    });

    if (txHash) {
      setWalletSwapStatus(`Swap submitted: ${shortenAddress(txHash)}`, 'success');
    }
  }

  walletSwapModalElement?.addEventListener('shown.bs.modal', () => {
    void initializeWalletSwapModal();
  });

  walletSwapNetworkSelect?.addEventListener('change', async () => {
    try {
      setWalletSwapStatus('Refreshing assets...');
      await loadWalletSwapSellTokens(walletSwapActiveChainId());
      syncWalletSwapBuyTokens();
      scheduleWalletSwapPreview();
    } catch (error) {
      setWalletSwapStatus(error instanceof Error ? error.message : 'Failed to refresh assets.', 'error');
    }
  });

  walletSwapSellTokenSelect?.addEventListener('change', () => {
    syncWalletSwapBuyTokens();
    scheduleWalletSwapPreview();
  });

  walletSwapBuyTokenSelect?.addEventListener('change', () => {
    scheduleWalletSwapPreview();
  });

  walletSwapAmountInput?.addEventListener('input', () => {
    scheduleWalletSwapPreview();
  });

  walletSwapConnectBtn?.addEventListener('click', async () => {
    try {
      await connectWalletProvider();
      await initializeWalletSwapModal(walletSwapActiveChainId());
      setWalletSwapStatus(connectedWalletAddress ? 'Wallet connected.' : 'Connect wallet to continue.', connectedWalletAddress ? 'success' : 'neutral');
    } catch (error) {
      setWalletSwapStatus(error instanceof Error ? error.message : 'Wallet connection failed.', 'error');
    }
  });

  walletSwapSubmitBtn?.addEventListener('click', async () => {
    if (!walletSwapSubmitBtn) return;

    walletSwapSubmitBtn.disabled = true;
    try {
      await submitWalletSwap();
    } catch (error) {
      setWalletSwapStatus(error instanceof Error ? error.message : 'Swap execution failed.', 'error');
    } finally {
      walletSwapSubmitBtn.disabled = false;
    }
  });

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

  async function fetchBalances(address, preferredChainId = null, refresh = false) {
    const fetchId = ++activeBalanceFetchId;
    rabbyTokensList.innerHTML = '<div class="text-center py-4" style="color: rgba(255,255,255,0.5);">Scanning real network values...</div>';
    walletDefiSections.innerHTML = '';
    walletDefiSections.style.display = 'none';
    const requestedChainId = normalizeChainId(preferredChainId || currentWalletChainId || configuredChainIds()[0] || '0x1');
    const chainId = configuredChainIds().includes(requestedChainId) ? requestedChainId : (configuredChainIds()[0] || requestedChainId || '0x1');
    const fetchKey = `${String(address).toLowerCase()}::${chainId}`;
    activeBalanceFetchKey = fetchKey;
    currentWalletChainId = chainId;
    syncNetworkSelector(chainId);

    const tokensPayload = await fetchWalletPortfolioTokens(address, refresh);
    if (fetchId !== activeBalanceFetchId || activeBalanceFetchKey !== fetchKey) {
      return;
    }
    if (!tokensPayload || tokensPayload.error) {
      rabbyTokensList.innerHTML = `<div class="text-center py-4" style="color:#fca5a5;">${escapeHtml(tokensPayload?.error || 'Не удалось загрузить токены кошелька через Alchemy.')}</div>`;
      return;
    }

    const errorText = tokensPayload?.error || 'Не удалось загрузить активы кошелька.';
    const networkCfg = COMMON_NETWORKS[chainId] || { tokens: [] };
    const alchemyChain = ALCHEMY_CHAIN_BY_HEX[chainId] || null;
    const assets = Array.isArray(tokensPayload.result)
      ? tokensPayload.result.filter((asset) => {
          if (!alchemyChain) return false;
          return String(asset.chain || '').toLowerCase() === alchemyChain;
        })
      : [];
    const protocols = chainId === 'solana' ? null : await fetchProtocolData(address, chainId, refresh);

    if (assets.length === 0) {
      currentWalletTokens = [];
      rabbyTokensList.innerHTML = `
        <div class="text-center py-4" style="color: rgba(255,255,255,0.58);">
          ${alchemyChain
            ? 'Alchemy не вернул токены для выбранной сети или баланс по ним нулевой.'
            : 'Выбранная сеть пока не подключена в Alchemy MVP.'}
        </div>
      `;
      renderProtocolSections(protocols);
      rabbyTotalFiat.textContent = '0.00';
      return;
    }

    const tokensToShow = assets.map((asset) => {
      const assetAddress = asset.token_address || asset.tokenAddress || null;
      const configuredToken = assetAddress
        ? networkCfg.tokens.find((token) => chainId === 'solana'
            ? String(token.address) === String(assetAddress)
            : String(token.address).toLowerCase() === String(assetAddress).toLowerCase())
        : null;
      const price = Number(asset.price_usd ?? 0);
      const balance = Number(asset.balance ?? 0);
      const valueUsd = Number(asset.value_usd ?? (balance * price));

      return {
        symbol: asset.symbol || configuredToken?.symbol || 'TOKEN',
        name: asset.name || configuredToken?.name || 'Token',
        balance,
        price,
        valueUsd,
        iconUrl: asset.logo || configuredToken?.iconUrl || 'https://cryptologos.cc/logos/ethereum-eth-logo.svg',
        address: assetAddress || configuredToken?.address || null,
        decimals: configuredToken?.decimals ?? 18,
        isNative: !assetAddress,
        isSpam: Boolean(asset.is_spam)
      };
    });

    let totalFiat = 0;
    let listHtml = '';
    currentWalletTokens = tokensToShow;

    tokensToShow.forEach(t => {
      const fiatValue = Number.isFinite(t.valueUsd) ? t.valueUsd : (t.balance * (t.price || 0));
      totalFiat += fiatValue;

      const priceText = t.price ? `$${t.price.toFixed(4)}` : 'Цена не загружена';
      const addressText = t.address || 'Token';
      const badgeText = t.isSpam ? '<span style="display:inline-block; margin-left:0.45rem; padding:0.12rem 0.42rem; border-radius:999px; background:rgba(248,113,113,0.12); color:#fca5a5; font-size:0.64rem; font-weight:700; letter-spacing:0.06em;">SPAM</span>' : '';
      listHtml += `
        <div class="token-row" style="display: flex; justify-content: space-between; align-items: center; gap: 1.25rem; padding: 1rem 0.5rem; border-radius: 12px; transition: background 0.2s; cursor: pointer;">
            <div style="display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1 1 auto;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #fff; padding: 4px; display: flex; justify-content: center; align-items: center;">
                    <img src="${t.iconUrl}" alt="${t.symbol}" style="max-width: 100%; max-height: 100%; border-radius: 50%;">
                </div>
                <div style="min-width: 0; flex: 1 1 auto;">
                    <div style="color: #fff; font-weight: 600; font-size: 1.05rem; line-height: 1.2;">${t.symbol}${badgeText}</div>
                    <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">${t.name}</div>
                    <div style="color: rgba(255,255,255,0.42); font-size: 0.72rem; font-family: monospace; word-break: break-all; overflow-wrap: anywhere;">${escapeHtml(addressText)}</div>
                    <div style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">${priceText}</div>
                </div>
            </div>
            <div style="text-align: right; flex: 0 0 auto;">
                <div style="color: #fff; font-weight: 600; font-size: 1.05rem; line-height: 1.2;">$${fiatValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                <div style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">${t.balance.toLocaleString('en-US', {maximumFractionDigits: 4})} ${t.symbol}</div>
            </div>
        </div>
      `;
    });

    const defiTotal = chainId === 'solana'
      ? 0
      : Object.values(protocols || {}).reduce((sum, protocol) => sum + protocolSectionTotal(protocol), 0);

    totalFiat += defiTotal;

    if (fetchId !== activeBalanceFetchId || activeBalanceFetchKey !== fetchKey) {
      return;
    }

    rabbyTokensList.innerHTML = listHtml;
    renderProtocolSections(protocols);

    if (totalFiatAnimationFrameId) {
      window.cancelAnimationFrame(totalFiatAnimationFrameId);
      totalFiatAnimationFrameId = null;
    }

    let startTimestamp = null;
    const duration = 1000;
    const finalValue = totalFiat;
    const step = (timestamp) => {
      if (fetchId !== activeBalanceFetchId || activeBalanceFetchKey !== fetchKey) {
        return;
      }

      if (!startTimestamp) startTimestamp = timestamp;
      const progress = Math.min((timestamp - startTimestamp) / duration, 1);
      const easing = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
      const currentVal = finalValue * easing;
      rabbyTotalFiat.textContent = currentVal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      if (progress < 1) {
        totalFiatAnimationFrameId = window.requestAnimationFrame(step);
      } else {
        totalFiatAnimationFrameId = null;
      }
    };
    totalFiatAnimationFrameId = window.requestAnimationFrame(step);
  }

  async function fetchWalletPortfolioTokens(address, refresh = false) {
    try {
      const params = new URLSearchParams();
      if (refresh) params.set('refresh', '1');

      const response = await fetch(`/api/wallet/${encodeURIComponent(address)}/tokens${params.toString() ? `?${params.toString()}` : ''}`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok) {
        throw new Error(payload?.message || 'Failed to load wallet tokens');
      }
      return payload;
    } catch (error) {
      console.error('Wallet portfolio error:', error);
      return {
        error: error instanceof Error ? error.message : 'Failed to load wallet tokens',
      };
    }
  }

  async function fetchWalletTokenSettings(address, chain, refresh = false) {
    try {
      const params = new URLSearchParams({
        include_unselected: '1',
        include_spam: '1',
      });
      if (refresh) params.set('refresh', '1');

      const response = await fetch(`/api/wallet/${encodeURIComponent(address)}/tokens/settings?${params.toString()}`, {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
      });
      if (!response.ok) throw new Error('Failed to load token settings');
      const payload = await response.json();
      const targetChain = (ALCHEMY_CHAIN_BY_HEX[chain] || '').toLowerCase();
      payload.result = Array.isArray(payload.result)
        ? payload.result.filter((item) => String(item.chain || '').toLowerCase() === targetChain)
        : [];
      return payload;
    } catch (error) {
      console.error('Wallet token settings error:', error);
      return null;
    }
  }

  function tokenSelectionKey(token) {
    return [
      String(token.chain || '').toLowerCase(),
      String(token.token_address || 'native').toLowerCase(),
      String(token.symbol || '').trim().toLowerCase(),
      String(token.name || '').trim().toLowerCase(),
    ].join(':');
  }

  function setWalletTokenSettingsStatus(message, tone = 'neutral') {
    if (!walletTokenSettingsStatus) return;
    walletTokenSettingsStatus.textContent = message;
    walletTokenSettingsStatus.className = tone === 'error'
      ? 'wallet-settings-status is-error'
      : tone === 'success'
        ? 'wallet-settings-status is-success'
        : 'wallet-settings-status';
  }

  function renderWalletTokenSettingsList() {
    if (!walletTokenSettingsList) return;

    const filteredItems = walletTokenSettingsItems.filter((token) => {
      if (!walletTokenSettingsQuery) return true;
      const address = String(token.token_address || '').toLowerCase();
      return address.includes(walletTokenSettingsQuery);
    });

    if (!filteredItems.length) {
      walletTokenSettingsList.innerHTML = '<div class="wallet-settings-empty">Для этой сети токены не найдены.</div>';
      return;
    }

    walletTokenSettingsList.innerHTML = filteredItems.map((token) => {
      const index = walletTokenSettingsItems.findIndex((item) => item.key === token.key);
      const symbol = escapeHtml(token.symbol || 'TOKEN');
      const name = escapeHtml(token.name || 'Token');
      const address = escapeHtml(token.token_address || 'Native');
      const value = Number(token.value_usd || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      const commission = Number(token.commission || 0).toFixed(4);
      return `
        <div class="wallet-settings-item">
          <input type="checkbox" data-index="${index}" ${token.is_selected ? 'checked' : ''}>
          <div class="wallet-settings-item__body">
            <div class="wallet-settings-item__title">${symbol}</div>
            <div class="wallet-settings-item__name">${name}</div>
            <div class="wallet-settings-item__meta">${address} · $${value}</div>
            <div class="wallet-settings-item__commission">
              <span>Комиссия %</span>
              <input type="number" min="0" max="3" step="0.0001" value="${commission}" data-commission-index="${index}">
            </div>
          </div>
        </div>
      `;
    }).join('');

    walletTokenSettingsList.querySelectorAll('input[type="checkbox"]').forEach((input) => {
      input.addEventListener('change', () => {
        const index = Number(input.dataset.index);
        if (!Number.isNaN(index) && walletTokenSettingsItems[index]) {
          walletTokenSettingsItems[index].is_selected = input.checked;
        }
      });
    });

    walletTokenSettingsList.querySelectorAll('input[data-commission-index]').forEach((input) => {
      input.addEventListener('input', () => {
        const index = Number(input.dataset.commissionIndex);
        if (!Number.isNaN(index) && walletTokenSettingsItems[index]) {
          walletTokenSettingsItems[index].commission = input.value;
        }
      });
    });
  }

  function mergeWalletTokenSettingsItem(token) {
    const nextItem = {
      ...token,
      key: tokenSelectionKey(token),
      is_selected: Boolean(token.is_selected),
    };
    const existingIndex = walletTokenSettingsItems.findIndex((item) => item.key === nextItem.key);
    if (existingIndex >= 0) {
      walletTokenSettingsItems[existingIndex] = {
        ...walletTokenSettingsItems[existingIndex],
        ...nextItem,
      };
      return;
    }

    walletTokenSettingsItems.unshift(nextItem);
  }

  async function searchWalletTokenSettingsViaAlchemy(tokenAddress) {
    if (!currentWalletAddress) return;

    const chainId = normalizeChainId(selectedProtocolChainId || currentWalletChainId || configuredChainIds()[0] || '0x1') || '0x1';
    const chainSlug = ALCHEMY_CHAIN_BY_HEX[chainId];
    if (!chainSlug || !isEvmAddress(tokenAddress)) {
      return;
    }

    const requestId = ++walletTokenSettingsSearchRequestId;
    setWalletTokenSettingsStatus('Ищем токен через Alchemy...');

    try {
      const params = new URLSearchParams({
        chain: chainSlug,
        token_address: tokenAddress,
      });
      const response = await fetch(`/api/wallet/${encodeURIComponent(currentWalletAddress)}/tokens/search?${params.toString()}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });
      const payload = await response.json().catch(() => ({}));

      if (requestId !== walletTokenSettingsSearchRequestId) {
        return;
      }

      if (!response.ok || !payload?.result) {
        setWalletTokenSettingsStatus(payload?.message || 'Токен не найден в Alchemy.', 'error');
        return;
      }

      mergeWalletTokenSettingsItem(payload.result);
      renderWalletTokenSettingsList();
      setWalletTokenSettingsStatus('Токен найден через Alchemy.');
    } catch (error) {
      if (requestId !== walletTokenSettingsSearchRequestId) {
        return;
      }
      setWalletTokenSettingsStatus('Не удалось выполнить поиск токена через Alchemy.', 'error');
    }
  }

  async function openWalletTokenSettings() {
    if (!currentWalletAddress) {
      alert('Сначала выберите кошелёк.');
      return;
    }
    if (!walletTokenSettingsModal) {
      alert('Модальное окно настройки недоступно.');
      return;
    }

    walletTokenSettingsModal.show();
    setWalletTokenSettingsStatus('Загрузка списка токенов...');
    walletTokenSettingsList.innerHTML = '';
    walletTokenSettingsSearchRequestId += 1;
    if (walletTokenSettingsSearchTimeoutId) {
      clearTimeout(walletTokenSettingsSearchTimeoutId);
      walletTokenSettingsSearchTimeoutId = null;
    }

    const chainId = normalizeChainId(selectedProtocolChainId || currentWalletChainId || configuredChainIds()[0] || '0x1') || '0x1';
    const payload = await fetchWalletTokenSettings(currentWalletAddress, chainId, true);
    if (!payload) {
      setWalletTokenSettingsStatus('Не удалось загрузить список токенов.', 'error');
      return;
    }

    walletTokenSettingsItems = Array.isArray(payload.result)
      ? payload.result.map((token) => ({
          ...token,
          key: tokenSelectionKey(token),
          is_selected: Boolean(token.is_selected),
        }))
      : [];
    walletTokenSettingsQuery = '';
    if (walletTokenSettingsSearch) {
      walletTokenSettingsSearch.value = '';
    }

    renderWalletTokenSettingsList();
    setWalletTokenSettingsStatus('Отметьте токены, которые нужно показывать.');
  }

  async function saveWalletTokenSettings() {
    if (!currentWalletAddress) {
      return;
    }

    const chainId = normalizeChainId(selectedProtocolChainId || currentWalletChainId || configuredChainIds()[0] || '0x1') || '0x1';
    const chainSlug = ALCHEMY_CHAIN_BY_HEX[chainId];
    if (!chainSlug) {
      setWalletTokenSettingsStatus('Для этой сети настройка недоступна.', 'error');
      return;
    }

    const selectedKeys = walletTokenSettingsItems
      .filter((token) => token.is_selected)
      .map((token) => token.key);
    const commissions = walletTokenSettingsItems.reduce((acc, token) => {
      acc[token.key] = token.commission ?? 0;
      return acc;
    }, {});

    setWalletTokenSettingsStatus('Сохраняем...');

    const response = await fetch(`/api/wallet/${encodeURIComponent(currentWalletAddress)}/tokens/settings`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
      },
      body: JSON.stringify({
        chain: chainSlug,
        selected_keys: selectedKeys,
        commissions,
      }),
      credentials: 'same-origin',
    });

    if (!response.ok) {
      setWalletTokenSettingsStatus('Не удалось сохранить выбор токенов.', 'error');
      return;
    }

    setWalletTokenSettingsStatus('Настройки сохранены.', 'success');
    await fetchBalances(currentWalletAddress, chainId);
    setTimeout(() => {
      walletTokenSettingsModal.hide();
    }, 400);
  }

  async function fetchProtocolData(address, chainId, refresh = false) {
    try {
      const params = new URLSearchParams({ address, chain_id: chainId });
      if (refresh) params.set('refresh', '1');
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

    const sections = Object.values(protocols)
      .sort((left, right) => {
        const leftTotal = protocolSectionTotal(left);
        const rightTotal = protocolSectionTotal(right);
        return rightTotal - leftTotal;
      })
      .map((protocol) => renderProtocolSection(protocol))
      .filter(Boolean)
      .join('');

    walletDefiSections.innerHTML = sections;
    walletDefiSections.style.display = sections ? 'block' : 'none';
  }

  function protocolSectionTotal(protocol) {
    if (!protocol || typeof protocol !== 'object') return 0;

    const tokens = Array.isArray(protocol.tokens) ? protocol.tokens : [];
    const loans = Array.isArray(protocol.loans) ? protocol.loans : [];
    const pools = Array.isArray(protocol.pools) ? protocol.pools : [];

    const tokensTotal = tokens.reduce((sum, item) => sum + Number(item?.usd_value || 0), 0);
    const poolsTotal = pools.reduce((sum, item) => sum + Number(item?.usd_value || 0), 0);
    const loansTotal = loans.reduce((sum, item) => sum + Number(item?.usd_value || 0), 0);

    return tokensTotal + poolsTotal - loansTotal;
  }

  function renderProtocolSection(protocol) {
    if (!protocol || (!protocol.available && !protocol.error)) return '';

    const tokens = Array.isArray(protocol.tokens) ? protocol.tokens : [];
    const loans = Array.isArray(protocol.loans) ? protocol.loans : [];
    const pools = Array.isArray(protocol.pools) ? protocol.pools : [];
    const protocolLink = typeof protocol.url === 'string' ? protocol.url : '';
    const protocolIcon = typeof protocol.icon === 'string' ? protocol.icon : '';

    if (!tokens.length && !loans.length && !pools.length && !protocol.error) return '';

    return `
      <div style="margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.75rem;">
          <div style="display:flex; align-items:center; gap:0.65rem;">
            ${protocolIcon ? `<img src="${escapeHtml(protocolIcon)}" alt="${escapeHtml(protocol.name || 'Protocol')}" style="width:28px; height:28px; border-radius:999px; object-fit:cover; background:rgba(255,255,255,0.04);">` : ''}
            <h4 style="font-size:1.05rem; color:rgba(255,255,255,0.9); font-weight:600; margin:0;">${escapeHtml(protocol.name || 'Protocol')}</h4>
          </div>
          <div style="display:flex; align-items:center; gap:0.6rem;">
            ${protocolLink ? `
              <a
                href="${escapeHtml(protocolLink)}"
                target="_blank"
                rel="noreferrer"
                style="display:inline-flex; align-items:center; gap:0.35rem; padding:0.38rem 0.7rem; border-radius:999px; background:rgba(59,130,246,0.12); border:1px solid rgba(96,165,250,0.25); color:#93c5fd; font-size:0.76rem; font-weight:600; text-decoration:none;"
              >
                Open protocol
                <span aria-hidden="true">↗</span>
              </a>
            ` : ''}
            <span style="font-size:0.8rem; color:${protocol.available ? '#4ade80' : '#fca5a5'};">${protocol.available ? 'API connected' : 'API unavailable'}</span>
          </div>
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
    return renderInfoRow(
      item.symbol || item.name,
      `${balance} ${item.symbol || ''}`,
      `${value}${apy}${collateral}`,
      '#4ade80',
      protocolItemDetails(item)
    );
  }

  function renderProtocolLoanRow(item) {
    const value = formatUsd(item.usd_value);
    const balance = formatAmount(item.balance);
    const side = item.side ? ` • ${escapeHtml(item.side)}` : '';
    const apy = item.apy ? ` • APR ${formatPercent(item.apy)}` : '';
    const pnl = item.pnl_usd ? ` • PnL ${formatUsd(item.pnl_usd)}` : '';
    return renderInfoRow(
      item.symbol || item.name,
      `${balance} ${item.symbol || ''}`,
      `${value}${side}${apy}${pnl}`,
      '#fca5a5',
      protocolItemDetails(item)
    );
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
    return renderInfoRow(
      item.symbol || item.name,
      item.name || item.symbol || '',
      metrics.join(' • '),
      '#93c5fd',
      protocolItemDetails(item)
    );
  }

  function renderInfoRow(title, subtitle, meta, accent, details = []) {
    const detailRows = Array.isArray(details)
      ? details
          .filter((entry) => entry && entry.label && entry.value)
          .map((entry) => `
            <div style="display:flex; justify-content:space-between; gap:0.75rem; padding:0.3rem 0; border-top:1px solid rgba(255,255,255,0.05);">
              <span style="color:rgba(255,255,255,0.5);">${escapeHtml(entry.label)}</span>
              <span style="color:rgba(255,255,255,0.9); text-align:right;">${entry.isHtml ? entry.value : escapeHtml(entry.value)}</span>
            </div>
          `)
          .join('')
      : '';

    const isExpandable = detailRows !== '';

    if (!isExpandable) {
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

    return `
      <details style="border-radius:12px; background:rgba(255,255,255,0.02);">
        <summary style="list-style:none; cursor:${isExpandable ? 'pointer' : 'default'}; display:flex; justify-content:space-between; gap:0.75rem; padding:0.75rem; border-radius:12px;">
          <div>
            <div style="color:#fff; font-weight:600; font-size:0.96rem;">${escapeHtml(title || '')}</div>
            <div style="color:rgba(255,255,255,0.5); font-size:0.82rem;">${escapeHtml(subtitle || '')}</div>
          </div>
          <div style="display:flex; align-items:center; gap:0.6rem;">
            <div style="text-align:right; color:${accent}; font-size:0.82rem; line-height:1.45;">${escapeHtml(meta || '')}</div>
            <span style="color:rgba(255,255,255,0.38); font-size:0.8rem;">Details</span>
          </div>
        </summary>
        <div style="padding:0 0.75rem 0.75rem 0.75rem; font-size:0.78rem; line-height:1.45;">${detailRows}</div>
      </details>
    `;
  }

  function protocolItemDetails(item) {
    const rows = [];

    if (item.chain) rows.push({ label: 'Chain', value: String(item.chain).toUpperCase() });
    if (item.position_type) rows.push({ label: 'Position type', value: item.position_type });
    if (item.protocol_module) rows.push({ label: 'Module', value: item.protocol_module });
    if (item.link) {
      rows.push({
        label: 'Protocol',
        value: `<a href="${escapeHtml(item.link)}" target="_blank" rel="noreferrer" style="color:#93c5fd; text-decoration:none;">Open dApp ↗</a>`,
        isHtml: true,
      });
    }

    return rows;
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

  function injectedEvmProvider() {
    if (window.ethereum) {
      return window.ethereum;
    }

    return null;
  }

  function dispatchWalletStateChange(detail) {
    window.dispatchEvent(new CustomEvent('app-wallet-state-changed', {
      detail,
    }));
  }

  async function signWalletPayload(provider, walletType, address, message) {
    const normalizedType = walletType === 'solana' ? 'solana' : 'evm';

    if (window.appWallet && typeof window.appWallet.signMessage === 'function') {
      return window.appWallet.signMessage({
        provider,
        walletType: normalizedType,
        address,
        message,
      });
    }

    if (normalizedType === 'solana') {
      if (!provider || typeof provider.signMessage !== 'function') {
        throw new Error('Solana-кошелек не поддерживает подпись сообщения.');
      }

      const encoded = new TextEncoder().encode(message);
      const signed = await provider.signMessage(encoded, 'utf8');
      const signatureBytes = signed?.signature || signed;

      if (!signatureBytes) {
        throw new Error('Solana-кошелек не вернул подпись.');
      }

      let binary = '';
      new Uint8Array(signatureBytes).forEach((byte) => {
        binary += String.fromCharCode(byte);
      });

      return window.btoa(binary);
    }

    if (!provider || typeof provider.request !== 'function') {
      throw new Error('EVM-кошелек недоступен для подписи сообщения.');
    }

    return provider.request({
      method: 'personal_sign',
      params: [message, address],
    });
  }

  async function ensureWalletLinked(address, chainId, provider, walletType = 'evm') {
    if (!isAuthenticated) {
      return { linked: false, skipped: true };
    }

    const normalizedType = walletType === 'solana' ? 'solana' : 'evm';
    const network = normalizedType === 'solana'
      ? 'solana'
      : (normalizeChainId(chainId) || '0x1');

    const challenge = await postJson(walletLinkChallengeUrl, {
      address,
      wallet_type: normalizedType,
    });

    const signature = await signWalletPayload(provider, normalizedType, address, challenge.message);
    const result = await postJson(walletLinkUrl, {
      address,
      signature,
      network,
      wallet_type: normalizedType,
    });

    if (Array.isArray(result?.user?.wallets)) {
      replaceProfileWallets(result.user.wallets);
    } else {
      upsertProfileWallet(address, chainId, normalizedType);
    }

    return { linked: true, user: result?.user || null };
  }

  function setConnectWalletButtonsBusy(busy) {
    const label = busy ? 'Подключаем...' : 'Подключить кошелек';
    [web3Button, web3ConnectBtnInline].forEach((btn) => {
      if (!btn) return;
      btn.disabled = busy;
      btn.textContent = label;
    });
  }

  async function connectWalletProvider() {
    setConnectWalletButtonsBusy(true);

    try {
      const provider = injectedEvmProvider();
      if (provider && typeof provider.request === 'function') {
        setWeb3Status('Запрашиваем подключение кошелька...');
        const accounts = await provider.request({ method: 'eth_requestAccounts' });
        const address = Array.isArray(accounts) ? accounts[0] : '';
        const chainId = normalizeChainId(await provider.request({ method: 'eth_chainId' })) || '0x1';

        if (!address) {
          throw new Error('Кошелек не вернул адрес для подключения.');
        }

        const linkResult = await ensureWalletLinked(address, chainId, provider, 'evm');
        connectedWalletAddress = address;
        connectedWalletChainId = chainId;
        setWalletAddress(address);
        updateWalletState(address, { chainId });
        dispatchWalletStateChange({
          provider,
          address,
          chainId,
          walletType: 'evm',
          linked: Boolean(linkResult.linked),
          connected: true,
        });
        setWeb3Status(linkResult.linked ? 'Кошелек подключен и добавлен к аккаунту.' : 'EVM кошелек подключен.');

        if (walletSendView.style.display !== 'none') {
          showSendView();
        }

        return true;
      }

      if (window.appWallet && typeof window.appWallet.openModal === 'function') {
        setWeb3Status('Открываем модальное окно подключения...');
        window.appWallet.openModal({
          async onConnected(session) {
            const walletType = session.walletType === 'solana' ? 'solana' : 'evm';
            const linkResult = await ensureWalletLinked(session.address, session.chainId, session.provider, walletType);
            connectedWalletAddress = session.address;
            connectedWalletChainId = session.chainId;
            setWalletAddress(session.address);
            updateWalletState(session.address, { chainId: session.chainId });
            dispatchWalletStateChange({
              provider: session.provider || null,
              address: session.address,
              chainId: session.chainId,
              walletType,
              linked: Boolean(linkResult.linked),
              connected: true,
            });
            setWeb3Status(linkResult.linked ? 'Кошелек подключен и добавлен к аккаунту.' : 'Кошелек подключен для работы с DeFi.');

            if (walletSendView.style.display !== 'none') {
              showSendView();
            }
          },
        });

        return false;
      }

      throw new Error('Web3 кошелек не найден. Установите MetaMask, Rabby или совместимый Solana wallet.');
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Не удалось подключить Web3 кошелек.';
      setWeb3Status(message, true);
      if (sendStatus) {
        sendStatus.textContent = message;
        sendStatus.style.color = '#ff8e8e';
      }
      return false;
    } finally {
      setConnectWalletButtonsBusy(false);
    }
  }

  function disconnectWalletProvider() {
    if (window.appWallet && typeof window.appWallet.disconnect === 'function') {
      window.appWallet.disconnect();
      return;
    }

    connectedWalletAddress = null;
    connectedWalletChainId = null;
    setWalletAddress(null);
    updateWalletState(null);
    dispatchWalletStateChange({
      provider: null,
      address: null,
      chainId: null,
      walletType: null,
      linked: false,
      connected: false,
    });
    setWeb3Status('Кошелек не подключен.');
  }

  function applyConnectedWalletState(address, chainId, linked = false) {
    connectedWalletAddress = address;
    connectedWalletChainId = chainId;
    if (isAuthenticated) {
      upsertProfileWallet(address, chainId, chainId === 'solana' ? 'solana' : 'evm');
    }
    setWalletAddress(address);
    updateWalletState(address, { chainId });
    dispatchWalletStateChange({
      provider: injectedEvmProvider(),
      address,
      chainId,
      walletType: chainId === 'solana' ? 'solana' : 'evm',
      linked: Boolean(linked),
      connected: true,
    });
    setWeb3Status(linked ? 'Кошелек привязан к аккаунту.' : 'Кошелек подключен для работы с DeFi.');
  }

  function applyDisconnectedWalletState() {
    connectedWalletAddress = null;
    connectedWalletChainId = null;
    setWalletAddress(null);
    const fallbackWallet = preferredProfileWalletView();
    if (fallbackWallet?.address) {
      updateWalletState(fallbackWallet.address, { chainId: fallbackWallet.chainId, forceReload: true });
    } else {
      updateWalletState(null);
    }
    dispatchWalletStateChange({
      provider: null,
      address: null,
      chainId: null,
      walletType: null,
      linked: false,
      connected: false,
    });
    setWeb3Status(fallbackWallet?.address
      ? 'Кошелек отключен. Доступен просмотр активов по адресу из профиля.'
      : 'Кошелек не подключен.');
  }

  function attachInjectedWalletListeners() {
    const provider = injectedEvmProvider();
    if (!provider || typeof provider.on !== 'function') {
      return;
    }

    provider.on('accountsChanged', (accounts) => {
      const address = Array.isArray(accounts) ? accounts[0] : '';
      if (address) {
        const chainId = connectedWalletChainId || currentWalletChainId || configuredChainIds()[0] || '0x1';
        applyConnectedWalletState(address, chainId, false);
        setWeb3Status('EVM кошелек обновлен.');
        return;
      }

      disconnectWalletProvider();
    });

    provider.on('chainChanged', (chainId) => {
      const normalizedChainId = normalizeChainId(chainId) || '0x1';
      connectedWalletChainId = normalizedChainId;

      if (connectedWalletAddress) {
        applyConnectedWalletState(connectedWalletAddress, normalizedChainId, false);
        setWeb3Status('Сеть EVM кошелька обновлена.');
      }
    });
  }

  function syncFromAppWalletState(state) {
    if (state?.connected && state?.address) {
      applyConnectedWalletState(state.address, state.chainId, Boolean(state.linked));
      return true;
    }

    applyDisconnectedWalletState();
    return false;
  }

  function attachAppWalletBridge() {
    if (!window.appWallet || typeof window.appWallet.subscribe !== 'function') {
      return false;
    }

    if (typeof window.appWallet.getState === 'function') {
      try {
        const state = window.appWallet.getState();
        if (state) {
          syncFromAppWalletState(state);
        }
      } catch (error) {
        console.error('App wallet state read error:', error);
      }
    }

    window.appWallet.subscribe((state) => {
      syncFromAppWalletState(state);
    });

    return true;
  }

  function waitForAppWalletBridge(attempt = 0) {
    if (attachAppWalletBridge()) {
      return;
    }

    if (attempt >= 20) {
      return;
    }

    window.setTimeout(() => {
      waitForAppWalletBridge(attempt + 1);
    }, 250);
  }

  async function initWalletPage() {
    try {
      await loadWeb3Catalog(true);
    } catch (error) {
      console.error('Web3 catalog error:', error);
      if (walletNetworkMeta) {
        walletNetworkMeta.textContent = 'Failed to load settings/web3 catalog';
      }
    }

    renderProfileWalletList();
    attachInjectedWalletListeners();

    const fallbackWallet = preferredProfileWalletView();
    if (fallbackWallet?.address) {
      updateWalletState(fallbackWallet.address, { chainId: fallbackWallet.chainId, forceReload: true });
      setWeb3Status('Открыт просмотр активов по адресу кошелька из профиля.');
    }

    if (attachAppWalletBridge()) {
      return;
    }

    waitForAppWalletBridge();
    if (!fallbackWallet?.address) {
      updateWalletState(null);
      setWeb3Status('Кошелек не подключен.');
    }
  }

  if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', () => {
      void initWalletPage();
    }, { once: true });
  } else {
    void initWalletPage();
  }

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
  if (web3ConnectBtnInline) {
    web3ConnectBtnInline.addEventListener('click', connectWalletProvider);
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
        await fetchBalances(currentWalletAddress, selectedProtocolChainId, true);
      } catch (e) {
        console.error('Update token data error:', e);
        alert('Ошибка обновления данных токенов.');
      } finally {
        btnRefreshTokens.disabled = false;
        btnRefreshTokens.textContent = 'Обновить';
      }
    });
  }

  if (btnOpenTokenSettings) {
    btnOpenTokenSettings.addEventListener('click', () => {
      void openWalletTokenSettings();
    });
  }

  if (btnSaveTokenSettings) {
    btnSaveTokenSettings.addEventListener('click', () => {
      void saveWalletTokenSettings();
    });
  }

  if (walletTokenSettingsSearch) {
    walletTokenSettingsSearch.addEventListener('input', () => {
      walletTokenSettingsQuery = walletTokenSettingsSearch.value.trim().toLowerCase();
      renderWalletTokenSettingsList();

      if (walletTokenSettingsSearchTimeoutId) {
        clearTimeout(walletTokenSettingsSearchTimeoutId);
      }

      if (!isEvmAddress(walletTokenSettingsQuery)) {
        walletTokenSettingsSearchRequestId += 1;
        return;
      }

      walletTokenSettingsSearchTimeoutId = setTimeout(() => {
        void searchWalletTokenSettingsViaAlchemy(walletTokenSettingsQuery);
      }, 450);
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

  .web3-connect-btn.web3-connect-btn--inline {
    width: auto;
    flex: 0 0 auto;
    min-height: 40px;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
    align-self: center;
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

  @media (max-width: 768px) {
    #wallet-connected {
      max-width: 100% !important;
    }

    .token-row {
      align-items: flex-start;
    }
  }

  .wallet-select-btn {
    min-width: 220px;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 999px;
    padding: 0.45rem 0.45rem 0.45rem 0.95rem;
    background: rgba(255,255,255,0.05);
    color: rgba(255,255,255,0.9);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    text-align: left;
  }

  .wallet-select-btn:hover,
  .wallet-select-btn.active {
    border-color: rgba(251,191,36,0.4);
    background: rgba(251,191,36,0.12);
    color: #fbbf24;
  }

  .wallet-select-btn__label {
    display: inline-block;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .wallet-select-remove {
    width: 32px;
    height: 32px;
    min-width: 32px;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 999px;
    background: rgba(255,255,255,0.04);
    color: rgba(255,255,255,0.7);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1rem;
    line-height: 1;
    padding: 0;
  }

  .wallet-select-btn.active .wallet-select-remove {
    border-color: rgba(248,113,113,0.25);
  }

  .wallet-select-remove:hover {
    border-color: rgba(248,113,113,0.45);
    background: rgba(248,113,113,0.12);
    color: #fca5a5;
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

  .wallet-settings-modal {
    border-radius: 24px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.08);
    background: linear-gradient(180deg, rgba(7,17,31,0.98), rgba(2,6,23,0.99));
    color: #fff;
  }

  .wallet-settings-modal__header {
    padding: 1.2rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.02);
  }

  .wallet-settings-modal__eyebrow {
    color: #93c5fd;
    font-size: 0.72rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    font-weight: 700;
    margin-bottom: 0.3rem;
  }

  .wallet-settings-modal__body {
    padding: 1.4rem;
  }

  .wallet-settings-status {
    padding: 0.85rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
    color: rgba(255,255,255,0.72);
    font-size: 0.92rem;
  }

  .wallet-settings-status.is-error {
    color: #fecaca;
    border-color: rgba(248,113,113,0.32);
    background: rgba(248,113,113,0.08);
  }

  .wallet-settings-status.is-success {
    color: #bbf7d0;
    border-color: rgba(52,211,153,0.28);
    background: rgba(52,211,153,0.08);
  }

  .wallet-settings-list {
    display: grid;
    gap: 0.8rem;
    margin-top: 1rem;
    max-height: 420px;
    overflow: auto;
  }

  .wallet-settings-search {
    margin-top: 1rem;
  }

  .wallet-settings-search input {
    width: 100%;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    background: rgba(255,255,255,0.03);
    color: #fff;
    padding: 0.85rem 1rem;
    outline: none;
  }

  .wallet-settings-item {
    display: flex;
    gap: 0.9rem;
    align-items: flex-start;
    padding: 0.9rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
    cursor: pointer;
  }

  .wallet-settings-item input {
    margin-top: 0.25rem;
  }

  .wallet-settings-item__title {
    color: #fff;
    font-weight: 700;
    font-size: 0.96rem;
  }

  .wallet-settings-item__name {
    color: rgba(255,255,255,0.62);
    font-size: 0.86rem;
    margin-top: 0.15rem;
  }

  .wallet-settings-item__meta {
    color: rgba(255,255,255,0.45);
    font-size: 0.78rem;
    margin-top: 0.2rem;
    word-break: break-all;
  }

  .wallet-settings-item__commission {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.75rem;
  }

  .wallet-settings-item__commission span {
    color: rgba(255,255,255,0.72);
    font-size: 0.8rem;
    min-width: 84px;
  }

  .wallet-settings-item__commission input {
    width: 120px;
    margin-top: 0;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 10px;
    background: rgba(255,255,255,0.04);
    color: #fff;
    padding: 0.45rem 0.65rem;
    outline: none;
  }

  .wallet-settings-empty {
    color: rgba(255,255,255,0.55);
    text-align: center;
    padding: 1.25rem 0;
  }

  .wallet-settings-actions {
    margin-top: 1rem;
    display: flex;
    justify-content: flex-end;
  }

  .wallet-settings-btn {
    border: 0;
    border-radius: 12px;
    padding: 0.85rem 1.2rem;
    font-weight: 700;
    cursor: pointer;
  }

  .wallet-settings-btn--primary {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: #fff;
  }

  .wallet-swap-modal {
    border-radius: 24px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.08);
    background:
      radial-gradient(circle at top left, rgba(56, 189, 248, 0.14), transparent 35%),
      linear-gradient(180deg, rgba(7,17,31,0.98), rgba(2,6,23,0.99));
    color: #fff;
    box-shadow: 0 24px 80px rgba(0,0,0,0.5);
  }

  .wallet-swap-modal__header {
    padding: 1.35rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.02);
  }

  .wallet-swap-modal__header .modal-title {
    font-size: 1.4rem;
    font-weight: 700;
  }

  .wallet-swap-modal__eyebrow {
    color: #7dd3fc;
    font-size: 0.72rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    font-weight: 700;
    margin-bottom: 0.35rem;
  }

  .wallet-swap-modal__body {
    padding: 1.5rem;
  }

  .wallet-swap-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }

  .wallet-swap-field {
    display: grid;
    gap: 0.45rem;
  }

  .wallet-swap-field label {
    color: rgba(255,255,255,0.66);
    font-size: 0.82rem;
    font-weight: 600;
  }

  .wallet-swap-field input,
  .wallet-swap-field select {
    width: 100%;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 14px;
    background: rgba(8,15,28,0.92);
    color: #fff;
    padding: 0.9rem 1rem;
    outline: none;
  }

  .wallet-swap-quote {
    margin-top: 1.25rem;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 18px;
    padding: 1rem 1.1rem;
    background: rgba(255,255,255,0.03);
  }

  .wallet-swap-quote__amount {
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 1.1;
  }

  .wallet-swap-quote__hint {
    margin-top: 0.35rem;
    color: rgba(255,255,255,0.56);
    font-size: 0.88rem;
  }

  .wallet-swap-quote__meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
    margin-top: 1rem;
  }

  .wallet-swap-quote__meta div {
    display: grid;
    gap: 0.25rem;
  }

  .wallet-swap-quote__meta span {
    color: rgba(255,255,255,0.56);
    font-size: 0.76rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  .wallet-swap-quote__meta strong {
    font-size: 0.98rem;
  }

  .wallet-swap-status {
    margin-top: 1rem;
    padding: 0.9rem 1rem;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
    color: rgba(255,255,255,0.72);
    font-size: 0.92rem;
  }

  .wallet-swap-status.is-error {
    color: #fecaca;
    border-color: rgba(248,113,113,0.32);
    background: rgba(248,113,113,0.08);
  }

  .wallet-swap-status.is-success {
    color: #bbf7d0;
    border-color: rgba(52,211,153,0.28);
    background: rgba(52,211,153,0.08);
  }

  .wallet-swap-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.9rem;
    margin-top: 1rem;
  }

  .wallet-swap-btn {
    border: 0;
    border-radius: 14px;
    padding: 0.95rem 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s ease, opacity 0.2s ease;
  }

  .wallet-swap-btn:hover:not(:disabled) {
    transform: translateY(-1px);
  }

  .wallet-swap-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .wallet-swap-btn--muted {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.08);
    color: #fff;
  }

  .wallet-swap-btn--primary {
    background: linear-gradient(135deg, #38bdf8, #0ea5e9);
    color: #00111a;
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

    .wallet-swap-grid,
    .wallet-swap-quote__meta,
    .wallet-swap-actions {
      grid-template-columns: 1fr;
    }
  }
</style>
@endpush
