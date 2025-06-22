FROM php:8.2-cli-alpine

RUN apk add --no-cache \
    g++ \
    make \
    autoconf \
    brotli-dev \
    openssl-dev \
    zlib-dev \
    curl-dev \
    c-ares-dev \
    postgresql-dev \
    libstdc++ \
    pkgconf

RUN pecl install swoole && docker-php-ext-enable swoole
RUN docker-php-ext-install pdo_pgsql

WORKDIR /app
COPY src/ /app
CMD ["php", "server.php"]
