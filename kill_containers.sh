#!/bin/bash

# convenience script to destroy any running containers
sudo docker rm -f devmysql
sudo docker rm -f plugindevwp
sudo docker rm -f seleniumserver
