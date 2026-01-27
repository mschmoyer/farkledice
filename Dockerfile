FROM php:8.3-apache

# Install PostgreSQL extension and opcache
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache modules (rewrite and SSL)
RUN a2enmod rewrite ssl headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set up Apache to point to public as document root (Symfony entry point)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/backbone/templates_c \
    /var/www/backbone/cache \
    /var/www/configs \
    /var/www/html/logs \
    /var/www/html/var/cache \
    /var/www/html/var/log

# Set permissions
RUN chown -R www-data:www-data /var/www/backbone /var/www/configs /var/www/html/logs /var/www/html/var
RUN chmod -R 755 /var/www/backbone /var/www/configs
RUN chmod -R 777 /var/www/backbone/templates_c /var/www/backbone/cache /var/www/html/logs /var/www/html/var

# Install Smarty from Composer to backbone directory
RUN mkdir -p /var/www/backbone/libs && \
    cp -r vendor/smarty/smarty/libs/* /var/www/backbone/libs/ 2>/dev/null || true

# Copy custom Apache configurations with separated logs
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

# Enable SSL site
RUN a2ensite default-ssl

# Copy entrypoint script
COPY docker/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose ports 80 and 443
EXPOSE 80 443

# Start with entrypoint (handles SSL cert generation if needed)
CMD ["/usr/local/bin/docker-entrypoint.sh"]
