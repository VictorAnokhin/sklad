# 🧾 Система управления чеками налоговой инспекции Украины

## Описание

Интегрированная система для управления и регистрации чеков в налоговой инспекции Украины (ДПІ / МТОВ). Позволяет:

- 📝 Создавать чеки для приходных (PO) и расходных (RO) ордеров
- 📤 Регистрировать чеки в системе налоговой инспекции через API
- 📊 Отслеживать статус регистрации чеков
- 🔄 Синхронизировать статусы в реальном времени
- 📋 Управлять чеками в интерфейсе Settings

## Структура компонентов

### Backend (Laravel)

#### Таблица базы данных
```
tax_receipts
├── id
├── firma (компания)
├── receipt_number (уникальный номер чека)
├── document_id (ID документа PO/RO)
├── document_type (PO или RO)
├── taxpayer_id (ИНН платежника)
├── cashier_name (имя кассира)
├── amount (сумма)
├── goods_description (описание товаров)
├── registration_status (pending, registered, failed)
├── tax_office_receipt_id (ID чека в ДПІ)
├── tax_office_response (ответ от ДПІ в JSON)
├── error_message (текст ошибки)
├── registered_at (дата регистрации)
└── timestamps (created_at, updated_at)
```

#### Модель: `TaxReceipt`
Файл: `app/Models/TaxReceipt.php`

**Основные методы:**
- `registerAtTaxOffice()` - Регистрировать чек в налоговой
- `createForDocument()` - Создать чек для документа
- `getStatistics()` - Получить статистику
- `getForUI()` - Получить данные для интерфейса

**Конфигурация:**
```php
TAX_API_URL=https://api.tax.gov.ua
TAX_API_KEY=your_api_key
TAX_API_SECRET=your_secret_key
TAX_API_TIMEOUT=30
```

#### Контроллер: `TaxReceiptController`
Файл: `app/Http/Controllers/TaxReceiptController.php`

**API endpoints:**
```
GET    /settings/tax-receipts                    # Получить список
POST   /settings/tax-receipts                    # Создать чек
DELETE /settings/tax-receipts/{id}               # Удалить чек
POST   /settings/tax-receipts/{id}/register      # Зарегистрировать один
POST   /settings/tax-receipts/register-pending   # Зарегистрировать все pending
GET    /settings/tax-receipts/statistics         # Получить статистику
GET    /settings/tax-receipts/settings           # Получить настройки API
POST   /settings/tax-receipts/settings           # Сохранить настройки API
```

#### Сервис: `TaxReceiptService`
Файл: `app/Services/TaxReceiptService.php`

**Основные методы:**
```php
// Создать чек для денежного документа
TaxReceiptService::createReceiptForMoneyDocument($firma, $docId, $type, $amount, $metadata);

// Получить настройки интеграции
TaxReceiptService::getIntegrationSettings($firma);

// Синхронизировать статусы
TaxReceiptService::syncReceiptStatuses($firma);

// Очистить старые ошибочные чеки
TaxReceiptService::cleanupFailedReceipts($firma, 30);

// Проверить наличие чека
TaxReceiptService::receiptExistsForDocument($firma, $docId, $type);
```

### Frontend (Blade/JavaScript)

#### Модальное окно
Файл: `resources/views/settings/index.blade.php`

**Компоненты:**
1. **Карточка в Settings** - Показывает количество чеков
2. **Модальное окно Чеков** (`#modalTaxReceipts`) - Управление чеками
3. **Форма создания** - Добавление нового чека
4. **Таблица чеков** - Список с действиями
5. **Модальное окно API Settings** - Настройка интеграции

#### JavaScript функции
```javascript
loadTaxReceipts()           // Загрузить список чеков
loadStatistics()            // Загрузить статистику
renderReceipts(items)       // Отрендерить таблицу
registerPendingReceipts()   // Зарегистрировать все pending
deleteReceipt(id)           // Удалить чек
registerReceipt(id)         // Зарегистрировать один чек
```

## Интеграция с денежными документами (PO/RO)

### Автоматическая регистрация

При сохранении денежного документа можно автоматически создать чек:

```php
// В контроллере сохранения документа
use App\Services\TaxReceiptService;

TaxReceiptService::createReceiptForMoneyDocument(
    firma: $firma,
    documentId: $money->id,
    documentType: 'PO',  // или 'RO'
    amount: $money->summa,
    metadata: [
        'taxpayer_id' => $taxpayerId,
        'cashier_name' => $cashierName,
        'goods_description' => 'Описание...'
    ]
);
```

### Миграция

Для создания таблицы запустите:
```bash
php artisan migrate
```

