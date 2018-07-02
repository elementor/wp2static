FROM wordpress:latest

RUN touch /var/log/apache2/php_err.log && chown www-data:www-data /var/log/apache2/php_err.log
COPY provisioning/php_error.ini /usr/local/etc/php/conf.d/php_error.ini

RUN apt-get update \
&& apt-get install -y inotify-tools rsync mysql-client iproute zlib1g-dev vim \
&& rm -rf /var/lib/apt/lists/* \
&& docker-php-ext-install zip

# install wp cli
RUN curl -L https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp

# install phpunit to path (version 5 dev will need older version)
RUN curl -L https://phar.phpunit.de/phpunit.phar -o /usr/local/bin/phpunit
RUN chmod +x /usr/local/bin/phpunit

COPY provisioning/*.sh /
COPY provisioning/.env-vars /

COPY test_data/ /test_data

COPY provisioning/install/plugins/* /plugins/
