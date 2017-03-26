#!/bin/bash

sudo docker rm -f php53sql
sudo docker run --name php53sql -e MYSQL_ROOT_PASSWORD=banana -d mariadb

