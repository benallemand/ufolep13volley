#!/bin/bash

# Démarrer le service MariaDB
service mariadb start

# Attendre que le service MariaDB soit en cours d'exécution
while ! mysqladmin ping -h127.0.0.1 --silent; do
  sleep 1
done
source .env
set -u
# Exécuter les scripts SQL
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "DROP USER IF EXISTS '$DB_USER'@'%';"
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "CREATE USER '$DB_USER'@'%' IDENTIFIED BY '$DB_PASSWORD';"
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "DROP DATABASE IF EXISTS $DB_NAME;"
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "CREATE DATABASE $DB_NAME;"
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$DB_NAME" < "/docker-entrypoint-initdb.d/$DB_NAME.sql"
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" -e "GRANT ALL PRIVILEGES ON *.* TO '$DB_USER'@'%';"

# Démarrer le serveur Apache en mode foreground
apachectl -D FOREGROUND
