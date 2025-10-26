# Apache + PHP 8.2
FROM php:8.2-apache

# Installera Postgres PDO och aktivera mod_rewrite
RUN apt-get update \
  && apt-get install -y libpq-dev unzip git \
  && docker-php-ext-install pdo pdo_pgsql \
  && a2enmod rewrite

# Sätt DocumentRoot till /var/www/html/public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Kopiera appen
WORKDIR /var/www/html
COPY . .

# Installera beroenden (ingen dev)
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Apache lyssnar på 80; Render mappar själv port
EXPOSE 80

# Klar – apache foreground
CMD ["apache2-foreground"]
