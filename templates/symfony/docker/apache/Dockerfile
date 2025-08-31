FROM php:apache

ARG USER_ID
ARG GROUP_ID

# Use buildkit cache + multi-stage to optimize downloads
RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    apt-get update && apt-get install -y \
    # Core PHP dependencies
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    # Image processing (GD + imagick for all project types)
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libavif-dev \
    # Advanced features (optional but available)
    libxslt1-dev \
    librabbitmq-dev \
    libmemcached-dev \
    libssl-dev \
    # Build tools
    zlib1g-dev \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configure GD with modern support (WebP, AVIF)
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    --with-avif

# Install PHP extensions in parallel for speed
RUN docker-php-ext-install -j$(nproc) \
    # Core Symfony requirements
    intl \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    # Image processing
    gd \
    exif \
    # XML/API support
    xml \
    soap \
    # Math/String processing
    bcmath \
    mbstring \
    # Performance
    opcache \
    # Utility
    calendar \
    sockets

# Install PECL extensions (separately for cache)
RUN --mount=type=cache,target=/tmp/pear \
    pecl install \
    redis \
    xdebug \
    apcu \
    && docker-php-ext-enable redis xdebug apcu

# Install advanced PECL extensions if needed (optional)
RUN --mount=type=cache,target=/tmp/pear \
    pecl install amqp memcached || true \
    && docker-php-ext-enable amqp memcached || true

# Install Composer (faster with multi-stage)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js 20 LTS (required for Tailwind CSS + Symfony UX + AssetMapper)
RUN --mount=type=cache,target=/var/cache/apt \
    --mount=type=cache,target=/var/lib/apt \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Yarn (alternative to npm, often faster)
RUN --mount=type=cache,target=/tmp \
    corepack enable || npm install -g yarn

# Install Symfony CLI (useful for debug + UX commands)
RUN --mount=type=cache,target=/tmp \
    curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

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