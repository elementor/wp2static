#!/bin/bash

# run wp-cli cmds from wp install path
cd /var/www/html

# install default
wp --allow-root core install --url='172.17.0.3' --title='wp plugindev' --admin_user=admin --admin_password=admin --admin_email=blah@blah.com --skip-email

# install static plugin
wp --allow-root plugin install static-html-output-plugin --activate

#TODO: option to use local app folder vs pulling plugin from web

# run continual process to keep container alive without daemonizing
tail -f /var/log/apache2/error.log

