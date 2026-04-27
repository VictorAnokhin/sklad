<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AV8 Swap</title>
    <style>
        :root {
            color-scheme: dark;
            --bg: #07111f;
            --panel: rgba(15, 23, 42, 0.92);
            --panel-2: rgba(10, 15, 28, 0.98);
            --line: rgba(255,255,255,0.08);
            --muted: rgba(255,255,255,0.58);
            --text: #f8fafc;
            --primary: #38bdf8;
            --primary-2: #0ea5e9;
            --success: #34d399;
            --danger: #f87171;
            --warning: #fbbf24;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(14, 165, 233, 0.18), transparent 34%),
                radial-gradient(circle at bottom right, rgba(56, 189, 248, 0.16), transparent 32%),
                linear-gradient(180deg, #020617 0%, var(--bg) 100%);
            color: var(--text);
            min-height: 100vh;
            padding: 28px;
        }

        .shell {
            max-width: 920px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
            gap: 20px;
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
            overflow: hidden;
        }

        .header {
            padding: 24px 24px 18px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(56,189,248,0.08), transparent);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #7dd3fc;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        h1, h2, h3, p { margin: 0; }
        h1 { font-size: 32px; line-height: 1.05; }
        .subtitle { color: var(--muted); margin-top: 10px; line-height: 1.5; }

        .body { padding: 24px; }
        .stack { display: grid; gap: 16px; }

        .field {
            display: grid;
            gap: 8px;
        }

        .field label {
            font-size: 13px;
            color: var(--muted);
            font-weight: 600;
        }

        .field input,
        .field select {
            width: 100%;
            background: var(--panel-2);
            border: 1px solid var(--line);
            border-radius: 16px;
            color: var(--text);
            padding: 14px 16px;
            outline: none;
            font-size: 15px;
        }

        .field input:focus,
        .field select:focus { border-color: rgba(56, 189, 248, 0.6); }

        .row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .token-meta, .summary-list {
            display: grid;
            gap: 10px;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(56,189,248,0.18);
            background: rgba(56,189,248,0.08);
            color: #bae6fd;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .address {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: 12px;
            color: rgba(255,255,255,0.66);
            word-break: break-all;
        }

        .quote-box {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: rgba(255,255,255,0.03);
            padding: 16px;
        }

        .quote-main {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid rgba(255,255,255,0.06);
            font-size: 14px;
        }

        .summary-item:first-child { border-top: 0; padding-top: 0; }
        .summary-item span:first-child { color: var(--muted); }
        .sources {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .source {
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,0.08);
            background: rgba(255,255,255,0.04);
            color: rgba(255,255,255,0.84);
            padding: 7px 10px;
            font-size: 12px;
        }

        .actions {
            display: grid;
            gap: 12px;
        }

        button {
            border: 0;
            border-radius: 16px;
            cursor: pointer;
            padding: 14px 16px;
            font-size: 15px;
            font-weight: 700;
            transition: transform .18s ease, opacity .18s ease, background .18s ease;
        }

        button:hover:not(:disabled) { transform: translateY(-1px); }
        button:disabled { opacity: 0.52; cursor: not-allowed; transform: none; }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-2));
            color: #00111a;
        }
        .btn-secondary {
            background: rgba(255,255,255,0.06);
            color: var(--text);
            border: 1px solid var(--line);
        }

        .status {
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--line);
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
            min-height: 54px;
        }

        .status.error {
            color: #fecaca;
            border-color: rgba(248,113,113,0.32);
            background: rgba(248,113,113,0.08);
        }

        .status.success {
            color: #bbf7d0;
            border-color: rgba(52,211,153,0.28);
            background: rgba(52,211,153,0.08);
        }

        .small-note {
            color: rgba(255,255,255,0.48);
            font-size: 12px;
            line-height: 1.5;
        }

        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            body { padding: 14px; }
            .row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="shell">
    <section class="card">
        <div class="header">
            <div class="eyebrow">0x Aggregator</div>
            <h1>Swap Window</h1>
            <p class="subtitle">Best-rate EVM swap flow in a separate window. Quotes and execution are routed through 0x AllowanceHolder.</p>
        </div>
        <div class="body">
            <div class="stack">
                <div class="row">
                    <div class="field">
                        <label for="swap-network">Network</label>
                        <select id="swap-network"></select>
                    </div>
                    <div class="field">
                        <label for="swap-slippage">Slippage</label>
                        <select id="swap-slippage">
                            <option value="50">0.50%</option>
                            <option value="100" selected>1.00%</option>
                            <option value="150">1.50%</option>
                            <option value="300">3.00%</option>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label for="swap-wallet-address">Connected wallet</label>
                    <input id="swap-wallet-address" type="text" value="" placeholder="Connect wallet first" readonly>
                    <div class="small-note" id="swap-wallet-hint">Popup uses the currently connected EVM wallet provider.</div>
                </div>

                <div class="row">
                    <div class="field">
                        <label for="swap-sell-token">You pay</label>
                        <select id="swap-sell-token"></select>
                    </div>
                    <div class="field">
                        <label for="swap-buy-token">You receive</label>
                        <select id="swap-buy-token"></select>
                    </div>
                </div>

                <div class="field">
                    <label for="swap-sell-amount">Sell amount</label>
                    <input id="swap-sell-amount" type="number" min="0" step="any" placeholder="0.0">
                </div>

                <div class="quote-box">
                    <div class="quote-main" id="swap-buy-amount">--</div>
                    <div class="small-note" id="swap-quote-subtitle">Choose tokens and amount to load the best executable quote.</div>
                    <div class="summary-list" style="margin-top:14px;">
                        <div class="summary-item"><span>Estimated buy amount</span><span id="swap-estimated-amount">--</span></div>
                        <div class="summary-item"><span>Allowance target</span><span id="swap-allowance-target">--</span></div>
                        <div class="summary-item"><span>Network fee</span><span id="swap-network-fee">--</span></div>
                    </div>
                </div>

                <div class="field">
                    <label>Route sources</label>
                    <div class="sources" id="swap-route-sources">
                        <span class="source">No route yet</span>
                    </div>
                </div>

                <div class="actions">
                    <button type="button" class="btn-secondary" id="swap-connect-btn">Connect wallet</button>
                    <button type="button" class="btn-secondary" id="swap-quote-btn">Get quote</button>
                    <button type="button" class="btn-primary" id="swap-execute-btn">Approve and swap</button>
                    <div class="status" id="swap-status">Ready.</div>
                </div>
            </div>
        </div>
    </section>

    <aside class="card">
        <div class="header">
            <div class="eyebrow">Execution</div>
            <h2 style="font-size:24px;">Trade Summary</h2>
        </div>
        <div class="body">
            <div class="stack">
                <div class="pill" id="swap-selected-chain">Chain: --</div>
                <div class="token-meta">
                    <div>
                        <div class="small-note">Sell token contract</div>
                        <div class="address" id="swap-sell-address">--</div>
                    </div>
                    <div>
                        <div class="small-note">Buy token contract</div>
                        <div class="address" id="swap-buy-address">--</div>
                    </div>
                </div>
                <div class="summary-list">
                    <div class="summary-item"><span>Requested wallet</span><span id="swap-requested-wallet">--</span></div>
                    <div class="summary-item"><span>Connected wallet</span><span id="swap-connected-wallet">--</span></div>
                    <div class="summary-item"><span>Approval needed</span><span id="swap-approval-needed">--</span></div>
                </div>
                <div class="small-note">
                    1. Quote is fetched from 0x AllowanceHolder.<br>
                    2. If `issues.allowance.spender` is returned, the popup first sends an ERC-20 approve transaction.<br>
                    3. Then it submits `quote.transaction.to/data/value` through your wallet.
                </div>
            </div>
        </div>
    </aside>
