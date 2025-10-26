FROM php:8.2-apache
RUN apt-get update \
  && apt-get install -y libpq-dev unzip git \
  && docker-php-ext-install pdo pdo_pgsql \
  && a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress
EXPOSE 80
CMD ["apache2-foreground"]
