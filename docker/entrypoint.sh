#!/usr/bin/env bash
set -e

if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is not set -- generating one at runtime. Set a"
    echo "real, stable one in Render's env vars instead, or sessions and"
    echo "encrypted settings break on every restart."
    php artisan key:generate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --force

exec php -S 0.0.0.0:"${PORT:-10000}" -t public
