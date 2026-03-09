# Makefile — быстрые команды для sklad docker
# Использование: make up | make down | make bash | make migrate | ...

.PHONY: up down build restart bash logs migrate seed fresh key perm pma

## ── Запуск ────────────────────────────────────────────────────────────────────

up:         ## Запустить все контейнеры (фон)
	docker compose up -d

down:       ## Остановить и удалить контейнеры
	docker compose down

build:      ## Пересобрать образ app
	docker compose build --no-cache app

restart:    ## Перезапустить app
	docker compose restart app

## ── Разработка ───────────────────────────────────────────────────────────────

bash:       ## Открыть bash внутри app-контейнера
	docker compose exec app bash

logs:       ## Хвост логов Laravel
	docker compose exec app tail -f storage/logs/laravel.log

apache-log: ## Хвост Apache error log
	docker compose exec app tail -f /var/log/apache2/error.log

## ── Laravel artisan ──────────────────────────────────────────────────────────

key:        ## Сгенерировать APP_KEY
	docker compose exec app php artisan key:generate

migrate:    ## Запустить миграции
	docker compose exec app php artisan migrate

seed:       ## Запустить сидеры
	docker compose exec app php artisan db:seed

fresh:      ## Дропнуть все таблицы и пересоздать (ОСТОРОЖНО: удаляет данные)
	docker compose exec app php artisan migrate:fresh --seed

cache-clear: ## Очистить кэш конфига, роутов, вьюх
	docker compose exec app php artisan optimize:clear

storage-link: ## Создать symlink public/storage
	docker compose exec app php artisan storage:link

perm:       ## Исправить права на storage и cache
	docker compose exec app chown -R www-data:www-data storage bootstrap/cache
	docker compose exec app chmod -R 775 storage bootstrap/cache

## ── БД ───────────────────────────────────────────────────────────────────────

db-dump:    ## Дамп базы в файл backup.sql
	docker compose exec db mysqldump -usklad -psklad sklad > backup.sql
	@echo "Saved to backup.sql"

db-import:  ## Импортировать backup.sql в контейнер
	docker compose exec -T db mysql -usklad -psklad sklad < backup.sql
	@echo "Import done"

## ── Состояние ────────────────────────────────────────────────────────────────

ps:         ## Статус контейнеров
	docker compose ps

pma:        ## Открыть phpMyAdmin в браузере (Mac/Linux)
	open http://localhost:8081 2>/dev/null || xdg-open http://localhost:8081

app-open:   ## Открыть приложение в браузере
	open http://localhost:8080 2>/dev/null || xdg-open http://localhost:8080

## ── Помощь ───────────────────────────────────────────────────────────────────

help:       ## Показать этот список команд
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
	awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'
