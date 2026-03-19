# Міграція workspace → Laravel 8+

## Що зроблено

| Старий файл | Laravel-аналог |
|---|---|
| `autorith.php` + `auth.php` | `AuthController` + session |
| `login.php` (include guard) | `WorkspaceAuth` middleware |
| `document/index.php` | `DocumentController::index/show` |
| `library/doc-run.php` | `DocumentService::saveHead/saveBody/provodka` |
| `doc-index.php` (bodyAdd/del) | `DocumentController::bodyAdd/bodyDelete` |
| `result.php` / `result2.php` | `document/index.blade.php` + `document/zakaz.blade.php` |
| `client/index.php` + `run.php` | `ClientController` |
| `comp/index.php` + `run-comp.php` | `GoodsController` |
| `delete-comp.php` | `GoodsController::destroy` |
| `toggle-sklad.php` | `GoodsController::toggleSklad` |
| `money/index.php` | `MoneyController` |
| `admin/` | `AdminController` |
| `kurs/` | `KursController` |
| `library/filter.php` | `FilterService` + `filter.blade.php` |
| `library/lib.inc` (helpers) | `app/Helpers/helpers.php` (autoloaded) |
| `library/lib.inc` (class document) | `Document` model + `DocumentService` |
| `panel.php` | `partials/panel.blade.php` |
| `top_reklama.php` + `menu.php` | `layouts/app.blade.php` + `partials/top_reklama.blade.php` |
| `img/`, `js/`, `css/` | `public/img/`, `public/js/`, `public/css/` |
| `files/` | `storage/app/public/files/` → `/storage/files/` |
| `library/class.php` (idstatus check) | `StatusMin` middleware |

---

## Кроки встановлення (свіжий сервер)

```bash
# 1. Встановити Laravel залежності
composer install

# 2. Скопіювати .env
cp .env.example .env
php artisan key:generate

# 3. Налаштувати DB у .env
# DB_DATABASE=av8fund
# DB_USERNAME=root
# DB_PASSWORD=secret

# 4. Якщо НОВА база — запустити міграції
php artisan migrate

# 5. Якщо СТАРА база (існуюча) — НЕ запускати migrate
#    Просто підключитися до існуючої БД.
#    ВАЖЛИВО: Паролі в users.pass — md5.
#    При першому логіні вони автоматично конвертуються у bcrypt.

# 6. Скопіювати статику
cp -r /old-project/img    public/
cp -r /old-project/js     public/
cp -r /old-project/css    public/
cp    /old-project/styles.css public/css/
cp -r /old-project/files  storage/app/public/

# 7. Зробити storage доступним з веба
php artisan storage:link

# 8. Налаштувати web-сервер
#    Document root → /path/to/project/public
#    Apache: .htaccess вже є у public/
#    Nginx: add try_files $uri $uri/ /index.php?$query_string;
```

---

## Налаштування Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/workspace/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## Що ще залишилось зробити

1. **`document/show.blade.php`** — форма редагування документа (складна, з товарними рядками, підказками клієнта). Базується на `show.php` з попередніх сесій.
2. **`comp/show.blade.php`** — форма редагування товару (базується на `show.php` comp модуля).
3. **`comp/index.blade.php`** — список товарів з фільтрами.
4. **`client/index.blade.php`** + **`client/show.blade.php`** — список та форма клієнта.
5. **`partials/selects/*.blade.php`** — вже є reteil, sklads, oplata, status, reestr.
6. **SMS / Telegram** — налаштувати `.env` ключі `SMS_API_KEY`, `TELEGRAM_BOT_TOKEN`.
7. **`sadmin2/`** — super-admin модуль (окремий контролер).
8. **`news/`, `stat/`, `profile/`, `setting/`** — по одному контролеру кожен.

---

## Структура файлів Laravel-проекту

```
workspace/
├── app/
│   ├── Helpers/helpers.php          ← convert_from_base, h(), formatPhone, nextdate
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── DocumentController.php
│   │   │   ├── ClientController.php
│   │   │   ├── GoodsController.php
│   │   │   ├── MoneyController.php
│   │   │   ├── AdminController.php
│   │   │   ├── KursController.php
│   │   │   └── FilterController.php
│   │   ├── Kernel.php
│   │   └── Middleware/
│   │       ├── WorkspaceAuth.php    ← replaces login.php include guard
│   │       └── StatusMin.php        ← replaces if ($idstatus < N) exit;
│   ├── Models/
│   │   ├── User.php
│   │   ├── Document.php             ← covers document + z_document tables
│   │   ├── ZBody.php
│   │   ├── Comp.php
│   │   ├── Price.php
│   │   └── Conf.php
│   └── Services/
│       ├── DocumentService.php      ← save/provodka logic from doc-run.php
│       └── FilterService.php        ← filter logic from library/filter.php
├── bootstrap/app.php
├── config/
├── database/migrations/
├── public/                          ← DOCUMENT ROOT (Apache/Nginx)
│   ├── index.php
│   ├── .htaccess
│   ├── css/
│   ├── js/
│   └── img/
├── resources/views/
│   ├── layouts/app.blade.php
│   ├── partials/
│   │   ├── filter.blade.php
│   │   ├── panel.blade.php
│   │   ├── navigator.blade.php
│   │   ├── top_reklama.blade.php
│   │   └── selects/
│   ├── auth/login.blade.php
│   └── document/
│       ├── index.blade.php          ← result2.php (PO/RO/PN/RN/WO1/CH)
│       └── zakaz.blade.php          ← result.php (ZIN/ZOUT)
├── routes/web.php
└── storage/app/public/files/        ← uploaded files
```
