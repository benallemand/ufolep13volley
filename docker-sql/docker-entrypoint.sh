#!/bin/bash

set -u

while ! mysqladmin ping -hmysql --silent; do
  echo "waiting for mysql server to start..."
  sleep 1
done

# Exécuter les scripts SQL
mysql -hmysql -uroot -ptest -e "DROP USER IF EXISTS '$MYSQL_USER'@'%';"
mysql -hmysql -uroot -ptest -e "CREATE USER '$MYSQL_USER'@'%' IDENTIFIED BY '$MYSQL_PASSWORD';"
mysql -hmysql -uroot -ptest -e "DROP DATABASE IF EXISTS $MYSQL_DATABASE;"
mysql -hmysql -uroot -ptest -e "CREATE DATABASE $MYSQL_DATABASE;"
mysql -hmysql -uroot -ptest -e "GRANT ALL PRIVILEGES ON *.* TO '$MYSQL_USER'@'%';"
mysql -hmysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < "/docker-sql/$MYSQL_DATABASE-structure-only.sql"
mysql -hmysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" < "/docker-sql/$MYSQL_DATABASE-data-only.sql"
