# syntax=docker/dockerfile:1.7
#
# LNX-360 production image.
#
#   docker build -t ghcr.io/lnxinc/monitor:local .
#
# Stage 1 installs Composer and npm dependencies and compiles the Vite bundle.
# Stage 2 is the runtime: PHP-FPM + NGINX (serversideup/php), unprivileged, with
# the Laravel automations (migrate, storage:link, config/route/view cache) enabled.
# The same image runs the web server, the queue worker and the scheduler; only
# the command differs (see compose.yaml).

ARG PHP_VERSION=8.4

# ---------------------------------------------------------------------------
# Stage 1: build
# ---------------------------------------------------------------------------
FROM serversideup/php:${PHP_VERSION}-cli AS build

USER root

# Node.js is needed for the Vite build. Wayfinder's Vite plugin shells out to
# `php artisan wayfinder:generate`, so PHP and Node must live in the same stage.
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
        --no-dev --no-scripts --no-autoloader --no-interaction --no-progress --prefer-dist

COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund

COPY . .

RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && php artisan package:discover --ansi \
    && npm run build \
    && rm -rf node_modules bootstrap/cache/*.php

# ---------------------------------------------------------------------------
# Stage 2: runtime
# ---------------------------------------------------------------------------
FROM serversideup/php:${PHP_VERSION}-fpm-nginx AS runtime

LABEL org.opencontainers.image.title="LNX-360" \
      org.opencontainers.image.description="Network operations and monitoring platform by LNX Inc." \
      org.opencontainers.image.vendor="LNX Inc." \
      org.opencontainers.image.url="https://lnxinc.com" \
      org.opencontainers.image.source="https://github.com/lnxinc/monitor" \
      org.opencontainers.image.licenses="LicenseRef-LNX-360-Source-Available"

USER root

# The ping monitor driver shells out to `ping`; grant it raw-socket capability so
# it works for the unprivileged www-data user.
RUN apt-get update \
    && apt-get install -y --no-install-recommends iputils-ping libcap2-bin \
    && setcap cap_net_raw+ep "$(command -v ping)" \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions bcmath

COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/lnx-entrypoint

COPY --from=build --chown=www-data:www-data /var/www/html /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PHP_OPCACHE_ENABLE=1 \
    PHP_MEMORY_LIMIT=256M \
    AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION_ISOLATION=true

USER www-data

ENTRYPOINT ["lnx-entrypoint"]
CMD ["/init"]
