#!/bin/bash


for deployer in bitbucket ftp github gitlab netlify s3

do
  echo "$deployer" ;
  wp option update blogname "$deployer test"
  wp wp2static options set selected_deployment_option "$deployer"
  wp wp2static options set baseUrl $(wp wp2static options get "baseUrl-$deployer")
  wp wp2static deploy
done

exit

# Example usage to get each Destination URL printed after deploy
#
# (or just get from wp2static options)
#
# function printArchiveInfo( $archive ) {
#     error_log( $archive->settings['baseUrl'] );
# }
#
# add_filter( 'wp2static_post_deploy_trigger', 'printArchiveInfo' );
