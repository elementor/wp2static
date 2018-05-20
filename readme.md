# WordPress Static HTML Output

Allows you to leverage WordPress as a great CMS, but benefit from the speed, security and portability that a static website provides.

---

## V3 Release - $261 of $1,000 funding goal met so far!

> Help get more features released in the next major version - [Back WP Static HTML Output Plugin on Kickstarter](https://www.kickstarter.com/projects/1776117837/the-wp-plugin-that-speeds-up-and-secures-your-site)

[![Kickstarter](https://upload.wikimedia.org/wikipedia/commons/thumb/b/b5/Kickstarter_logo.svg/512px-Kickstarter_logo.svg.png)](https://www.kickstarter.com/projects/1776117837/the-wp-plugin-that-speeds-up-and-secures-your-site)

The lead developer of this plugin, [Leon Stafford](https://leonstafford.github.io), is also available for custom web development work. Shoot him an email to see if he can solve your problems: [mailto:leonstafford@protonmail.com](leonstafford@protonmail.com).


---

## Features

 - generates a standalone, static html copy of your whole WordPress website
 - auto-deploy to local folder, FTP, Dropbox, S3 or GitHub Pages
 - one site to unlimited export targets
 - specify extra files to include in the output (ie, dynamically loaded assets)
 - desktop notifications alert you to when exports are complete
 - multi-language support (English/Japanese currently)

## Demo site

You can [see a working example here](https://leonstafford.github.io/demo-site-wordpress-static-html-output-plugin) of a plain WordPress install which has had a few tweaks done to optimize it for static HTML output. It is hosted on GitHub Pages, but could just as easily be hosted on Dropbox, BitBucket, GitLab, S3, your own server or anywhere else you can host HTML files.  

*TODO: move the demo theme into this repo, along with demonstrations of WP Hide and other useful plugins for WP static sites.*

### Scheduling exports via CRON/WP-CRON, etc

This is an in-development feature, currently only in the GitHub repo, not in the official plugin yet.

Using the [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/) plugin, you can add this hook to a schedule to trigger an export: `wp_static_html_output_server_side_export_hook`. This will run your export using the settings you've saved via the GUI. Via this method, you can schedule your exports to happen daily or if you're after an *on-post publish* kind of behaviour, you could set this to every few minutes.

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

### Theme & Plugin compatibility

Whilst it would be a challenge to test with every possible combination of plugins and themes, WP Static HTML Output Plugin has been tested with those in the following table and any notes are included. If you would like to see a fix to make it compatible with a certain theme or plugin, contact me.

|Theme/plugin name   |   |Known to work well   |Issues   |Notes   |
|---|---|---|---|---|
|Elementor page builder   |   |   |   |   |
|GeneratePress   |   |   |   |   |
|   |   |   |   |   |

## Development

Latest development build status: [![CircleCI](https://circleci.com/gh/leonstafford/wordpress-static-html-plugin/tree/master.svg?style=svg)](https://circleci.com/gh/leonstafford/wordpress-static-html-plugin/tree/master)

This repo contains the latest code, which you can clone/download to get the bleeding edge, else install via the [official WordPress Plugin page](https://wordpress.org/plugins/static-html-output-plugin/)

If you'd like to contribute, please follow the usual GitHub procedures (create an Issue, fork repo, submit PR). If you're unsure about any of that, contact me and I'll be happy to help. 

### Docker quickstart

To quickly try out the plugin, without affecting your other WordPress installations:

 - [install Docker](http://docker.com)
 - `./provisioning/destroy_and_rebuild.sh # view contents of this file to see how it builds
 - `./provisioning/get_webserver_ip.sh # outputs the IP address of the WordPress container
 - open IP in browser and you have a clean WP install, including the plugin (l/p: admin/admin)

Optional use case - for me, I sometimes need to do development on a remote EC2 instance (to overcome terrible internet speeds where I am). In this instance, I need to set the site URL to the public DNS or assigned domain name of my EC2 instance. You can copy the `./provisioning/.env-vars-SAMPLE` file to `./provisioning/.env-vars` and set the `WPSTATICURL` variable within to your publicly accessible URL on port `8091`.

### Demo website content

Included in the `./demo_site_content/` dir, are the posts used for the demo sites for this plugin, including guides on functionality. 

To capture content from the development instance, run `./provisioning/backup_demo_content.sh`

There is a great [Dockerized FTP server](https://github.com/stilliard/docker-pure-ftpd) which I've found useful in development. I may extend this to also serve the hosted files for more complete test capabilities. So long as you can install Docker, this is a much less painful way to get a local FTP server and users setup than what I've experienced before.

### Localisation / translations

Uses the [https://github.com/cedaro/grunt-wp-i18n](https://github.com/cedaro/grunt-wp-i18n) npm module and the Gruntfile.js in the project root. `npm i -g grunt` then `grunt` to scan plugin source and generate a new `languages/static-html-output-plugin.pot` file.

A `packages.json` file and `.nvmrc` exist to help show the dependencies required to get the grunt task working.

Our official [translation page](https://translate.wordpress.org/projects/wp-plugins/static-html-output-plugin) on wordpress.org. 

### Debugging

Connect to the container running the Apache instance.

`sudo docker ps` To find the running container.

`sudo docker exec -it {CONTAINER_ID} bash`

Tail the PHP access/error logs as such:

`docker logs -f plugindevwp`

To display only errors and hide the access log, you can pipe stdout to /dev/null:

`docker logs -f plugindevwp >/dev/null`

To follow only the access log, you can pipe stderr to /dev/null:

`docker logs -f your_php_apache_container 2>/dev/null`

## Support

Please [raise an issue](https://github.com/leonstafford/wordpress-static-html-plugin/issues/new) here on GitHub or on the plugin's [support forum](https://wordpress.org/support/plugin/static-html-output-plugin).

## Contact

Email me, Leon Stafford, at [lionhive@gmail.com](mailto:lionhive@gmail.com)

## Donations

Has this plugin helped you save some money on web hosting or otherwise helped you out? Please consider [donating via PayPal](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NHEV6WLYJ6QWQ) to help me help others. 

[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=leonstafford&url=https://github.com/leonstafford/wordpress-static-html-plugin&language=en_US&tags=github&category=software)

