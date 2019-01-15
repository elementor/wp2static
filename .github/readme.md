# WP2Static

WordPress plugin to generate a static copy of your site and deploy to GitHub Pages, S3, Netlify, etc.  Increase security, pageload speed and hosting options. Connect WordPress into your CI/CD workflow.

## WordPress as a Static Site Generator

Watch Leon Stafford's [talk from WordCamp Brisbane 2018](http://www.youtube.com/watch?v=HPc4JjBvkrU)

[![WordPress as a Static Site Generator](http://img.youtube.com/vi/HPc4JjBvkrU/0.jpg)](http://www.youtube.com/watch?v=HPc4JjBvkrU)

## External resources

 - [WordPress.org plugin page](https://wordpress.org/plugins/static-html-output-plugin)
 - [Marketing site](https://wp2static.com)
 - [Documentation](https://docs.wp2static.com)
 - [Forum](https://forum.wp2static.com)
 - [Slack](https://join.slack.com/t/wp2static/shared_invite/enQtNDQ4MDM4MjkwNjEwLTVmN2I2MmU4ODI2MWRkNzM4ZGU3YWU4ZGVhMzgwZTc1MDE2OGNmYTFhOGMwM2U0ZTVlYTljYmM2Yjk2ODJlOTk)  
 - [Twitter](https://twitter.com/wp2static)  
 - [CircleCI](https://circleci.com/gh/leonstafford/wp2static) *master* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/master.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/master) *develop* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/develop.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/develop)

## Table of contents

* [Opinionated software](#opinionated-software)
* [WP-CLI commands](#wp-cli-commands)
* [Hooks](#hooks)
  * [Modify the initial list of URLs to crawl](#modify-the-initial-list-of-urls-to-crawl)
* [Development](#development)
* [Localisation / translations](#localisation--translations)
* [Support](#support)
* [Notes](#notes)
* [Sponsorship / supporting open-source](#sponsorship--supporting-open-source)

## Opinionated software

 - speed over beautiful code
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


## Development 

This repo contains the latest code, which you can clone/download to get the bleeding edge, else install via the [official WordPress Plugin page](https://wordpress.org/plugins/static-html-output-plugin/)

If you'd like to contribute, please follow the usual GitHub procedures (create an Issue, fork repo, submit PR). If you're unsure about any of that, contact me and I'll be happy to help.

In trying to make development/contributing easier, we'll keep requirements to a minimum. If you prefer Docker, Local by FlyWheel, Valet, Bedrock, Linux, BSD, Mac, they're all fine. This is a WordPress plugin, so anywhere you can run WordPress, you can do development on this :)


### Localisation / translations

Localisation has fallen behind on this project. I welcome anyone who can contribute some expertise in this area / help me get the project easier to translate.

Our official [translation page](https://translate.wordpress.org/projects/wp-plugins/static-html-output-plugin) on wordpress.org.


## Support

Please [raise an issue](https://github.com/leonstafford/wp2static/issues/new) here on GitHub or on the plugin's [support forum](https://forum.wp2static.com).

There is also a [Slack group](https://join.slack.com/t/wp2static/shared_invite/enQtNDQ4MDM4MjkwNjEwLTVmN2I2MmU4ODI2MWRkNzM4ZGU3YWU4ZGVhMzgwZTc1MDE2OGNmYTFhOGMwM2U0ZTVlYTljYmM2Yjk2ODJlOTk), for quick discussions among the user community.

## Notes

When cloning the repo for direct use, clone it into a dir named after the official WP plugin's slug, `static-html-output-plugin`, this will make life easier.

## Sponsorship / supporting open-source

I'm committed (git-pun) to keeping this software open-source and free from selling out user data to a 3rd party. As of version 6, we'll no longer be using Freemius for this reason. We'll accept payments with Snipcart + Stripe, but they will have no knowledge of your WordPress website or any info not required for a payment. The only thing that tracks you on our marketing website is a YouTube embed, which I'll soon switch to an image to avoid that. I rock OpenBSD on my workstation and increasingly on servers because they are an open source project done very well.

There is no company behind this software, besides a sole proprietership in my name, in order to comply with taxation requirements for me as an individual.

In order for me to continue to develop this software, maintain a free and open-source version, I need support.

If you're a company that benefits from the continued development of this or you're a big player that I recommend in our docs, like Netlify, GitHub, AWS, CloudFlare or even Mozilla. If you're a hosting company like DigitalOcean, Vultr or other developer-friendly, cost efficient provider, I still do recommend such hosts.

I need your sponsorship right now.

Leon

leon@wp2static.com
