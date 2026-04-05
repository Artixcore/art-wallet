#!/bin/sh
set -e
cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "error: APP_KEY must be set in the runtime environment" >&2
    exit 1
fi

php artisan package:discover --ansi --no-interaction 2>/dev/null || true
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction 2>/dev/null || true
php artisan view:cache --no-interaction 2>/dev/null || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}" --no-reload
