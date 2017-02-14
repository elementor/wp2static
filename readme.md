# WordPress Static HTML Output

Allows you to leverage WordPress as a great CMS, but benefit from the speed, security and portability that a static website provides.

## Features

 - generates a standalone, static html copy of your whole WordPress website
 - specify extra files to include in the output (ie, dynamically loaded assets)
 - multi-language support (English/Japanese currently)

## Demo site

You can [see a working example here](https://leonstafford.github.io/demo-site-wordpress-static-html-output-plugin) of a plain WordPress install which has had a few tweaks done to optimize it for static HTML output. It is hosted on GitHub Pages, but could just as easily be hosted on Dropbox, BitBucket, GitLab, S3, your own server or anywhere else you can host HTML files.  

## Roadmap

 - selectively export only changed pages since last output
 - deploy your static files via sFTP, SCP, Dropbox, etc
 - have a one-liner provisioning script for testing/development

## Development

This repo contains the latest code, which you can clone/download to get the bleeding edge, else install via the [official WordPress Plugin page](https://wordpress.org/plugins/static-html-output-plugin/)

If you'd like to contribute, please follow the usual GitHub procedures (create an Issue, fork repo, submit PR). If you're unsure about any of that, contact me and I'll be happy to help. 

## Docker quickstart

To quickly try out the plugin, without affecting your other WordPress installations:

 - [install Docker](http://docker.com)
 - `docker build -t leonstafford/wordpress-static-html-plugin:latest # add zip extension and WP cli on top of latest WordPress Docker image
 - `docker run --name devmysql -e MYSQL_ROOT_PASSWORD=banana -d mariadb` # run a MySQL container for the WordPress one to later use
 - `docker run --name plugindevwp --link devmysql:mysql -p 8080:80 -d wordpress` # run the official WordPress image, linking to your MySQL instance
 - `docker exec -it plugindevwp bash` # get CLI access to the container
 - `sh /post_launch.sh` # install WP; install and activate static output plugin
 - `exit` # leave the container's shell
 - `docker ps` # get WordPress container's id so you can connect from the host
 - `docker inspect __yourcontainerid__ | grep Address` # get IP for connecting in your browser
 - open IP in browser and you have a clean WP install, including the plugin (l/p: admin/admin)

## Support

Development is done in my personal time. If you would like to see some new features added, bugs fixed, etc, think about sending me a donation for motivation ;)

## Contact

Email me, Leon Stafford, at [lionhive@gmail.com](mailto:lionhive@gmail.com)