</div>

<script>
const query = new URLSearchParams(window.location.search);
const requestedWallet = (query.get('wallet') || '').trim();
const requestedChainId = normalizeChainId(query.get('chain_id') || '0xa4b1') || '0xa4b1';

const NETWORKS = {
  '0x1': { name: 'Ethereum', chainIdDecimal: '1', buyTokens: [] },
  '0x38': { name: 'BNB Chain', chainIdDecimal: '56', buyTokens: [] },
  '0x89': { name: 'Polygon', chainIdDecimal: '137', buyTokens: [] },
  '0xa4b1': {
    name: 'Arbitrum',
    chainIdDecimal: '42161',
    buyTokens: [
      { symbol: 'USDC', name: 'USD Coin', address: '0xaf88d065e77c8cC2239327C5EDb3A432268e5831', decimals: 6 },
      { symbol: 'USDT', name: 'Tether USD', address: '0xfd086bc7cd5c481dcc9c85ebe478a1c0b69fcbb9', decimals: 6 },
      { symbol: 'DAI', name: 'Dai', address: '0xda10009cbd5d07dd0cecc66161fc93d7c9000da1', decimals: 18 },
      { symbol: 'WETH', name: 'Wrapped Ether', address: '0x82af49447d8a07e3bd95bd0d56f35241523fbab1', decimals: 18 },
      { symbol: 'WBTC', name: 'Wrapped BTC', address: '0x2f2a2543b76a4166549f7aaab2e75bef0aaefc5b', decimals: 8 },
      { symbol: 'ARB', name: 'Arbitrum', address: '0x912ce59144191c1204e64559fe8253a0e49e6548', decimals: 18 },
      { symbol: 'GMX', name: 'GMX', address: '0xfc5a1a6eb076aeb857ad4d5fae4789c75ecca8c9', decimals: 18 }
    ]
  },
  '0xa': { name: 'Optimism', chainIdDecimal: '10', buyTokens: [] },
  '0x2105': { name: 'Base', chainIdDecimal: '8453', buyTokens: [] },
  '0xa86a': {
    name: 'Avalanche',
    chainIdDecimal: '43114',
    buyTokens: [
      { symbol: 'USDC', name: 'USD Coin', address: '0xb97ef9ef8734c71904d8002f8b6bc66dd9c48a6e', decimals: 6 },
      { symbol: 'USDT', name: 'Tether USD', address: '0x9702230a8ea53601f5cd2dc00fdbc13d4df4a8c7', decimals: 6 },
      { symbol: 'WETH.e', name: 'Wrapped Ether', address: '0x49d5c2bdffac6ce2bfdb6640f4f80f226bc10bab', decimals: 18 },
      { symbol: 'WBTC.e', name: 'Wrapped BTC', address: '0x50b7545627a5162f82a992c33b87adc75187b218', decimals: 8 }
    ]
  }
};

