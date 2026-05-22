#!/usr/bin/env sh
set -e

cd /var/www/html

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true

if [ ! -f .env ] && [ -f .env.example ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

php artisan optimize:clear --ansi >/dev/null 2>&1 || true
php artisan package:discover --ansi >/dev/null 2>&1 || true

if [ -f .env ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --ansi --force >/dev/null
fi

php artisan storage:link --force --ansi >/dev/null 2>&1 || true

exec "$@"
