#FROM helder/php-5.3
#
#RUN apt-get update \
#&& apt-get install -y vim  wget mysql-client apache2
## && apt-get install -y vim mysql-client apache2 libapache2-mod-php5 pwgen python-setuptools php5-mysql  php5-ldap wget
#
#ADD ./httpd-custom.conf /usr/local/apache2/conf/httpd.conf
#
#COPY *.sh /

FROM debian:squeeze

LABEL maintainer="raguchi@gmail.com"

# squeeze new mirrors
RUN echo "deb http://archive.debian.org/debian/ squeeze contrib main non-free" > /etc/apt/sources.list

RUN apt-get update
# Common packages
RUN apt-get update && \
    apt-get install -y --force-yes curl wget

RUN apt-get update && \
    apt-get install -y --force-yes \
            apache2 \
            libapache2-mod-php5 \
            ntp \
        php5 \
        php5-curl \
        php5-gd \
        php5-ldap \
        php5-mcrypt \
        php5-mysql \
        vim

RUN a2enmod \
            php5 \
        rewrite \
        ssl


ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP    www-data
ENV APACHE_LOG_DIR  /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid
# RUN     chown -R www-data:www-data /var/www
COPY     conf/apache/virtualhosts /etc/apache2/sites-enabled

# disable default virtualhost
RUN a2dissite 000-default

EXPOSE 80
EXPOSE 443

COPY *.sh /


CMD     ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]
