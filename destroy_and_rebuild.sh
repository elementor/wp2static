#!/bin/bash
# convenience script to destroy any running containers, rebuild (with cache) and shell into the running WP container
sudo docker rm -f devmysql
sudo docker rm -f plugindevwp
sudo docker build -t leonstafford/wordpress-static-html-plugin:latest . 
sudo docker run --name devmysql -e MYSQL_ROOT_PASSWORD=banana -d mariadb
sudo docker run --name plugindevwp --link devmysql:mysql -p 8080:80 -d -v /home/leon/wordpress-static-html-plugin/:/app leonstafford/wordpress-static-html-plugin
echo 'sleeping 20 secs to allow mysql to be accessible'
sleep 20
sudo docker exec -it plugindevwp bash
