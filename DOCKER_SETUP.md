# Локальная разработка в Docker — sklad

## Порты

| Сервис     | URL                         | Назначение         |
|------------|-----------------------------|--------------------|
| Приложение | http://localhost:**8080**   | Laravel + Apache   |
| phpMyAdmin | http://localhost:**8081**   | Управление БД      |
| MySQL      | localhost:**3307**          | Прямое подключение |

> Порт 3307 (не 3306) — чтобы не конфликтовать с локально установленным MySQL.

---

## Быстрый старт

```bash
# 1. Клонировать репозиторий
git clone https://github.com/VictorAnokhin/sklad.git
cd sklad

# 2. Скопировать .env для Docker
cp .env.docker .env
# Если .env.docker нет — скопировать из .env.example и изменить DB_HOST=db

# 3. Запустить контейнеры
docker compose up -d

# 4. Подождать ~10 секунд пока MySQL инициализируется, затем:
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate

# 5. Открыть в браузере
open http://localhost:8080
```

---

## Если уже есть дамп базы (перенос с продакшена)

```bash
# Вариант А — автоматически при первом старте
cp your_dump.sql docker/mysql/init/01_dump.sql
docker compose up -d
# MySQL сам выполнит dump при первом старте (когда volume пустой)

# Вариант Б — вручную в уже запущенный контейнер
docker compose up -d
docker compose exec -T db mysql -usklad -psklad sklad < your_dump.sql

# Или через Makefile:
make db-import   # импортирует backup.sql
```

---

## Переменные .env для Docker

```dotenv
DB_HOST=db        # ← имя сервиса в docker-compose, НЕ localhost!
DB_PORT=3306      # ← внутренний порт контейнера, НЕ 3307!
DB_DATABASE=sklad
DB_USERNAME=sklad
DB_PASSWORD=sklad
```

**Частая ошибка**: ставить `DB_HOST=localhost` или `DB_PORT=3307`.  
Внутри Docker-сети хост = имя сервиса (`db`), порт = 3306.  
3307 — это только для подключения с хост-машины (TablePlus, DBeaver и т.д.)

---

## Подключение внешнего клиента к БД (TablePlus / DBeaver)

```
Host:     127.0.0.1
Port:     3307
User:     sklad
Password: sklad
Database: sklad
```

---

## Makefile — удобные команды

```bash
make up           # запустить
make down         # остановить
make bash         # войти в bash контейнера
make logs         # laravel.log в реальном времени
make migrate      # php artisan migrate
make fresh        # migrate:fresh (СБРАСЫВАЕТ ДАННЫЕ)
make key          # php artisan key:generate
make perm         # исправить права storage/
make db-dump      # сохранить backup.sql
make db-import    # загрузить backup.sql
make cache-clear  # очистить кэш Laravel
make pma          # открыть phpMyAdmin в браузере
make help         # показать все команды
```

---

## Volumes — где хранятся данные

| Volume / mount | Что хранит |
|---|---|
| `db_data` (Docker volume) | Данные MySQL — сохраняются между `down/up` |
| `./storage` → контейнер | Загруженные файлы, логи, сессии |
| `.` → `/var/www/html` | Код приложения (live sync для разработки) |

```bash
# Полностью сбросить БД (удалить volume):
docker compose down -v   # -v удаляет volumes!
docker compose up -d
```

---

## Apple Silicon (M1/M2/M3)

Образы MySQL 8.0 и phpmyadmin работают на ARM нативно.  
Если возникнут проблемы с MySQL — замените в docker-compose.yml:

```yaml
db:
  image: mysql:8.0
  platform: linux/arm64   # добавить эту строку
```

Или использовать MariaDB (100% совместима, лучше работает на ARM):

```yaml
db:
  image: mariadb:10.11
  environment:
    MARIADB_ROOT_PASSWORD: secret
    MARIADB_DATABASE: sklad
    MARIADB_USER: sklad
    MARIADB_PASSWORD: sklad
```

---

## Решение типичных проблем

### "Connection refused" / "SQLSTATE[HY000]"
```bash
# Подождать пока MySQL готов:
docker compose logs db
# Должна быть строка: "ready for connections"
# Затем:
docker compose restart app
```

### "Permission denied" на storage/
```bash
make perm
# или вручную:
docker compose exec app chmod -R 775 storage bootstrap/cache
```

### "Class not found" / "View not found"
```bash
make cache-clear
# или:
docker compose exec app php artisan optimize:clear
```

### Пересборка после изменения Dockerfile
```bash
docker compose build --no-cache app
docker compose up -d
```

### Посмотреть ошибки Apache
```bash
docker compose exec app cat /var/log/apache2/error.log
```
