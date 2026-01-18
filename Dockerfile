FROM php:8.3-apache

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y \
    libpq-dev \
    git \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Set up Apache to point to wwwroot as document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/wwwroot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/backbone/templates_c \
    /var/www/backbone/cache \
    /var/www/configs \
    /var/www/html/logs

# Set permissions
RUN chown -R www-data:www-data /var/www/backbone /var/www/configs /var/www/html/logs
RUN chmod -R 755 /var/www/backbone /var/www/configs
RUN chmod -R 777 /var/www/backbone/templates_c /var/www/backbone/cache /var/www/html/logs

# Install Smarty from Composer to backbone directory
RUN mkdir -p /var/www/backbone/libs && \
    cp -r vendor/smarty/smarty/libs/* /var/www/backbone/libs/ 2>/dev/null || true

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
