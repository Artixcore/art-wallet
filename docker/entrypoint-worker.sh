#!/bin/sh
set -e
cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "error: APP_KEY must be set in the runtime environment" >&2
    exit 1
fi

php artisan config:cache --no-interaction

exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600 "$@"
