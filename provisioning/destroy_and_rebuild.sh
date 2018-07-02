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
sudo docker rm -f {devmysql,plugindevwp,phpmyadmin}
sudo docker build -t leonstafford/wordpress-static-html-plugin:latest . 
sudo docker run --name devmysql -e MYSQL_ROOT_PASSWORD=banana -d mariadb
sudo docker run --name plugindevwp --env-file ./provisioning/.env-vars --link devmysql:mysql $INSTALL_PATH_OVERRIDE -p 8091:80 -d -v $(pwd):/app leonstafford/wordpress-static-html-plugin
sudo docker run --name phpmyadmin -d --link devmysql:db -p 3008:80 phpmyadmin/phpmyadmin
sudo docker exec plugindevwp bash /post_launch.sh
sudo docker exec -it plugindevwp sh /watch_source_files.sh

# launch php 5.3 environment
#/bin/bash provisioning/php53tests/destroy_and_rebuild.sh

