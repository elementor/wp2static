=== WP2Static ===
Contributors: leonstafford
Tags: security, performance, static
Requires at least: 3.2
Tested up to: 5.0.2
Requires PHP: 5.6
Stable tag: 6.5.1

Security & Performance via static website publishing. One plugin to solve WordPress's biggest problems.

== Description ==

Security & Performance via static website publishing. One plugin to solve WordPress's biggest problems.

Keep using WordPress for what it's best at - managing content, but remove the security and performance headaches by publishing the site as static HTML.

A static site closes all the doors that an out of date WordPress, theme or plugin can leave open.

A pre-generated static site will outperform caching plugins, allowing you to serve at local speeds to users around the world.

[**https://wp2static.com**](https://wp2static.com/)

Not compatible with WooCommerce or membership sites, but solutions like [Snipcart](https://snipcart.com) allow for eCommerce on static websites.

= Features =

 * publishes a standalone, static html copy of your whole WordPress website
 * removes tell-tale signs your site is running WordPress, making it unattractive to hackers
 * auto-deploy to a folder on your server, a ZIP file, FTP server, S3, GitHub, Netlif, BunnyCDN, BitBucket or GitLab
 * schedule unattended exports via the WP Crontrol plugin or by hitting the custom hook
 * desktop notifications alert you to when exports are complete


= Benefits =

 * protects you from malicious attacks/malware
 * speeds up your site by not hitting the database or executing any PHP code
 * allows you to host your site for free on GitHub Pages, Netlify or the free tier of AWS S3, Azure, etc
 * allows you to deploy to crazy fast hosting options, like S3, behind CloudFront
 * have a nice development -> staging -> production workflow and integrate with your CI tools

= A word from the author =

[youtube http://www.youtube.com/watch?v=HPc4JjBvkrU]

= Who loves this? =

 - Digital Agencies with many sites to manage, no need to worry about WP/plugin updates for client sites
 - Internet Marketers can create a bunch of quick sites/landing pages that load fast and are free to host
 - Solo website owners and content creators who like WordPress but don't want to worry about how to secure it
 - Operations people at large corporations don't often like dealing with WordPress, this allows them to close the security holes and have more control over the hosting
 - Budget conscious people like free hosting (who doesn't?!?)
 - Government agencies who have strict security requirements, but have users who prefer to use WordPress
 * Thos who want to use it to archive an old WordPress website, keeping the content online, but not worrying about keeping WP up to date

This plugin produces a static HTML version of your wordpress install, incredibly useful for anyone who would like the publishing power of wordpress but whose webhost doesn't allow dynamic PHP driven sites - such as GitHub Pages. You can run your development site on a different domain or offline, and the plugin will change all relevant URLs when you publish your site. It's a simple but powerful plugin, and after hitting the publish button, the plugin will output a ZIP file of your entire site, ready to upload straight to it's new home.


= Getting started =

Here is the basic premise:

You need 2 URLs of some sort (they can be on the same server, different servers, subdomains, etc).

 - 1st URL is for where you keep WP - this doesn't need to be accessible or known to anyone but you, if you're the only one working on your content

 - 2nd URL is where you'll "publish" the static version of your site to. This is likely to be your main domain (ie, http://mywordpresssite.com).

That said, you can install the plugin and do an easy test without any other configuration. This will publish a static version to a subdirectory, such as http://mywordpresssite.com/mystatictest/. That's a good way to check the static site is publishing properly, then you can switch to another deployment option, such as FTP or GitHub Pages and deploy to your live site.

As WordPress allows infinite customization and configurations, I don't think any plugin author would be willing to guarantee complete compatibility with every theme, plugin and custom coding on every site. But my aim is to get as high a % of people as possible able to take advantage of static hosting with their WP site.

If you haven't read on why you may want to host statically, please have a read of this article:

http://docs.wp2static.com/blog/how-and-why-to-host-your-wordpress-site-for-free/


Developed by [**Leon Stafford**](http://leonstafford.github.io). If you have any questions about this plugin's usage, installation or development, please email me at: [help@wp2static.com](mailto:help@wp2static.com)

== Installation ==

= via WP Admin panel =

1. Go to Plugins > Add New
2. Search for "WP2Static"
3. Click on the Install Now button
4. Activate the plugin and find it under the Tools menu

[Watch  an installation video](https://youtu.be/kTYlohYGmBk)

= manual installation =

1. Upload the static-html-output directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the plugin settings from the "Tools" menu

= via WP CLI =

1. `wp --allow-root plugin install static-html-output-plugin --activate`


== Frequently Asked Questions ==

= How do I configure all the options? =

There's some useful information on the page once you select a deployment method. There are also some good tutorials linked at https://wp2static.com/

= Where can I publish my static site to? =

Anywhere that allows HTML files to be uploaded, ie:

 * Any FTP server
 * GitHub/GitLab/BitBucket Pages (GitHub API integration now included)
 * S3 / CloudFront
 * Netlify
 * BunnyCDN
 * Rackspace Cloud Files

= My export failed - how do I proceed? =

Everyone's WordPress hosting environment and configuration is unique, with different plugins, themes, PHP versions, to name a few. Whilst the plugin does its best to support all environments, sometimes you'll encounter a new issue. Sometimes we can adjust the settings in the plugin to overcome an issue, other times, it will require a bugfix and a new release of the plugin (usually a quick process).

When you have an issue, send the contents of your "Export Log" on the plugin screen to the developer, at [help@wp2static.com](mailto:help@wp2static.com). He'll usually respond within 12 hrs, often sooner.

== Screenshots ==

1. Deployment screen
2. Crawling options
3. Processing options
4. Advanced options
5. Help & Documentation
6. Detailed log files
7. Ways to support the plugin

== Changelog ==

= 6.5.1 =

 * Bugfix: fixes exports where homepage is 404

= 6.5 =

 * Enhancement: pagination URLs for all post types now included in initial crawl
 * Enhancement: pagination URLs for taxonomies, comments now included in initial crawl
 * Bugfix: posts and page URLs weren't all being detected

= 6.4 =

 * New feature: Debug Mode - helps diagnose any errors causing the plugin to fail
 * Enhancement: Improvements to URL rewriting engine
 * Enhancement: Set unique user-agent while crawling site
 * Bugfix: Allow uploads to S3 buckets with dots in bucket name
 * Bugfix: Detect Webpack-built themes' dist directory
 * Bugfix: Fix false-positives in deploy tests
 * Bugfix: Fix error with WP-CLI options commands

= 6.3 =

 * New feature: incremental deploys for all but Netlify (cache unchanged files to speed up deploys)
 * New feature: Post-deploy hook, allowing users to trigger their own scripts after deploy
 * New feature: Additional URLs filter, allowing users to hook into plugin from their own code to manipulate the intial crawl list
 * Enhancement: PowerPack / all deploy methods now available in the free version.
 * Enhancement: progress bars now show during all compatible deploy methods
 * Enhancement: reduced plugin size/overhead by using custom code over 3rd party libraries
 * Enhancement: simplified CloudFront invalidation process (behind the scenes)
 * Enhancement: cURL requests replace Guzzle library for less bloat (behind the scenes)
 * Bugfix: BunnyCDN cache purging working properly (Storage & Pull Zone keys required)
 * Bugfix: email notification on deploy now working for browser based deploys (previously only WP-CLI deploys would trigger it if selected)
 * Bugfix: support Unicode chars in source HTML (thanks @dimobelov!)
 * Bugfix: fix Additional URLs not being crawled
 * Bugfix: choosing 'Everything' in WP URL detection really gets everything
 * Bugfix: protocol relative URLs being properly replace in HTML files
 * Bugfix: ignore Base HREF and relative URL settings when creating offline ZIP

= 6.2 =

 * New feature: choose your WordPress URL detection level (Crawl settings)
 * New feature: diff-based deploys for GitHub (PowerPack only)
 * Enhancement: New deployment method for GitHub (PowerPack only)
 * Enhancement: Progress-bar showing long-running task % complete (partial rollout)
 * Enhancement: Sticky footer for plugin controls (first iteration, not pretty yet)
 * Enhancement: Plugin download size reduced
 * Bugfix: Non HTML/CSS files not being properly written in export (proper fix for what should have been reoslved in last version)
 * Bugfix: Fix for detected WP theme URLs being malformed
 * Dropped: No longer creating symlinks to last export directory

= 6.1.3 =

 * Bugfix: JS files not being properly written in export

= 6.1.2 =

 * Bugfix: JS files not being properly written in export

= 6.1.1 =

 * Bugfix: Cachebust to ensure latest ZIP download link
 * Bugfix: Fix issue preventing certain 3rd party deploys (PowerPack only)

= 6.1 =

 * Bugfix: Site URLs without trailing slash weren't rewritten

= 6.0 =

 * New feature: WP CLI integration
 * New feature: auto-deploy to Bitbucket
 * New feature: auto-deploy to GitLab
 * New feature: send email upon completion
 * New feature: exclude URLs from being crawled/exported
 * New feature: initial support for feeds/xml exporting
 * Enhancement: much improved export speeds via batching requests
 * Enhancement: test external deploy settings before starting export
 * Enhancement: flexible URL rewriting and directory renaming
 * Enhancement: preview initial crawl list before starting your export
 * Enhancement: support sleeping between GitHub API requests, avoid rate limits
 * Enhancement: support multiple site ID formats within Netlify deploy
 * Enhancement: remove Freemius SDK from free version
 * Enhancement: scheduled CRON deploys now in the free version
 * Enhancement: detect Elementor, copy FontAwesome to archive
 * Enhancement: easy access to export/deploy logs from Logs tab
 * Enhancement: strip WP's noindex,follow meta tag
 * Enhancement: support Netlify _headers and _redirects files
 * Enhancement: support Yoast SEO sitemaps
 * Bugfix: Support for Bedrock, Debian & non-standard WP paths
 * Bugfix: Prevent rewriting mailto: links containing domain
 * Bugfix: Prevent rewriting URLs on external domains
 * Bugfix: Offline ZIP creation now functioning properly
 * Bugfix: Fix ZIP download button not always appearing
 * Bugfix: Ignore certificate errors when crawling
 * Drop feature: diff-based deploys (now possible via WP-CLI)
 * Drop feature: deploy to Dropbox (they don't love static sites anymore)
 * Plugin name change to WP2Static

= 5.8 =

 * Bugfix: Allow activation (with warning) for PHP < 5.4 users

= 5.7 =

 * Bugfix: Allow for WPMU/network site activation
 * Bugfix: Include gallery files for NextGEN Gallery

= 5.6 =

 * Bugfix: Major bug preventing certain files being crawled has been fixed
 * Improvement: Partial support for WPMU/network site activation

= 5.5.1 =

 * Improvement: Deploy times reduced by ~ 30%, amount of data transferred by client minimized
 * Improvement: Diff-based deploys to only copy changed files (for folder, S3 and FTP deployments only)
 * Improvement: Don't block other plugin usage if ZIP extension is not available
 * Improvement: UX - 1-click same-server deployments; defaults to same-server for new installs
 * Improvement: De-cluttered UI

= 5.4.2 =

 * Bugfix: include all nested directories when building initial list to crawl

= 5.4.1 =

 * Bugfix: missing library for GitHub Pages export in free version

= 5.4 =

 * Improvement: more deployment options included (Netlify, GitHub Pages)
 * Bugfix: certain cases where inline style images are written with incorrect filenames
 * Bugfix: fix for cron-scheduled exports failing
 * Bugfix: offline copy not rewriting home URLs

= 5.3 =

 * Bugfix: subdir WP installations not exporting properly
 * Improvement: cleaner UI for first export

= 5.2 =

 * Support for latest WordPress 4.9.8
 * Plugin name change to WP Static Site Generator

= 5.1 =

 * Enhancement: improved accessibility of plugin menu

= 5.0 =

 * Major bugfixes - must update
 * fixes issues preventing deployments on certain hosting environments

= 4.4 =

 * Enhancement: More feedback on export errors with troubleshooting tips
 * Enhancement: Updated Frequently Asked Questions

= 4.3 =

 * Bugfix: fix cases where exported site is placed in site root

= 4.2 =

 * New feature: support for relative URLs with base href

= 4.1 =

 * New feature: deploy directly to a folder on the current server
 * Improvement: FTP deployments included in the free forever version
 * Bugfix: fix for rewriting escaped URLs within JavaScript for some themes
 * Bugfix: fix for subdomains being duplicated during rewriting

= 4.0 =

 * Improvement: simplified UI for easier usage
 * Improvement: livechat from within plugin for easier support (during support hours)
 * Improvement: basic auth setting available for free users
 * Improvement: ability to reset plugin to default settings
 * Bugfix: fix from crawling prematurely ending when empty files encountered

= 3.1 =

 * Bugfix: fix certain CloudFront exceptions not being caught/logged
 * Bugfix: previous exports being included in deployments in some cases
 * Bugfix: issue preventing Dropbox deployments from working
 * Bugfix: enable S3 deploys to all regions
 * Bugfix: allow crawling local/self-cert SSL sites
 * Improvement: Dropbox export done incrementally to support shared hosting environments
 * Improvement: allow setting a subfolder within your S3 bucket to deploy to
 * Improvement: minimized number of files from plugin for faster install times
 * Improvement: allow crawling basic auth protected sites

= 3.0 =

 * Bugfix: fix certain CloudFront exceptions not being caught/logged

= 2.9 =

 * Bugfix: critical fix for exported directories not being rewritten

= 2.8 =

 * Bugfix: critical fix for Dropbox, BunnyCDN and Netlify exports

= 2.7 =

 * Bugfix: Fixes major issue where a failed first export blocked subsequent ones unless page was refreshed
 * Bugfix: Plugin was not respecting the Output Directory Override

= 2.6.4 =

 * Improvement: Reduced plugin download size from 4+ MB to about 0.8MB
 * Improvement: Streamlined S3 and CloudFront export codes not to require massive AWS SDK
 * Improvement: Add check for cURL extension and add more help to system requirements page
 * Improvement: Make UI cleaner; place export button above Export Log;call to action on n exports
 * Bugfix: Remove message about deleting ZIP when none has been created

= 2.6.3 =

 * Bugfix: Reduced plugin download size and fix missing libraries needed for export

= 2.6.2 =

 * Improvement: Reduced plugin total ZIP size to allow installation for limited hosts

= 2.6.1 =

 * Bugfix: Fix CloudFront Cache Invalidation and update to latest AWS SDK V3.6.13
 * Bugfix: allow crawling sites served via SSL / HTTPS
 * Bugfix: prevent PHP warnings in error_log for unlink and renaming files - check they exist first
 * Bugfix: correctly determine WP root in filesystem
 * Improvement: allow FTP active mode, not just passive
 * Improvement: add Osaka endpoint for S3
 * Improvement: include more information in Export Log to help debug

= 2.6 =

 * Feature: Remove all traces of WordPress from your site - improve your SEO/SEM
 * Feature: Include all of your uploads folder by default - ensures all files are exported
 * Improvement: Streamlined interface for less clutter
 * Improvement: Cleanup export folder upon completion - no more filling up your uploads dir
 * Improvement: Make ZIP creation an optional step - not everyone needs to create ZIPs!
 * Bugfix: Respect custom output folder setting
 * Bugfix: Strip query strings from extracted URLs
 * Bugfix: Use base uploads dir for export folder - no more digging around for your export folder

= 2.5 =

 * Under the hood improvements, increasing stability and performance of the plugin.

= 2.4 =

 * Feature: Export to BunnyCDN - a very cheap and quick static site hosting option
 * Bugfix: Extracts relative URLs like fonts, background images, etc linked from your theme's CSS files

= 2.3 =

 * Feature: Scheduled exports via WP Crontrol
 * Bugfix: FTP export now works on shared/limited hosting
 * Bugfix: Extracts all URLs when crawling your website's HTML files
 * Bugfix: Subsequent exports correctly show realtime progress in log

= 2.2 =

 * Bugfix: GitHub export now works on shared/limited hosting
 * Feature: Realtime export progress logs

= 2.1 =

 * Bugfix: don't hang on failures
 * Bugfix: fix option to retain files on server after export
 * Feature: 1-click publishing to a Netlify static site
 * Feature: view server log on failure


= 2.0 =

Critical bug fixes and a shiny new feature!

 * Bugfix: Dropbox export once again working after they killed version 1 of their API
 * Bugfix: Amazon S3 publishing fixed after bug introduced in 1.9
 * Feature: 1-click publishing to a GitHub Pages static site

Thanks to a user donation for funding the development work to get GitHub Pages exporting added as a new feature. I was also able to merge some recently contributed code from @patrickdk77, fixing the recent issues with AWS S3 and CloudFront. Finally, I couldn't make a new release without fixing the Dropbox export functionality - unbeknowst to me, they had killed version 1 of their API in September, breaking the functionality in this plugin, along with many other apps.

= 1.9 =

 * Bugfix: Plugin now works on PHP 5.3

Though this is no longer an officially supported PHP version, many of this plugin's users are running PHP 5.3 or earlier. This fix should once again allow them to use the plugin, which has not been possible for them since about version 1.2. If you are one of these affected users, please now upgrade and enjoy all the new useful features!

= 1.8 =

 * Bugfix: improved URL rewriting

Plugin now ensures that formatted versions of your site's URL, ie //mydomain.com or http:\/\/mydomain.com\/ or the https/http equivalent are detected and rewritten to your target Base URL. The rewriting should now also work within CSS and JavaScript files.

= 1.7 =

 * Bugfix: index.html contents empty for some users' themes/setups
 * Bugfix: remove PHP short open tags for better compatibility

= 1.6 =

 * Additional URLs now work again! Much needed bugfix.

= 1.5 =

 * bugfix for Dropbox export function not exporting all files

= 1.4 =

 * add Dropbox export option
 * fix bug some users encountered with 1.3 release

= 1.3 =

 * reduce plugin download size

= 1.2.2 =

 * supports Amazon Web Service's S3 as an export option

= 1.2.1 =

 * unlimited export targets
 * desktop notifications alert you when all exports are completed (no more staring at the screen)

= 1.2.0 =

 * 1-click generation and exporting to an FTP server
 * improved user experience when saving and exporting sites (no more white screen of boredom!)

= 1.1.3 =

* Now able to choose whether to strip unneeded meta tags from generated source code.
* Improved layout for config/export screen.
* Better feedback to user when system requirements are not met

= 1.1.2 =

* Version bump for supporting latest WP (4.7)

= 1.1.1 =

Added Features

* Updated author URL

Removed Features

* Premium options for One-Click publishing to provided hosting and domain

= 1.1.0 =

Added Features

* Premium options for One-Click publishing to provided hosting and domain

= 1.0.9 =

Added Features

* Japanese localization added (ja_UTF)

= 1.0.8 =

Added Features

* long-awaited FTP transfer option integrated with basic functionality
* option to save generated static HTML files on server

= 1.0.7 =

Fixed bug introduced with previous version. Applied following modifications contributed by Brian Coca (https://github.com/bcoca):

Added Features

* zip is now written atomically (write tmp file first, then rename to zip) which now allows polling scripts to only deal with completed zip file.
* username and blog id are now part of the file name. For auditing and handling 
multi site exports.

Bug fixes

* . and .. special directory entries are now ignored
* dirname is checked before access avoiding uninitialized warning

= 1.0.6 =

Added shortcut to Settings page with Plugin Action Links

= 1.0.5 =

Added link to relevant Settings page when permalinks structure is not set.

= 1.0.4 =

Added a timeout value to URL request which was breaking for slow sites

= 1.0.3 =

Altered main codebase to fix recursion bug and endless loop. Essential upgrade. 

= 1.0.2 =

Initial release to Wordpress community

== Upgrade Notice ==

= 6.5.1 =

 * Bugfix: fixes exports where homepage is 404

= 6.5 =

 * Enhancement: pagination URLs for all post types now included in initial crawl
 * Enhancement: pagination URLs for taxonomies, comments now included in initial crawl
 * Bugfix: posts and page URLs weren't all being detected

= 6.4 =

 * New feature: Debug Mode - helps diagnose any errors causing the plugin to fail
 * Enhancement: Improvements to URL rewriting engine
 * Enhancement: Set unique user-agent while crawling site
 * Bugfix: Allow uploads to S3 buckets with dots in bucket name
 * Bugfix: Detect Webpack-built themes' dist directory
 * Bugfix: Fix false-positives in deploy tests
 * Bugfix: Fix error with WP-CLI options commands

= 6.3 =

 * New feature: incremental deploys for all but Netlify (cache unchanged files to speed up deploys)
 * New feature: Post-deploy hook, allowing users to trigger their own scripts after deploy
 * New feature: Additional URLs filter, allowing users to hook into plugin from their own code to manipulate the intial crawl list
 * Enhancement: PowerPack / all deploy methods now available in the free version.
 * Enhancement: progress bars now show during all compatible deploy methods
 * Enhancement: reduced plugin size/overhead by using custom code over 3rd party libraries
 * Enhancement: simplified CloudFront invalidation process (behind the scenes)
 * Enhancement: cURL requests replace Guzzle library for less bloat (behind the scenes)
 * Bugfix: BunnyCDN cache purging working properly (Storage & Pull Zone keys required)
 * Bugfix: email notification on deploy now working for browser based deploys (previously only WP-CLI deploys would trigger it if selected)
 * Bugfix: support Unicode chars in source HTML (thanks @dimobelov!)
 * Bugfix: fix Additional URLs not being crawled
 * Bugfix: choosing 'Everything' in WP URL detection really gets everything
 * Bugfix: protocol relative URLs being properly replace in HTML files
 * Bugfix: ignore Base HREF and relative URL settings when creating offline ZIP

= 6.2 =

 * New feature: choose your WordPress URL detection level (Crawl settings)
 * New feature: diff-based deploys for GitHub (PowerPack only)
 * Enhancement: New deployment method for GitHub (PowerPack only)
 * Enhancement: Progress-bar showing long-running task % complete (partial rollout)
 * Enhancement: Sticky footer for plugin controls (first iteration, not pretty yet)
 * Enhancement: Plugin download size reduced
 * Bugfix: Non HTML/CSS files not being properly written in export (proper fix for what should have been reoslved in last version)
 * Bugfix: Fix for detected WP theme URLs being malformed
 * Dropped: No longer creating symlinks to last export directory

= 6.1.3 =

 * Bugfix: JS files not being properly written in export

= 6.1.2 =

 * Bugfix: JS files not being properly written in export

= 6.1.1 =

 * Bugfix: Cachebust to ensure latest ZIP download link
 * Bugfix: Fix issue preventing certain 3rd party deploys (PowerPack only)

= 6.1 =

 * Bugfix: Site URLs without trailing slash weren't rewritten

= 6.0 =

 * New feature: WP CLI integration
 * New feature: auto-deploy to Bitbucket
 * New feature: auto-deploy to GitLab
 * New feature: send email upon completion
 * New feature: exclude URLs from being crawled/exported
 * New feature: initial support for feeds/xml exporting
 * Enhancement: much improved export speeds via batching requests
 * Enhancement: test external deploy settings before starting export
 * Enhancement: flexible URL rewriting and directory renaming
 * Enhancement: preview initial crawl list before starting your export
 * Enhancement: support sleeping between GitHub API requests, avoid rate limits
 * Enhancement: support multiple site ID formats within Netlify deploy
 * Enhancement: remove Freemius SDK from free version
 * Enhancement: scheduled CRON deploys now in the free version
 * Enhancement: detect Elementor, copy FontAwesome to archive
 * Enhancement: easy access to export/deploy logs from Logs tab
 * Enhancement: strip WP's noindex,follow meta tag
 * Enhancement: support Netlify _headers and _redirects files
 * Enhancement: support Yoast SEO sitemaps
 * Bugfix: Support for Bedrock, Debian & non-standard WP paths
 * Bugfix: Prevent rewriting mailto: links containing domain
 * Bugfix: Prevent rewriting URLs on external domains
 * Bugfix: Offline ZIP creation now functioning properly
 * Bugfix: Fix ZIP download button not always appearing
 * Bugfix: Ignore certificate errors when crawling
 * Drop feature: diff-based deploys (now possible via WP-CLI)
 * Drop feature: deploy to Dropbox (they don't love static sites anymore)
 * Plugin name change to WP2Static

= 5.8 =

 * Bugfix: Allow activation (with warning) for PHP < 5.4 users

= 5.7 =

 * Bugfix: Allow for WPMU/network site activation
 * Bugfix: Include gallery files for NextGEN Gallery 

= 5.6 =

 * Bugfix: Major bug preventing certain files being crawled has been fixed
 * Improvement: Partial support for WPMU/network site activation

= 5.5.1 =

 * Improvement: Deploy times reduced by ~ 30%, amount of data transferred by client minimized
 * Improvement: Diff-based deploys to only copy changed files (for folder, S3 and FTP deployments only)
 * Improvement: Don't block other plugin usage if ZIP extension is not available
 * Improvement: UX - 1-click same-server deployments; defaults to same-server for new installs
 * Improvement: De-cluttered UI

= 5.4.2 =

 * Bugfix: include all nested directories when building initial list to crawl

= 5.4.1 =

 * Bugfix: missing library for GitHub Pages export in free version

= 5.4 =

 * Improvement: more deployment options included (Netlify, GitHub Pages)
 * Bugfix: certain cases where inline style images are written with incorrect filenames
 * Bugfix: fix for cron-scheduled exports failing 
 * Bugfix: offline copy not rewriting home URLs

= 5.3 =

 * Bugfix: subdir WP installations not exporting properly
 * Improvement: cleaner UI for first export

= 5.2 =

 * Support for latest WordPress 4.9.8
 * Plugin name change to WP Static Site Generator

= 5.1 =

 * Enhancement: improved accessibility of plugin menu

= 5.0 =

 * Major bugfixes - must update
 * fixes issues preventing deployments on certain hosting environments

= 4.4 =

 * Enhancement: More feedback on export errors with troubleshooting tips
 * Enhancement: Updated Frequently Asked Questions

= 4.3 =

 * Bugfix: fix cases where exported site is placed in site root

= 4.2 =

 * New feature: support for relative URLs with base href

= 4.1 =

 * New feature: deploy directly to a folder on the current server
 * Improvement: FTP deployments included in the free forever version
 * Bugfix: fix for rewriting escaped URLs within JavaScript for some themes
 * Bugfix: fix for subdomains being duplicated during rewriting

= 4.0 =

Major upgrade recommended for all users. Adds new functionality and fixes a major bug.

 * Improvement: simplified UI for easier usage
 * Improvement: livechat from within plugin for easier support (during support hours)
 * Improvement: basic auth setting available for free users
 * Improvement: ability to reset plugin to default settings
 * Bugfix: fix from crawling prematurely ending when empty files encountered

= 3.1 =

Critical upgrade with bugfixes and improvements

 * Bugfix: fix certain CloudFront exceptions not being caught/logged
 * Bugfix: previous exports being included in deployments in some cases 
 * Bugfix: issue preventing Dropbox deployments from working
 * Bugfix: enable S3 deploys to all regions
 * Bugfix: allow crawling local/self-cert SSL sites
 * Improvement: Dropbox export done incrementally to support shared hosting environments
 * Improvement: allow setting a subfolder within your S3 bucket to deploy to
 * Improvement: minimized number of files from plugin for faster install times
 * Improvement: allow crawling basic auth protected sites

= 3.0 =

 * Bugfix: fix certain CloudFront exceptions not being caught/logged

= 2.9 =

 * Bugfix: critical fix for exported directories not being rewritten

= 2.8 =

Critical upgrade - recommended for all users. If you have troubles upgrading, please contact the developer at help@wp2static.com for assistance.

 * Bugfix: critical fix for Dropbox, BunnyCDN and Netlify exports

= 2.7 =

Critical upgrade - recommended for all users. If you have troubles upgrading, please contact the developer at help@wp2static.com for assistance.

 * Bugfix: Fixes major issue where a failed first export blocked subsequent ones unless page was refreshed
 * Bugfix: Plugin was not respecting the Output Directory Override

= 2.6.4 =

Non-critical update - get some UI and exporting improvements and a minor bug fix

 * Improvement: Reduced plugin download size from 4+ MB to about 0.8MB
 * Improvement: Streamlined S3 and CloudFront export codes not to require massive AWS SDK
 * Improvement: Add check for cURL extension and add more help to system requirements page
 * Improvement: Make UI cleaner; place export button above Export Log;call to action on n exports
 * Bugfix: Remove message about deleting ZIP when none has been created

= 2.6.3 =

Critical update - fixes issues blocking installation/export for some users.

 * Bugfix: Reduced plugin download size and fix missing libraries needed for export

= 2.6.2 =

Important fix for those users trying to upgrade to a recent version of the plugin. The reduced filesize of this version should allow installs where others where failing.

 * Improvement: Reduced plugin total ZIP size to allow installation for limited hosts

= 2.6.1 =

Minor release - fixes some minor issues discovered in V2.6, brings some improvements. Recommeneded to upgrade for increased stability with your exports and an easier time troubleshooting when something goes wrong.

 * Bugfix: Fix CloudFront Cache Invalidation and update to latest AWS SDK V3.6.13
 * Bugfix: allow crawling sites served via SSL / HTTPS
 * Bugfix: prevent PHP warnings in error_log for unlink and renaming files - check they exist first
 * Bugfix: correctly determine WP root in filesystem
 * Improvement: allow FTP active mode, not just passive
 * Improvement: add Osaka endpoint for S3
 * Improvement: include more information in Export Log to help debug

= 2.6 =

Important upgrade, bringing a killer new feature, nice improvements and important bugfixes:

 * Feature: Remove all traces of WordPress from your site - improve your SEO/SEM
 * Feature: Include all of your uploads folder by default - ensures all files are exported
 * Improvement: Streamlined interface for less clutter
 * Improvement: Cleanup export folder upon completion - no more filling up your uploads dir
 * Improvement: Make ZIP creation an optional step - not everyone needs to create ZIPs!
 * Bugfix: Respect custom output folder setting
 * Bugfix: Strip query strings from extracted URLs
 * Bugfix: Use base uploads dir for export folder - no more digging around for your export folder

= 2.5 =

 * Under the hood improvements, increasing stability and performance of the plugin.

= 2.4 =

All the important bits from the 2.3 release, plus:

 * Feature: Export to BunnyCDN - a very cheap and quick static site hosting option 
 * Bugfix: Extracts relative URLs like fonts, background images, etc linked from your theme's CSS files

= 2.3 =

Important upgrade - critical bugfixes and new features. As we hit the 100,000 alltime downloads mark, there are big things in the pipeline coming in the major 3.0 release. Get the latest 2.3 version for a marked improvement to the plugin! 

 * Feature: Scheduled exports via WP Crontrol 
 * Bugfix: FTP export now works on shared/limited hosting
 * Bugfix: Extracts all URLs when crawling your website's HTML files
 * Bugfix: Subsequent exports correctly show realtime progress in log

= 2.2 =

Important upgrade - bug fix and better error reporting. Recommended for all users.

 * Bugfix: GitHub export now works on shared/limited hosting
 * Feature: Realtime export progress logs

Recommended upgrade for all users. Exporting from shared hosting has been improved. Better ability to debug issues and get help when an export is failing.

= 2.1 =

 * Bugfix: don't hang on failures
 * Bugfix: fix option to retain files on server after export
 * Feature: 1-click publishing to a Netlify static site
 * Feature: view server log on failure

= 2.0 =

Critical bug fixes and a shiny new feature!

 * Bugfix: Dropbox export once again working after they killed version 1 of their API
 * Bugfix: Amazon S3 publishing fixed after bug introduced in 1.9
 * Feature: 1-click publishing to a GitHub Pages static site

Thanks to a user donation for funding the development work to get GitHub Pages exporting added as a new feature. I was also able to merge some recently contributed code from @patrickdk77, fixing the recent issues with AWS S3 and CloudFront. Finally, I couldn't make a new release without fixing the Dropbox export functionality - unbeknowst to me, they had killed version 1 of their API in September, breaking the functionality in this plugin, along with many other apps. 

Please contact me to report any bugs or request new features. Thanks again for your support of this plugin!

= 1.9 =

Critical update for many users~!

 * Bugfix: Plugin now works on PHP 5.3

Though this is no longer an officially supported PHP version, many of this plugin's users are running PHP 5.3 or earlier. This fix should once again allow them to use the plugin, which has not been possible for them since about version 1.2. If you are one of these affected users, please now upgrade and enjoy all the new useful features!

= 1.8 =

 * Bugfix: improved URL rewriting

Plugin now ensures that formatted versions of your site's URL, ie //mydomain.com or http:\/\/mydomain.com\/ or the https/http equivalent are detected and rewritten to your target Base URL. The rewriting should now also work within CSS and JavaScript files. 

= 1.7 =

 * Bugfix: index.html contents empty for some users' themes/setups
 * Bugfix: remove PHP short open tags for better compatibility

= 1.6 =

 * Additional URLs now work again! Much needed bugfix. Recommended upgrade.

= 1.5 =

 * bugfix for Dropbox export function not exporting all files

= 1.4 =

 * add Dropbox export option
 * fix bug some users encountered with 1.3 release

= 1.3 =

From this update on, will only do major point increases, ie 1.3, 1.4, vs 1.3.1, 1.3.2. This is due to way WP plugin directory only reports usage stats across major version numbers.

 * reduce plugin download size

= 1.2.2 =

 * supports Amazon Web Service's S3 as an export option

= 1.2.1 =

This update brings much desired multiple export targets. Please note, it will need you to enter your settings again as the guts of the plugin changed quite a bit and a settings migration didn't make the cut.

 * unlimited export targets
 * desktop notifications alert you when all exports are completed (no more staring at the screen)

= 1.2.0 =

Good to be back into developing the plugin again. This release brings some good functionality, though may be some bugs. 

 * 1-click generation and exporting to an FTP server
 * improved user experience when saving and exporting sites (no more white screen of boredom!)

= 1.1.2 =

Minor version bump after compatibility checking with latest WordPress (4.7).

= 1.1.0 =
Premium VIP subscription option added, providing static optimized hosting and a domain for your website.
