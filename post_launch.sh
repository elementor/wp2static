#!/bin/bash

# run wp-cli cmds from wp install path
cd /var/www/html

# install default
wp --allow-root core install --url='172.17.0.3' --title='wp plugindev' --admin_user=admin --admin_password=admin --admin_email=blah@blah.com --skip-email

# copy plugin source files to WP path (else changing ownership will change on host)
cp -r /app wp-content/plugins/wordpress-static-html-output

# www-data to own plugin src files
chown -R www-data:www-data wp-content/plugins/wordpress-static-html-output
chown -R www-data:www-data wp-content/uploads

# OPTIONAL: install latest static plugin from WP plugins site vs local src
#wp --allow-root plugin install static-html-output-plugin --activate

# OPTIONAL: run log apache errors
# tail -f /var/log/apache2/error.log

