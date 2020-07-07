#!/bin/bash

if [ -f .env ]; then
  source .env
fi

# Storage folder permissions
chown -R www-data storage/

composer install --no-interaction

# Set application key
if [ -z "$APP_KEY" ]; then
  touch .env
  if ! grep -q ^APP_KEY= .env; then
    echo 'APP_KEY=' >> .env 
  fi
  php artisan key:generate
fi

exec "$@"
