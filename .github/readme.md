# WP2Static

WordPress plugin to publish a static copy of your site to GitHub Pages, S3, Netlify or anywhere else you can pipe into your CI/CD workflow.

Formerly, "WP Static Site Generator"

For all the reasons why to use it and the benefits of going static, visit [https://wp2static.com](https://wp2static.com). For documentation, there's a [site for that](https://docs.wp2static.com), too.

Being a GitHub page, this is tailored for developers, sys admins or other technically inclined people wanting to poke around in the code and see how it's put together.

## Opionated software

 - speed over beautiful code
 - human readable code over variable names that fit within 80chars
 - own-code vs adding libraries
 - benchmarking over opinions (performance)
 - less clicks == better UX
 - user configurable options vs developer opinions

## CLI usage

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



## Development

Latest development build status: [![CircleCI](https://circleci.com/gh/leonstafford/wp2static/tree/master.svg?style=svg)](https://circleci.com/gh/leonstafford/wp2static/tree/master)

This repo contains the latest code, which you can clone/download to get the bleeding edge, else install via the [official WordPress Plugin page](https://wordpress.org/plugins/static-html-output-plugin/)

If you'd like to contribute, please follow the usual GitHub procedures (create an Issue, fork repo, submit PR). If you're unsure about any of that, contact me and I'll be happy to help.

In trying to make development/contributing easier, we'll keep requirements to a minimum. If you prefer Docker, Local by FlyWheel, Valet, Bedrock, Linux, BSD, Mac, they're all fine. This is a WordPress plugin, so anywhere you can run WordPress, you can do development on this :)


### Localisation / translations

Localisation has fallen behind on this project. I welcome anyone who can contribute some expertise in this area / help me get the project easier to translate.

Our official [translation page](https://translate.wordpress.org/projects/wp-plugins/static-html-output-plugin) on wordpress.org.


## Support

Please [raise an issue](https://github.com/leonstafford/wp2static/issues/new) here on GitHub or on the plugin's [support forum](https://forum.wp2static.com).

There is also a [Slack group](https://join.slack.com/t/wp2static/shared_invite/enQtNDQ4MDM4MjkwNjEwLTVmN2I2MmU4ODI2MWRkNzM4ZGU3YWU4ZGVhMzgwZTc1MDE2OGNmYTFhOGMwM2U0ZTVlYTljYmM2Yjk2ODJlOTk), for quick discussions among the user community.

## Sponsorship / supporting open-source

I'm committed (git-pun) to keeping this software open-source and free from selling out user data to a 3rd party. As of version 6, we'll no longer be using Freemius for this reason. We'll accept payments with Snipcart + Stripe, but they will have no knowledge of your WordPress website or any info not required for a payment. The only thing that tracks you on our marketing website is a YouTube embed, which I'll soon switch to an image to avoid that. I rock OpenBSD on my workstation and increasingly on servers because they are an open source project done very well.

There is no company behind this software, besides a sole proprietership in my name, in order to comply with taxation requirements for me as an individual.

In order for me to continue to develop this software, maintain a free and open-source version, I need support.

If you're a company that benefits from the continued development of this or you're a big player that I recommend in our docs, like Netlify, GitHub, AWS, CloudFlare or even Mozilla. If you're a hosting company like DigitalOcean, Vultr or other developer-friendly, cost efficient provider, I still do recommend such hosts.

I need your sponsorship right now.

Leon

leon@wp2static.com
