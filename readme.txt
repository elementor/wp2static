=== WP Static HTML Output ===
Contributors: leonstafford
Donate link: http://leonstafford.github.io
Tags: static,html,export,performance,security,portable
Requires at least: 3.2
Tested up to: 4.7
Stable tag: 1.2.0

Allows you to leverage WordPress as a great CMS, but benefit from the speed, security and portability that a static website provides.

== Description ==

## Features

 - generates a standalone, static html copy of your whole WordPress website
 - specify extra files to include in the output (ie, dynamically loaded assets)
 - 1-click static site creation and publishing to an FTP server
 - multi-language support (English/Japanese currently)

This plugin produces a static HTML version of your wordpress install, incredibly useful for anyone who would like the publishing power of wordpress but whose webhost doesn't allow dynamic PHP driven sites - such as Dropbox. You can run your development site on a different domain or offline, and the plugin will change all relevant URLs when you publish your site. It's a simple but powerful plugin, and after hitting the publish button, the plugin will output a ZIP file of your entire site, ready to upload straight to it's new home. 

Limitations:

 - The nature of a static site implies that any dynamic elements of your wordpress install that reply upon Wordpress plugins or internal functions to operate dynamically will no longer work. Significantly, this means comments. You can workaround this by including a non-Wordpress version of an external comments provider into your theme code, such as the Disqus comment system. Any page elements that rely upon Javascript will function normally. 
 - inability to correctly capture some relative links in posts
 - inability to detect assets dynamically loaded via javascript after page load, these will need to specified separately (but will work)

Planned upgrades:

 - progress meter to show % of .ZIP creation 
 - speed improvements 
 - selectively export only changed pages since last output
 - 1-click deployment your static files via sFTP, SCP, Dropbox, etc

Developed by [**Leon Stafford**](http://leonstafford.github.io). If you have any questions about this plugin's usage, installation or development, please email me at: [leon.stafford@mac.com](mailto:leon.stafford@mac.com)

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the static-html-output directory to the `/wp-content/plugins/` directory, or install via the wordpress interface "add new" or "upload" the zip file
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Access the plugin settings from the "tools" menu

== Frequently Asked Questions ==

= Where can I publish my static site to? =

Anywhere that allows HTML files to be uploaded, ie:

 - GitHub/GitLab/BitBucket Pages
 - S3 / CloudFront
 - Dropbox

= My comments don't work anymore! = 

See the readme. In brief: you can't use dynamic WordPress functions such as comments on a static site. Use an external comments provider such as Disqus, or live without them.

== Screenshots ==

1. The main interface
2. The main interface (Japanese)

== Changelog ==

= 1.2.0 =

Good to be back into developing the plugin again. This release brings some good functionality, though may be some bugs. If so, please contact me to fix: [mailto:lionhive@gmail.com](lionhive@gmail.com) - Cheers, Leon

 - 1-click generation and exporting to an FTP server
 - improved user experience when saving and exporting sites (no more white screen of boredom!)

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

= 1.1.2 =

Minor version bump after compatibility checking with latest WordPress (4.7).

= 1.1.0 =
Premium VIP subscription option added, providing static optimized hosting and a domain for your website.
