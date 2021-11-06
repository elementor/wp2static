## Unreleased

 - [d3977eab](d3977eab6be24c4985d998a7f4bf07409ef4a71b): Create an index on `wp2static_jobs.status`. @john-shaffer
 - [#785](https://github.com/leonstafford/wp2static/issues/785): Accept self-signed certs during sitemap crawling. @working-name, @john-shaffer
 - [#806](https://github.com/leonstafford/wp2static/pull/806): Detect dead jobs and mark as failed. @john-shaffer
 - [#806](https://github.com/leonstafford/wp2static/pull/806): Mark duplicated waiting jobs as skipped on jobs page. @john-shaffer
 - [#794](https://github.com/leonstafford/wp2static/issues/794): Add an option to process the queue immediately. @john-shaffer
 - [#809](https://github.com/leonstafford/wp2static/pull/809): Add ability to rewrite hosts specified on a new advanced options page. @john-shaffer
   - As part of this, changed the host replacement function to use strtr instead of str_replace to avoid replacing things that we just replaced.
 - [#809](https://github.com/leonstafford/wp2static/pull/809): Add advanced option to skip URL rewriting. @john-shaffer
 - [#812](https://github.com/leonstafford/wp2static/pull/812): Add .editorconfig. @bookwyrm
 - [#816](https://github.com/leonstafford/wp2static/pull/816): Add wp2static_siteinfo filter. @palmiak
 - [bbc8abba](https://github.com/leonstafford/wp2static/commit/bbc8abba9103d097a62a6bbbd8d7a4229e788f4b): Fix error when a sitemap path starts with `//`. @jhatmaker, @john-shaffer
 - [#829](https://github.com/leonstafford/wp2static/pull/829): Move options labels and definitions out of the db and into code. @john-shaffer
 - [#826](https://github.com/leonstafford/wp2static/pull/826): Allow multiple redirects and report on redirects in wp-cli. @bookwyrm, @jhatmaker
 - [28fc58e5](https://github.com/leonstafford/wp2static/commit/28fc58e5f7694129e5919530adcd6c57435391fb): Add warning-level log messages. @john-shaffer
 - [#834](https://github.com/leonstafford/wp2static/pull/834): Implement concurrent crawling. @palmiak
   - Deprecate Crawler::crawlURL.
 - [#833](https://github.com/leonstafford/wp2static/pull/833): Add advanced options for specifying directories, files, and file extensions to ignore @john-shaffer

## WP2Static 7.1.7 (2021-09-04)

 - logging and fixes for Sitemap detection @palmiak, @john-shaffer
 - fix #793 properly dequeue + deregister scripts @mrwweb
 - fix diagnostics uploadsWritable description @yilinjuang
 - fix #730 detect network-wide enabled plugins @stefanullinger
 - `INSERT IGNORE` to silence add-on duplicate insert warnings
 - add filters to deployment webhook:
  - `wp2static_deploy_webhook_user_agent`
  - `wp2static_deploy_webhook_body`
  - `wp2static_deploy_webhook_headers`
 - improved unit test coverage for Detection classes
 - add trailing slash to detected category pagination URLs @john-shaffer
 - rm `autoload-dev` from composer.json @szepeviktor
 - extend PHPStan coverage to view/template files
 - allow toggling an add-on via WP-CLI
 - new `wp2static_detect` hook fires at URL detection start @john-shaffer
 - diagnostics checks for trailing slash in permalinks @john-shaffer, @jonmhutch7
 - use sfely namespaced Guzzle to avoid conflicts with other plugins
 - default to showing DeployCache paths across all namespaces #745
 - allow setting deploy webhook headers/body/user-agent via filter
 - fix PostsPaginationURL detection #758 @petewilcock, @john-shaffer
 - use custom request options for sitemap crawling
 - move from cURL to Guzzle for requests
 - fix incompatibilities with PHP8
 - import SitemapParser as internal class
 - fix MySQL issue preventing Add-on activations @john-shaffer, @TheLQ

## WP2Static 7.1.6 (2020-12-04)

 - code quality improvements (thanks @szepeviktor)

## WP2Static 7.1.5 (2020-12-04)

 - fix PHP version check to >=7.3
 - fix errors during sitemap detection (thanks @fromcouch)
 - fix errors during cache table initialisation
 - fix pagination URLs not using correct schema
 - fix CLI command registration issue

## WP2Static 7.1.2 (2020-11-03)

 - update dependencies
 - add CHANGELOG
 - #682 only toggle other deploy addons, not other types when enabling a deployer
 - rm redundant Composer workaround
 - quieten build output
 - code quality improvements (thanks @szepeviktor!)

## WP2Static &lt; 7.1.2

 - didn't maintain Changelog or use tags, please review version control if curious

