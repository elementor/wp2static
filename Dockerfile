FROM wordpress:latest

RUN touch /var/log/apache2/php_err.log && chown www-data:www-data /var/log/apache2/php_err.log
COPY php_error.ini /usr/local/etc/php/conf.d/php_error.ini

RUN apt-get update \
&& apt-get install -y inotify-tools rsync mysql-client \
&& docker-php-ext-install zip

# install wp cli
RUN curl -L https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp

COPY *.sh /

CMD /post_launch.sh

