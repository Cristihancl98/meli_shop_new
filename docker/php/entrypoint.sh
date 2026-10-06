#!/bin/sh
set -e

READY_FLAG=storage/framework/.docker-ready

if [ "$CONTAINER_ROLE" = "app" ]; then
    rm -f "$READY_FLAG"

    [ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist
    [ -f .env ] || cp .env.example .env

    grep -q '^APP_KEY=base64' .env || php artisan key:generate --force
    grep -qE '^JWT_SECRET=.+' .env || php artisan jwt:secret --force

    until mysqladmin ping --skip-ssl -h "$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent >/dev/null 2>&1; do
        echo "Esperando MySQL..."; sleep 2
    done

    php artisan tenancy:install
    php artisan tenants:migrate --force
    [ -L public/storage ] || php artisan storage:link

    touch "$READY_FLAG"
else
    until [ -f "$READY_FLAG" ]; do
        echo "Esperando a que app termine de inicializar..."; sleep 3
    done
fi

exec "$@"
