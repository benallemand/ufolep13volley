FROM composer as builder
WORKDIR /app/
COPY composer.* ./
RUN composer install --ignore-platform-reqs

# Utiliser l'image officielle PHP
FROM php:8.1-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Copier les fichiers de notre site dans le conteneur
COPY . /var/www/html/
COPY --from=builder /app/vendor /var/www/html/vendor