#!/bin/bash

sudo docker exec -it plugindevwp bash -c ' wp --allow-root export --dir=/app/demo_site_content/ --user=admin --post_type=post --path=/var/www/html/ --filename_format="wp_static_demo_content.xml"'

