=== WP Static HTML Output ===
Contributors: leonstafford
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NHEV6WLYJ6QWQ
Tags: static,html,export,performance,security,portable
Requires at least: 3.2
Tested up to: 4.9.1
Stable tag: 2.2

Allows you to leverage WordPress as a great CMS, but benefit from the speed, security and portability that a static website provides.

== Description ==

= Features =

 * generates a standalone, static html copy of your whole WordPress website
 * auto-deploy to local folder, FTP, S3, Dropbox or GitHub
 * one site to unlimited export targets
 * specify extra files to include in the output (ie, dynamically loaded assets)
 * desktop notifications alert you to when exports are complete
 * multi-language support (English/Japanese currently)

This plugin produces a static HTML version of your wordpress install, incredibly useful for anyone who would like the publishing power of wordpress but whose webhost doesn't allow dynamic PHP driven sites - such as Dropbox. You can run your development site on a different domain or offline, and the plugin will change all relevant URLs when you publish your site. It's a simple but powerful plugin, and after hitting the publish button, the plugin will output a ZIP file of your entire site, ready to upload straight to it's new home. 

= Limitations =

 * The nature of a static site implies that any dynamic elements of your wordpress install that reply upon Wordpress plugins or internal functions to operate dynamically will no longer work. Significantly, this means comments. You can workaround this by including a non-Wordpress version of an external comments provider into your theme code, such as the Disqus comment system. Any page elements that rely upon Javascript will function normally. 
 * inability to correctly capture some relative links in posts
 * inability to detect assets dynamically loaded via javascript after page load, these will need to specified separately (but will work)

= Similar plugins =

Having issues with this plugin? I try to support any issues via the official support forum or email, but if you want to try some other plugins for static export, give these a go:

 * [Simply Static](https://wordpress.org/plugins/simply-static/)

= Planned upgrades =

 * re-write export to relative URLs
 * progress meter to show % of .ZIP creation
 * realtime logs visible during / saved after export
 * speed improvements for large sites
 * selectively export only changed pages since last output
 * increase 1-click deployment options

Developed by [**Leon Stafford**](http://leonstafford.github.io). If you have any questions about this plugin's usage, installation or development, please email me at: [leon.stafford@mac.com](mailto:leon.stafford@mac.com)

== Installation ==

= via WP Admin panel =

1. Go to Plugins > Add New
2. Search for "WP Static HTML Output"
3. Click on the Install Now button
4. Activate the plugin and find it under the Tools menu

= manual installation =

1. Upload the static-html-output directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the plugin settings from the "Tools" menu

= via WP CLI =

1. `wp --allow-root plugin install static-html-output-plugin --activate`


== Frequently Asked Questions ==

= Where can I publish my static site to? =

Anywhere that allows HTML files to be uploaded, ie:

 * GitHub/GitLab/BitBucket Pages (GitHub API integration now included)
 * S3 / CloudFront
 * Dropbox

= My comments don't work anymore! = 

See the readme. In brief: you can't use dynamic WordPress functions such as comments on a static site. Use an external comments provider such as Disqus, or live without them.

== Screenshots ==

1. The main interface
2. The main interface (Japanese)

== Changelog ==

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

Good to be back into developing the plugin again. This release brings some good functionality, though may be some bugs. If so, please contact me to fix: lionhive@gmail.com Cheers, Leon

 * 1-click generation and exporting to an FTP server
 * improved user experience when saving and exporting sites (no more white screen of boredom!)

= 1.1.2 =

Minor version bump after compatibility checking with latest WordPress (4.7).

= 1.1.0 =
Premium VIP subscription option added, providing static optimized hosting and a domain for your website.
