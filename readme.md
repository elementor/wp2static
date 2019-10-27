# WP2Static

A WordPress plugin for static site generation and deployment.

## Functional description

WP2Static detects and crawls all HTML, CSS, JS, images and other resources in the WordPress site it is installed in, generating a self-contained static website, with URLs and other post-processing filters applied.

It allows you to deploy the generated static site to a staging or production environment.

WP2Static detects resources in a WordPress site by querying its database, installed plugins, themes and the filesystem for known resource paths. An initial list of URLs to crawl is generated from these detected URLs. The user has control over WP2Static's detection levels.

As WP2Static crawls your site, it can discover URLs to any resources not already detected.

The expecxted output of the crawling phase is a self-contained static website.

Rules determine how to process each URL as the site is crawled.

Unless a URL is skipped due to caching or a particular rule, the content at the URL (HTML, CSS, image, etc) is inspected and if qualifies, is included in the generated static site.



__Note: GitHub repo is a read-only mirror. Our main repos and information on contributing are hosted at [https://code.wp2static.com](https://code.wp2static.com).__

## Static Site Generator for WordPress

Watch the developer's [talk from WordCamp Brisbane 2018](http://www.youtube.com/watch?v=HPc4JjBvkrU)


## Table of contents

* [External resources](#external-resources)
* [Opinionated software](#opinionated-software)
* [Installation](#installation)
* [WP-CLI commands](#wp-cli-commands)
* [Hooks](#hooks)
  * [Modify the initial list of URLs to crawl](#modify-the-initial-list-of-urls-to-crawl)
  * [Post-deployment hook](#post-deployment-hook)
* [Development](#development)
* [Localisation / translations](#localisation--translations)
* [Support](#support)
* [Notes](#notes)
* [Sponsorship / supporting open-source](#sponsorship--supporting-open-source)

## External resources

 - [WordPress.org plugin page](https://wordpress.org/plugins/static-html-output-plugin)
 - [Marketing site](https://wp2static.com)
 - [Documentation](https://docs.wp2static.com)
 - [Forum](https://forum.wp2static.com)
 - [Twitter](https://twitter.com/wp2static)

## Opinionated software

 - ~~speed over beautiful code~~ code quality is now prioritized! Thanks @szepeviktor, @arswright!
 - human readable code over short variable names
 - own-code vs adding libraries
 - benchmarking over opinions (performance)
 - less clicks == better UX
 - user configurable options vs developer opinions


## WP-CLI commands

 - `wp wp2static options --help`
```
NAME

  wp wp2static options

DESCRIPTION

  Read / write plugin options

SYNOPSIS

  wp wp2static options

OPTIONS

  <list> [--reveal-sensitive-values]

  Get all option names and values (explicitly reveal sensitive values)

  <get> <option-name>

  Get or set a specific option via name

  <set> <option-name> <value>

  Set a specific option via name


EXAMPLES

  List all options

    wp wp2static options list

  List all options (revealing sensitive values)

    wp wp2static options list --reveal_sensitive_values

  Get option

    wp wp2static options get selected_deployment_option

  Set option

    wp wp2static options set baseUrl 'https://mystaticsite.com'
```
 - `wp wp2static generate`

```
Generating static copy of WordPress site
Success: Generated static site archive in 00:00:04
```

 - `wp wp2static deploy --test`
 - `wp wp2static deploy`
 - `wp wp2static generate`

```
Generating static copy of WordPress site
Success: Generated static site archive in 00:00:04
```

 - `wp wp2static deploy --test`
 - `wp wp2static deploy`

```
Deploying static site via: zip
Success: Deployed to: zip in 00:00:01
Sending confirmation email...
```

## Hooks

### Modify the initial list of URLs to crawl

 - `wp2static_modify_initial_crawl_list`
 - Filter hook

*signature*
```php
apply_filters(
    'wp2static_modify_initial_crawl_list',
    $url_queue
);
```

*example usage*
```php
function add_additional_urls( $url_queue ) {
    $additional_urls = array(
        'http://mydomain.com/custom_link_1/',
        'http://mydomain.com/custom_link_2/',
    );

    $url_queue = array_merge(
        $url_queue,
        $additional_urls
    );

    return $url_queue;
}

add_filter( 'wp2static_modify_initial_crawl_list', 'add_additional_urls' );
```
### Post-deployment hook

 - `wp2static_post_deploy_trigger`
 - Action hook

*signature*
```php
do_action(
  'wp2static_post_deploy_trigger',
  $archive
);
```

*example usage*
```php
function printArchiveInfo( $archive ) {
    error_log( print_r( $archive, true ) );
}

add_filter( 'wp2static_post_deploy_trigger', 'printArchiveInfo' );
```

*example response*
```
Archive Object
(
    [settings] => Array
        (
            [selected_deployment_option] => github
            [baseUrl] => https://leonstafford.github.io/demo-site-wordpress-static-html-output-plugin/
            [wp_site_url] => http://example.test/
            [wp_site_path] => /srv/www/example.com/current/web/wp/
            [wp_uploads_path] => /srv/www/example.com/current/web/app/uploads
            [wp_uploads_url] => http://example.test/app/uploads
            [wp_active_theme] => /wp/wp-content/themes/twentyseventeen
            [wp_themes] => /srv/www/example.com/current/web/app/themes
            [wp_uploads] => /srv/www/example.com/current/web/app/uploads
            [wp_plugins] => /srv/www/example.com/current/web/app/plugins
            [wp_content] => /srv/www/example.com/current/web/app
            [wp_inc] => /wp-includes
            [crawl_increment] => 1
        )

    [path] => /srv/www/example.com/current/web/app/uploads/wp-static-html-output-1547668758/
    [name] => wp-static-html-output-1547668758
    [crawl_list] =>
    [export_log] =>
)

```
### Add deployment option to UI

 - `wp2static_add_deployment_method_option_to_ui`
 - Filter hook

*signature*
```php
apply_filters(
    'wp2static_modify_initial_crawl_list',
    $options
);
```

*example usage*
```php
function add_deployment_option_to_ui( $deploy_options ) {
    $deploy_options['azure'] = array('Microsoft Azure');

    return $deploy_options;
}

add_filter(
    'wp2static_add_deployment_method_option_to_ui',
    'add_deployment_option_to_ui'
);
```
### Load deployment option template

 - `wp2static_load_deploy_option_template`
 - Filter hook

*signature*
```php
apply_filters(
    'wp2static_load_deploy_option_template',
    $options
);
```

*example usage*
```php
function load_deployment_option_template( $templates ) {
    $templates[] =  '/path/to/deployment_settings_block.phtml';

    return $templates;
}

add_filter(
    'wp2static_load_deploy_option_template',
    'load_deployment_option_template'
);
```
### Register new plugin option key

 - `wp2static_add_option_keys`
 - Filter hook

*signature*
```php
apply_filters(
    'wp2static_add_option_keys',
    $options
);
```

*example usage*
```php
function addWP2StaticOption( $options ) {
    $new_options = array(
        'baseUrl-azure',
        'azStorageAccountName',
        'azContainerName',
        'azAccessKey',
        'azPath',
    );

    $options = array_merge(
        $options,
        $new_options
    );

    return $options;
}

add_filter(
    'wp2static_load_deploy_option_template',
    'addWP2StaticOption'
);
```
### Whitelist plugin option keys

 - `wp2static_whitelist_option_keys`
 - Filter hook

*signature*
```php
apply_filters(
    'wp2static_whitelist_option_keys',
    $options
);
```

*example usage*
```php
function whitelistWP2StaticOption( $options ) {
    $whitelist_options = array(
      'baseUrl-azure',
    );

    $options = array_merge(
        $options,
        $whitelist_options
    );

    return $options;
}

add_filter(
    'wp2static_load_deploy_option_template',
    'addWP2StaticOption'
);
```

### Add deployment option for CLI usage

Every Add-on listens for this event, checks selected deployment method matches
 Add-on type and triggers the deployment actions specific to the Add-on.

 - `wp2static_addon_trigger_deploy`
 - Action hook

*signature*
```php
do_action(
  'wp2static_addon_trigger_deploy',
  $method
);
```

*example usage*
```php
function runBackendDeployment( $method ) {
    if ( $method !== 'bunnycdn' ) {
        return;
    }

    $bunnyCDN = new WP2Static\BunnyCDN();
    $bunnyCDN->bootstrap();
    $bunnyCDN->prepareDeploy( true );
    $bunnyCDN->bunnycdn_transfer_files();
    $bunnyCDN->bunnycdn_purge_cache();
}

add_filter( 'wp2static_addon_trigger_deploy', 'runBackendDeployment' );
```
## Development

This repo contains the latest code, which you can clone/download to get the bleeding edge, else install via the [official WordPress Plugin page](https://wordpress.org/plugins/static-html-output-plugin/)

If you'd like to contribute, please follow the usual GitHub procedures (create an Issue, fork repo, submit PR). If you're unsure about any of that, contact me and I'll be happy to help.

In trying to make development/contributing easier, we'll keep requirements to a minimum. If you prefer Docker, Local by FlyWheel, Valet, Bedrock, Linux, BSD, Mac, they're all fine. This is a WordPress plugin, so anywhere you can run WordPress, you can do development on this :)

### Installing from source

 - `git clone -b git@github.com:WP2Static/wp2static.git static-html-output-plugin`
 - `cd static-html-output-plugin`
 - `npm i`
 - `composer install`
 - `composer buildjs`

### Running tests

 - `composer test`

End to end tests, driven viu the UI via Puppeteer, Jest and Chromium can be run with:

 - `jest` (assuming globally installed Jest via `npm i -g jest`)

A new WP site for testing is created when running via CircleCI. For local testing, point to a test WordPress site with the following environment variables defined:

 - `WP2STATIC_E2E_TEST_URL`
 - `WP2STATIC_E2E_TEST_USER`
 - `WP2STATIC_E2E_TEST_PASS`


### Localisation / translations

Localisation has fallen behind on this project. I welcome anyone who can contribute some expertise in this area / help me get the project easier to translate.

Our official [translation page](https://translate.wordpress.org/projects/wp-plugins/static-html-output-plugin) on wordpress.org.


## Support

Please [raise an issue](https://github.com/leonstafford/wp2static/issues/new) here on GitHub or on the plugin's [support forum](https://forum.wp2static.com).

Support email is [mailto:help@wp2static.com](help@wp2static.com)

## Notes

When cloning the repo for direct use, clone it into a dir named after the official WP plugin's slug, `static-html-output-plugin`, this will make life easier.

## Sponsorship / supporting open-source

I'm committed (git-pun) to keeping this software open-source and free from selling out user data to a 3rd party. As of version 6, we'll no longer be using Freemius for this reason. We'll accept payments with Snipcart + Stripe, but they will have no knowledge of your WordPress website or any info not required for a payment. The only thing that tracks you on our marketing website is a YouTube embed, which I'll soon switch to an image to avoid that. I rock OpenBSD on my workstation and increasingly on servers because they are an open source project done very well.

There is no big company behind this software, besides a sole proprietership in my name, in order to comply with taxation requirements for me as an Australian resident.

Help keep me doing what I love: building and supporting this software.

 - [Buy a commercial license](https://wp2static.com)

Leon

[me@ljs.dev](mailto:me@ljs.dev)
