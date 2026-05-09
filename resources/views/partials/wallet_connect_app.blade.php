{{-- Web3 modal + window.appWallet: кнопка входа — на странице «Кошелек». --}}
<div class="modal fade" id="wallet-connect-modal" tabindex="-1" aria-labelledby="wallet-connect-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered wallet-modal-dialog">
    <div class="modal-content wallet-modal-content">
      <div class="modal-header wallet-modal-header border-0">
        <div>
          <p class="wallet-modal-eyebrow">WEB3 ACCESS</p>
          <h5 class="modal-title" id="wallet-connect-modal-title">Подключить кошелек</h5>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="wallet-modal-copy">
          Выберите EVM или Solana кошелек. Если адрес уже привязан, откроется dashboard.
        </p>
        <div id="wallet-modal-provider-list" class="wallet-modal-provider-list"></div>
        <div id="wallet-modal-install-list" class="wallet-modal-install-list" style="display:none;"></div>
        <div id="wallet-modal-status" class="wallet-modal-status" style="display:none;"></div>
      </div>
    </div>
  </div>
</div>

<style>
  .wallet-modal-dialog {
    max-width: 400px;
    margin: 1rem auto;
  }

  .wallet-modal-content {
    color: #fff;
    border: 1px solid rgba(251, 191, 36, 0.18);
    border-radius: 16px;
    background:
      radial-gradient(circle at top left, rgba(251, 191, 36, 0.18), transparent 38%),
      linear-gradient(180deg, rgba(19, 24, 33, 0.98), rgba(10, 13, 18, 0.98));
    box-shadow: 0 18px 48px rgba(0, 0, 0, 0.42);
  }

  .wallet-modal-header {
    padding: 1rem 1rem 0.35rem;
  }

  .wallet-modal-content .modal-body {
    padding: 0.6rem 1rem 1rem;
  }

  .wallet-modal-eyebrow {
    margin: 0 0 0.2rem;
    color: #fbbf24;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.14em;
  }

  #wallet-connect-modal-title {
    margin: 0;
    font-size: 1.1rem;
    line-height: 1.2;
  }

  .wallet-modal-copy {
    margin-bottom: 0.75rem;
    color: rgba(255, 255, 255, 0.72);
    font-size: 0.9rem;
    line-height: 1.35;
  }

  .wallet-modal-provider-list,
  .wallet-modal-install-list {
    display: grid;
    gap: 0.5rem;
  }

  .wallet-modal-provider {
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.03);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.75rem 0.85rem;
    transition: all 0.2s ease;
    text-align: left;
  }

  .wallet-modal-provider:hover:not(:disabled) {
    border-color: rgba(251, 191, 36, 0.4);
    background: rgba(255, 255, 255, 0.05);
  }

  .wallet-modal-provider:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }

  .wallet-modal-provider__name {
    font-weight: 600;
    font-size: 0.92rem;
  }

  .wallet-modal-provider__meta {
    display: block;
    margin-top: 0.15rem;
    color: rgba(255, 255, 255, 0.55);
    font-size: 0.76rem;
    line-height: 1.25;
  }

  .wallet-modal-provider__badge {
    color: #fbbf24;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
  }

  .wallet-modal-status {
    margin-top: 0.75rem;
    padding: 0.7rem 0.85rem;
    border-radius: 12px;
    font-size: 0.84rem;
    line-height: 1.35;
    background: rgba(255, 255, 255, 0.04);
  }

  @media (max-width: 576px) {
    .wallet-modal-dialog {
      max-width: none;
      margin: 0.75rem;
    }

    .wallet-modal-header {
      padding: 0.9rem 0.9rem 0.25rem;
    }

    .wallet-modal-content .modal-body {
      padding: 0.5rem 0.9rem 0.9rem;
    }
  }

  .wallet-modal-status.is-error {
    color: #fecaca;
    border: 1px solid rgba(248, 113, 113, 0.24);
    background: rgba(127, 29, 29, 0.22);
  }

  .wallet-modal-status.is-success {
    color: #bbf7d0;
    border: 1px solid rgba(74, 222, 128, 0.24);
    background: rgba(20, 83, 45, 0.22);
  }

  .wallet-modal-status.is-info {
    color: #fde68a;
    border: 1px solid rgba(251, 191, 36, 0.24);
    background: rgba(120, 53, 15, 0.22);
  }
