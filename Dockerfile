FROM php:8.3-apache

# Installera dependencies om behövs (t.ex. för MySQL)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Kopiera app-filer
COPY . /var/www/html/

# Sätt rätt permissions
RUN chown -R www-data:www-data /var/www/html

# Exponera port 80
EXPOSE 80

# Starta Apache
CMD ["apache2-foreground"]