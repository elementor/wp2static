#!/bin/bash

sudo docker exec -it plugindevwp bash -c ' wp --allow-root media import /app/provisioning/demo_site_content/images/*.png'
sudo docker exec -it plugindevwp bash -c ' wp --allow-root media import /app/provisioning/demo_site_content/images/*.jpg'

