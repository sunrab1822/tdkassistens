#!/bin/bash
set -e

mkdir -p /data/chroma /data/ollama/models

cd /var/www

# .env perzisztensen a /data volume-ban, hogy az APP_KEY túlélje a rebuildet
if [ ! -f /data/.env ]; then
    cp .env.example /data/.env
fi
ln -sf /data/.env .env

touch /data/database.sqlite
chown -R www-data:www-data /data /var/www/storage /var/www/bootstrap/cache
chown www-data:www-data /var/www/public

WWW="su www-data -s /bin/sh -c"

if [ -z "$APP_KEY" ] && ! grep -q '^APP_KEY=base64:' /data/.env; then
    $WWW "cd /var/www && php artisan key:generate --force --no-interaction"
fi

$WWW "cd /var/www && php artisan migrate --force --no-interaction"
$WWW "cd /var/www && php artisan storage:link --no-interaction" || true
$WWW "cd /var/www && php artisan config:cache --no-interaction"
$WWW "cd /var/www && php artisan route:cache --no-interaction"
$WWW "cd /var/www && php artisan view:cache --no-interaction"

exec supervisord -n -c /etc/supervisor/conf.d/app.conf
