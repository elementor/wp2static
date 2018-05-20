#!/bin/bash

sudo docker rm -f php53sql
sudo docker rm -f plugindev53
sudo docker network rm php53net
sudo docker network create --subnet=172.19.0.0/16 php53net
sudo docker run --name php53sql --net php53net -e MYSQL_ROOT_PASSWORD=banana -d mariadb  
sudo docker build -t leonstafford/wordpress-static-html-plugin:latest . 
sudo docker run --name plugindev53 --net php53net --link php53sql:mysql -p 8093:80 --ip 172.19.0.6 -d -v $(pwd):/app leonstafford/wordpress-static-html-plugin 

sudo docker exec plugindev53 bash /post_install.sh

sudo docker exec -itd plugindev53 bash 
# something to keep alive with -it