Файл миграции: `database/migrations/2026_04_20_100000_create_tax_receipts_table.php`

## API налоговой инспекции Украины

### Endpoint для регистрации чека

```
POST https://api.tax.gov.ua/receipts/register
```

### Payload (JSON)
```json
{
  "receipt_number": "RCP-firma-timestamp-random",
  "document_id": "12345",
  "document_type": "PO",
  "taxpayer_id": "1234567890",
  "cashier_name": "Иван Петров",
  "amount": 1500.50,
  "goods_description": "Товары и услуги",
  "timestamp": "2026-04-20T10:30:00Z"
}
```

### Response (успешный)
```json
{
  "receipt_id": "TAX-2026-04-20-001",
  "status": "registered",
  "timestamp": "2026-04-20T10:30:15Z"
}
```

### Response (ошибка)
```json
{
  "message": "Invalid taxpayer ID",
  "code": "ERR_INVALID_TAXPAYER"
}
```

## Настройка окружения

### .env файл
```bash
# Tax API Configuration
TAX_RECEIPTS_ENABLED=true
TAX_RECEIPTS_AUTO_REGISTER=false
TAX_API_URL=https://api.tax.gov.ua
TAX_API_KEY=your_api_key_from_tax_authority
TAX_API_SECRET=your_secret_key
TAX_API_TIMEOUT=30
```

## Статусы чеков

| Статус | Описание |
|--------|-----------|
| `pending` | Чек создан, ожидает регистрации в налоговой |
| `registered` | ✓ Успешно зарегистрирован в налоговой |
| `failed` | ✗ Ошибка при регистрации |

## Использование в интерфейсе

### 1. Открыть Settings → 🧾 Чеки ДПІ

### 2. Настроить API
- Нажать на кнопку "⚙️ Налаштування API"
- Ввести учетные данные из налоговой инспекции
- Сохранить

### 3. Добавить чек
- Нажать "+ Додати чек"
- Выбрать тип документа (PO или RO)
- Ввести данные:
  - ID документа
  - ИНН платежника
  - Имя кассира
  - Сумму
  - Описание товаров
- Нажать "💾 Додати чек"

### 4. Зарегистрировать чеки
- **Один чек:** Нажать "📤 Зареєстр." на нужной строке
- **Все pending:** Нажать "📤 Зареєструвати усі"

### 5. Отслеживать статус
- Таблица показывает текущий статус каждого чека
- Дата регистрации отображается после успешной регистрации
- При ошибке показывается сообщение об ошибке

## Логирование

Все события логируются в `storage/logs/laravel.log`:

```
[2026-04-20 10:30:15] local.INFO: Tax receipt created for money document {"receipt_id":1,"receipt_number":"RCP-1-20260420103015-1234"}
[2026-04-20 10:31:00] local.INFO: Tax receipt registered: RCP-1-20260420103015-1234 {"receipt_id":"TAX-2026-04-20-001"}
[2026-04-20 10:32:45] local.ERROR: Failed to register tax receipt: RCP-1-20260420103015-5678 {"error":"Invalid taxpayer ID"}
```

## Примеры кода

### Создать чек программно

```php
use App\Models\TaxReceipt;
use App\Services\TaxReceiptService;

// Вариант 1: Через сервис
$receipt = TaxReceiptService::createReceiptForMoneyDocument(
    firma: 1,
    documentId: '12345',
    documentType: 'PO',
    amount: 1500.50,
    metadata: [
        'taxpayer_id' => '1234567890',
        'cashier_name' => 'Иван Петров',
    ]
);

// Вариант 2: Напрямую через модель
$receipt = TaxReceipt::createForDocument(
    firma: 1,
    documentId: '12345',
    documentType: 'PO',
    taxpayerId: '1234567890',
    cashierName: 'Иван Петров',
    amount: 1500.50,
    goodsDescription: 'Описание товаров'
);

// Зарегистрировать в налоговой
$receipt->registerAtTaxOffice();
```

### Получить статистику

```php
use App\Services\TaxReceiptService;

$stats = TaxReceiptService::getReceiptStatistics($firma);

echo "Всего: " . $stats['total'];
echo "Зареєстровано: " . $stats['registered'];
echo "Очікування: " . $stats['pending'];
echo "Помилки: " . $stats['failed'];
```

## Требования

- PHP 8.0+
- Laravel 10+
- MySQL 5.7+
- Доступ к API налоговой инспекции Украины

## Поддержка

Для вопросов и поддержки обратитесь к администратору системы.

## Лицензия

Все права защищены © 2026 AV8 Capital DAO
