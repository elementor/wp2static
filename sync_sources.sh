#!/bin/bash

# run wp-cli cmds from wp install path
cd /var/www/html

# copy plugin source files to WP path (else changing ownership will change on host)
cp -r /app wp-content/plugins/wordpress-static-html-output

# www-data to own plugin src files 
chown -R www-data:www-data wp-content/plugins/wordpress-static-html-output
chown -R www-data:www-data wp-content/uploads

