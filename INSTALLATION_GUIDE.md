# 🚀 Инструкция по установке и настройке системы чеков ДПІ

## 📋 Содержание
1. [Требования](#требования)
2. [Установка](#установка)
3. [Конфигурация](#конфигурация)
4. [Использование](#использование)
5. [Тестирование](#тестирование)
6. [Решение проблем](#решение-проблем)

---

## Требования

### Системные требования
- PHP 8.0 или выше
- Laravel 10 или выше
- MySQL 5.7 или выше (рекомендуется 8.0+)
- composer 2.0+

### API требования
- Учетные данные от налоговой инспекции Украины (ДПІ)
- API Key
- Secret Key
- Доступ к https://api.tax.gov.ua

---

## Установка

### Шаг 1: Обновить проект

```bash
cd laravel-api

# Обновить composer зависимости (если нужны новые пакеты)
composer update

# Или просто установить существующие
composer install
```

### Шаг 2: Запустить миграцию

```bash
# Вариант 1: Через docker-compose
make migrate

# Или напрямую
docker compose exec app php artisan migrate
```

**Что создается:**
- Таблица `tax_receipts` со всеми необходимыми полями
- Индексы для оптимизации запросов

### Шаг 3: Очистить кэш (если нужно)

```bash
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:cache
```

---

## Конфигурация

### Шаг 1: Получить учетные данные

Обратитесь в налоговую инспекцию Украины для получения:
- `API_KEY` - Ключ доступа к API
- `API_SECRET` - Секретный ключ
- `API_URL` - URL endpoint (обычно https://api.tax.gov.ua)

### Шаг 2: Добавить переменные в .env

```bash
# Отредактировать файл .env
nano .env
```

Добавить или обновить следующие строки:

```env
# Tax API Configuration
TAX_RECEIPTS_ENABLED=true
TAX_RECEIPTS_AUTO_REGISTER=false
TAX_API_URL=https://api.tax.gov.ua
TAX_API_KEY=your_api_key_here
TAX_API_SECRET=your_secret_key_here
TAX_API_TIMEOUT=30
```

### Шаг 3: Проверить конфигурацию

```bash
# Перезапустить приложение
docker compose restart app

# Проверить логи
docker compose exec app tail -f storage/logs/laravel.log
```

---

## Использование

### Через веб-интерфейс

#### 1. Открыть Settings

```
Dashboard → Налаштування → 🧾 Чеки ДПІ
```

#### 2. Настроить API (первый раз)

1. Нажать на карточку "🧾 Чеки ДПІ"
2. В модальном окне нажать на "⚙️ Налаштування API"
3. Ввести:
   - **API URL**: `https://api.tax.gov.ua`
   - **API Key**: Ваш ключ доступа
   - **Secret Key**: Ваш секретный ключ
4. Нажать "💾 Зберегти"

#### 3. Создать чек

1. Нажать "+ Додати чек"
2. Заполнить форму:
   - **Тип документа**: Прихід грошей (PO) или Видача грошей (RO)
   - **ID документа**: ID денежного документа
   - **ІПН платника**: ИНН компании (10 цифр)
   - **Касир**: ПИБ кассира
   - **Сума**: Сумма в гривнях
   - **Опис**: Описание товаров/услуг
3. Нажать "💾 Додати чек"

#### 4. Зарегистрировать чеки

**Опция 1: Зарегистрировать один чек**
- В таблице найти нужный чек со статусом "⏳ Очікування"
- Нажать "📤 Зареєстр."
- Статус изменится на "✓ Зареєстровано"

**Опция 2: Зарегистрировать все pending**
- Нажать "📤 Зареєструвати усі"
- Система пройдет по всем чекам и попытается их зарегистрировать
- Результат: сообщение с количеством успешных и неудачных

### Программно (через PHP)

#### Создать и зарегистрировать чек

```php
use App\Services\TaxReceiptService;

// Создать чек для денежного документа
$receipt = TaxReceiptService::createReceiptForMoneyDocument(
    firma: 1,  // ID компании
    documentId: '12345',  // ID документа PO
    documentType: 'PO',   // Тип: PO или RO
    amount: 1500.50,      // Сумма
    metadata: [
        'taxpayer_id' => '1234567890',  // ИНН платежника
        'cashier_name' => 'Иван Петров', // Имя кассира
        'goods_description' => 'Товары и услуги'  // Описание
    ]
);

// Зарегистрировать в налоговой
if ($receipt) {
    $receipt->registerAtTaxOffice();
}
```

#### Получить статистику

```php
use App\Services\TaxReceiptService;

$stats = TaxReceiptService::getReceiptStatistics(1);

echo "Всього: " . $stats['total'];       // Общее количество
echo "Зареєстровано: " . $stats['registered'];  // Зарегистрировано
echo "Очікування: " . $stats['pending'];        // Ожидают регистрации
echo "Помилки: " . $stats['failed'];            // Ошибки
```

#### Синхронизировать статусы

```php
use App\Services\TaxReceiptService;

// Попытаться зарегистрировать все pending чеки
$result = TaxReceiptService::syncReceiptStatuses(1);

echo "Синхронизировано: {$result['synced']}";
echo "Обновлено: {$result['updated']}";
echo "Ошибок: {$result['errors']}";
```

---

## Тестирование

### Тест 1: Проверка конфигурации

```bash
# Внутри контейнера
docker compose exec app php artisan tinker

# В tinker консоли:
> $config = App\Models\TaxReceipt::getApiConfig();
> dd($config);
```

Должны вывести:
```
[
  'base_url' => 'https://api.tax.gov.ua',
  'api_key' => 'your_api_key...',
  'timeout' => 30,
]
```

### Тест 2: Создание чека

```bash
docker compose exec app php artisan tinker

# В tinker консоли:
> $receipt = App\Models\TaxReceipt::createForDocument(
    firma: 1,
    documentId: 'TEST-001',
    documentType: 'PO',
    taxpayerId: '1234567890',
    cashierName: 'Test Cashier',
    amount: 100.00,
    goodsDescription: 'Test goods'
  );
> dd($receipt);
```

### Тест 3: Проверка БД

```bash
# Посмотреть созданные чеки
docker compose exec mysql mysql -u root -p$MYSQL_ROOT_PASSWORD -e "
  SELECT * FROM tax_receipts LIMIT 5;
"
```

### Тест 4: Просмотр логов

```bash
# Смотреть логи в реальном времени
docker compose exec app tail -f storage/logs/laravel.log

# Искать ошибки чеков
docker compose exec app grep "tax receipt" storage/logs/laravel.log
```

---

## Решение проблем

### Проблема 1: "API ключ налоговой не сконфигурирован"

**Решение:**
1. Проверить переменные в `.env`:
```bash
grep TAX_API .env
```

2. Если пусто, добавить:
```bash
echo "TAX_API_KEY=your_key" >> .env
```

3. Перезагрузить приложение:
```bash
docker compose restart app
```

### Проблема 2: "Таблица tax_receipts не найдена"

**Решение:**
```bash
# Запустить миграцию
docker compose exec app php artisan migrate

# Проверить
docker compose exec app php artisan migrate:status | grep tax_receipts
```

### Проблема 3: Чеки не регистрируются

**Проверьте:**

1. Статус в БД:
```bash
docker compose exec mysql mysql -u root -p$MYSQL_ROOT_PASSWORD -e "
  SELECT id, receipt_number, registration_status, error_message 
  FROM tax_receipts 
  LIMIT 1\G
"
```

2. Логи ошибок:
```bash
docker compose exec app grep "Failed to register" storage/logs/laravel.log -A 5
```

3. API доступность:
```bash
docker compose exec app curl -v https://api.tax.gov.ua/health
```

### Проблема 4: "Ошибка 403 Forbidden" при API запросе

**Решение:**
- Проверить API Key и Secret в `.env`
- Убедиться, что IP сервера добавлен в whitelist налоговой
- Проверить срок действия ключа (может истечь)

### Проблема 5: "Connection refused"

**Решение:**
1. Проверить статус контейнеров:
```bash
docker compose ps
```

2. Перезапустить приложение:
```bash
docker compose restart app
```

3. Проверить сетевую конфигурацию:
```bash
docker compose logs app
```

---

## Команды Docker Compose

```bash
# Запустить все
make up

# Остановить
make down

# Запустить миграции
make migrate

# Bash в контейнер
make bash

# Логи
make logs

# Tinker console
docker compose exec app php artisan tinker
```

---

## Автоматизация (Опционально)

### Настроить периодическую регистрацию

Добавить в `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Регистрировать pending чеки каждые 5 минут
    $schedule->command('tax:register-pending')
        ->everyFiveMinutes()
        ->withoutOverlapping();
    
    // Очистить старые ошибочные чеки раз в неделю
    $schedule->call(function () {
        foreach (\App\Models\Firma::all() as $firma) {
            TaxReceiptService::cleanupFailedReceipts($firma->id, 30);
        }
    })->weekly();
}
```

Затем запустить scheduler:

```bash
# Добавить в crontab
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Поддержка и помощь

- 📖 Документация: `TAX_RECEIPTS_README.md`
- 💻 Примеры кода: `INTEGRATION_EXAMPLES.php`
- 📝 Структура БД: `tax_receipts_schema.sql`
- 🔍 Логи: `storage/logs/laravel.log`

---

**Версия:** 1.0  
**Дата:** 20 апреля 2026  
**Автор:** GitHub Copilot
