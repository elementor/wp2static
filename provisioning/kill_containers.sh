#!/bin/bash

# convenience script to destroy any running containers
sudo docker rm -f {devmysql,plugindevwp,phpmyadmin,ftpserver}
sudo docker network rm devwp
