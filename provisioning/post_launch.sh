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

# import plugin needed for demo site content
wp --allow-root plugin install wordpress-importer --activate

# install supported languages
wp --allow-root language core install es_ES
wp --allow-root language core install ja

# delete hello world post
wp --allow-root post delete 1 --force

# import demo content media before post import
wp --allow-root media import /app/demo_site_content/images/*.png
wp --allow-root media import /app/demo_site_content/images/*.jpg
# import the demo site content
wp --allow-root import /app/demo_site_content/wp_static_demo_content.xml --authors=create


# OPTIONAL: install latest static plugin from WP plugins site vs local src
#wp --allow-root plugin install static-html-output-plugin --activate

# OPTIONAL: install convenience / common plugins here
wp --allow-root plugin install wp-crontrol --activate # look into WP Cron
wp --allow-root plugin install elementor --activate # test plugin compatibility
wp --allow-root theme install generatepress --activate # test theme compatibility
#wp --allow-root plugin install fakerpress --activate # generate dummy content for testing
#wp --allow-root plugin install wp-hide-security-enhancer --activate # strip out things making your site identifiable as a WP one
#wp --allow-root plugin install static-html-output-plugin --activate

# OPTIONAL: run log apache errors
#tail -f /var/log/apache2/error.log

echo "WordPress installed and accessible on $containerIP"
echo "WordPress siteurl:"

wp --allow-root option get siteurl
