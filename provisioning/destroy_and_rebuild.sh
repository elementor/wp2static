#!/bin/bash

# get vars from .env-vars
. ./provisioning/.env-vars

INSTALL_PATH_OVERRIDE=

if [ -z "${SUBDIR_TO_INSTALL}" ]; then 
	echo "Installing into root"; 
else 
	echo "Installing into subdirectory: ${SUBDIR_TO_INSTALL}"; 
	INSTALL_PATH_OVERRIDE=" -w=/var/www/html/${SUBDIR_TO_INSTALL}"
fi


# convenience script to destroy any running containers, rebuild (with cache) and output notifications from script watching/syncing source files
sudo docker rm -f {devmysql,plugindevwp,phpmyadmin,ftpserver}
sudo docker network rm devwp
sudo docker network create --subnet=172.18.0.0/16 devwp


sudo docker build -t leonstafford/wordpress-static-html-plugin:latest . 
sudo docker run --name devmysql  --net devwp -e MYSQL_ROOT_PASSWORD=banana -d mariadb
sudo docker run --name plugindevwp  --net devwp  --ip 172.18.0.3 --env-file ./provisioning/.env-vars --link devmysql:mysql $INSTALL_PATH_OVERRIDE -p 8091:80 -d -v $(pwd):/app leonstafford/wordpress-static-html-plugin


sudo docker run --name phpmyadmin  --net devwp -d --link devmysql:db -p 3008:80 phpmyadmin/phpmyadmin
sudo docker run -d --name ftpserver  --net devwp -p 21:21 -p 30000-30009:30000-30009 -e "PUBLICHOST=ftpserver"  -e FTP_USER_NAME=admin -e FTP_USER_PASS=banana -e FTP_USER_HOME=/home/admin  stilliard/pure-ftpd:latest
sudo docker exec plugindevwp bash /post_launch.sh
sudo docker exec -it plugindevwp sh /watch_source_files.sh

# launch php 5.3 environment
#/bin/bash provisioning/php53tests/destroy_and_rebuild.sh

# FTP:
# connect from host using localhost:21 admin/banana


