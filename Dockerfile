FROM php:8.2-apache

RUN apt-get update \
  && apt-get install -y libpq-dev unzip git \
  && docker-php-ext-install pdo pdo_pgsql \
  && a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 1) Kopiera bara composer-filer först (bättre cache)
COPY composer.json composer.lock ./

# 2) Installera dependencies (skapar en ren vendor/)
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# 3) Kopiera resten av koden (men inte vendor/)
COPY public ./public
COPY src ./src
COPY .htaccess* ./  # om du har någon i roten (valfritt)

EXPOSE 80
CMD ["apache2-foreground"]
