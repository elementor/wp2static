FROM ubuntu:precise

RUN apt-get update && apt-get install -y vim wget mysql-client apache2-dev curl
# && apt-get install -y vim mysql-client apache2 libapache2-mod-php5 pwgen python-setuptools php5-mysql  php5-ldap wget
#
ADD ./httpd-custom.conf /usr/local/apache2/conf/httpd.conf
#
COPY *.sh /
COPY phpinfo.php /var/www/html/

EXPOSE 80
EXPOSE 443

