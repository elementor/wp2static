FROM ubuntu:precise

RUN apt-get update \
&& apt-get install -y vim mysql-client mysql-server apache2 libapache2-mod-php5 pwgen python-setuptools php5-mysql  php5-ldap

RUN rm -rf /var/www/
ADD https://wordpress.org/latest.tar.gz /wordpress.tar.gz
RUN tar xvzf /wordpress.tar.gz && rm /wordpress.tar.gz
RUN mv /wordpress /var/www/

RUN chown -R www-data:www-data /var/www/

EXPOSE 80

