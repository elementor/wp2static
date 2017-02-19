#!/bin/bash

# run wp-cli cmds from wp install path
cd /var/www/html

# install default
wp --allow-root core install --url='172.17.0.3' --title='wp plugindev' --admin_user=admin --admin_password=admin --admin_email=blah@blah.com --skip-email

. /sync_sources.sh


# activate wp static output plugin
wp --allow-root plugin activate wordpress-static-html-output

# OPTIONAL: install latest static plugin from WP plugins site vs local src
#wp --allow-root plugin install static-html-output-plugin --activate

# OPTIONAL: run log apache errors
# tail -f /var/log/apache2/error.log

