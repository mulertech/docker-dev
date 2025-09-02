ARG PHP_IMAGE=php:apache
FROM ${PHP_IMAGE}

ARG USER_ID
ARG GROUP_ID

# Use PHP extension installer optimized for Docker
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install PHP extensions including MySQL support
RUN install-php-extensions \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    gd \
    mbstring \
    opcache

# Install MySQL client
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Remove and recreate www-data user with correct IDs
RUN userdel -r www-data 2>/dev/null || true \
    && groupadd -g ${GROUP_ID} www-data \
    && useradd -u ${USER_ID} -g ${GROUP_ID} -m -s /bin/bash www-data

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

WORKDIR /var/www/html
EXPOSE 80
CMD ["apache2-foreground"]