ARG PHP_IMAGE=php:fpm-alpine
FROM ${PHP_IMAGE}

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath gd pdo_mysql pdo_pgsql redis xdebug yaml

# install mysql client and connector for MySQL 8 authentication
RUN apk add --no-cache mysql-client mariadb-connector-c

# xdebug settings
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# add user and group ids to match host user and group ids to avoid permission issues
ARG USER_ID
ARG GROUP_ID

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# delete www-data user and recreate with host user and group ids
RUN deluser www-data \
    && if getent group www-data ; then delgroup www-data; fi \
    && addgroup -g ${GROUP_ID} -S www-data \
    && adduser -u ${USER_ID} -D -S -G www-data www-data

USER www-data