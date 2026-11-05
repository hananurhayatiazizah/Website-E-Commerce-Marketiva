FROM php:8.1-cli

# Install ekstensi MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Tentukan folder kerja
WORKDIR /var/www/html

# Salin seluruh file project
COPY . .

EXPOSE 80

# Jalankan server internal PHP
CMD ["php", "-S", "0.0.0.0:80"]