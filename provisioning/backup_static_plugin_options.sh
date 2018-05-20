#!/bin/bash

# export plugin's options from WP
sudo docker exec -it plugindevwp bash -c 'mysqldump -u root -pbanana -h devmysql --databases wordpress --tables  wp_options --where="option_name = \"wp-static-html-output-options\"" > /app/plugin_options_backup.sql'

# move out of plugin dir
sudo rm ~/plugin_options_backup.sql
sudo mv plugin_options_backup.sql ~/

# local rm just in case
sudo rm -f ./plugin_options_backup.sql
