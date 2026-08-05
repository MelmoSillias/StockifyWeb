FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_mysql zip gd \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .
RUN composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader \
    || composer install --no-interaction --prefer-dist

EXPOSE 8001
CMD ["php", "-S", "0.0.0.0:8001", "-t", "public"]
