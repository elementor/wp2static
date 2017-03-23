#!/bin/bash

# run wp-cli cmds from wp install path
cd /var/www/html


# wait for mysql container
# * wordpress image's default entrypoint will also take some time
echo 'awaiting mysql to be reachable'

while ! mysqladmin ping -h devmysql --silent; do
    printf "."
    sleep 1
done

# still requires buffer before accessible for wp cli
sleep 5

# get container IP address
containerIP=$(ip route get 1 | awk '{print $NF;exit}')

echo 'pwd:'
pwd

# install default
wp --allow-root core install --url="$containerIP" --title='wp plugindev' --admin_user=admin --admin_password=admin --admin_email=blah@blah.com --skip-email

. /sync_sources.sh

# activate wp static output plugin
wp --allow-root plugin activate wordpress-static-html-output

# OPTIONAL: install latest static plugin from WP plugins site vs local src
#wp --allow-root plugin install static-html-output-plugin --activate

# OPTIONAL: run log apache errors
#tail -f /var/log/apache2/error.log

echo "WordPress installed and accessible on $containerIP"
