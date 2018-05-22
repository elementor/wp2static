FROM ubuntu:precise

# purposefully separating to cache hard, simple to force cache clear when needed
RUN apt-get update 
RUN apt-get install -y vim wget mysql-client apache2-dev curl apache2 libxml2-dev build-essential libcurl4-openssl-dev

# dont need, else add to /etc/apache2/apache2 conf?
#ADD ./httpd-custom.conf /usr/local/apache2/conf/httpd.conf

RUN mkdir /compilestuff
WORKDIR /compilestuff
RUN wget http://au1.php.net/distributions/php-5.3.29.tar.gz
RUN tar xfz php-5.3.29.tar.gz
WORKDIR /compilestuff/php-5.3.29
RUN ./configure --with-mysql --with-apxs2=/usr/bin/apxs2 --with-openssl --with-curl --with-openssl-dir=/usr/bin --enable-zip --enable-mbstring --with-zlib
RUN make
RUN make install

RUN echo 'AddType application/x-httpd-php .php' >> /etc/apache2/apache2.conf 

# install WP CLI

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp

COPY *.sh /
COPY phpinfo.php /var/www/



EXPOSE 80
EXPOSE 443

