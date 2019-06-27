# WP2Static

[![CircleCI](https://circleci.com/gh/WP2Static/wp2static.svg?style=svg)](https://circleci.com/gh/WP2Static/wp2static)

Plugin WordPress untuk generate salinan statis situs Anda dan deploy ke GitHub Pages, S3, Netlify, dll. Meningkatkan keamanan, kecepatan pageload, dan opsi hosting. Hubungkan WordPress ke dalam alur kerja CI/CD Anda.

[English ![English](https://cdn.staticaly.com/misc/flags/us.png?w=20)](readme.md) |
[日本語 ![日本語](https://cdn.staticaly.com/misc/flags/jp.png?w=20)](readme.jp.md) |
[Français ![Français](https://cdn.staticaly.com/misc/flags/fr.png?w=20)](readme.fr.md) |
[简体中文 ![Simplified Chinese](https://cdn.staticaly.com/misc/flags/cn.png?w=20)](readme.zh-cn.md) |
[Indonesia ![Indonesia](https://cdn.staticaly.com/misc/flags/id.png?w=20)](readme.id.md)

## WordPress sebagai Generator Situs Statis

Lihat presentasi Leon Stafford [di WordCamp Brisbane 2018](http://www.youtube.com/watch?v=HPc4JjBvkrU)

[![WordPress as a Static Site Generator](http://img.youtube.com/vi/HPc4JjBvkrU/0.jpg)](http://www.youtube.com/watch?v=HPc4JjBvkrU)


## Daftar Isi

* [Sumber daya eksternal](#sumber-daya-external)
* [Opinionated software](#opinionated-software)
* [Instalasi](#instalasi)
* [Perintah WP-CLI](#perintah-wp-cli)
* [Hooks](#hooks)
  * [Ubah daftar awal URL yang akan dirayapi](#ubah-daftar-awal-url-yang-akan-dirayapi)
  * [Post-deployment hook](#post-deployment-hook)
* [Pengembangan](#pengembangan)
* [Lokalisasi / terjemahan](#localisasi--terjemahan)
* [Dukungan](#dukungan)
* [Catatan](#catatan)
* [Sponsorship / mendukung open-source](#sponsorship--mendukung-open-source)

## Sumber daya eksternal

 - [WordPress.org plugin page](https://wordpress.org/plugins/static-html-output-plugin)
 - [Situs utama](https://wp2static.com)
 - [Dokumentasi](https://docs.wp2static.com)
 - [Forum](https://forum.wp2static.com)
 - [Slack](https://join.slack.com/t/wp2static/shared_invite/enQtNDQ4MDM4MjkwNjEwLTVmN2I2MmU4ODI2MWRkNzM4ZGU3YWU4ZGVhMzgwZTc1MDE2OGNmYTFhOGMwM2U0ZTVlYTljYmM2Yjk2ODJlOTk)  
 - [Twitter](https://twitter.com/wp2static)  
 - [CircleCI](https://circleci.com/gh/leonstafford/wp2static) *master* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/master.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/master) *develop* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/develop.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/develop)

## Opinionated software

 - kecepatan lebih dari kode yang indah
 - kode yang dapat dibaca manusia dari nama variabel pendek
 - own-code vs menambahkan perpustakaan
 - membuat tolok ukur atas pendapat (performa)
 - lebih sedikit klik == UX yang lebih baik
 - opsi yang dapat dikonfigurasi pengguna vs opini pengembang


## Perintah WP-CLI

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

There is no big company behind this software, besides a sole proprietership in my name, in order to comply with taxation requirements for me as an Australian resident.

Help keep me doing what I love: building and supporting this software. 

 - [Buy a commercial license](https://wp2static.com)

Leon

[me@ljs.dev](mailto:me@ljs.dev)
