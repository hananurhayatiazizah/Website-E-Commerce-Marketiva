FROM php:8.1-cli

# Install ekstensi MySQL yang dibutuhkan aplikasi
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Arahkan langsung ke folder kerja utama server web
WORKDIR /var/www/html

# Salin semua file dari repository ke dalam container
COPY . .

# Gunakan port 8080 sebagai default aman jika PORT railway tidak terbaca
ENV PORT=8080
EXPOSE 8080

# Jalankan server PHP dengan port dinamis
CMD php -S 0.0.0.0:$PORT