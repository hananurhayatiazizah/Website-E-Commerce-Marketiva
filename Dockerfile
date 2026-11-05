FROM php:8.1-cli

# Install ekstensi MySQL
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy semua file project
COPY . .

# Expose port (Railway akan override port ini secara internal)
EXPOSE 80

# Jalankan server internal PHP pada port 80
CMD ["php", "-S", "0.0.0.0:80"]