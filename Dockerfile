FROM wordpress:latest

RUN apt-get update \
&& apt-get install -y inotify-tools rsync \
&& docker-php-ext-install zip

# install wp cli
RUN curl -L https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp
RUN chmod +x /usr/local/bin/wp

COPY *.sh /

