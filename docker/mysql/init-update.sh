#!/bin/bash

echo "Setting charset to utf8mb4..."
mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SET NAMES utf8mb4;"

echo "Running update.sql..."
mysql --force -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --default-character-set=utf8mb4 "$MYSQL_DATABASE" < /update.sql

echo "Update completed."
