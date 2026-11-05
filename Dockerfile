FROM php:8.1-fpm-alpine

# Install Apache dan pustaka yang dibutuhkan
RUN apk add --no-cache apache2 apache2-utils

# Install ekstensi MySQL untuk PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Salin file project
COPY . /var/www/html/

# Atur permission
RUN chown -R www-data:www-data /var/www/html

# Atur DocumentRoot Apache ke /var/www/html
RUN sed -i 's#^DocumentRoot ".*"#DocumentRoot "/var/www/html"#' /etc/apache2/httpd.conf \
    && sed -i 's#^<Directory "/var/www/localhost/htdocs">#<Directory "/var/www/html">#' /etc/apache2/httpd.conf

EXPOSE 80

CMD ["httpd", "-D", "FOREGROUND"]