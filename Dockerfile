FROM php:8.1-apache

# Paksa nonaktifkan modul mpm_event dan pastikan mpm_prefork yang aktif
RUN sed -i '/LoadModule mpm_event_module/s/^/#/' /etc/apache2/mods-available/mpm_event.load \
    && sed -i '/LoadModule mpm_worker_module/s/^/#/' /etc/apache2/mods-available/mpm_worker.load \
    && a2enmod mpm_prefork

# Install ekstensi MySQL yang dibutuhkan PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Salin semua file project ke dalam server web apache
COPY . /var/www/html/

# Ubah hak akses folder jika diperlukan
RUN chown -R www-data:www-data /var/www/html