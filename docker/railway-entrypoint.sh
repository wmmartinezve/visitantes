#!/bin/sh
set -e

echo ">> Visitantes — arranque Railway"

if [ -z "$APP_KEY" ]; then
  echo "ERROR: APP_KEY no está definida. Genere una con: php artisan key:generate --show"
  exit 1
fi

php artisan package:discover --ansi

php artisan migrate --force --no-interaction

php artisan storage:link --force 2>/dev/null || true

php artisan config:cache
php artisan route:cache
php artisan view:cache

if [ "${RUN_DEMO_SEED:-false}" = "true" ] || [ "${RUN_SEED:-false}" = "true" ]; then
  echo ">> Ejecutando seeders demo (RUN_DEMO_SEED/RUN_SEED)"
  php artisan db:seed --force --no-interaction
fi

echo ">> Servidor en puerto ${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
