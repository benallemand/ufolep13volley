FROM composer as builder
WORKDIR /app/
COPY composer.* ./
RUN docker-php-ext-install mysqli pdo pdo_mysql && composer install


# Utiliser l'image officielle PHP
FROM php:8.1-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copier les fichiers de notre site dans le conteneur
COPY . /var/www/html/
COPY --from=builder /app/vendor /var/www/html/vendor