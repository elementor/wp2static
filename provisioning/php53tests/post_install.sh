#!/bin/bash

echo 'awaiting mysql to be reachable'

while ! mysqladmin ping -h php53sql --silent; do
    printf "."
    sleep 1
done

# still requires buffer before accessible for wp cli
sleep 5



# install default
cd /var/www
#rm index.html

wp --allow-root core download 
wp --allow-root config create --dbname=wordpress --dbuser=root --dbpass=banana --dbhost=php53sql
wp --allow-root db create
wp --allow-root core install --url="172.19.0.6" --title='PHP 5.3.29 WordPress Instance' --admin_user=admin --admin_password=admin --admin_email=blah@blah.com --skip-email

# activate wp static output plugin
#wp --allow-root plugin activate wordpress-static-html-output

rm /var/www/index.html

chown -R www-data:www-data /var/www

service apache2 start

# keep container alive

while true
do
    sleep 1
done
