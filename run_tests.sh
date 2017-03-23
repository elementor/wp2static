#!/bin/bash
echo 'what is running now?'
docker ps

webContainerID=$(sudo docker ps | grep plugindevwp | grep -o -e '^\S*')
sqlContainerID=$(sudo docker ps | grep devmysql | grep -o -e '^\S*')
seleniumContainerID=$(sudo docker ps | grep seleniumserver  | grep -o -e '^\S*')

webContainerIP=$( sudo docker inspect --format="{{ .NetworkSettings.IPAddress }}" $webContainerID)
sqlContainerIP=$( sudo docker inspect --format="{{ .NetworkSettings.IPAddress }}" $sqlContainerID)
seleniumContainerIP=$( sudo docker inspect --format="{{ .NetworkSettings.IPAddress }}" $seleniumContainerID)

echo $webContainerIP
echo $sqlContainerIP
echo $seleniumContainerIP

siteurl=$(cat /app/siteurl)

gem install bundler
bundle install
bundle exec ruby run_tests.rb $siteurl
