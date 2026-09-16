#!/bin/sh
#
# LNX-360 container entrypoint.
#
# Guarantees an APP_KEY exists, then hands off to the serversideup/php entrypoint
# (which runs the Laravel automations and starts NGINX + PHP-FPM, or the custom
# command for the queue and scheduler containers).
#
# If APP_KEY is not provided in the environment, a key is generated once and
# persisted in the storage volume so every container in the stack, and every
# restart, shares the same key. Set APP_KEY explicitly to opt out.

set -eu

APP_ROOT="${APP_BASE_DIR:-/var/www/html}"
KEY_FILE="${APP_KEY_FILE:-$APP_ROOT/storage/app/.app_key}"

if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s "$KEY_FILE" ]; then
        mkdir -p "$(dirname "$KEY_FILE")"
        (
            flock 9
            if [ ! -s "$KEY_FILE" ]; then
                php "$APP_ROOT/artisan" key:generate --show --no-ansi --no-interaction | tr -d '[:space:]' > "$KEY_FILE.tmp"
                chmod 600 "$KEY_FILE.tmp"
                mv "$KEY_FILE.tmp" "$KEY_FILE"
                echo "lnx360: generated APP_KEY and stored it in $KEY_FILE"
            fi
        ) 9>"$KEY_FILE.lock"
    fi

    APP_KEY="$(cat "$KEY_FILE")"
    export APP_KEY
fi

exec docker-php-serversideup-entrypoint "$@"
