# Эта папка остается доступной для raw MySQL init, но основной bootstrap
# проекта теперь выполняется через Compose-сервис `bootstrap`.
#
# Он делает:
# 1. `php artisan migrate --force`
# 2. `php artisan db:import-maha --path=/var/www/html/filtered_maha_2.sql --with-users`
# 3. `php artisan db:import-legacy-users --path=/var/www/html/import_users.sql`
#
# Поэтому для обычного старта достаточно `docker compose up -d`.