const networkSelect = document.getElementById('swap-network');
const walletAddressInput = document.getElementById('swap-wallet-address');
const walletHint = document.getElementById('swap-wallet-hint');
const sellTokenSelect = document.getElementById('swap-sell-token');
const buyTokenSelect = document.getElementById('swap-buy-token');
const sellAmountInput = document.getElementById('swap-sell-amount');
const slippageSelect = document.getElementById('swap-slippage');
const connectBtn = document.getElementById('swap-connect-btn');
const quoteBtn = document.getElementById('swap-quote-btn');
const executeBtn = document.getElementById('swap-execute-btn');
const statusBox = document.getElementById('swap-status');
const buyAmountEl = document.getElementById('swap-buy-amount');
const estimatedAmountEl = document.getElementById('swap-estimated-amount');
const allowanceTargetEl = document.getElementById('swap-allowance-target');
const networkFeeEl = document.getElementById('swap-network-fee');
const routeSourcesEl = document.getElementById('swap-route-sources');
const selectedChainEl = document.getElementById('swap-selected-chain');
const sellAddressEl = document.getElementById('swap-sell-address');
const buyAddressEl = document.getElementById('swap-buy-address');
const requestedWalletEl = document.getElementById('swap-requested-wallet');
const connectedWalletEl = document.getElementById('swap-connected-wallet');
const approvalNeededEl = document.getElementById('swap-approval-needed');

let connectedWalletAddress = '';
let currentQuote = null;
let availableSellTokens = [];

function normalizeChainId(value) {
  if (!value) return null;
  const raw = String(value).trim().toLowerCase();
  if (!raw) return null;
  if (raw.startsWith('0x')) {
    const n = parseInt(raw, 16);
    return Number.isFinite(n) ? '0x' + n.toString(16) : null;
  }
  if (/^\d+$/.test(raw)) {
    const n = parseInt(raw, 10);
    return Number.isFinite(n) ? '0x' + n.toString(16) : null;
  }
  return null;
}

function shortenAddress(value) {
  if (!value || value.length < 10) return value || '--';
  return value.slice(0, 6) + '...' + value.slice(-4);
}

function setStatus(message, tone = 'neutral') {
  statusBox.textContent = message;
  statusBox.className = tone === 'error'
    ? 'status error'
    : tone === 'success'
      ? 'status success'
      : 'status';
}

function uniqueSources(route) {
  const fills = Array.isArray(route?.fills) ? route.fills : [];
  return Array.from(new Set(fills.map((fill) => fill.source).filter(Boolean)));
}

