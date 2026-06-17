FROM php:8.2-apache

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql pdo_pgsql mysqli zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN mkdir -p uploads \
    && chmod -R 777 uploads \
    && composer install --no-interaction --prefer-dist --optimize-autoloader || true

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads

EXPOSE 8080

# Fly.io exige 0.0.0.0:8080 (internal_port no fly.toml)
CMD ["bash", "-c", "PORT=\"${PORT:-8080}\"; sed -i \"s/^Listen .*/Listen 0.0.0.0:${PORT}/\" /etc/apache2/ports.conf; sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/\" /etc/apache2/sites-enabled/000-default.conf; mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads; exec apache2-foreground"]
