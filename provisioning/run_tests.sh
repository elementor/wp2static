#!/bin/bash

sudo docker rm -f devmysql
sudo docker rm -f plugindevwp
sudo docker rm -f seleniumserver
sudo docker build -t leonstafford/wordpress-static-html-plugin:latest .
sudo docker run --name seleniumserver -d -p 4444:4444 -v /dev/shm:/dev/shm selenium/standalone-chrome:latest
sudo docker run --name devmysql -e MYSQL_ROOT_PASSWORD=banana -d mariadb
sudo docker run --name plugindevwp --link devmysql:mysql -p 8088:80 -d -v $(pwd):/app leonstafford/wordpress-static-html-plugin
sudo docker exec plugindevwp sh /post_launch.sh

echo 'what is running now?'
sudo docker ps

webContainerID=$(sudo docker ps | grep plugindevwp | grep -o -e '^\S*')
sqlContainerID=$(sudo docker ps | grep devmysql | grep -o -e '^\S*')
seleniumContainerID=$(sudo docker ps | grep seleniumserver  | grep -o -e '^\S*')

webContainerIP=$( sudo docker inspect --format="{{ .NetworkSettings.IPAddress }}" $webContainerID)
sqlContainerIP=$( sudo docker inspect --format="{{ .NetworkSettings.IPAddress }}" $sqlContainerID)
seleniumContainerIP=$( sudo docker inspect --format="{{ .NetworkSettings.IPAddress }}" $seleniumContainerID)

echo $webContainerIP
echo $sqlContainerIP
echo $seleniumContainerIP

gem install bundler
bundle install
bundle exec ruby run_tests.rb $webContainerIP

# cleanup
sudo docker rm -f devmysql
sudo docker rm -f plugindevwp
sudo docker rm -f seleniumserver
