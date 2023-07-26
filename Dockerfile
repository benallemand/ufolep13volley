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

# Exposer le port 80 pour accéder à notre site
EXPOSE 80

# Définir les variables d'environnement pour la base de données
ENV MYSQL_DATABASE=ufolep_13volley
ENV MYSQL_USER=root
ENV MYSQL_PASSWORD=test

# Installer le serveur MySQL
RUN apt-get update && apt-get install -y mariadb-server

# Copier le fichier de configuration pour MySQL
COPY docker-sql/mysql.cnf /etc/mysql/conf.d/

# Exposer le port 3306 pour la base de données
EXPOSE 3306

# Importer la base de données
COPY docker-sql/ufolepvocbufolep.sql /docker-entrypoint-initdb.d/

# Copier le fichier de configuration pour Apache
COPY docker-apache/httpd.conf /etc/apache2/conf-enabled/

# Copier le script d'entrée personnalisé
COPY docker-sql/docker-entrypoint.sh /usr/local/bin/

# Rendre le script exécutable
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Démarrer les services Apache et MySQL
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
