#!/bin/bash
echo 'what is running now?'
docker ps

containerID=$(sudo docker ps | grep plugindevwp | grep -o -e '^\S*')

containerIP=$( sudo docker inspect --format="{{ .NetworkSettings.IPAddress }}" $containerID)

gem install bundler
bundle install
bundle exec ruby run_tests.rb $containerIP
