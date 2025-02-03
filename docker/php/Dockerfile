#syntax=docker/dockerfile:1

# Базовый образ, может быть нашим внутренним, заранее собранным с необходимыми расширениями и конфигами
FROM php:8.3-fpm-alpine AS base
MAINTAINER Sprint-F <devops@sprintf.ru>

WORKDIR /app

ENV TIMEZONE            Europe/Moscow
ENV TZ                  $TIMEZONE
ENV PHP_MEMORY_LIMIT    1024M
ENV UPLOAD_MAX_FILESIZE 200M
ENV PHP_MAX_POST        300M
ENV ERROR_REPORTING     E_ALL
ENV LANG                ru_RU.UTF-8
ENV LANGUAGE            ru_RU:en
ENV LC_ALL              ru_RU.UTF-8

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN \
    install-php-extensions gd iconv mbstring pdo_pgsql zip gettext intl sysvsem

RUN <<EOF cat >>/usr/local/etc/php/php.ini
date.timezone = ${TIMEZONE}
memory_limit = ${PHP_MEMORY_LIMIT}
upload_max_filesize = ${UPLOAD_MAX_FILESIZE}
post_max_size = ${PHP_MAX_POST}
error_reporting = ${ERROR_REPORTING}
EOF

RUN <<EOF cat >/usr/local/etc/php-fpm.d/logging.conf
[global]
error_log = /dev/stderr
log_level = notice
log_limit = 10240
log_buffering = no
[www]
catch_workers_output = yes
decorate_workers_output = yes
access.log = /dev/stderr
access.format = "%R - %u %t \"%m %r\" %s"
EOF

# Слой для разработки = базовый образ + инструменты разработки
FROM base AS dev
RUN \
   apk --update add openssh-client git wget curl zip unzip
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN \
    install-php-extensions xdebug

ARG UID
ARG GID

RUN \
    apk --no-cache add shadow && \
    groupmod --gid ${GID} www-data && \
    usermod --uid ${UID} --gid ${GID} www-data && \
    apk del shadow

USER www-data

# Сборка приложения, как единого целого, установка всех зависимостей
FROM dev AS builder
ENV COMPOSER_HOME=/tmp/composer
USER root

RUN \
    --mount=type=bind,source=./,target=/app,rw \
    --mount=type=cache,id=cache,target=/tmp/composer/cache \
    --mount=type=ssh \
    cd /app && composer install --no-dev --classmap-authoritative && cp -r /app/ /build/

RUN composer install

# Слой для продакшна = базовый образ + все файлы приложения
FROM base AS prod
USER www-data
COPY --from=builder --chown=www-data:www-data /build /app
