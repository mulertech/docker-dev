FROM php:8.3-apache

ARG USER_ID
ARG GROUP_ID

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxslt1-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    libwebp-dev \
    libavif-dev \
    librabbitmq-dev \
    libmemcached-dev \
    zlib1g-dev \
    unzip \
    git \
    curl \
    vim \
    nano \
    wget \
    htop \
    && rm -rf /var/lib/apt/lists/*

# Configure GD with modern format support
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    --with-avif

# Install PHP extensions
RUN docker-php-ext-install \
    intl \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    gd \
    xml \
    soap \
    bcmath \
    mbstring \
    xsl \
    curl \
    opcache \
    exif \
    calendar \
    sockets

# Install PECL extensions
RUN pecl install \
    redis \
    xdebug \
    amqp \
    memcached \
    && docker-php-ext-enable redis xdebug amqp memcached

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Install Node.js and npm (for Webpack Encore)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Configure Apache
RUN a2enmod rewrite headers ssl deflate expires

# Remove and recreate www-data user with correct IDs
RUN userdel -r www-data 2>/dev/null || true
RUN groupadd -g ${GROUP_ID} www-data
RUN useradd -u ${USER_ID} -g ${GROUP_ID} -m -s /bin/bash www-data

# Configure permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Configure working directory
WORKDIR /var/www/html

# Expose port
EXPOSE 80

# Start Apache in foreground mode
CMD ["apache2-foreground"]