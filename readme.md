# WordPress Static HTML Output

Allows you to leverage WordPress as a great CMS, but benefit from the speed, security and portability that a static website provides.

---
    
## Features

 - generates a standalone, static html copy of your whole WordPress website
 - auto-deploy to local folder, FTP, Dropbox, S3, GitHub Pages, Netlify or BunnyCDN
 - scheduled exports via WP-Crontrol of by hitting the custom hook
 - one site to unlimited export targets
 - specify extra files to include in the output (ie, dynamically loaded assets)
 - desktop notifications alert you to when exports are complete

## Use cases

 - Securing a website from malicious attacks/malware
 - Fastest hosting options for static websites
 - Free hosting via GitHub, GitLab, BitBucket, etc.
 - Website archival
 - Cheap, fast and secure hosting for a digital agency

## Getting started

Please refer to the [documentation](https://docs.wp2static.com).


## Development

Latest development build status: [![CircleCI](https://circleci.com/gh/leonstafford/wordpress-static-html-plugin/tree/master.svg?style=svg)](https://circleci.com/gh/leonstafford/wordpress-static-html-plugin/tree/master)

This repo contains the latest code, which you can clone/download to get the bleeding edge, else install via the [official WordPress Plugin page](https://wordpress.org/plugins/static-html-output-plugin/)

If you'd like to contribute, please follow the usual GitHub procedures (create an Issue, fork repo, submit PR). If you're unsure about any of that, contact me and I'll be happy to help. 

To get a local development environment setup, copy the `./provisioning/.env-vars-SAMPLE` to `./provisioning/.env-vars` and run `./provisioning/destroy_and_rebuild.sh`, the development site will be accessible at [http://172.18.0.3](http://172.18.0.3).


### Localisation / translations

Uses the [https://github.com/cedaro/grunt-wp-i18n](https://github.com/cedaro/grunt-wp-i18n) npm module and the Gruntfile.js in the project root. `npm i -g grunt` then `grunt` to scan plugin source and generate a new `languages/static-html-output-plugin.pot` file.

A `packages.json` file and `.nvmrc` exist to help show the dependencies required to get the grunt task working.

Our official [translation page](https://translate.wordpress.org/projects/wp-plugins/static-html-output-plugin) on wordpress.org. 


## Support

Please [raise an issue](https://github.com/leonstafford/wordpress-static-html-plugin/issues/new) here on GitHub or on the plugin's [support forum](https://wordpress.org/support/plugin/static-html-output-plugin).

Email for support: [help@wp2static.com](mailto:help@wp2static.com).

