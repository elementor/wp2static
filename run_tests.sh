#!/bin/bash

containerID=$(sudo docker ps | grep plugindevwp | grep -o -e '^\S*')

containerIP=$( sudo docker inspect --format="{{ .NetworkSettings.IPAddress }}" $containerID)

ruby run_tests.rb $containerIP
