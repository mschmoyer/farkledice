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

# Enable Apache modules (rewrite and SSL)
RUN a2enmod rewrite ssl headers

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

# Generate self-signed SSL certificate for local development
RUN mkdir -p /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/localhost.key \
    -out /etc/apache2/ssl/localhost.crt \
    -subj "/C=US/ST=Local/L=Local/O=Dev/CN=localhost"

# Copy custom Apache configurations with separated logs
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

# Enable SSL site
RUN a2ensite default-ssl

# Expose ports 80 and 443
EXPOSE 80 443

# Start Apache
CMD ["apache2-foreground"]