</style>

@push('scripts')
<script>
  (function () {
    const burger = document.getElementById('header-burger');
    const menu = document.getElementById('header-nav-menu');
    const walletDropdown = document.getElementById('header-wallet-dropdown');
    const walletTrigger = document.getElementById('header-wallet-trigger');
    const walletMenu = document.getElementById('header-wallet-menu');
    const connectWalletBtn = document.getElementById('menu-connect-wallet');
    const disconnectWalletBtn = document.getElementById('menu-disconnect-wallet');
    const walletAddressNode = document.getElementById('menu-wallet-address');
    const walletModalNode = document.getElementById('wallet-connect-modal');
    const walletModalStatus = document.getElementById('wallet-modal-status');
    const walletProviderList = document.getElementById('wallet-modal-provider-list');
    const walletInstallList = document.getElementById('wallet-modal-install-list');
    const isAuthenticated = @json(auth()->check());
    const web3ChallengeUrl = '{{ route('web3.challenge') }}';
    const web3LoginUrl = '{{ route('web3.login') }}';
    const walletLinkChallengeUrl = '{{ route('wallet.challenge') }}';
    const walletLinkUrl = '{{ route('wallet.link') }}';
    const walletPageUrl = '{{ route('wallet') }}';
    const dashboardUrl = '{{ route('dashboard') }}';
    const stateListeners = new Set();
    const eip6963Providers = new Map();
    const KNOWN_WALLETS = [
      {
        id: 'metamask',
        type: 'evm',
        name: 'MetaMask',
        installUrl: 'https://metamask.io/download/',
        mobileDeeplink(url) { return `https://link.metamask.io/dapp/${encodeURIComponent(url)}`; },
        matches(provider) { return provider && provider.isMetaMask === true; }
      },
      {
        id: 'rabby',
        type: 'evm',
        name: 'Rabby',
        installUrl: 'https://rabby.io/',
        matches(provider, info) {
          const providerName = String(info?.name || provider?.name || '').toLowerCase();
          const providerRdns = String(info?.rdns || '').toLowerCase();

          return provider && (
            provider.isRabby === true
            || providerName.includes('rabby')
            || providerRdns.includes('rabby')
          );
        }
      },
      {
        id: 'coinbase',
        type: 'evm',
        name: 'Coinbase Wallet',
        installUrl: 'https://www.coinbase.com/wallet/downloads',
        matches(provider, info) {
          const providerName = String(info?.name || provider?.name || '').toLowerCase();
          const providerRdns = String(info?.rdns || '').toLowerCase();

          return provider && (
            provider.isCoinbaseWallet === true
            || providerName.includes('coinbase')
            || providerRdns.includes('coinbase')
          );
        }
      },
      {
        id: 'trust',
        type: 'evm',
        name: 'Trust Wallet',
        installUrl: 'https://trustwallet.com/browser-extension',
        mobileDeeplink(url) { return `https://link.trustwallet.com/open_url?coin_id=60&url=${encodeURIComponent(url)}`; },
        matches(provider) { return provider && provider.isTrust === true; }
      },
      {
        id: 'okx',
        type: 'evm',
        name: 'OKX Wallet',
        installUrl: 'https://www.okx.com/web3',
        matches(provider, info) {
          const providerName = String(info?.name || provider?.name || '').toLowerCase();
          const providerRdns = String(info?.rdns || '').toLowerCase();

          return provider && (
            provider.isOKExWallet === true
            || provider.isOKXWallet === true
            || providerName.includes('okx')
            || providerRdns.includes('okx')
          );
        }
      },
      {
        id: 'phantom',
        type: 'solana',
        name: 'Phantom',
        installUrl: 'https://phantom.com/download',
        mobileDeeplink(url) { return `https://phantom.app/ul/browse/${encodeURIComponent(url)}?ref=${encodeURIComponent(window.location.origin)}`; },
        matches(provider) { return provider && provider.isPhantom === true; }
      },
      { id: 'solflare', type: 'solana', name: 'Solflare', installUrl: 'https://solflare.com/download', matches(provider) { return provider && provider.isSolflare === true; } },
      { id: 'backpack', type: 'solana', name: 'Backpack', installUrl: 'https://backpack.app/', matches(provider) { return provider && provider.isBackpack === true; } },
    ];
    const walletState = {
      provider: null,
      address: null,
      chainId: null,
      walletType: null,
      linked: false,
      connected: false,
    };
    let pendingModalOptions = null;
    let walletModal = null;

    if (walletModalNode && walletModalNode.parentElement !== document.body) {
      document.body.appendChild(walletModalNode);
    }

    if (walletModalNode && window.bootstrap) {
      walletModal = window.bootstrap.Modal.getOrCreateInstance(walletModalNode);
    }

    function normalizeEip6963Provider(detail) {
      const provider = detail?.provider || null;
      const info = detail?.info || null;
      const uuid = String(info?.uuid || '');
      const rdns = String(info?.rdns || '');
      const name = String(info?.name || '');

      if (!provider || typeof provider !== 'object') {
        return null;
      }

      return {
        id: uuid || rdns || name || `eip6963-${eip6963Providers.size + 1}`,
        provider,
        info,
      };
    }

    function registerEip6963Provider(detail) {
      const normalized = normalizeEip6963Provider(detail);
      if (!normalized) {
        return;
      }

      eip6963Providers.set(normalized.id, normalized);
    }

    window.addEventListener('eip6963:announceProvider', function (event) {
      registerEip6963Provider(event.detail);
      renderWalletModalProviders(null);
    });

    window.dispatchEvent(new Event('eip6963:requestProvider'));

    function closeWalletDropdown() {
      if (!walletDropdown || !walletTrigger || !walletMenu) {
        return;
      }

      walletDropdown.classList.remove('is-open');
      walletTrigger.setAttribute('aria-expanded', 'false');
      walletMenu.hidden = true;
    }

    function closeHeaderMenu() {
      if (!burger || !menu) {
        return;
      }

      burger.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      document.body.classList.remove('header-menu-open');
    }

    function normalizeChainId(value) {
      if (value === null || value === undefined) return null;

      if (typeof value === 'number' && Number.isFinite(value)) {
        return '0x' + value.toString(16);
      }

      if (typeof value !== 'string') return null;

      const raw = value.trim().toLowerCase();
      if (!raw) return null;

      if (raw.startsWith('0x')) {
        const parsed = parseInt(raw, 16);
        return Number.isFinite(parsed) ? '0x' + parsed.toString(16) : null;
      }

      if (/^\d+$/.test(raw)) {
        const parsed = parseInt(raw, 10);
        return Number.isFinite(parsed) ? '0x' + parsed.toString(16) : null;
      }

      return null;
    }

    function evmLinkWalletTypeFromChainId(chainId) {
      const id = normalizeChainId(chainId) || '0x1';
      const map = {
        '0x1': 'eth',
        '0xa4b1': 'arbitrum',
        '0x2105': 'base',
        '0x89': 'polygon',
        '0x38': 'bnb',
      };
      return map[id] || 'eth';
    }

    function getInjectedProviders() {
      const mapped = new Map();
      const addProvider = (provider, info = null) => {
        if (!provider || typeof provider !== 'object' || mapped.has(provider)) {
          return;
        }

        mapped.set(provider, {
          provider,
          info,
        });
      };

      eip6963Providers.forEach((entry) => {
        addProvider(entry?.provider, entry?.info || null);
      });

      addProvider(window.okxwallet, {
        name: 'OKX Wallet',
        rdns: 'com.okx.wallet',
      });

      addProvider(window.okexchain, {
        name: 'OKX Wallet',
        rdns: 'com.okx.wallet',
      });

      if (!window.ethereum) {
        return Array.from(mapped.values());
      }

      if (Array.isArray(window.ethereum.providers) && window.ethereum.providers.length > 0) {
        window.ethereum.providers.forEach((provider) => {
          addProvider(provider, null);
        });

        return Array.from(mapped.values());
      }

      addProvider(window.ethereum, null);

      return Array.from(mapped.values());
    }

    function isMobileDevice() {
      return /android|iphone|ipad|ipod|iemobile|opera mini|mobile/i.test(navigator.userAgent || '')
        || (window.matchMedia && window.matchMedia('(max-width: 991px)').matches && navigator.maxTouchPoints > 0);
    }

    function isWalletAppBrowser() {
      return Boolean(
        window.ethereum
        || window.phantom?.solana
        || window.solflare
        || window.backpack?.solana
      );
    }

    function currentDappUrl() {
      return window.location.href;
    }

    function getSolanaProviders() {
      const candidates = [
        window.phantom?.solana,
        window.solflare,
        window.backpack?.solana,
        window.solana,
      ];
      const seen = new Set();

      return candidates.filter((provider) => {
        if (!provider || typeof provider !== 'object' || seen.has(provider)) {
          return false;
        }

        seen.add(provider);
        return true;
      });
    }

    function listWalletOptions() {
      const mapped = new Map();

      getInjectedProviders().forEach(({ provider, info }) => {
        const known = KNOWN_WALLETS.find((wallet) => wallet.matches(provider, info));
        const id = known ? known.id : `evm-${mapped.size + 1}`;
        const fallbackName = info?.name || 'Browser Wallet';

        if (!mapped.has(id)) {
          mapped.set(id, {
            id,
            type: known ? known.type : 'evm',
            name: known ? known.name : fallbackName,
            installUrl: known ? known.installUrl : 'https://ethereum.org/wallets/',
            provider,
            installed: true,
          });
        }
      });

      getSolanaProviders().forEach((provider) => {
        const known = KNOWN_WALLETS.find((wallet) => wallet.type === 'solana' && wallet.matches(provider));
        const id = known ? known.id : `solana-${mapped.size + 1}`;

        if (!mapped.has(id)) {
          mapped.set(id, {
            id,
            type: known ? known.type : 'solana',
            name: known ? known.name : 'Solana Wallet',
            installUrl: known ? known.installUrl : 'https://solana.com/ecosystem/explore?categories=wallet',
            provider,
            installed: true,
          });
        }
      });

      KNOWN_WALLETS.forEach((wallet) => {
        if (!mapped.has(wallet.id)) {
          mapped.set(wallet.id, {
            id: wallet.id,
            type: wallet.type,
            name: wallet.name,
            installUrl: wallet.installUrl,
            provider: null,
            installed: false,
          });
        }
      });

      return Array.from(mapped.values());
    }

    function listMobileWalletOptions() {
      if (!isMobileDevice() || isWalletAppBrowser()) {
        return [];
      }

      const url = currentDappUrl();

      return KNOWN_WALLETS
        .filter((wallet) => typeof wallet.mobileDeeplink === 'function')
        .map((wallet) => ({
          id: wallet.id,
          type: wallet.type,
          name: wallet.name,
          url: wallet.mobileDeeplink(url),
        }));
    }

    function shortenAddress(address) {
      if (!address || address.length < 10) {
        return address || '';
      }

      return address.slice(0, 6) + '...' + address.slice(-4);
    }

    function walletTriggerLabel() {
      if (!walletState.connected || !walletState.address) {
        return 'Кошелек';
      }

      return `Выход ${walletState.address.slice(-4)}`;
    }

    function syncWalletButtons() {
      if (walletTrigger) {
        const label = walletTriggerLabel();
        walletTrigger.textContent = label;
        walletTrigger.setAttribute('aria-label', label);
        walletTrigger.title = walletState.address
          ? `Отключить ${walletState.address}`
          : 'Подключить кошелек';
      }

      if (connectWalletBtn) {
        connectWalletBtn.style.display = walletState.connected ? 'none' : 'inline-flex';
      }

      if (disconnectWalletBtn) {
        disconnectWalletBtn.style.display = walletState.connected ? 'inline-flex' : 'none';
      }

      if (walletAddressNode) {
        walletAddressNode.textContent = walletState.address ? ` ${shortenAddress(walletState.address)}` : '';
      }
    }

    function emitWalletState() {
      syncWalletButtons();
      stateListeners.forEach((listener) => {
        try {
          listener({ ...walletState });
        } catch (error) {
          console.error('Wallet state listener error:', error);
        }
      });
    }

    function setWalletState(patch) {
      Object.assign(walletState, patch);
      emitWalletState();
    }

    function syncWalletStateFromExternal(detail) {
      if (!detail || typeof detail !== 'object') {
        return;
      }

      setWalletState({
        provider: detail.provider || walletState.provider || window.ethereum || null,
        address: detail.connected ? (detail.address || null) : null,
        chainId: detail.connected ? (normalizeChainId(detail.chainId) || detail.chainId || null) : null,
        walletType: detail.walletType === 'solana' ? 'solana' : (detail.walletType || (detail.connected ? 'evm' : null)),
        linked: Boolean(detail.linked),
        connected: Boolean(detail.connected && detail.address),
      });

      if (detail.connected) {
        localStorage.removeItem('walletDisconnectedExplicitly');
      } else {
        localStorage.setItem('walletDisconnectedExplicitly', 'true');
      }
    }

    function setWalletModalStatus(message, type) {
      if (!walletModalStatus) {
        return;
      }

      if (!message) {
        walletModalStatus.style.display = 'none';
        walletModalStatus.textContent = '';
        walletModalStatus.className = 'wallet-modal-status';
        return;
      }

      walletModalStatus.style.display = 'block';
      walletModalStatus.textContent = message;
      walletModalStatus.className = `wallet-modal-status is-${type || 'info'}`;
    }

    function renderWalletModalProviders(activeWalletId) {
      if (!walletProviderList || !walletInstallList) {
        return;
      }

      const options = listWalletOptions();
      const installed = options.filter((wallet) => wallet.installed);
      const missing = options.filter((wallet) => !wallet.installed);
      const mobileWallets = listMobileWalletOptions();

      if (installed.length > 0) {
        walletProviderList.innerHTML = installed.map((wallet) => `
          <button type="button" class="wallet-modal-provider" data-wallet-id="${wallet.id}" ${activeWalletId ? 'disabled' : ''}>
            <span>
              <span class="wallet-modal-provider__name">${wallet.name}</span>
              <span class="wallet-modal-provider__meta">Подключить ${wallet.type === 'solana' ? 'Solana' : 'EVM'}-адрес и проверить привязку</span>
            </span>
            <span class="wallet-modal-provider__badge">${activeWalletId === wallet.id ? 'Подключаем...' : 'Установлен'}</span>
          </button>
        `).join('');
      } else if (mobileWallets.length > 0) {
        walletProviderList.innerHTML = mobileWallets.map((wallet) => `
          <a href="${wallet.url}" class="wallet-modal-provider" rel="noreferrer">
            <span>
              <span class="wallet-modal-provider__name">${wallet.name}</span>
              <span class="wallet-modal-provider__meta">Открыть эту страницу внутри ${wallet.name} и подключить ${wallet.type === 'solana' ? 'Solana' : 'EVM'}-кошелек</span>
            </span>
            <span class="wallet-modal-provider__badge">Открыть</span>
          </a>
        `).join('');
      } else {
        walletProviderList.innerHTML = '';
      }

      if (missing.length > 0) {
        walletInstallList.style.display = 'grid';
        walletInstallList.innerHTML = missing.slice(0, 4).map((wallet) => `
          <a href="${wallet.installUrl}" target="_blank" rel="noreferrer" class="wallet-modal-provider">
            <span>
              <span class="wallet-modal-provider__name">${wallet.name}</span>
              <span class="wallet-modal-provider__meta">${isMobileDevice() ? 'Установить приложение кошелька' : 'Установить расширение'}</span>
            </span>
            <span class="wallet-modal-provider__badge">Скачать</span>
          </a>
        `).join('');
      } else {
        walletInstallList.style.display = 'none';
        walletInstallList.innerHTML = '';
      }

      if (installed.length === 0 && mobileWallets.length > 0) {
        setWalletModalStatus('В обычном мобильном браузере кошелек часто недоступен. Откройте текущую страницу через приложение кошелька, установленное на телефоне.', 'info');
      } else if (installed.length === 0) {
        setWalletModalStatus('В браузере не найден Web3-кошелек. Установите MetaMask, Phantom, Solflare или другой совместимый кошелек.', 'error');
      }
    }

    function uint8ArrayToBase64(value) {
      const bytes = value instanceof Uint8Array ? value : new Uint8Array(value || []);
      let binary = '';

      bytes.forEach((byte) => {
        binary += String.fromCharCode(byte);
      });

      return window.btoa(binary);
    }

    async function signWalletMessage(provider, walletType, address, message) {
      if (walletType === 'solana') {
        if (!provider || typeof provider.signMessage !== 'function') {
          throw new Error('Solana-кошелек не поддерживает подпись сообщений.');
        }

        const encoded = new TextEncoder().encode(message);
        const signed = await provider.signMessage(encoded, 'utf8');
        const signatureBytes = signed?.signature || signed;

        if (!signatureBytes) {
          throw new Error('Solana-кошелек не вернул подпись.');
        }

        return uint8ArrayToBase64(signatureBytes);
      }

      return provider.request({
        method: 'personal_sign',
        params: [message, address],
      });
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
        const error = new Error(data.message || 'Web3 request failed.');
        error.status = response.status;
        error.payload = data;
        throw error;
      }

      return data;
    }

    async function attemptWalletLogin(address, provider, options) {
      const walletType = options?.walletType === 'solana' ? 'solana' : 'evm';

      if (isAuthenticated || options?.autoLogin === false) {
        return { linked: false, skipped: true };
      }

      const challenge = await postJson(web3ChallengeUrl, { address, wallet_type: walletType });
      const signature = await signWalletMessage(provider, walletType, address, challenge.message);

      try {
        await postJson(web3LoginUrl, { address, signature, wallet_type: walletType });
        return { linked: true };
      } catch (error) {
        if (error && error.status === 404) {
          return { linked: false };
        }

        throw error;
      }
    }

    async function ensureWalletLinked(address, provider, options) {
      const isSol = options?.walletType === 'solana';
      const walletType = isSol ? 'solana' : evmLinkWalletTypeFromChainId(options?.chainId);
      const chainId = isSol
        ? 'solana'
        : (normalizeChainId(options?.chainId) || '0x1');

      if (!isAuthenticated) {
        return { linked: false, skipped: true };
      }

      const challenge = await postJson(walletLinkChallengeUrl, {
        address,
        wallet_type: walletType,
        network: chainId,
      });

      const signature = await signWalletMessage(provider, walletType, address, challenge.message);
      await postJson(walletLinkUrl, {
        address,
        signature,
        network: chainId,
        wallet_type: walletType,
      });

      return { linked: true };
    }

    async function connectWallet(walletId, options) {
      const selected = listWalletOptions().find((wallet) => wallet.id === walletId);

      if (!selected || !selected.provider) {
        throw new Error('Кошелек недоступен в этом браузере.');
      }

      renderWalletModalProviders(walletId);
      setWalletModalStatus('Подтвердите подключение кошелька во всплывающем окне расширения.', 'info');

      let address = null;
      let chainId = null;

      if (selected.type === 'solana') {
        const response = await selected.provider.connect();
        address = response?.publicKey?.toString?.() || selected.provider.publicKey?.toString?.() || null;
        chainId = 'solana';
      } else {
        const accounts = await selected.provider.request({ method: 'eth_requestAccounts' });
        address = Array.isArray(accounts) ? accounts[0] : null;

        try {
          chainId = normalizeChainId(await selected.provider.request({ method: 'eth_chainId' }));
        } catch (error) {
          console.error('Failed to detect wallet chain id:', error);
        }
      }

      if (!address) {
        throw new Error('Кошелек не вернул адрес.');
      }

      setWalletState({
        provider: selected.provider,
        address,
        chainId,
        walletType: selected.type,
        connected: true,
        linked: false,
      });

      localStorage.removeItem('walletDisconnectedExplicitly');

      const linkResult = await ensureWalletLinked(address, selected.provider, { ...(options || {}), walletType: selected.type, chainId });
      const loginResult = await attemptWalletLogin(address, selected.provider, { ...(options || {}), walletType: selected.type });
      setWalletState({ linked: Boolean(linkResult.linked || loginResult.linked) });

      if (loginResult.linked) {
        setWalletModalStatus('Кошелек привязан к аккаунту. Открываем dashboard...', 'success');
        setTimeout(() => {
          window.location.href = dashboardUrl;
        }, 250);
      } else if (!loginResult.skipped) {
        setWalletModalStatus('Кошелек подключен, но адрес пока не привязан к аккаунту. Можно продолжить работу на странице кошелька.', 'info');
      } else {
        setWalletModalStatus(linkResult.linked ? 'Кошелек подключен и добавлен к аккаунту.' : 'Кошелек подключен.', 'success');
      }

      if (walletModal && !loginResult.linked) {
        setTimeout(() => walletModal.hide(), 250);
      }

      if (options && typeof options.onConnected === 'function') {
        await options.onConnected({
          address,
          chainId,
          provider: selected.provider,
          walletType: selected.type,
          linked: Boolean(linkResult.linked || loginResult.linked),
        });
      }

      renderWalletModalProviders(null);
      return { address, chainId, provider: selected.provider, walletType: selected.type, linked: Boolean(linkResult.linked || loginResult.linked) };
    }

    function openWalletModal(options = {}) {
      pendingModalOptions = options;
      renderWalletModalProviders(null);
      setWalletModalStatus('', null);
      closeHeaderMenu();

      if (walletModal) {
        walletModal.show();
      } else if (options.redirectToWallet !== false) {
        window.location.href = walletPageUrl;
      }
    }

    function disconnectWallet() {
      localStorage.setItem('walletDisconnectedExplicitly', 'true');
      setWalletState({
        provider: null,
        address: null,
        chainId: null,
        walletType: null,
        linked: false,
        connected: false,
      });
    }

    async function restoreWalletConnection() {
      if (localStorage.getItem('walletDisconnectedExplicitly') === 'true') {
        emitWalletState();
        return;
      }

      try {
        const options = listWalletOptions();
        const solanaEntry = options.find((wallet) => wallet.type === 'solana' && wallet.provider?.isConnected && wallet.provider?.publicKey);

        if (solanaEntry?.provider?.publicKey) {
          setWalletState({
            provider: solanaEntry.provider,
            address: solanaEntry.provider.publicKey.toString(),
            chainId: 'solana',
            walletType: 'solana',
            linked: false,
            connected: true,
          });
          return;
        }

        const providerEntry = options.find((wallet) => wallet.type === 'evm' && wallet.provider) || null;
        if (!providerEntry?.provider) {
          emitWalletState();
          return;
        }

        const accounts = await providerEntry.provider.request({ method: 'eth_accounts' });
        if (!Array.isArray(accounts) || accounts.length === 0) {
          emitWalletState();
          return;
        }

        const chainId = normalizeChainId(await providerEntry.provider.request({ method: 'eth_chainId' }).catch(() => null));

        setWalletState({
          provider: providerEntry.provider,
          address: accounts[0],
          chainId,
          walletType: 'evm',
          linked: false,
          connected: true,
        });
      } catch (error) {
        console.error('Failed to restore wallet connection:', error);
      }
    }

    if (connectWalletBtn) {
      connectWalletBtn.addEventListener('click', function () {
        closeWalletDropdown();
        openWalletModal();
      });
    }

    if (disconnectWalletBtn) {
      disconnectWalletBtn.addEventListener('click', function (event) {
        event.preventDefault();
        closeWalletDropdown();
        disconnectWallet();
      });
    }

    if (walletTrigger) {
      walletTrigger.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (walletState.connected) {
          closeWalletDropdown();
          disconnectWallet();
          return;
        }

        closeWalletDropdown();
        openWalletModal();
      });
    }

    document.addEventListener('click', function (event) {
      if (!walletDropdown || !walletDropdown.classList.contains('is-open')) {
        return;
      }

      if (walletDropdown.contains(event.target)) {
        return;
      }

      closeWalletDropdown();
    });

    if (walletProviderList) {
      walletProviderList.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-wallet-id]');
        if (!button) {
          return;
        }

        const walletId = button.getAttribute('data-wallet-id');
        if (!walletId) {
          return;
        }

        try {
          await connectWallet(walletId, pendingModalOptions || {});
        } catch (error) {
          console.error('Wallet connection error:', error);
          renderWalletModalProviders(null);
          setWalletModalStatus(error.message || 'Не удалось подключить кошелек.', 'error');
        }
      });
    }

    if (walletModalNode) {
      walletModalNode.addEventListener('hidden.bs.modal', function () {
        pendingModalOptions = null;
        renderWalletModalProviders(null);
        setWalletModalStatus('', null);
      });
    }

    window.appWallet = {
      openModal: openWalletModal,
      disconnect: disconnectWallet,
      subscribe(listener) {
        if (typeof listener !== 'function') {
          return function () {};
        }

        stateListeners.add(listener);
        listener({ ...walletState });

        return function unsubscribe() {
          stateListeners.delete(listener);
        };
      },
      getState() {
        return { ...walletState };
      },
      signMessage({ provider, walletType, address, message }) {
        return signWalletMessage(provider, walletType, address, message);
      },
      connect(options = {}) {
        openWalletModal(options);
      },
    };

    window.addEventListener('app-wallet-state-changed', function (event) {
      syncWalletStateFromExternal(event.detail);
    });

    restoreWalletConnection();

    if (window.ethereum) {
      window.ethereum.on('accountsChanged', async function (accounts) {
        if (!Array.isArray(accounts) || accounts.length === 0) {
          disconnectWallet();
          return;
        }

        const nextChainId = normalizeChainId(await window.ethereum.request({ method: 'eth_chainId' }).catch(() => walletState.chainId));
        localStorage.removeItem('walletDisconnectedExplicitly');
        setWalletState({
          provider: walletState.provider || window.ethereum,
          address: accounts[0],
          chainId: nextChainId,
          connected: true,
        });
      });

      window.ethereum.on('chainChanged', function (chainId) {
        if (!walletState.connected) {
          return;
        }

        setWalletState({
          chainId: normalizeChainId(chainId) || walletState.chainId,
        });
      });
    }
  })();
</script>
@endpush