function formatUnits(rawAmount, decimals) {
  const value = Number(rawAmount || 0) / Math.pow(10, decimals);
  return Number.isFinite(value)
    ? value.toLocaleString('en-US', { maximumFractionDigits: decimals > 8 ? 8 : decimals })
    : '--';
}

function toBaseUnits(input, decimals) {
  const normalized = String(input || '').trim();
  if (!/^\d+(\.\d+)?$/.test(normalized)) {
    throw new Error('Invalid amount format.');
  }

  const [whole, fraction = ''] = normalized.split('.');
  const paddedFraction = (fraction + '0'.repeat(decimals)).slice(0, decimals);
  const combined = (whole + paddedFraction).replace(/^0+/, '') || '0';
  return combined;
}

function encodeApprove(spender, amountHex) {
  const spenderPart = spender.toLowerCase().replace(/^0x/, '').padStart(64, '0');
  const amountPart = amountHex.toLowerCase().replace(/^0x/, '').padStart(64, '0');
  return '0x095ea7b3' + spenderPart + amountPart;
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
    const message = data?.message || data?.error || 'Swap API request failed.';
    throw new Error(message);
  }
  return data;
}

async function connectWallet() {
  if (!window.ethereum) {
    setStatus('No injected EVM wallet found in this window.', 'error');
    return;
  }

  const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
  connectedWalletAddress = Array.isArray(accounts) && accounts[0] ? accounts[0] : '';
  walletAddressInput.value = connectedWalletAddress;
  connectedWalletEl.textContent = shortenAddress(connectedWalletAddress);
  walletHint.textContent = connectedWalletAddress && requestedWallet && connectedWalletAddress.toLowerCase() !== requestedWallet.toLowerCase()
    ? 'Connected wallet differs from the wallet currently viewed in the parent page.'
    : 'Connected wallet is ready for quotes and execution.';
  setStatus(connectedWalletAddress ? 'Wallet connected.' : 'Wallet connection returned no address.', connectedWalletAddress ? 'success' : 'error');
}

async function switchChainIfNeeded(chainIdHex) {
  if (!window.ethereum) throw new Error('No injected wallet found.');

  const currentChain = normalizeChainId(await window.ethereum.request({ method: 'eth_chainId' }));
  if (currentChain === chainIdHex) {
    return;
  }

  await window.ethereum.request({
    method: 'wallet_switchEthereumChain',
    params: [{ chainId: chainIdHex }],
  });
}

async function waitForReceipt(txHash, attempts = 40, delayMs = 2500) {
  for (let i = 0; i < attempts; i += 1) {
    const receipt = await window.ethereum.request({
      method: 'eth_getTransactionReceipt',
      params: [txHash],
    });

    if (receipt) {
      return receipt;
    }

    await new Promise((resolve) => setTimeout(resolve, delayMs));
  }

  throw new Error('Transaction confirmation timed out.');
}

