#!/bin/bash

# ### Demo website content
# 
# Included in the `./demo_site_content/` dir, are the posts used for the demo sites for this plugin, including guides on functionality. 
# 
# To capture content from the development instance, run `./provisioning/backup_demo_content.sh`

sudo docker exec -it plugindevwp bash -c ' wp --allow-root export --dir=/app/provisioning/demo_site_content/ --user=admin --post_type=post,page --path=/var/www/html/ --filename_format="wp_static_demo_content.xml"'

