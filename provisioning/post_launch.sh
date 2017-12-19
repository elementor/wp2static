#!/bin/bash

# run wp-cli cmds from wp install path
cd /var/www/html

# source env vars to use in Docker run commands
. /.env-vars


# wait for mysql container
# * wordpress image's default entrypoint will also take some time
echo 'awaiting mysql to be reachable'

while ! mysqladmin ping -h devmysql --silent; do
    printf "."
    sleep 1
done

# still requires buffer before accessible for wp cli
sleep 5


# override site URL from env var if set
if [[ -z "${WPSTATICURL}" ]]; then
	echo 'no site URL specified, use container IP'
	# get container IP address to use as site URL
	containerIP=$(ip route get 1 | awk '{print $NF;exit}')
else
  containerIP="${WPSTATICURL}"
fi

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
echo "WordPress siteurl:"

wp --allow-root option get siteurl
