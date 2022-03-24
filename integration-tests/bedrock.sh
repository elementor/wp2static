#!/usr/bin/env bash

set -oeux

rm -rf bedrock
composer create-project roots/bedrock bedrock 1.17.1
cp bedrock.env bedrock/.env
echo "DB_HOST='localhost:$PWD/mariadb/data/mysql.sock'" >> bedrock/.env

while ! test -S "mariadb/data/mysql.sock"; do
  sleep 1
done

mysql --socket=mariadb/data/mysql.sock -e "CREATE USER IF NOT EXISTS 'bedrock'@'localhost' IDENTIFIED BY '8BVMm2'; CREATE DATABASE IF NOT EXISTS bedrock; GRANT ALL PRIVILEGES ON bedrock.* TO 'bedrock'@'localhost'; FLUSH PRIVILEGES;"

wp core install --path=bedrock/web/wp --url="https://example.com" --title=WordPress --admin_user=user --admin_email="user@example.com" --admin_password=pass
wp option update permalink_structure "/%postname%/" --path=bedrock/web/wp
echo "BEDROCK"
