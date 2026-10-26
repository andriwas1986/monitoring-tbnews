# ==============================================================================
# Dockerfile for Railway.app Deployment
# Sistem Monitoring Website Tribratanews - Polda Jawa Timur
# ==============================================================================
FROM php:8.2-apache

# Install ekstensi & dependencies sistem (libcurl, ssl, zip)
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    pkg-config \
    libssl-dev \
    && docker-php-ext-install curl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Set direktori kerja
WORKDIR /var/www/html

# Salin semua source code ke container
COPY . /var/www/html/

# Atur permission agar direktori data/ dapat ditulis oleh web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/data

# Konfigurasi Apache agar mendukung dynamic $PORT dari Railway.app
# Jika $PORT tidak ada (lokal), otomatis default ke port 80
CMD sh -c "sed -i \"s/Listen 80/Listen \${PORT:-80}/g\" /etc/apache2/ports.conf && \
           sed -i \"s/:80/:\${PORT:-80}/g\" /etc/apache2/sites-available/000-default.conf && \
           apache2-foreground"
