# WP2Static

WP2Static是一个用来将您的网站静态化，并存储到Github Pages、 亚马逊S3、Netlify等平台的Wordpress插件，以此增强网站的安全性、页面加载速度以及托管环境的兼容性。更可以以此将Wordpress纳入到您的CI/CD工作流中。

[English ![English](docs/images/flags/greatbritain.png)](readme.md) |
[日本語 ![日本語](docs/images/flags/japan.png)](readme.jp.md) |
[Français ![Français](docs/images/flags/france.png)](readme.fr.md) |
[简体中文 ![Simplified Chinese](docs/images/flags/prchina.png)](readme.zh-cn.md)

# 使用WordPress作为静态网站生成器(Static Site Generator)

观看Leo Stafford在[WordCamp Brisbane 2018的演讲](http://www.youtube.com/watch?v=HPc4JjBvkrU)

[![使用WordPress作为静态网站生成器](http://img.youtube.com/vi/HPc4JjBvkrU/0.jpg)](http://www.youtube.com/watch?v=HPc4JjBvkrU)


## 目录

* [扩展资源](#扩展资源)
* [软件态度](#软件态度)
* [安装指南](#安装指南)
* [WP-CLI 命令](#WP-CLI命令行)
* [钩子](#hooks)
  * [修改需要抓取的初始化URL列表](#修改需要抓取的初始化URL列表)
  * [部署后钩子](#post-deployment-hook)
* [开发](#development)
* [本地化/翻译](#本地化--翻译)
* [支持](#支持)
* [说明](#说明)
* [赞助/支持开源软件](#赞助--支持开源软件)

## 扩展资源

 - [WordPress.org插件主页](https://wordpress.org/plugins/static-html-output-plugin)
 - [推广网站](https://wp2static.com)
 - [在线文档](https://docs.wp2static.com)
 - [论坛](https://forum.wp2static.com)
 - [Slack](https://join.slack.com/t/wp2static/shared_invite/enQtNDQ4MDM4MjkwNjEwLTVmN2I2MmU4ODI2MWRkNzM4ZGU3YWU4ZGVhMzgwZTc1MDE2OGNmYTFhOGMwM2U0ZTVlYTljYmM2Yjk2ODJlOTk)  
 - [Twitter](https://twitter.com/wp2static)  
 - [CircleCI](https://circleci.com/gh/leonstafford/wp2static) *master* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/master.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/master) *develop* [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/develop.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/develop)

## 软件态度

 - 重视速度胜过代码的美观
 - 代码可读性胜过简短的变量名
 - 自有代码 vs 外部类库
 - 建立衡量标准胜过经验观点(评估方面)
 - 更少的点击 == 更好的用户体验
 - 用户可设置 vs 开发者观点

## WP-CLI命令行

 - `wp wp2static options --help`
```
名称

  wp wp2static options

描述

  Read / write plugin options

概要

  wp wp2static options

选项

  <list> [--reveal-sensitive-values]

  Get all option names and values (explicitly reveal sensitive values)

  <get> <option-name>

  Get or set a specific option via name

  <set> <option-name> <value>

  Set a specific option via name


示例

  显示所有选项

    wp wp2static options list

  显示所有选项(展示敏感值)

    wp wp2static options list --reveal_sensitive_values

  获得某个选项值

    wp wp2static options get selected_deployment_option

  设置选项内容

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

### 修改需要抓取的初始化URL列表

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
### 部署后钩子

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

## 开发 

本仓库保存了最新的代码，你可以克隆或下载获得最新的跟新，同时也可以通过[官方Wordpress插件页](https://wordpress.org/plugins/static-html-output-plugin/)进行安装。

如果你希望参与贡献，请遵循通常的GitHub步骤(create an Issue, fork repo, submit PR)。如果你对任何事情有疑问，请联系我，我很乐意提供帮助。

为了让开发和共享更容易，我们会让参与的需要变得最简单。如果你比较喜欢使用Docker, Local by FlyWheel, Valet, Bedrock, Linux, BSD, Mac,这些已经足够了。这是一个WordPress插件，任何能跑Wordpress的地方都可以在上面开始开发。 :)


### 本地化 / 翻译

这个项目的本地化工作相对落后。我欢迎任何可以在这方面共享经验的人参与进来，帮助我让这个项目的翻译更容易。

我们在Wordpress.org上的[官方翻译页](https://translate.wordpress.org/projects/wp-plugins/static-html-output-plugin)。


## 支持

请移步到GitHub[提交Issue](https://github.com/leonstafford/wp2static/issues/new) 或者访问本插件的[支持论坛](https://forum.wp2static.com).

这里还有一个[Slack群组](https://join.slack.com/t/wp2static/shared_invite/enQtNDQ4MDM4MjkwNjEwLTVmN2I2MmU4ODI2MWRkNzM4ZGU3YWU4ZGVhMzgwZTc1MDE2OGNmYTFhOGMwM2U0ZTVlYTljYmM2Yjk2ODJlOTk), 可以在社区里快速参与讨论.

## 说明

当从仓库直接克隆下来作为使用用途时，将该文件夹命名为WordPress插件的官方slug:`static-html-output-plugin`，这样后续会更简单简单一些。

## 赞助/支持开源软件

I'm committed (git-pun) to keeping this software open-source and free from selling out user data to a 3rd party. As of version 6, we'll no longer be using Freemius for this reason. We'll accept payments with Snipcart + Stripe, but they will have no knowledge of your WordPress website or any info not required for a payment. The only thing that tracks you on our marketing website is a YouTube embed, which I'll soon switch to an image to avoid that. I rock OpenBSD on my workstation and increasingly on servers because they are an open source project done very well.

There is no big company behind this software, besides a sole proprietership in my name, in order to comply with taxation requirements for me as an Australian resident.

Help keep me doing what I love: building and supporting this software. 
协助让我坚持做我所热爱的————开发和支持这个软件

 - [购买商业许可](https://wp2static.com)
 - [在Patreon上资助我](https://www.patreon.com/leonstafford)
 - [在PayPal上资助我](https://www.paypal.me/leonjstafford)

Leon

leon@wp2static.com
