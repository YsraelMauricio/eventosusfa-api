#!/bin/sh
set -eu
APACHE_PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${APACHE_PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${APACHE_PORT}>/g" /etc/apache2/sites-available/000-default.conf
if [ -z "${APP_KEY:-}" ]; then
  echo "APP_KEY must be set in the service environment." >&2
  exit 1
fi
php artisan config:clear
php artisan migrate --force
if [ "${RUN_SEED:-false}" = "true" ]; then php artisan db:seed --force; fi
exec "$@"
