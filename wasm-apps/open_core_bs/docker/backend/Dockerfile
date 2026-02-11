# Backend Dockerfile for Laravel (production-ready, single-container with Nginx + PHP-FPM)
# Intended for deployment to Azure App Service or AKS. Adjust PHP extensions and config as needed.

FROM composer:2 AS vendor
WORKDIR /src
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

FROM php:8.2-fpm-alpine

# install system deps, php extensions, and nginx
RUN apk add --no-cache nginx bash icu-libs libzip zlib-dev libpng libjpeg-turbo libwebp \
    oniguruma-dev autoconf g++ make curl-dev libxml2-dev libzip-dev

# install PHP extensions required by Laravel
RUN docker-php-ext-configure zip
RUN docker-php-ext-install pdo pdo_mysql mbstring zip bcmath intl

# Configure nginx
RUN mkdir -p /run/nginx /var/www/html
COPY ./docker/backend/default.conf /etc/nginx/conf.d/default.conf

# copy vendor from composer stage
WORKDIR /var/www/html
COPY --from=vendor /src/vendor ./vendor

# copy app files
COPY . .

# set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 80

# Start php-fpm and nginx in foreground
CMD ["/bin/sh", "-c", "php-fpm --nodaemonize & nginx -g 'daemon off;'"]
