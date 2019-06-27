# WP2Static

WordPressで静的HTMLを生成して、色んな可能性を開きます。

バックエンドを公共から守って。

リソースよりも幸福をスケールしよう！

クラスの一番データ配信を使えるになる。

[English ![English](https://cdn.staticaly.com/misc/flags/us.png?w=20)](readme.md) |
[日本語 ![日本語](https://cdn.staticaly.com/misc/flags/jp.png?w=20)](readme.jp.md) |
[Français ![Français](https://cdn.staticaly.com/misc/flags/fr.png?w=20)](readme.fr.md) |
[简体中文 ![Simplified Chinese](https://cdn.staticaly.com/misc/flags/cn.png?w=20)](readme.zh-cn.md) |
[Indonesia ![Indonesia](https://cdn.staticaly.com/misc/flags/id.png?w=20)](readme.id.md)

## ワードプレスを静的サイトジェネレータとして使用します

開発者スタフォード・レオン様の [WordCamp Brisbane 2018でのプレゼンテーション](http://www.youtube.com/watch?v=HPc4JjBvkrU) を見ます。

[![ワードプレスを静的サイトジェネレータとして使用します](http://img.youtube.com/vi/HPc4JjBvkrU/0.jpg)](http://www.youtube.com/watch?v=HPc4JjBvkrU)


## 目次

* [外部リソース](#external-resources)
* [意見のあるソフトウェア](#opinionated-software)
* [インストール仕方](#installation)
* [WP-CLIのコマンド](#wp-cli-commands)
* [フック](#hooks)
  * [Modify the initial list of URLs to crawl](#modify-the-initial-list-of-urls-to-crawl)
  * [Post-deployment hook](#post-deployment-hook)
* [開発の協力すりには](#development)
* [ローカライゼーション・翻訳](#localisation--translations)
* [サポート](#support)
* [お知らせ](#notes)
* [オープンソースを応援しましょう〜](#sponsorship--supporting-open-source)

## External resources

 - [WordPress.org plugin page](https://wordpress.org/plugins/static-html-output-plugin)
 - [Marketing site](https://wp2static.com)
 - [Documentation](https://docs.wp2static.com)
 - [Forum](https://forum.wp2static.com)
 - [Slack](https://join.slack.com/t/wp2static/shared_invite/enQtNDQ4MDM4MjkwNjEwLTVmN2I2MmU4ODI2MWRkNzM4ZGU3YWU4ZGVhMzgwZTc1MDE2OGNmYTFhOGMwM2U0ZTVlYTljYmM2Yjk2ODJlOTk)
 - [Twitter](https://twitter.com/wp2static)
 - [CircleCI](https://circleci.com/gh/leonstafford/wp2static) *master* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/master.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/master) *develop* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/develop.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/develop)

## Opinionated software

 - 完璧のコードよりパーフォーマンス
 - 短い変数名より人間が読めるコード
 - 外部ライブラリより自分達の書いたコード
 - 意見よりもデータ（ベンチマーク、など）
 - クリック数が低い方が良いUX
 - 開発者のお好みよりユーザが決める設定


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

## Development

このレポの「`develop`」ブランチには最新コードがあります。安定したコードは[リリース](https://github.com/leonstafford/wp2static/releases)または[wordpress.org](https://wordpress.org/plugins/static-html-output-plugin/)の方からダウンロードして下さい。

プラグインに協力したいの方には普通のGitHubの方法で：

 - [Issue](https://github.com/leonstafford/wp2static/issues)を作って
 - レポをフォークして
 - PRを送信する

何かの不明所がありましたら、是非開発者に連絡して下さい：

[スタフォード・レオン](mailto:me@ljs.dev)　（英語・日本語）

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

 - [Buy the Plugin](https://wp2static.com)

Leon

[me@ljs.dev](mailto:me@ljs.dev)
