# WordPress Static HTML Output

Allows you to leverage WordPress as a great CMS, but benefit from the speed, security and portability that a static website provides.

## Features

 - generates a standalone, static html copy of your whole WordPress website
 - auto-deploy to local folder, FTP, Dropbox, S3 or GitHub Pages
 - one site to unlimited export targets
 - specify extra files to include in the output (ie, dynamically loaded assets)
 - desktop notifications alert you to when exports are complete
 - multi-language support (English/Japanese currently)

## Demo site

You can [see a working example here](https://leonstafford.github.io/demo-site-wordpress-static-html-output-plugin) of a plain WordPress install which has had a few tweaks done to optimize it for static HTML output. It is hosted on GitHub Pages, but could just as easily be hosted on Dropbox, BitBucket, GitLab, S3, your own server or anywhere else you can host HTML files.  

## Roadmap

 - selectively export only changed pages since last output
 - auto-deploy your static files via sFTP, SCP, Netlify, etc
 - auto trigger an export via CRON job or on each blog update

### 2.1 release brings:

 - updated AWS regions for S3/CloudFront
 - auto export site to Netlify (BETA)
 - live status of export
 - logging of errors when an export fails 
 - prevent hanging on failure

## Development

Latest development build status: [![CircleCI](https://circleci.com/gh/leonstafford/wordpress-static-html-plugin/tree/master.svg?style=svg)](https://circleci.com/gh/leonstafford/wordpress-static-html-plugin/tree/master)

This repo contains the latest code, which you can clone/download to get the bleeding edge, else install via the [official WordPress Plugin page](https://wordpress.org/plugins/static-html-output-plugin/)

If you'd like to contribute, please follow the usual GitHub procedures (create an Issue, fork repo, submit PR). If you're unsure about any of that, contact me and I'll be happy to help. 

## Docker quickstart

To quickly try out the plugin, without affecting your other WordPress installations:

 - [install Docker](http://docker.com)
 - `./provisioning/destroy_and_rebuild.sh # view contents of this file to see how it builds
 - above command outputs the IP address of the WordPress container, but you can also run below steps
 - `docker ps` # get WordPress container's id so you can connect from the host
 - `docker inspect __yourcontainerid__ | grep Address` # get IP for connecting in your browser
 - open IP in browser and you have a clean WP install, including the plugin (l/p: admin/admin)

Optional use case - for me, I sometimes need to do development on a remote EC2 instance (to overcome terrible internet speeds where I am). In this instance, I need to set the site URL to the public DNS or assigned domain name of my EC2 instance. You can copy the `./provisioning/.env-vars-SAMPLE` file to `./provisioning/.env-vars` and set the `WPSTATICURL` variable within to your publicly accessible URL on port `8091`.

There is a great [Dockerized FTP server](https://github.com/stilliard/docker-pure-ftpd) which I've found useful in development. I may extend this to also serve the hosted files for more complete test capabilities. So long as you can install Docker, this is a much less painful way to get a local FTP server and users setup than what I've experienced before.

### Debugging

Connect to the container running the Apache instance.

`sudo docker ps` To find the running container.

`sudo docker exec -it {CONTAINER_ID} bash`

Tail the PHP error log as such `tail -f /var/log/apache2/php_err.log`

## Support

Please [raise an issue](https://github.com/leonstafford/wordpress-static-html-plugin/issues/new) here on GitHub or on the plugin's [support forum](https://wordpress.org/support/plugin/static-html-output-plugin).

## Contact

Email me, Leon Stafford, at [lionhive@gmail.com](mailto:lionhive@gmail.com)

## Donations

Has this plugin helped you save some money on web hosting or otherwise helped you out? Please consider [donating via PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NHEV6WLYJ6QWQ) to help me help others. 
