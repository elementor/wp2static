=== Simply Static ===
Contributors: codeofconduct
Tags: html, static website generator, static site, secure, fast
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create a static copy of your WordPress site that you can serve on your favorite web server.

== Description ==

Simply Static is a static site generator for WordPress that helps you create a static site that you can serve separately from your WordPress installation. This provides a couple benefits. One, this allows you to keep WordPress in a secure location that no one can access but you. Two, your static site is going to be really, _really_ fast.

= Security =

WordPress is used by [one in four websites](http://venturebeat.com/2015/11/08/wordpress-now-powers-25-of-the-web/). That makes it a prime target for hackers. There are a lot of ways that your site can be compromised, but two-thirds of all hacks are caused by [vulnerabilities in WordPress plugins, themes, and core files](https://www.wordfence.com/blog/2016/03/attackers-gain-access-wordpress-sites/).

Keeping WordPress secure requires constant vigilance. Exploits are being found for WordPress themes and plugins every day. Even WordPress itself has critical vulnerabilities from time to time. If you don’t stay on top of updates, your site *will* get hacked. It’s just a matter of when.

But what if there was an easy way to keep WordPress secure? What if you could lock WordPress away somewhere where no one can get to it but you?

With Simply Static you can put your WordPress installation in a secure location and publish a static site for the rest of the world to see. You can keep WordPress at a secret URL, protect it with .htaccess, or even put it behind a VPN. Simply Static will create static copies of all of the pages from your WordPress installation and replace the URLs to match where you’ll be hosting it.

= Performance =

Every time you visit a WordPress page it needs to perform database queries to fetch content and run PHP code to render the page. These actions take time to perform.

With Simply Static, you’re creating a static copy of all of your WordPress pages. That time to create each page is incurred once, when Simply Static runs. When someone visits your static site they can instantly receive the page because Simply Static already did the work of creating it.

Depending on the complexity of your site, theme, and plugins, using a static site can easily increase the performance of your site by 10x.

= Other Similar Plugins =

In the event that Simply Static doesn't meet your needs, give this plugin a try:

- [WP Static HTML Output](https://wordpress.org/plugins/static-html-output-plugin/)

It's updated regularly and the author is responsive to support requests.

== Installation ==

1. Log into your WordPress website.
2. On the left menu, hover over Plugins and then click on Add New.
3. In the Search Plugins box, type in "Simply Static" and press the Enter key.
4. You will see a list of search results which should include the Simply Static plugin. Click on the Install Now button to install the plugin.
5. After installing the plugin you will be prompted to activate it. Click on the Activate Plugin link.
6. The Simply Static plugin is now installed and can be found on the left menu.

or

1. Upload the entire `simply-static` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Frequently Asked Questions ==

= What does Simply Static do? =

Simply Static generates static (HTML) copies of your WordPress pages. It works a bit like a web crawler, starting at the main page of your website and looking for links to other pages to create static copies of. It also includes any images, CSS & JS files, and any other files that it can find a link to.

As Simply Static is creating the static pages, it will automatically replace the URLs for the WordPress installation with either (a) absolute URLs, at a domain of your choice or (b) relative URLs, so you can host the static files on any domain or (c) URLs constructed for offline use, so you can browse the site locally on your own computer.

= Who should use Simply Static? =

Simply Static is great for sites with no user interactivity, such as blogs (with comments disabled) or brochure-ware sites for small businesses (with no forms).

= Are there any limitations? =

Yes. Simply Static is only able to create a static copy of an *entire* site. It cannot selectively create static copies of specific pages, such as recently added posts. This means that if you have a site with 20,000 posts, and you add a new post, Simply Static will create a static copy of all 20,001 posts. This, combined with the fact that the plugin doesn't provide any kind of progress notification, means that Simply Static will provide a less-than-optimal experience for very large WordPress sites. We do plan to support very large sites eventually.

= How do I set up Simply Static? =

Let's assume you presently have WordPress hosting a site at www.example.com, and that's where you'd like to have your static site instead. Your first task is going to be to move your WordPress installation to a subdomain, such as wordpress.example.com. Once that is complete, you'll set up www.example.com to receive your static files.

If www.example.com is on the same server as your WordPress installation, you can have Simply Static copy the static files to the directory that www.example.com is serving files from. If www.example.com is on a different server, you can download a zip of your static files and then upload them to www.example.com.

= Will this plugin interfere with other plugins? =

No, Simply Static will not interfere with other plugins. In fact, Simply Static works well alongside other plugins, such as plugins to improve your site's SEO.

Simply Static creates a static copy of your website, which is just a collection of files: HTML, CSS, JS, images, etc. Any functionality that requires PHP code will not work with that static copy. That includes, but is not limited to: blog post comments, contact forms, forums, membership areas, and eCommerce.

Note that you can achieve much of this functionality by using plugins that interact with third-party services. For example, for blog post comments you could use [Disqus](https://wordpress.org/plugins/disqus-comment-system/) and for forms you could use [Wufoo](https://wordpress.org/plugins/wufoo-shortcode/).

= How is Simply Static different from cache plugins? =

Cache plugins -- such as W3 Total Cache or WP Super Cache -- make your existing WordPress site faster by caching pages as they're visited. This makes your site much faster, but still leaves your WordPress installation accessible to the outside world. Unless you keep on top of updates, your WordPress installation can become vulnerable to hackers due to security vulnerabilities that are found over time.

Simply Static creates a static copy of your WordPress site that is intended to be used completely separately from your WordPress installation. Your WordPress installation lives on one server and your static site is served on a different server. Or, they're both on the same server, but your WordPress installation is restricted to only allow access from certain ip addresses or with an additional username/password requirement. Your static site is just a collection of static files with no server-side code or database -- nothing for hackers to hack -- while your WordPress installation remains safe and secure.

= Does Simply Static work on Windows hosts? What about WAMP? =

No. We haven't done any testing on Windows and, based on user feedback, it seems like it is not working on Windows presently.

== Screenshots ==

1. This is what the Simply Static Generate page looks like after you've generated your static files. While Simply Static is running, you can view it's progress in the Activity Log. Once Simply Static has finished running, we'll show you exactly which files we made a static copy of in the Export Log.
2. The Simply Static General Settings page. With the Destination URLs option you can select how you want URLs to show up on your static site. And for the Delivery Method you have the choice of creating a ZIP archive or saving the files to a directory on the server.
3. On the Include/Exclude page, you can select additional URLs, files, or directories to include in your static site. And you can also choose URLs (or URL patterns) to exclude from your static site.

== Changelog ==

= 2.1.0, May 12, 2017 =

* New: Added the ability to set a user/pass for HTTP Basic Authentication

= 2.0.7, April 7, 2017 =

* Fix: Non-latin characters should no longer get converted to HTML entities (thanks xi80r6!)

= 2.0.6, April 4, 2017 =

* Fix: Simply Static should now display a useful error message when using PHP <5.3

= 2.0.5, March 25, 2017 =

* Fix: Fix for downloading 0-byte ZIPs (no Content-Length header)

= 2.0.4, March 25, 2017 =

* Fix: Moving the default temp files directory back to within the plugin

= 2.0.3, March 16, 2017 =

* Fix: Protocol-relative URLs now work again

= 2.0.2, January 18, 2017 =

* Fix: Individual additional files are now properly included in static file generation
* New: Added a diagnostics check to ensure that WordPress can make calls to itself

= 2.0.1, January 15, 2017 =

* Fix: Removed Simply Static's new error catching; was catching errors in other plugins

= 2.0.0, January 14, 2017 =

* New: New logo/icon (thanks Hajo!)
* New: Generate static files without having to stay on the Generate page (a job runs in the background)
* New: Added the ability to specify URLs (or parts of URLs) to exclude from static file generation
* New: Added a debugging mode with the ability to create/email a debug log
* New: Added the ability to reset the plugin settings back to their defaults
* New: Future versions will be able to downgrade to 2.0.0 (by overwriting files) without errors
* New: Friendly error message if trying to use Simply Static with PHP < 5.3
* New: Added support for Accelerated Mobile Pages (AMP) HTML attributes
* Fix: URLs containing the WP URL as a query param will rewrite the param with the new URL
* Fix: Non-WP URLs in the Additional URLs section no longer cause Simply Static to hang
* Fix: Zip creation will no longer throw errors when trying to process 0 byte files
* Fix: Fixed an issue that could cause Simply Static to not remove all files when uninstalling
* Fix: Local directories are now always chmod'd to 0755 so that they're readable
* Fix: Additional errors will be caught and displayed when generating static files

= 1.8.0, November 01, 2016 =

* Fix: XML pages (e.g. /feed/) now generate index.xml files
* Fix: XML pages now replace URLs properly
* Fix: No longer adding extra backslashes (\) to directories with backslashes
* Fix: No longer adding extra slashes (/) on user-specified additional directories

= 1.7.1, October 21, 2016 =

* New: Auto-adding wp-content/uploads as a directory to include files from
* New: Usage of the mbstring PHP extension is now optional
* New: Added links for support, rating, and changelog in the footer
* Fix: Added protection for cross-site request forgery (thanks pluginvulnerabilities.com!)
* Fix: Now sanitizing all user inputs
* Fix: Locking down generation of static files to users with proper permissions
* Fix: The check for trailing slash redirection now works with relative URLs
* Fix: "Cannot redeclare class PclZip" error should no longer occur

= 1.7.0, October 06, 2016 =

* New: Destination URLs can now begin with // (in addition to http:// & https://)
* New: You can now use relative URLs (instead of absolute URLs) for the static site
* New: Now able to export a static site for use offline

= 1.6.3, September 23, 2016 =

* Fix: Eliminated a security vulnerability in relation to zip downloads (thanks Bas!)
* Fix: SQL diagnostic checks now work with wildcard permission grants (thanks Jon!)

= 1.6.2, July 14, 2016 =

* Fix: Made a slight modification to the prior fix

= 1.6.1, July 14, 2016 =

* Fix: No more "Call to a member function find() on a non-object" error. Thanks jwatkins0101!

= 1.6.0, June 07, 2016 =

* Improvement: The DomDocument PHP extension is no longer required (replaced by SimpleHtmlDomParser)
* Fix: No longer creating empty html attributes
* Fix: No longer throwing the 'Function name must be a string' error in diagnostics. Thanks andrew-s!

= 1.5.1, June 05, 2016 =

* Fix: Removing usage of composer, which was causing issues for certain PHP versions

= 1.5.0, June 02, 2016 =

* Fix: The img srcset attribute now displays all urls (instead of only the last one)
* Improvement: Less memory usage; less likely to exhaust allowed memory size
* Improvement: Export log is now paginated / less likely to overwhelm your browser

= 1.4.1, April 28, 2016 =

* Fix: Displaying exception messages instead of a generic error message

= 1.4.0, April 26, 2016 =

* New: French translations. Thanks Pierre!
* New: Diagnostics page displays pass/fail for everything needed for SS to run correctly
* New: Auto-adding WP's emoji url to Additional URLs
* New: If a PHP error is encountered during processing it will be displayed
* Improvement: If you can edit posts, you can generate a static site
* Fix: Bad additional URLs could cause an infinite loop during static file generation
* Fix: Unprocessable pages are now properly removed on subsequent static file runs

= 1.3.5, April 14, 2016 =

* Fix: Simply Static was sometimes throwing an error on a certain WP action

= 1.3.4, April 14, 2016 =

* Fix: URLs were not getting properly replaced

= 1.3.3, April 13, 2016 =

* Fix: Attempt #3 at fixing Simply Static's upgrading process :'(

= 1.3.2, April 12, 2016 =

* Fix: Attempt #2 at fixing Simply Static's upgrading process :(

= 1.3.1, April 12, 2016 =

* Fix: Attempt #1 at fixing Simply Static's upgrading process

= 1.3.0, April 9, 2016 =

* New: Static archive creation handled via AJAX; PHP timeouts are a thing of the past
* New: Activity Log - see what Simply Static is doing while it's working

= 1.2.4, March 25, 2016 =

* Fix: Link hashes (e.g. href='#section-three') will no longer be rewritten as full URLs

= 1.2.3, March 8, 2016 =

* Fix: http and https on the same domain are treated as the same site (no redirect files will be created)
* New: Added support for the img tag's srcset attribute, used for responsive images

= 1.2.2, January 26, 2016 =

* Fix: Additional Files/Dirs now allows for usage of WP_PLUGIN_DIR and WP_CONTENT_DIR
* Fix: Able to use WordPress' Export feature with Simply Static installed

= 1.2.1, January 18, 2016 =

* Fix: index.html was not getting added to the static archive

= 1.2.0, January 16, 2016 =

* New: The export log now lists off all URLs, their http status codes, and the first page it was linked from
* New: You can specify additional files/directories to include in your static archive
* Fix: Switched libraries for ZIP generation, which should reduce extraction errors

= 1.1.3, December 7, 2015 =

* Fix: Able to fetch protocol-less URLs, e.g. href='//example.com/image.png'
* Fix: Static files won't be put in a subdirectory if your WP install is in a subdirectory
* Fix: Able to download ZIP regardless of whether files are on an accessible path on the server
* Fix: Escaped URLs, such as those used by WP's concatemoji, are now properly replaced with Destination URLs

= 1.1.2, October 22, 2015 =

* Fix: Now handling 302, 303, 307 HTTP codes for redirection in addition to 301
* Fix: Redirection now properly handles relative URLs

= 1.1.1, October 9, 2015 =

* Fix: No longer checking for valid SSL certificates on local requests (allows for self-signed certs to work)
* Fix: No longer creating redirect HTML pages for WP's 301 redirects from /path to /path/
* Fix: Improved URL extraction from CSS files, particularly from larger files

= 1.1.0, October 6, 2015 =

* New: Relative URLs in HTML and CSS files are now identified for static file creation
* New: 301 redirects are now identified and an HTML page is created to handle the redirection
* Fix: Repeatedly generating static files in a local directory should no longer throw errors
* Fix: System requirements issues should no longer continue to display an error for one pageview past when the error is resolved

= 1.0.2, October 4, 2015 =

* Fix: PHP 5.4 array initialization syntax caused errors in PHP <= 5.3

= 1.0.1, September 29, 2015 =

* Fix: Settings/options are now deleted upon uninstallation

= 1.0.0, September 21, 2015 =

* New: Initial release

== Upgrade Notice ==

= 1.7.1 =
This version fixes several minor security bugs. We recommend upgrading as soon as possible.

= 1.6.3 =
This version fixes a major security vulnerability. Please upgrade immediately.
