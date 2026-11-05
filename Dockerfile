FROM php:8.1-apache

# Nonaktifkan modul MPM event/worker yang bentrok dan pastikan prefork aktif
RUN a2dismod mpm_event mpm_worker || true && a2enmod mpm_prefork

# Install ekstensi MySQL yang dibutuhkan PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Salin semua file project ke dalam server web apache
COPY . /var/www/html/

# Ubah hak akses folder jika diperlukan
RUN chown -R www-data:www-data /var/www/html