function renderRouteSources(route) {
  const sources = uniqueSources(route);
  routeSourcesEl.innerHTML = sources.length
    ? sources.map((source) => `<span class="source">${escapeHtml(source)}</span>`).join('')
    : '<span class="source">No route metadata</span>';
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function networkBuyTokens(chainId) {
  return Array.isArray(NETWORKS[chainId]?.buyTokens) ? NETWORKS[chainId].buyTokens : [];
}

function selectedSellToken() {
  return availableSellTokens.find((token) => token.address === sellTokenSelect.value) || null;
}

function selectedBuyToken() {
  return networkBuyTokens(networkSelect.value).find((token) => token.address === buyTokenSelect.value) || null;
}

function syncTokenMeta() {
  const sellToken = selectedSellToken();
  const buyToken = selectedBuyToken();
  sellAddressEl.textContent = sellToken?.address || '--';
  buyAddressEl.textContent = buyToken?.address || '--';
}

function hydrateNetworkSelect() {
  networkSelect.innerHTML = Object.entries(NETWORKS)
    .filter(([, config]) => config.buyTokens.length > 0)
    .map(([chainId, config]) => `<option value="${chainId}">${config.name}</option>`)
    .join('');

  if (NETWORKS[requestedChainId]?.buyTokens?.length) {
    networkSelect.value = requestedChainId;
  }

  selectedChainEl.textContent = `Chain: ${NETWORKS[networkSelect.value]?.name || '--'}`;
}

async function loadWalletAssets() {
  const chainId = networkSelect.value;
  const targetWallet = connectedWalletAddress || requestedWallet;

  if (!targetWallet) {
    availableSellTokens = [];
    sellTokenSelect.innerHTML = '<option value="">Connect wallet first</option>';
    return;
  }

  const response = await fetch(`/api/wallet/overview?address=${encodeURIComponent(targetWallet)}&chain_id=${encodeURIComponent(chainId)}`, {
    headers: { Accept: 'application/json' },
    credentials: 'same-origin',
  });
  const payload = await response.json().catch(() => ({}));

  if (!response.ok || !payload?.assets?.available) {
    throw new Error(payload?.assets?.error || 'Failed to load wallet assets for swap.');
  }

  availableSellTokens = Array.isArray(payload.assets.assets)
    ? payload.assets.assets
        .filter((asset) => asset.address)
        .map((asset) => ({
          address: asset.address,
          symbol: asset.symbol,
          name: asset.name,
          decimals: Number(asset.decimals || 18),
          balance: Number(asset.balance || 0),
        }))
        .filter((asset) => asset.balance > 0)
    : [];

  sellTokenSelect.innerHTML = availableSellTokens.length
    ? availableSellTokens.map((token) => `<option value="${token.address}">${escapeHtml(token.symbol)} · ${token.balance.toLocaleString('en-US', { maximumFractionDigits: 6 })}</option>`).join('')
    : '<option value="">No ERC-20 balance on this network</option>';

  const buyOptions = networkBuyTokens(chainId);
  buyTokenSelect.innerHTML = buyOptions
    .map((token) => `<option value="${token.address}">${escapeHtml(token.symbol)} · ${escapeHtml(token.name)}</option>`)
    .join('');

  if (buyOptions[0]) {
    buyTokenSelect.value = buyOptions[0].address;
  }

  const currentSell = selectedSellToken();
  if (currentSell) {
    const nextBuy = buyOptions.find((token) => token.address.toLowerCase() !== currentSell.address.toLowerCase());
    if (nextBuy) {
      buyTokenSelect.value = nextBuy.address;
    }
  }

  syncTokenMeta();
}

async function fetchQuote() {
  const sellToken = selectedSellToken();
  const buyToken = selectedBuyToken();

  if (!connectedWalletAddress) {
    throw new Error('Connect wallet first.');
  }
  if (!sellToken || !buyToken) {
    throw new Error('Choose both sell and buy tokens.');
  }
  if (sellToken.address.toLowerCase() === buyToken.address.toLowerCase()) {
    throw new Error('Sell and buy token must differ.');
  }

  const sellAmountRaw = toBaseUnits(sellAmountInput.value, sellToken.decimals);
  if (sellAmountRaw === '0') {
    throw new Error('Sell amount must be greater than zero.');
  }

  const chainId = networkSelect.value;
  const payload = {
    chain_id: chainId,
    sell_token: sellToken.address,
    buy_token: buyToken.address,
    sell_amount: sellAmountRaw,
    taker: connectedWalletAddress,
    slippage_bps: Number(slippageSelect.value || 100),
  };

  const quote = await postJson('/api/wallet/swap/quote', payload);
  currentQuote = { ...quote, sellToken, buyToken, chainId };

  const estimatedBuy = formatUnits(quote.buyAmount, buyToken.decimals);
  buyAmountEl.textContent = `${estimatedBuy} ${buyToken.symbol}`;
  estimatedAmountEl.textContent = `${estimatedBuy} ${buyToken.symbol}`;
  allowanceTargetEl.textContent = quote?.issues?.allowance?.spender
    ? shortenAddress(quote.issues.allowance.spender)
    : 'Not required';
  networkFeeEl.textContent = quote.totalNetworkFee
    ? `${formatUnits(quote.totalNetworkFee, 18)} ${NETWORKS[chainId]?.name === 'Avalanche' ? 'AVAX' : 'ETH'}`
    : '--';
  approvalNeededEl.textContent = quote?.issues?.allowance ? 'Yes' : 'No';
  renderRouteSources(quote.route);
  setStatus('Firm quote loaded.', 'success');
}

async function ensureAllowance(quote) {
  if (!quote?.issues?.allowance?.spender) {
    return null;
  }

  const spender = quote.issues.allowance.spender;
  const tokenAddress = quote.sellToken.address;
  const maxUint256 = '0xffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';
  const approveData = encodeApprove(spender, maxUint256);

  setStatus('Approval required. Confirm approve transaction in wallet...');
  const approveTxHash = await window.ethereum.request({
    method: 'eth_sendTransaction',
    params: [{
      from: connectedWalletAddress,
      to: tokenAddress,
      data: approveData,
    }],
  });

  setStatus(`Approval sent: ${shortenAddress(approveTxHash)}. Waiting for confirmation...`);
  await waitForReceipt(approveTxHash);
  setStatus('Approval confirmed. Refreshing quote...', 'success');

  await fetchQuote();
}

async function executeSwap() {
  if (!window.ethereum) {
    throw new Error('No injected wallet provider found.');
  }

  if (!connectedWalletAddress) {
    await connectWallet();
  }

  await switchChainIfNeeded(networkSelect.value);
  if (!currentQuote) {
    await fetchQuote();
  }

  await ensureAllowance(currentQuote);

  if (!currentQuote?.transaction?.to || !currentQuote?.transaction?.data) {
    throw new Error('Quote transaction payload is missing.');
  }

  setStatus('Confirm swap transaction in wallet...');
  const txHash = await window.ethereum.request({
    method: 'eth_sendTransaction',
    params: [{
      from: connectedWalletAddress,
      to: currentQuote.transaction.to,
      data: currentQuote.transaction.data,
      value: currentQuote.transaction.value && currentQuote.transaction.value !== '0'
        ? '0x' + BigInt(currentQuote.transaction.value).toString(16)
        : undefined,
      gas: currentQuote.transaction.gas ? '0x' + BigInt(currentQuote.transaction.gas).toString(16) : undefined,
      gasPrice: currentQuote.transaction.gasPrice ? '0x' + BigInt(currentQuote.transaction.gasPrice).toString(16) : undefined,
    }],
  });

  setStatus(`Swap submitted: ${shortenAddress(txHash)}. Waiting for confirmation...`);
  await waitForReceipt(txHash);
  setStatus(`Swap confirmed: ${shortenAddress(txHash)}`, 'success');
}

async function initialize() {
  hydrateNetworkSelect();
  requestedWalletEl.textContent = requestedWallet ? shortenAddress(requestedWallet) : '--';
  connectedWalletEl.textContent = '--';

  networkSelect.addEventListener('change', async () => {
    selectedChainEl.textContent = `Chain: ${NETWORKS[networkSelect.value]?.name || '--'}`;
    currentQuote = null;
    buyAmountEl.textContent = '--';
    estimatedAmountEl.textContent = '--';
    allowanceTargetEl.textContent = '--';
    networkFeeEl.textContent = '--';
    approvalNeededEl.textContent = '--';
    routeSourcesEl.innerHTML = '<span class="source">No route yet</span>';
    await loadWalletAssets().catch((error) => setStatus(error.message || 'Failed to reload assets.', 'error'));
  });

  sellTokenSelect.addEventListener('change', () => {
    const sellToken = selectedSellToken();
    if (!sellToken) return;
    const buyOptions = networkBuyTokens(networkSelect.value);
    if (buyTokenSelect.value.toLowerCase() === sellToken.address.toLowerCase()) {
      const nextBuy = buyOptions.find((token) => token.address.toLowerCase() !== sellToken.address.toLowerCase());
      if (nextBuy) buyTokenSelect.value = nextBuy.address;
    }
    syncTokenMeta();
  });
  buyTokenSelect.addEventListener('change', syncTokenMeta);

  connectBtn.addEventListener('click', async () => {
    try {
      await connectWallet();
      await loadWalletAssets();
    } catch (error) {
      setStatus(error.message || 'Wallet connection failed.', 'error');
    }
  });

  quoteBtn.addEventListener('click', async () => {
    try {
      quoteBtn.disabled = true;
      await connectWallet();
      await loadWalletAssets();
      await fetchQuote();
    } catch (error) {
      setStatus(error.message || 'Failed to load quote.', 'error');
    } finally {
      quoteBtn.disabled = false;
    }
  });

  executeBtn.addEventListener('click', async () => {
    try {
      executeBtn.disabled = true;
      await connectWallet();
      await loadWalletAssets();
      await executeSwap();
    } catch (error) {
      setStatus(error.message || 'Swap execution failed.', 'error');
    } finally {
      executeBtn.disabled = false;
    }
  });

  await loadWalletAssets().catch(() => {
    setStatus('Connect wallet to load sell-side ERC-20 balances.', 'neutral');
  });
}

initialize();
</script>
</body>
</html>
