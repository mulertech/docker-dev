ARG PHP_IMAGE=php:apache
FROM ${PHP_IMAGE}

ARG USER_ID
ARG GROUP_ID

# Use PHP extension installer optimized for Docker
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install PHP extensions required for Symfony in a single command
RUN install-php-extensions \
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
    sockets \
    # Cache and debugging
    redis \
    xdebug \
    apcu \
    # Advanced extensions (ignore if fail)
    @composer \
    yaml

# Install required system dependencies
RUN apt-get update && apt-get install -y \
    # Build tools and utilities
    git \
    curl \
    unzip \
    # Database
    default-mysql-client \
    # Node.js and npm for Tailwind CSS, Symfony UX and AssetMapper
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Install Yarn using npm (more reliable method)
RUN npm install -g yarn

# Copy Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Symfony CLI (direct binary download)
RUN curl -sS -o /tmp/symfony-cli.tar.gz -L https://github.com/symfony-cli/symfony-cli/releases/latest/download/symfony-cli_linux_amd64.tar.gz \
    && tar -xzf /tmp/symfony-cli.tar.gz -C /tmp \
    && mv /tmp/symfony /usr/local/bin/symfony \
    && chmod +x /usr/local/bin/symfony \
    && rm -f /tmp/symfony-cli.tar.gz

# Remove and recreate www-data user with correct IDs
RUN userdel -r www-data 2>/dev/null || true \
    && groupadd -g ${GROUP_ID} www-data \
    && useradd -u ${USER_ID} -g ${GROUP_ID} -m -s /bin/bash www-data

# Enable required Apache modules
RUN a2enmod rewrite headers ssl deflate expires

# Configure permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure working directory
WORKDIR /var/www/html

# Expose HTTP port
EXPOSE 80

# Start Apache in foreground mode
CMD ["apache2-foreground"]