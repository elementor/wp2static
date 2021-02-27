## WP2Static 7.1.7

 - default to showing DeployCache paths across all namespaces #745
 - allow setting deploy webhook headers/body/user-agent via filter
 - fix PostsPaginationURL detection #758
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

