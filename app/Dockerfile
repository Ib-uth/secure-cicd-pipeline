FROM php:8.2-fpm-alpine
WORKDIR /var/www
RUN apk add --no-cache git curl libpng-dev oniguruma-dev libxml2-dev zip unzip
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .
RUN composer install --no-dev --optimize-autoloader
EXPOSE 9000
CMD ["php-fpm"]
