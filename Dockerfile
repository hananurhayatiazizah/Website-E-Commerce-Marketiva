FROM php:8.1-fpm

# Install Nginx dan ekstensi MySQL
RUN apt-get update && apt-get install -y nginx \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Salin konfigurasi default Nginx agar mengarah ke folder project
RUN echo 'server {\n \
    listen 80;\n \
    root /var/www/html;\n \
    index index.php index.html index.htm;\n \
    location / {\n \
        try_files $uri $uri/ /index.php?$query_string;\n \
    }\n \
    location ~ \\.php$ {\n \
        include fastcgi_params;\n \
        fastcgi_pass 127.0.0.1:9000;\n \
        fastcgi_index index.php;\n \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n \
    }\n \
}' > /etc/nginx/sites-enabled/default

# Salin file project
COPY . /var/www/html/

# Ubah hak akses
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Jalankan Nginx dan PHP-FPM bersamaan
CMD service nginx start && php-fpm