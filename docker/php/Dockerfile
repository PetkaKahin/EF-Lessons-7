FROM php:8.5-fpm-bookworm AS base

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install \
        bcmath \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN groupadd --gid ${GID} laravel \
    && useradd --uid ${UID} --gid laravel --shell /bin/bash --create-home laravel \
    && sed -i 's/user = www-data/user = laravel/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = laravel/' /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/php.ini

RUN mkdir -p \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R laravel:laravel /var/www/html

# хватит только composer, во время разработки зависимости сами подтянутся через volume
FROM base AS dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["/usr/local/sbin/php-fpm"]

# Если это всё запихать в prod, то останется кэш и прочий ненужный мусор для прода
# Поэтому собираю всё тут, а в проде просто беру итоговый результат
FROM base AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader

COPY . .
RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --no-interaction \
    --no-scripts \
    && chown -R laravel:laravel /var/www/html/storage /var/www/html/bootstrap/cache

# Composer нужен для деплоя через docker-compose.yml
FROM base AS prod

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=build --chown=laravel:laravel /var/www/html /var/www/html

CMD ["/usr/local/sbin/php-fpm"]
