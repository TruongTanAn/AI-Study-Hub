# =========================================
# AI Study Hub - PHP 8.2 + Apache Dockerfile
# =========================================
FROM php:8.2-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    libssl-dev \
    default-mysql-client \
    nano \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install -j$(nproc) \
    mysqli \
    pdo_mysql \
    fileinfo \
    mbstring \
    opcache \
    zip \
    gd

# Install intl separately (needs ICU)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# json is built into PHP 8.x core, no need to install it

# Install composer (optional, for reference)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite headers

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy custom php.ini
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy custom Apache VirtualHost
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

# Copy entrypoint script and ensure LF line endings (important on Windows hosts)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && apt-get update && apt-get install -y --no-install-recommends dos2unix \
    && dos2unix /usr/local/bin/entrypoint.sh \
    && apt-get purge -y dos2unix && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . /var/www/html/

# Set permissions for writable directories
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads \
    && chmod -R 775 /var/www/html/logs \
    && mkdir -p /var/www/html/cache \
    && chmod -R 775 /var/www/html/cache

# Set proper DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
