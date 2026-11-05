FROM php:8.1-apache

# Install ekstensi MySQL yang dibutuhkan PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Salin semua file project ke dalam server web apache
COPY . /var/www/html/

# Ubah hak akses folder jika diperlukan
RUN chown -R www-data:www-data /var/www/html