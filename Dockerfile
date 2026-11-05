FROM php:8.1-cli

# Install ekstensi MySQL yang dibutuhkan aplikasi
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Arahkan langsung ke folder kerja utama server web
WORKDIR /var/www/html

# Salin semua file dari repository ke dalam container
COPY . .

# Gunakan port dinamis yang disediakan oleh Railway
EXPOSE 8080

# Jalankan server PHP dengan port dinamis Railway ($PORT)
CMD sh -c "php -S 0.0.0.0:${PORT:-8080}"