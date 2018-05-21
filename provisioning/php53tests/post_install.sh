#!/bin/bash

echo 'awaiting mysql to be reachable'

while ! mysqladmin ping -h php53sql --silent; do
    printf "."
    sleep 1
done

# still requires buffer before accessible for wp cli
sleep 5


# need apache2 sources for php
apt-get install -y apache2 apache2-dev libxml2-dev build-essential libcurl4-openssl-dev 

#mkdir /etc/apache2/mods-available



cd 
wget http://au1.php.net/distributions/php-5.3.29.tar.gz
tar xfz php-5.3.29.tar.gz
cd php-5.3.29
./configure --with-mysql --with-apxs2=/usr/bin/apxs2 --with-openssl --with-curl --with-openssl-dir=/usr/bin --enable-zip --enable-mbstring --with-zlib
make
make install


# need apache2 sources for php

# stop complaining about php and apache being differently threaded
#RUN a2dismod mpm_event
#RUN a2enmod mpm_prefork
#
#RUN a2enmod \
#            php5 \
#        rewrite \
#        ssl

# stop complaining about php and apache being differently threaded
#a2dismod mpm_event
#a2enmod mpm_prefork

echo 'AddType application/x-httpd-php .php' >> /etc/apache2/apache2.conf 

service apache2 stop
service apache2 start


# install WP CLI

curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
mv wp-cli.phar /usr/local/bin/wp



# install default
cd /var/www
#rm index.html

wp --allow-root core download 
wp --allow-root config create --dbname=wordpress --dbuser=root --dbpass=banana --dbhost=php53sql
wp --allow-root db create
wp --allow-root core install --url="172.19.0.6" --title='PHP 5.3.29 WordPress Instance' --admin_user=admin --admin_password=admin --admin_email=blah@blah.com --skip-email

# activate wp static output plugin
#wp --allow-root plugin activate wordpress-static-html-output

chown -R www-data:www-data /var/www

service apache2 stop
service apache2 start

# keep container alive

while true
do
    sleep 1

done
