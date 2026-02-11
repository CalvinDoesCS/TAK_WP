# Backend Dockerfile for Laravel (production-ready, single-container with Nginx + PHP-FPM)
# Based on Ubuntu 22.04 for better package availability

FROM ubuntu:22.04

LABEL maintainer="Open Core BS Backend"

ARG WWWGROUP=1000
ARG WWWUSER=1000

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Install PHP 8.2 and required extensions
RUN apt-get update \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor nginx \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y \
    php8.2-fpm php8.2-cli \
    php8.2-gd php8.2-curl php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-zip php8.2-bcmath php8.2-intl \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Create www user
RUN groupadd --force -g $WWWGROUP www \
    && useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u $WWWUSER www

# Configure PHP-FPM
RUN sed -i 's/listen = \/run\/php\/php8.2-fpm.sock/listen = 127.0.0.1:9000/' /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i 's/user = www-data/user = www/' /etc/php/8.2/fpm/pool.d/www.conf \
    && sed -i 's/group = www-data/group = www/' /etc/php/8.2/fpm/pool.d/www.conf

# Configure nginx
COPY ./docker/backend/default.conf /etc/nginx/sites-available/default

# Copy application files
COPY --chown=www:www . /var/www/html

# Install composer dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Set permissions
RUN chown -R www:www /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 80

# Start PHP-FPM and Nginx
CMD service php8.2-fpm start && nginx -g 'daemon off;'
