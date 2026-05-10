# Локальное хранилище газа для Sui (без Node.js на сервере)

Спонсирование выполняется **на PHP** через пакет [`inodrahq/sui-sdk`](https://github.com/inodrahq/php-sui-sdk): десериализация `TransactionKind` (BCS), подбор монет газа у сервисного кошелька, оценка бюджета через `sui_dryRunTransactionBlock`, сборка `TransactionData`, подпись спонсора.

## Требования

- **PHP 8.2+** (минимум пакета `inodrahq/sui-sdk`; в `composer.json` проекта указано `"php": "^8.2"`).
- Расширения PHP: **`ext-sodium`**, **`ext-bcmath`**, **`ext-openssl`**.
- После изменений выполните в `laravel-api`:

```bash
composer update
```

**Node.js на сервере не нужен.**

## Какой кошелёк использовать

Нужен **отдельный «сервисный» Sui-кошелёк только для оплаты газа**, не тот же адрес, что у пользователей.

Подойдёт любой стандартный кошелёк Sui, например:

- [Sui Wallet](https://chrome.google.com/webstore/detail/sui-wallet) (расширение Mysten)
- [Surf](https://surfwallet.io/) и другие кошельки с экспортом ключа в формате Sui

**Рекомендации:** новый адрес только под газ, минимально достаточный баланс SUI на нужной сети, ключ **`suiprivkey1...`** только в `.env` на сервере.

## Переменные окружения

| Переменная | Описание |
|------------|-----------|
| `SUI_RPC_URL` | JSON-RPC полной ноды (**та же сеть**, что у SPA), например `https://fullnode.testnet.sui.io:443`. |
| `SUI_GAS_SPONSOR_PRIVATE_KEY` | Экспортированный `suiprivkey1...` сервисного кошелька. |

Если заданы **и** локальный ключ **и** Shinami, используется **локальный** спонсор.

## Поведение API

- `GET /api/auth/zklogin/config` — `gasSponsorshipEnabled: true` при настроенном локальном ключе (и установленном SDK) **или** Shinami; поле `gasSponsorshipProvider`: `"local"` \| `"shinami"` \| `null`.
- `POST /api/sui/shinami/sponsor-transaction` — единая точка спонсирования (имя маршрута историческое).

## Если `composer update` не проходит

Проверьте версию PHP (`php -v` ≥ 8.2) и наличие расширений. Альтернатива без локального ключа — только **Shinami** (`SHINAMI_GAS_ACCESS_KEY`).
