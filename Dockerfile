FROM php:8.2-apache

# Install required system dependencies and PHP extensions (mysqli, pdo_mysql, gd, zip)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite and headers
RUN a2enmod rewrite headers

WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Copy and setup start script
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Setup directory permissions for assets and uploads
RUN mkdir -p /var/www/html/assets/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/assets/uploads

EXPOSE 80 10000

CMD ["/usr/local/bin/start.sh"]

