#!/bin/sh
set -eu

php artisan migrate --force

if [ "${SEED_ON_DEPLOY:-false}" = "true" ]; then
    php artisan db:seed --force
fi
