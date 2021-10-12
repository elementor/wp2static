## WP2Static 7.2.0-dev

 - create an index on `wp2static_jobs.status` @john-shaffer
 - accept self-signed certs during sitemap crawling @john-shaffer
 - detect dead jobs and mark as failed @john-shaffer
 - mark duplicated waiting jobs as skipped on jobs page @john-shaffer
 - add an option to process the queue immediately #794 @john-shaffer
 - add ability to rewrite hosts specified on a new advanced options page @john-shaffer
  - as part of this, changed the host replacement function to use strtr instead of str_replace to avoid replacing things that we just replaced
 - add advanced option to skip URL rewriting @john-shaffer
 - fix error when a sitemap path starts with // @jhatmaker, @john-shaffer
 - move options labels and definitions out of the db and into code @john-shaffer

## WP2Static 7.1.7

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

## WP2Static 7.1.6

 - code quality improvements (thanks @szepeviktor)

## WP2Static 7.1.5

 - fix PHP version check to >=7.3
 - fix errors during sitemap detection (thanks @fromcouch)
 - fix errors during cache table initialisation
 - fix pagination URLs not using correct schema
 - fix CLI command registration issue

## WP2Static 7.1.2

 - update dependencies
 - add CHANGELOG
 - #682 only toggle other deploy addons, not other types when enabling a deployer
 - rm redundant Composer workaround
 - quieten build output
 - code quality improvements (thanks @szepeviktor!)

## WP2Static &lt; 7.1.2

 - didn't maintain Changelog or use tags, please review version control if curious

