FROM php:8.2-apache

# ── System deps ───────────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev \
    libzip-dev libicu-dev libpq-dev libgmp-dev \
    && docker-php-ext-install \
        pdo pdo_mysql mysqli mbstring exif pcntl bcmath gd zip intl gmp \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Apache: enable mod_rewrite for Laravel routing ────────────────────────────
RUN a2enmod rewrite headers

# ── Apache config: point DocumentRoot → /var/www/html/public ─────────────────
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# ── App files ─────────────────────────────────────────────────────────────────
WORKDIR /var/www/html

COPY composer.json composer.lock ./

# ── Install PHP deps ──────────────────────────────────────────────────────────
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && composer clear-cache

COPY --chown=www-data:www-data . .

RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi

# ── Storage permissions ───────────────────────────────────────────────────────
RUN chmod -R 775 storage bootstrap/cache

EXPOSE 80
