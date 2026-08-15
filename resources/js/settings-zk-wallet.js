import { jwtToAddress } from '@mysten/sui/zklogin';

function loadGoogleIdentityScript() {
  if (window.google?.accounts?.id) {
    return Promise.resolve(window.google.accounts.id);
  }

  return new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-settings-google-identity="true"]');
    if (existing) {
      existing.addEventListener('load', () => {
        if (window.google?.accounts?.id) {
          resolve(window.google.accounts.id);
        } else {
          reject(new Error('Google Identity не загрузился.'));
        }
      }, { once: true });
      existing.addEventListener('error', () => reject(new Error('Google Identity не загрузился.')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.defer = true;
    script.dataset.settingsGoogleIdentity = 'true';
    script.onload = () => {
      if (window.google?.accounts?.id) {
        resolve(window.google.accounts.id);
      } else {
        reject(new Error('Google Identity не загрузился.'));
      }
    };
    script.onerror = () => reject(new Error('Google Identity не загрузился.'));
    document.head.appendChild(script);
  });
}

async function postJson(url, payload) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(data.message || `Request failed: ${response.status}`);
  }
  return data;
}

function setText(selector, value) {
  const node = document.querySelector(selector);
  if (node) {
    node.textContent = value;
  }
}

function setFeedback(message, isError = false) {
  const node = document.getElementById('zk-wallet-feedback');
  if (!node) {
    return;
  }
  node.style.display = 'block';
  node.textContent = message;
  node.style.color = isError ? '#fca5a5' : '#86efac';
}

function renderWallet(address) {
  const card = document.getElementById('zk-wallet-card');
  const addressNode = document.getElementById('zk-wallet-address');
  const createBtn = document.getElementById('zk-wallet-create-btn');
  if (card) {
    card.classList.add('is-linked');
  }
  if (addressNode) {
    addressNode.textContent = address;
    addressNode.setAttribute('title', address);
  }
  if (createBtn) {
    createBtn.classList.add('d-none');
  }
  setText('#zk-wallet-status-badge', 'Створено');
}

export function initSettingsZkWallet(options) {
  const createBtn = document.getElementById('zk-wallet-create-btn');
  if (!createBtn) {
    return;
  }

  const googleClientId = String(options?.googleClientId || '').trim();
  if (!googleClientId) {
    createBtn.disabled = true;
    setFeedback('Google Client ID не налаштований.', true);
    return;
  }

  createBtn.addEventListener('click', async () => {
    createBtn.disabled = true;
    setFeedback('Відкриваємо Google авторизацію...');

    try {
      const googleIdentity = await loadGoogleIdentityScript();

      await new Promise((resolve, reject) => {
        googleIdentity.cancel();
        googleIdentity.initialize({
          client_id: googleClientId,
          ux_mode: 'popup',
          use_fedcm_for_prompt: false,
          cancel_on_tap_outside: false,
          callback: ({ credential }) => {
            if (!credential) {
              reject(new Error('Google не повернув ID token.'));
              return;
            }

            resolve(credential);
          },
        });

        googleIdentity.prompt((notification) => {
          if (
            notification.isNotDisplayed?.()
            || notification.isSkippedMoment?.()
            || notification.isDismissedMoment?.()
          ) {
            reject(new Error('Google авторизацію скасовано або заблоковано браузером.'));
          }
        });
      }).then(async (credential) => {
        setFeedback('Генеруємо Sui zkLogin адресу...');
        const saltPayload = await postJson('/api/auth/zklogin/google/salt', { jwt: credential });
        const walletAddress = jwtToAddress(credential, saltPayload.salt, false);
        const loginPayload = await postJson('/api/auth/zklogin/google/login', {
          jwt: credential,
          address: walletAddress,
        });
        renderWallet(loginPayload.wallet_address || walletAddress);
        setFeedback('Sui zk-гаманець створено та збережено.');
      });
    } catch (error) {
      setFeedback(error instanceof Error ? error.message : 'Не вдалося створити zk-гаманець.', true);
      createBtn.disabled = false;
    }
  });
}

window.initSettingsZkWallet = initSettingsZkWallet;
