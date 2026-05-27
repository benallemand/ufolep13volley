# Étape 1 : dépendances PHP (Composer)
FROM composer as composer_builder
WORKDIR /app/
COPY composer.* ./
RUN composer install --ignore-platform-reqs

# Étape 2 : build frontend (Vite)
# Produit le dossier /app/dist (JS/CSS minifiés + manifest.json hashé).
FROM node:20-alpine AS frontend_builder
WORKDIR /app/
COPY package.json package-lock.json* ./
RUN npm install --no-audit --no-fund
COPY . .
RUN npm run build

# Étape 3 : image PHP finale
FROM php:8.1-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd mysqli pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# mod_rewrite : requis pour les RewriteRule du .htaccess
# (mapping /pages/home.html -> /dist/pages/home.html etc.)
RUN a2enmod rewrite

# Copier les fichiers de notre site dans le conteneur
COPY . /var/www/html/
COPY --from=composer_builder /app/vendor /var/www/html/vendor
COPY --from=frontend_builder /app/dist /var/www/html/dist
