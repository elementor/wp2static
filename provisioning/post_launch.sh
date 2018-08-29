#!/bin/bash

#  ### Debugging
#  
#  Connect to the container running the Apache instance.
#  
#  `sudo docker ps` To find the running container.
#  
#  `sudo docker exec -it {CONTAINER_ID} bash`
#  
#  Tail the PHP access/error logs as such:
#  
#  `docker logs -f plugindevwp`
#  
#  To display only errors and hide the access log, you can pipe stdout to /dev/null:
#  
#  `docker logs -f plugindevwp >/dev/null`
#  
#  To follow only the access log, you can pipe stderr to /dev/null:
#  
#  `docker logs -f your_php_apache_container 2>/dev/null`
#  
#  *Debugging cURL requests*
#  
#  Set the `CURLOPT_VERBOSE` to `true`, with an example in the S3 library. 

# run wp-cli cmds from wp install path
if [ -z "${SUBDIR_TO_INSTALL}" ]; then 
	echo "Installing into root"; 
	cd /var/www/html
else 
	echo "Installing into subdirectory: ${SUBDIR_TO_INSTALL}"; 
	cd /var/www/html/${SUBDIR_TO_INSTALL}
fi

# copy plugin source files to avoid installing online
if [ -z "${SUBDIR_TO_INSTALL}" ]; then 
	cp -r /plugins/* /var/www/html/wp-content/plugins/
else 
	cp -r /plugins/* /var/www/html/${SUBDIR_TO_INSTALL}/wp-content/plugins/
fi

# source env vars to use in Docker run commands (now moved to run cmd using env-file)

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

INSTALL_URL=$containerIP

if [ -z "${SUBDIR_TO_INSTALL}" ]; then 
	echo "Installing into root"; 
else 
	echo "Installing into subdirectory: ${SUBDIR_TO_INSTALL}"; 
	INSTALL_URL="$containerIP/${SUBDIR_TO_INSTALL}/"
	#mkdir ${SUBDIR_TO_INSTALL}
	#cd ${SUBDIR_TO_INSTALL}
fi

# install core (replace default version on container)
if [ -z "${WP_INSTALL_VERSION}" ]; then 
	echo "Using WP version that came with container"; 
else 
	echo "Installing WP version: ${WP_INSTALL_VERSION}"; 
	wp --allow-root core download --version="$WP_INSTALL_VERSION" --force
fi

pwd
ls 
echo "$INSTALL_URL"

# install default
wp --allow-root core install --url="$INSTALL_URL" --title='wp plugindev' --admin_user=admin --admin_password=admin --admin_email=leonstafford@protonmail.com --skip-email


# before this

if [ -z "${INSTALL_PLUGIN_FROM_SOURCES}" ]; then 
	echo "Launching without any plugin files synced"; 
else 
	. /sync_sources.sh
fi


# sh: 1: -t: not found
# error around here


if [ -z "${WPMU_ENABLED}" ]; then 
	echo "NOT installing plugin from sources"; 
else 
  wp --allow-root config set WP_ALLOW_MULTISITE true --type=constant --raw
fi


wp --allow-root config set WP_DEBUG true --raw
# wp --allow-root config set WP_FS__DEV_MODE true --type=constant --raw
wp --allow-root config set SAVEQUERIES true --type=constant --raw
wp --allow-root config set WP_FS__static-html-output-plugin_SECRET_KEY $FREEMIUM_SECRET_KEY --type=constant --raw 
wp --allow-root config set WP_FS__SKIP_EMAIL_ACTIVATION true --type=constant --raw 

# activate wp static output plugin
if [ -z "${INSTALL_PLUGIN_FROM_SOURCES}" ]; then 
	echo "NOT installing plugin from sources"; 
else 
	wp --allow-root plugin activate wordpress-static-html-output
fi

# ensure additional plugin dirs are at correct permissions before activating
chown -R www-data:www-data wp-content/uploads

# OPTIONAL: install convenience / common plugins here
wp --allow-root plugin activate wp-crontrol 
#wp --allow-root plugin activate simply-static  
wp --allow-root plugin activate wordpress-importer 
#wp --allow-root plugin activate debug-bar  

# install supported languages
#wp --allow-root language core install es_ES
#wp --allow-root language core install ja

# delete hello world post
wp --allow-root post delete 1 --force
# delete sample page
wp --allow-root post delete 2 --force
# delete privacy policy page
wp --allow-root post delete 3 --force

# set uploads dir not to organise by year month day
wp --allow-root db query "update wp_options set option_value = 0 where option_name = 'uploads_use_yearmonth_folders';"

# import demo content media before post import
wp --allow-root media import /app/provisioning/demo_site_content/images/*.png
wp --allow-root media import /app/provisioning/demo_site_content/images/*.jpg
# import the demo site content
wp --allow-root import /app/provisioning/demo_site_content/wp_static_demo_content.xml --authors=create


if [ -z "${DUMMY_POSTS_PAGES_TO_CREATE}" ]; then 
	echo "not creating any dummy data"; 
	cd /var/www/html
else 
	echo "generating dummy post data: ${DUMMY_POSTS_PAGES_TO_CREATE}"; 
  wp --allow-root post generate --count=${DUMMY_POSTS_PAGES_TO_CREATE} --post_type=page
  wp --allow-root post generate --count=${DUMMY_POSTS_PAGES_TO_CREATE} --post_type=post
fi



# import data used for testing
cp /test_data/1px_yellow_background.png /var/www/html/wp-content/themes/twentyseventeen/

# OPTIONAL: install latest static plugin from WP plugins site vs local src
#wp --allow-root plugin install static-html-output-plugin --activate




#wp --allow-root plugin install elementor --activate # test plugin compatibility
#wp --allow-root theme install generatepress --activate # test theme compatibility
#wp --allow-root plugin install fakerpress --activate # generate dummy content for testing
#wp --allow-root plugin install wp-hide-security-enhancer --activate # strip out things making your site identifiable as a WP one
#wp --allow-root plugin install static-html-output-plugin --activate

# OPTIONAL: run log apache errors
#tail -f /var/log/apache2/error.log

chown -R www-data:www-data /var/www/html

echo "WordPress installed and accessible on $containerIP"
echo "WordPress siteurl:"

wp --allow-root option get siteurl
