ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-cli-bookworm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

RUN git config --global --add safe.directory /app

WORKDIR /app
