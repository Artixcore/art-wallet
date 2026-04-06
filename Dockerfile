# syntax=docker/dockerfile:1
#
# ArtWallet production image (Laravel 13 / PHP 8.3).
#
# artixcore/artgate is a Composer *path* repo (packages/artgate). In the image, that path is /var/www/html/packages/artgate.
#
# Option 1 — single Git repo on App Platform (recommended):
#   Build args (set in DO → Settings → art-wallet → Build-time environment variables):
#     ARTGATE_GIT_URL=https://github.com/your-org/artgate.git
#     ARTGATE_GIT_REF=main   (optional branch/tag)
#   Private repo: add a read-only deploy token / SSH key per DO docs, or use a VCS Composer repo instead.
#
# Option 2 — monorepo checkout:
#   Build from parent directory with a Dockerfile that copies both trees (see docs/deploy/digitalocean.md).

# --- Front-end assets (Vite)
FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY . .
RUN npm run build

# --- PHP application
FROM php:8.3-cli-bookworm AS app

ARG DEBIAN_FRONTEND=noninteractive
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" intl pdo_mysql mbstring bcmath pcntl opcache zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG ARTGATE_GIT_URL=""
ARG ARTGATE_GIT_REF="main"

WORKDIR /var/www/html
COPY . .

# Clone after COPY so the app tree is not overwritten (path repo: packages/artgate).
RUN mkdir -p packages \
    && if [ -n "$ARTGATE_GIT_URL" ]; then \
        git clone --depth 1 --branch "$ARTGATE_GIT_REF" "$ARTGATE_GIT_URL" packages/artgate; \
    fi

RUN if [ ! -f packages/artgate/composer.json ]; then \
        echo "Build failed: artgate not found at packages/artgate."; \
        echo "Set Docker build-arg ARTGATE_GIT_URL to a git clone URL, or build from a monorepo context."; \
        exit 1; \
    fi

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY --from=frontend /app/public/build ./public/build

RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

COPY docker/entrypoint-web.sh /usr/local/bin/entrypoint-web.sh
COPY docker/entrypoint-worker.sh /usr/local/bin/entrypoint-worker.sh
RUN chmod +x /usr/local/bin/entrypoint-web.sh /usr/local/bin/entrypoint-worker.sh \
    && chown -R www-data:www-data /var/www/html

ENV APP_ENV=production \
    LOG_CHANNEL=stderr \
    VITE_USE_HOT=false

USER www-data

EXPOSE 8080

# App Platform `run_command` overrides CMD (use entrypoint-worker.sh for queue workers).
CMD ["/usr/local/bin/entrypoint-web.sh"]
