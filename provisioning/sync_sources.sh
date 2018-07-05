#!/bin/bash
echo 'syncing source files to plugin directory'

# run wp-cli cmds from wp install path
if [ -z "${SUBDIR_TO_INSTALL}" ]; then 
	echo "Syncing into root"; 
	cd /var/www/html
else 
	echo "Syncing into subdirectory: ${SUBDIR_TO_INSTALL}"; 
	cd /var/www/html/${SUBDIR_TO_INSTALL}
fi


# copy plugin source files to WP path (else changing ownership will change on host)
# ignore file ownership as we modify this post sync; ignore git folder
rsync -a --no-owner --exclude '.git/' --exclude 'setests' --exclude '*.swp' --delete  /app/ wp-content/plugins/wordpress-static-html-output/

# www-data to own plugin src files 
chown -R www-data:www-data wp-content/plugins/wordpress-static-html-output
chown -R www-data:www-data wp-content/uploads

