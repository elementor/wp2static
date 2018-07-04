<?php

declare(strict_types=1);

require_once 'library/StaticHtmlOutput/UrlRequest.php';

use PHPUnit\Framework\TestCase;

final class StaticHtmlOutput_UrlRequestTest extends TestCase {
	public function setUp() {
		if (!function_exists('apply_filters')) {
			// dummy up the apply_filters() func
			function apply_filters($tag, $value) {
				return true;
			}

			// dummy up the wp_remote_get call 
			function wp_remote_get($url, $someArray) {
				return true;
			}

			// dummy up the is_wp_error call 
			function is_wp_error($response) {
				return false;
			}

			function esc_attr($some_string) {
				// TODO: replicate esc_attr here
				return $some_string;
			}
		}
	}

    public function testGetUrlIsPrettyUseless(): void {
		$url = 'http://google.com';
		$basicAuth = null;

		// create a new instance
        $urlResponse = new StaticHtmlOutput_UrlRequest($url, $basicAuth);

		// call the _getURL method
		// assert it returns the url
        $this->assertEquals(
            'http://google.com',
            $urlResponse->getURL()
        );
    }

    public function testWordpressTopleveldomainExportingToTopleveldomain(): void {
		$wpURL = 'http://example.com';
		$baseURL = 'http://google.com';

		$url = 'http://someurl.com';	
		$basicAuth = null;

		// mock out only the unrelated methods
		$mockUrlResponse = $this->getMockBuilder('StaticHtmlOutput_UrlRequest')
			->setMethods([
				'isRewritable',
				'getResponseBody',
				'setResponseBody'
			])
			->setConstructorArgs([$url, $basicAuth])
			->getMock();


		$mockUrlResponse->method('isRewritable')
             ->willReturn(true);

		// mock getResponseBody with testable HTML content
		$mockUrlResponse->method('getResponseBody')
             ->willReturn('<html><head></head><body>Something with a <a href="http://example.com">link</a>.</body></html>');


		$mockUrlResponse->expects($this->once())
			 ->method('isRewritable') ;

		$mockUrlResponse->expects($this->once())
			 ->method('getResponseBody') ;

		// assert that setResponseBody() is called with the correctly rewritten HTML
		$mockUrlResponse->expects($this->once())
			->method('setResponseBody')
			->with('<html><head>
<base href="http://google.com" />
</head><body>Something with a <a href="http://google.com">link</a>.</body></html>') ;

		$mockUrlResponse->replaceBaseUrl($wpURL, $baseURL);
    }

    public function testWordpressTopleveldomainExportingToSubdomain(): void {
		$wpURL = 'http://example.com';
		$baseURL = 'http://subdomain.google.com';

		$url = 'http://someurl.com';	
		$basicAuth = null;

		// mock out only the unrelated methods
		$mockUrlResponse = $this->getMockBuilder('StaticHtmlOutput_UrlRequest')
			->setMethods([
				'isRewritable',
				'getResponseBody',
				'setResponseBody'
			])
			->setConstructorArgs([$url, $basicAuth])
			->getMock();


		$mockUrlResponse->method('isRewritable')
             ->willReturn(true);

		// mock getResponseBody with testable HTML content
		$mockUrlResponse->method('getResponseBody')
             ->willReturn('<html><head></head><body>Something with a <a href="http://example.com">link</a>.</body></html>');


		$mockUrlResponse->expects($this->once())
			 ->method('isRewritable') ;

		$mockUrlResponse->expects($this->once())
			 ->method('getResponseBody') ;

		// assert that setResponseBody() is called with the correctly rewritten HTML
		$mockUrlResponse->expects($this->once())
			->method('setResponseBody')
			->with('<html><head>
<base href="http://subdomain.google.com" />
</head><body>Something with a <a href="http://subdomain.google.com">link</a>.</body></html>') ;

		$mockUrlResponse->replaceBaseUrl($wpURL, $baseURL);
    }

    public function testWordpressSubdomainExportingToTopleveldomain(): void {
		$wpURL = 'http://mysite.example.com';
		$baseURL = 'http://google.com';

		$url = 'http://someurl.com';	
		$basicAuth = null;

		// mock out only the unrelated methods
		$mockUrlResponse = $this->getMockBuilder('StaticHtmlOutput_UrlRequest')
			->setMethods([
				'isRewritable',
				'getResponseBody',
				'setResponseBody'
			])
			->setConstructorArgs([$url, $basicAuth])
			->getMock();


		$mockUrlResponse->method('isRewritable')
             ->willReturn(true);

		// mock getResponseBody with testable HTML content
		$mockUrlResponse->method('getResponseBody')
             ->willReturn('<html><head></head><body>Something with a <a href="http://mysite.example.com">link</a>.</body></html>');


		$mockUrlResponse->expects($this->once())
			 ->method('isRewritable') ;

		$mockUrlResponse->expects($this->once())
			 ->method('getResponseBody') ;

		// assert that setResponseBody() is called with the correctly rewritten HTML
		$mockUrlResponse->expects($this->once())
			->method('setResponseBody')
			->with('<html><head>
<base href="http://google.com" />
</head><body>Something with a <a href="http://google.com">link</a>.</body></html>') ;

		$mockUrlResponse->replaceBaseUrl($wpURL, $baseURL);
    }

    public function testWordpressSubdomainExportingToAnotherSubdomain(): void {
		$wpURL = 'http://mysite.example.com';
		$baseURL = 'http://subdomain.google.com';

		$url = 'http://someurl.com';	
		$basicAuth = null;

		// mock out only the unrelated methods
		$mockUrlResponse = $this->getMockBuilder('StaticHtmlOutput_UrlRequest')
			->setMethods([
				'isRewritable',
				'getResponseBody',
				'setResponseBody'
			])
			->setConstructorArgs([$url, $basicAuth])
			->getMock();


		$mockUrlResponse->method('isRewritable')
             ->willReturn(true);

		// mock getResponseBody with testable HTML content
		$mockUrlResponse->method('getResponseBody')
             ->willReturn('<html><head></head><body>Something with a <a href="http://mysite.example.com">link</a>.</body></html>');


		$mockUrlResponse->expects($this->once())
			 ->method('isRewritable') ;

		$mockUrlResponse->expects($this->once())
			 ->method('getResponseBody') ;

		// assert that setResponseBody() is called with the correctly rewritten HTML
		$mockUrlResponse->expects($this->once())
			->method('setResponseBody')
			->with('<html><head>
<base href="http://subdomain.google.com" />
</head><body>Something with a <a href="http://subdomain.google.com">link</a>.</body></html>') ;

		$mockUrlResponse->replaceBaseUrl($wpURL, $baseURL);
    }

    public function testStripsWordpressIdentifiersFromHtmlFile(): void {
		$this->markTestSkipped('Test is a WIP');

		$wpURL = 'http://mysite.example.com';
		$baseURL = 'http://subdomain.google.com';

		$url = 'http://someurl.com';	
		$basicAuth = null;

		// mock out only the unrelated methods
		$mockUrlResponse = $this->getMockBuilder('StaticHtmlOutput_UrlRequest')
			->setMethods([
				'isHtml',
				'isCSS',
				'getResponseBody',
				'setResponseBody'
			])
			->setConstructorArgs([$url, $basicAuth])
			->getMock();

		// simulate a HTML file being detected
		$mockUrlResponse->method('isHtml')
             ->willReturn(true);

		$mockUrlResponse->method('isCSS')
             ->willReturn(false);

$twenty_seventeen_home = <<<EOHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">

<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>
<title>wp plugindev &#8211; Just another WordPress site</title>
<link rel='dns-prefetch' href='//fonts.googleapis.com' />
<link rel='dns-prefetch' href='//s.w.org' />
<link href='https://fonts.gstatic.com' crossorigin rel='preconnect' />
<link rel="alternate" type="application/rss+xml" title="wp plugindev &raquo; Feed" href="http://172.17.0.3/banana/index.php/feed/" />
<link rel="alternate" type="application/rss+xml" title="wp plugindev &raquo; Comments Feed" href="http://172.17.0.3/banana/index.php/comments/feed/" />
		<script type="text/javascript">
			window._wpemojiSettings = {"baseUrl":"https:\/\/s.w.org\/images\/core\/emoji\/2.4\/72x72\/","ext":".png","svgUrl":"https:\/\/s.w.org\/images\/core\/emoji\/2.4\/svg\/","svgExt":".svg","source":{"concatemoji":"http:\/\/172.17.0.3\/banana\/wp-includes\/js\/wp-emoji-release.min.js?ver=4.9.6"}};
			!function(a,b,c){function d(a,b){var c=String.fromCharCode;l.clearRect(0,0,k.width,k.height),l.fillText(c.apply(this,a),0,0);var d=k.toDataURL();l.clearRect(0,0,k.width,k.height),l.fillText(c.apply(this,b),0,0);var e=k.toDataURL();return d===e}function e(a){var b;if(!l||!l.fillText)return!1;switch(l.textBaseline="top",l.font="600 32px Arial",a){case"flag":return!(b=d([55356,56826,55356,56819],[55356,56826,8203,55356,56819]))&&(b=d([55356,57332,56128,56423,56128,56418,56128,56421,56128,56430,56128,56423,56128,56447],[55356,57332,8203,56128,56423,8203,56128,56418,8203,56128,56421,8203,56128,56430,8203,56128,56423,8203,56128,56447]),!b);case"emoji":return b=d([55357,56692,8205,9792,65039],[55357,56692,8203,9792,65039]),!b}return!1}function f(a){var c=b.createElement("script");c.src=a,c.defer=c.type="text/javascript",b.getElementsByTagName("head")[0].appendChild(c)}var g,h,i,j,k=b.createElement("canvas"),l=k.getContext&&k.getContext("2d");for(j=Array("flag","emoji"),c.supports={everything:!0,everythingExceptFlag:!0},i=0;i<j.length;i++)c.supports[j[i]]=e(j[i]),c.supports.everything=c.supports.everything&&c.supports[j[i]],"flag"!==j[i]&&(c.supports.everythingExceptFlag=c.supports.everythingExceptFlag&&c.supports[j[i]]);c.supports.everythingExceptFlag=c.supports.everythingExceptFlag&&!c.supports.flag,c.DOMReady=!1,c.readyCallback=function(){c.DOMReady=!0},c.supports.everything||(h=function(){c.readyCallback()},b.addEventListener?(b.addEventListener("DOMContentLoaded",h,!1),a.addEventListener("load",h,!1)):(a.attachEvent("onload",h),b.attachEvent("onreadystatechange",function(){"complete"===b.readyState&&c.readyCallback()})),g=c.source||{},g.concatemoji?f(g.concatemoji):g.wpemoji&&g.twemoji&&(f(g.twemoji),f(g.wpemoji)))}(window,document,window._wpemojiSettings);
		</script>
		<style type="text/css">
img.wp-smiley,
img.emoji {
	display: inline !important;
	border: none !important;
	box-shadow: none !important;
	height: 1em !important;
	width: 1em !important;
	margin: 0 .07em !important;
	vertical-align: -0.1em !important;
	background: none !important;
	padding: 0 !important;
}
</style>
<link rel='stylesheet' id='twentyseventeen-fonts-css'  href='https://fonts.googleapis.com/css?family=Libre+Franklin%3A300%2C300i%2C400%2C400i%2C600%2C600i%2C800%2C800i&#038;subset=latin%2Clatin-ext' type='text/css' media='all' />
<link rel='stylesheet' id='twentyseventeen-style-css'  href='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/style.css?ver=4.9.6' type='text/css' media='all' />
<!--[if lt IE 9]>
<link rel='stylesheet' id='twentyseventeen-ie8-css'  href='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/css/ie8.css?ver=1.0' type='text/css' media='all' />
<![endif]-->
<!--[if lt IE 9]>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/js/html5.js?ver=3.7.3'></script>
<![endif]-->
<script type='text/javascript' src='http://172.17.0.3/banana/wp-includes/js/jquery/jquery.js?ver=1.12.4'></script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-includes/js/jquery/jquery-migrate.min.js?ver=1.4.1'></script>
<link rel='https://api.w.org/' href='http://172.17.0.3/banana/index.php/wp-json/' />
<link rel="EditURI" type="application/rsd+xml" title="RSD" href="http://172.17.0.3/banana/xmlrpc.php?rsd" />
<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="http://172.17.0.3/banana/wp-includes/wlwmanifest.xml" /> 
<meta name="generator" content="WordPress 4.9.6" />
		<style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
		</head>

<body class="home blog logged-in hfeed has-header-image has-sidebar colors-light">
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content">Skip to content</a>

	<header id="masthead" class="site-header" role="banner">

		<div class="custom-header">

		<div class="custom-header-media">
			<div id="wp-custom-header" class="wp-custom-header"><img src="http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/images/header.jpg" width="2000" height="1200" alt="wp plugindev" /></div>		</div>

	<div class="site-branding">
	<div class="wrap">

		
		<div class="site-branding-text">
							<h1 class="site-title"><a href="http://172.17.0.3/banana/" rel="home">wp plugindev</a></h1>
			
							<p class="site-description">Just another WordPress site</p>
					</div><!-- .site-branding-text -->

				<a href="#content" class="menu-scroll-down"><svg class="icon icon-arrow-right" aria-hidden="true" role="img"> <use href="#icon-arrow-right" xlink:href="#icon-arrow-right"></use> </svg><span class="screen-reader-text">Scroll down to content</span></a>
	
	</div><!-- .wrap -->
</div><!-- .site-branding -->

</div><!-- .custom-header -->

		
	</header><!-- #masthead -->

	
	<div class="site-content-contain">
		<div id="content" class="site-content">

<div class="wrap">
		<header class="page-header">
		<h2 class="page-title">Posts</h2>
	</header>
	
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			
<article id="post-9" class="post-9 post type-post status-publish format-standard hentry category-uncategorized">
		<header class="entry-header">
		<div class="entry-meta"><span class="screen-reader-text">Posted on</span> <a href="http://172.17.0.3/banana/index.php/2018/05/11/scheduling-exports-of-your-static-site-from-wordpress/" rel="bookmark"><time class="entry-date published updated" datetime="2018-05-11T14:27:47+00:00">May 11, 2018</time></a><span class="edit-link"><a class="post-edit-link" href="http://172.17.0.3/banana/wp-admin/post.php?post=9&#038;action=edit">Edit<span class="screen-reader-text"> "Scheduling exports of your static site from WordPress"</span></a></span></div><!-- .entry-meta --><h3 class="entry-title"><a href="http://172.17.0.3/banana/index.php/2018/05/11/scheduling-exports-of-your-static-site-from-wordpress/" rel="bookmark">Scheduling exports of your static site from WordPress</a></h3>	</header><!-- .entry-header -->

	
	<div class="entry-content">
		<p>You can use the <a href="https://wordpress.org/plugins/wp-crontrol/">WP Crontrol</a> plugin to schedule the export of your static site.</p>
<p>This feature is yet to be released into the official version of <a href="http://wordpress.org/plugins/static-html-output-plugin/">WP Static HTML Output Plugin</a>, but you can try it out by downloading the latest development version of the plugin from the <a href="http://github.com/leonstafford/wordpress-static-html-plugin">GitHub repository</a>.</p>
<h2>Scheduling your WordPress site to be exported as a static HTML site</h2>
<ol>
<li>Install the <a href="https://wordpress.org/plugins/wp-crontrol/">WP Crontrol</a> plugin.</li>
<li>Go to your Cron Schedules via Settings &gt; Cron Schedules<br />
<a href="http://172.17.0.3/wp-content/uploads/go_to_cron_schedules.png"><img class="alignnone wp-image-11 size-full" src="http://172.17.0.3/wp-content/uploads/go_to_cron_schedules.png" alt="" width="406" height="375" /></a></li>
<li>Add a new Cron Schedule, using WP Static HTML Output&#8217;s custom hook, setting the following options:
<ol>
<li><strong> Internal name:</strong> <em>wp_static_html_output_server_side_export_hook</em></li>
<li><strong>Interval (seconds):</strong> ie <em>3600</em> to run your export every hour, <em>86400</em> for daily exports, etc</li>
<li><strong>Display name:</strong> This can be whatever you like. Something that makes sense to you is recommended, ie &#8220;<em>Static site export to S3 once a day</em>&#8221; <a style="font-size: 1rem;" href="http://172.17.0.3/wp-content/uploads/wpcrontrol_add_scheduled_task.png"><img class="alignnone wp-image-8" src="http://172.17.0.3/wp-content/uploads/wpcrontrol_add_scheduled_task-1024x545.png" alt="" width="800" height="426" /></a></li>
</ol>
</li>
<li>Click on Add Cron Schedule to save your scheduled task. It should now appear in your list of tasks, as below: <a style="font-size: 1rem;" href="http://172.17.0.3/wp-content/uploads/wp_crontrol_added_task.png"><img class="alignnone wp-image-7" src="http://172.17.0.3/wp-content/uploads/wp_crontrol_added_task-1024x490.png" alt="" width="800" height="383" /></a></li>
</ol>
<p>&nbsp;</p>
	</div><!-- .entry-content -->

	
</article><!-- #post-## -->

<article id="post-8" class="post-8 post type-post status-publish format-standard hentry category-uncategorized">
		<header class="entry-header">
		<div class="entry-meta"><span class="screen-reader-text">Posted on</span> <a href="http://172.17.0.3/banana/index.php/2018/04/28/removing-comments-for-a-wordpress-static-website/" rel="bookmark"><time class="entry-date published updated" datetime="2018-04-28T08:27:26+00:00">April 28, 2018</time></a><span class="edit-link"><a class="post-edit-link" href="http://172.17.0.3/banana/wp-admin/post.php?post=8&#038;action=edit">Edit<span class="screen-reader-text"> "Removing comments for a WordPress static website"</span></a></span></div><!-- .entry-meta --><h3 class="entry-title"><a href="http://172.17.0.3/banana/index.php/2018/04/28/removing-comments-for-a-wordpress-static-website/" rel="bookmark">Removing comments for a WordPress static website</a></h3>	</header><!-- .entry-header -->

	
	<div class="entry-content">
		<p>This is demo content</p>
	</div><!-- .entry-content -->

	
</article><!-- #post-## -->

		</main><!-- #main -->
	</div><!-- #primary -->
	
<aside id="secondary" class="widget-area" role="complementary" aria-label="Blog Sidebar">
	<section id="search-2" class="widget widget_search">

<form role="search" method="get" class="search-form" action="http://172.17.0.3/banana/">
	<label for="search-form-5b3ccb9931bc6">
		<span class="screen-reader-text">Search for:</span>
	</label>
	<input type="search" id="search-form-5b3ccb9931bc6" class="search-field" placeholder="Search &hellip;" value="" name="s" />
	<button type="submit" class="search-submit"><svg class="icon icon-search" aria-hidden="true" role="img"> <use href="#icon-search" xlink:href="#icon-search"></use> </svg><span class="screen-reader-text">Search</span></button>
</form>
</section>		<section id="recent-posts-2" class="widget widget_recent_entries">		<h2 class="widget-title">Recent Posts</h2>		<ul>
											<li>
					<a href="http://172.17.0.3/banana/index.php/2018/05/11/scheduling-exports-of-your-static-site-from-wordpress/">Scheduling exports of your static site from WordPress</a>
									</li>
											<li>
					<a href="http://172.17.0.3/banana/index.php/2018/04/28/removing-comments-for-a-wordpress-static-website/">Removing comments for a WordPress static website</a>
									</li>
					</ul>
		</section><section id="recent-comments-2" class="widget widget_recent_comments"><h2 class="widget-title">Recent Comments</h2><ul id="recentcomments"></ul></section><section id="archives-2" class="widget widget_archive"><h2 class="widget-title">Archives</h2>		<ul>
			<li><a href='http://172.17.0.3/banana/index.php/2018/05/'>May 2018</a></li>
	<li><a href='http://172.17.0.3/banana/index.php/2018/04/'>April 2018</a></li>
		</ul>
		</section><section id="categories-2" class="widget widget_categories"><h2 class="widget-title">Categories</h2>		<ul>
	<li class="cat-item cat-item-1"><a href="http://172.17.0.3/banana/index.php/category/uncategorized/" >Uncategorized</a>
</li>
		</ul>
</section><section id="meta-2" class="widget widget_meta"><h2 class="widget-title">Meta</h2>			<ul>
			<li><a href="http://172.17.0.3/banana/wp-admin/">Site Admin</a></li>			<li><a href="http://172.17.0.3/banana/wp-login.php?action=logout&#038;_wpnonce=292745da4e">Log out</a></li>
			<li><a href="http://172.17.0.3/banana/index.php/feed/">Entries <abbr title="Really Simple Syndication">RSS</abbr></a></li>
			<li><a href="http://172.17.0.3/banana/index.php/comments/feed/">Comments <abbr title="Really Simple Syndication">RSS</abbr></a></li>
			<li><a href="https://wordpress.org/" title="Powered by WordPress, state-of-the-art semantic personal publishing platform.">WordPress.org</a></li>			</ul>
			</section></aside><!-- #secondary -->
</div><!-- .wrap -->


		</div><!-- #content -->

		<footer id="colophon" class="site-footer" role="contentinfo">
			<div class="wrap">
				
<div class="site-info">
		<a href="https://wordpress.org/" class="imprint">
		Proudly powered by WordPress	</a>
</div><!-- .site-info -->
			</div><!-- .wrap -->
		</footer><!-- #colophon -->
	</div><!-- .site-content-contain -->
</div><!-- #page -->
<script type='text/javascript'>
/* <![CDATA[ */
var twentyseventeenScreenReaderText = {"quote":"<svg class=\"icon icon-quote-right\" aria-hidden=\"true\" role=\"img\"> <use href=\"#icon-quote-right\" xlink:href=\"#icon-quote-right\"><\/use> <\/svg>"};
/* ]]> */
</script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/js/skip-link-focus-fix.js?ver=1.0'></script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/js/global.js?ver=1.0'></script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/js/jquery.scrollTo.js?ver=2.1.2'></script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-includes/js/wp-embed.min.js?ver=4.9.6'></script>

</body>
</html>
EOHTML;

$twenty_seventeen_home_expected_rewrite = <<<EOHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">

<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>
<title>wp plugindev &#8211; Just another WordPress site</title>
<link rel='dns-prefetch' href='//fonts.googleapis.com' />
<link rel='dns-prefetch' href='//s.w.org' />
<link href='https://fonts.gstatic.com' crossorigin rel='preconnect' />
<link rel="alternate" type="application/rss+xml" title="wp plugindev &raquo; Feed" href="http://172.17.0.3/banana/index.php/feed/" />
<link rel="alternate" type="application/rss+xml" title="wp plugindev &raquo; Comments Feed" href="http://172.17.0.3/banana/index.php/comments/feed/" />
		<script type="text/javascript">
			window._wpemojiSettings = {"baseUrl":"https:\/\/s.w.org\/images\/core\/emoji\/2.4\/72x72\/","ext":".png","svgUrl":"https:\/\/s.w.org\/images\/core\/emoji\/2.4\/svg\/","svgExt":".svg","source":{"concatemoji":"http:\/\/172.17.0.3\/banana\/wp-includes\/js\/wp-emoji-release.min.js?ver=4.9.6"}};
			!function(a,b,c){function d(a,b){var c=String.fromCharCode;l.clearRect(0,0,k.width,k.height),l.fillText(c.apply(this,a),0,0);var d=k.toDataURL();l.clearRect(0,0,k.width,k.height),l.fillText(c.apply(this,b),0,0);var e=k.toDataURL();return d===e}function e(a){var b;if(!l||!l.fillText)return!1;switch(l.textBaseline="top",l.font="600 32px Arial",a){case"flag":return!(b=d([55356,56826,55356,56819],[55356,56826,8203,55356,56819]))&&(b=d([55356,57332,56128,56423,56128,56418,56128,56421,56128,56430,56128,56423,56128,56447],[55356,57332,8203,56128,56423,8203,56128,56418,8203,56128,56421,8203,56128,56430,8203,56128,56423,8203,56128,56447]),!b);case"emoji":return b=d([55357,56692,8205,9792,65039],[55357,56692,8203,9792,65039]),!b}return!1}function f(a){var c=b.createElement("script");c.src=a,c.defer=c.type="text/javascript",b.getElementsByTagName("head")[0].appendChild(c)}var g,h,i,j,k=b.createElement("canvas"),l=k.getContext&&k.getContext("2d");for(j=Array("flag","emoji"),c.supports={everything:!0,everythingExceptFlag:!0},i=0;i<j.length;i++)c.supports[j[i]]=e(j[i]),c.supports.everything=c.supports.everything&&c.supports[j[i]],"flag"!==j[i]&&(c.supports.everythingExceptFlag=c.supports.everythingExceptFlag&&c.supports[j[i]]);c.supports.everythingExceptFlag=c.supports.everythingExceptFlag&&!c.supports.flag,c.DOMReady=!1,c.readyCallback=function(){c.DOMReady=!0},c.supports.everything||(h=function(){c.readyCallback()},b.addEventListener?(b.addEventListener("DOMContentLoaded",h,!1),a.addEventListener("load",h,!1)):(a.attachEvent("onload",h),b.attachEvent("onreadystatechange",function(){"complete"===b.readyState&&c.readyCallback()})),g=c.source||{},g.concatemoji?f(g.concatemoji):g.wpemoji&&g.twemoji&&(f(g.twemoji),f(g.wpemoji)))}(window,document,window._wpemojiSettings);
		</script>
		<style type="text/css">
img.wp-smiley,
img.emoji {
	display: inline !important;
	border: none !important;
	box-shadow: none !important;
	height: 1em !important;
	width: 1em !important;
	margin: 0 .07em !important;
	vertical-align: -0.1em !important;
	background: none !important;
	padding: 0 !important;
}
</style>
<link rel='stylesheet' id='twentyseventeen-fonts-css'  href='https://fonts.googleapis.com/css?family=Libre+Franklin%3A300%2C300i%2C400%2C400i%2C600%2C600i%2C800%2C800i&#038;subset=latin%2Clatin-ext' type='text/css' media='all' />
<link rel='stylesheet' id='twentyseventeen-style-css'  href='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/style.css?ver=4.9.6' type='text/css' media='all' />
<!--[if lt IE 9]>
<link rel='stylesheet' id='twentyseventeen-ie8-css'  href='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/css/ie8.css?ver=1.0' type='text/css' media='all' />
<![endif]-->
<!--[if lt IE 9]>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/js/html5.js?ver=3.7.3'></script>
<![endif]-->
<script type='text/javascript' src='http://172.17.0.3/banana/wp-includes/js/jquery/jquery.js?ver=1.12.4'></script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-includes/js/jquery/jquery-migrate.min.js?ver=1.4.1'></script>
<link rel='https://api.w.org/' href='http://172.17.0.3/banana/index.php/wp-json/' />
<link rel="EditURI" type="application/rsd+xml" title="RSD" href="http://172.17.0.3/banana/xmlrpc.php?rsd" />
<link rel="wlwmanifest" type="application/wlwmanifest+xml" href="http://172.17.0.3/banana/wp-includes/wlwmanifest.xml" /> 
<meta name="generator" content="WordPress 4.9.6" />
		<style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
		</head>

<body class="home blog logged-in hfeed has-header-image has-sidebar colors-light">
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#content">Skip to content</a>

	<header id="masthead" class="site-header" role="banner">

		<div class="custom-header">

		<div class="custom-header-media">
			<div id="wp-custom-header" class="wp-custom-header"><img src="http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/images/header.jpg" width="2000" height="1200" alt="wp plugindev" /></div>		</div>

	<div class="site-branding">
	<div class="wrap">

		
		<div class="site-branding-text">
							<h1 class="site-title"><a href="http://172.17.0.3/banana/" rel="home">wp plugindev</a></h1>
			
							<p class="site-description">Just another WordPress site</p>
					</div><!-- .site-branding-text -->

				<a href="#content" class="menu-scroll-down"><svg class="icon icon-arrow-right" aria-hidden="true" role="img"> <use href="#icon-arrow-right" xlink:href="#icon-arrow-right"></use> </svg><span class="screen-reader-text">Scroll down to content</span></a>
	
	</div><!-- .wrap -->
</div><!-- .site-branding -->

</div><!-- .custom-header -->

		
	</header><!-- #masthead -->

	
	<div class="site-content-contain">
		<div id="content" class="site-content">

<div class="wrap">
		<header class="page-header">
		<h2 class="page-title">Posts</h2>
	</header>
	
	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			
<article id="post-9" class="post-9 post type-post status-publish format-standard hentry category-uncategorized">
		<header class="entry-header">
		<div class="entry-meta"><span class="screen-reader-text">Posted on</span> <a href="http://172.17.0.3/banana/index.php/2018/05/11/scheduling-exports-of-your-static-site-from-wordpress/" rel="bookmark"><time class="entry-date published updated" datetime="2018-05-11T14:27:47+00:00">May 11, 2018</time></a><span class="edit-link"><a class="post-edit-link" href="http://172.17.0.3/banana/wp-admin/post.php?post=9&#038;action=edit">Edit<span class="screen-reader-text"> "Scheduling exports of your static site from WordPress"</span></a></span></div><!-- .entry-meta --><h3 class="entry-title"><a href="http://172.17.0.3/banana/index.php/2018/05/11/scheduling-exports-of-your-static-site-from-wordpress/" rel="bookmark">Scheduling exports of your static site from WordPress</a></h3>	</header><!-- .entry-header -->

	
	<div class="entry-content">
		<p>You can use the <a href="https://wordpress.org/plugins/wp-crontrol/">WP Crontrol</a> plugin to schedule the export of your static site.</p>
<p>This feature is yet to be released into the official version of <a href="http://wordpress.org/plugins/static-html-output-plugin/">WP Static HTML Output Plugin</a>, but you can try it out by downloading the latest development version of the plugin from the <a href="http://github.com/leonstafford/wordpress-static-html-plugin">GitHub repository</a>.</p>
<h2>Scheduling your WordPress site to be exported as a static HTML site</h2>
<ol>
<li>Install the <a href="https://wordpress.org/plugins/wp-crontrol/">WP Crontrol</a> plugin.</li>
<li>Go to your Cron Schedules via Settings &gt; Cron Schedules<br />
<a href="http://172.17.0.3/wp-content/uploads/go_to_cron_schedules.png"><img class="alignnone wp-image-11 size-full" src="http://172.17.0.3/wp-content/uploads/go_to_cron_schedules.png" alt="" width="406" height="375" /></a></li>
<li>Add a new Cron Schedule, using WP Static HTML Output&#8217;s custom hook, setting the following options:
<ol>
<li><strong> Internal name:</strong> <em>wp_static_html_output_server_side_export_hook</em></li>
<li><strong>Interval (seconds):</strong> ie <em>3600</em> to run your export every hour, <em>86400</em> for daily exports, etc</li>
<li><strong>Display name:</strong> This can be whatever you like. Something that makes sense to you is recommended, ie &#8220;<em>Static site export to S3 once a day</em>&#8221; <a style="font-size: 1rem;" href="http://172.17.0.3/wp-content/uploads/wpcrontrol_add_scheduled_task.png"><img class="alignnone wp-image-8" src="http://172.17.0.3/wp-content/uploads/wpcrontrol_add_scheduled_task-1024x545.png" alt="" width="800" height="426" /></a></li>
</ol>
</li>
<li>Click on Add Cron Schedule to save your scheduled task. It should now appear in your list of tasks, as below: <a style="font-size: 1rem;" href="http://172.17.0.3/wp-content/uploads/wp_crontrol_added_task.png"><img class="alignnone wp-image-7" src="http://172.17.0.3/wp-content/uploads/wp_crontrol_added_task-1024x490.png" alt="" width="800" height="383" /></a></li>
</ol>
<p>&nbsp;</p>
	</div><!-- .entry-content -->

	
</article><!-- #post-## -->

<article id="post-8" class="post-8 post type-post status-publish format-standard hentry category-uncategorized">
		<header class="entry-header">
		<div class="entry-meta"><span class="screen-reader-text">Posted on</span> <a href="http://172.17.0.3/banana/index.php/2018/04/28/removing-comments-for-a-wordpress-static-website/" rel="bookmark"><time class="entry-date published updated" datetime="2018-04-28T08:27:26+00:00">April 28, 2018</time></a><span class="edit-link"><a class="post-edit-link" href="http://172.17.0.3/banana/wp-admin/post.php?post=8&#038;action=edit">Edit<span class="screen-reader-text"> "Removing comments for a WordPress static website"</span></a></span></div><!-- .entry-meta --><h3 class="entry-title"><a href="http://172.17.0.3/banana/index.php/2018/04/28/removing-comments-for-a-wordpress-static-website/" rel="bookmark">Removing comments for a WordPress static website</a></h3>	</header><!-- .entry-header -->

	
	<div class="entry-content">
		<p>This is demo content</p>
	</div><!-- .entry-content -->

	
</article><!-- #post-## -->

		</main><!-- #main -->
	</div><!-- #primary -->
	
<aside id="secondary" class="widget-area" role="complementary" aria-label="Blog Sidebar">
	<section id="search-2" class="widget widget_search">

<form role="search" method="get" class="search-form" action="http://172.17.0.3/banana/">
	<label for="search-form-5b3ccb9931bc6">
		<span class="screen-reader-text">Search for:</span>
	</label>
	<input type="search" id="search-form-5b3ccb9931bc6" class="search-field" placeholder="Search &hellip;" value="" name="s" />
	<button type="submit" class="search-submit"><svg class="icon icon-search" aria-hidden="true" role="img"> <use href="#icon-search" xlink:href="#icon-search"></use> </svg><span class="screen-reader-text">Search</span></button>
</form>
</section>		<section id="recent-posts-2" class="widget widget_recent_entries">		<h2 class="widget-title">Recent Posts</h2>		<ul>
											<li>
					<a href="http://172.17.0.3/banana/index.php/2018/05/11/scheduling-exports-of-your-static-site-from-wordpress/">Scheduling exports of your static site from WordPress</a>
									</li>
											<li>
					<a href="http://172.17.0.3/banana/index.php/2018/04/28/removing-comments-for-a-wordpress-static-website/">Removing comments for a WordPress static website</a>
									</li>
					</ul>
		</section><section id="recent-comments-2" class="widget widget_recent_comments"><h2 class="widget-title">Recent Comments</h2><ul id="recentcomments"></ul></section><section id="archives-2" class="widget widget_archive"><h2 class="widget-title">Archives</h2>		<ul>
			<li><a href='http://172.17.0.3/banana/index.php/2018/05/'>May 2018</a></li>
	<li><a href='http://172.17.0.3/banana/index.php/2018/04/'>April 2018</a></li>
		</ul>
		</section><section id="categories-2" class="widget widget_categories"><h2 class="widget-title">Categories</h2>		<ul>
	<li class="cat-item cat-item-1"><a href="http://172.17.0.3/banana/index.php/category/uncategorized/" >Uncategorized</a>
</li>
		</ul>
</section><section id="meta-2" class="widget widget_meta"><h2 class="widget-title">Meta</h2>			<ul>
			<li><a href="http://172.17.0.3/banana/wp-admin/">Site Admin</a></li>			<li><a href="http://172.17.0.3/banana/wp-login.php?action=logout&#038;_wpnonce=292745da4e">Log out</a></li>
			<li><a href="http://172.17.0.3/banana/index.php/feed/">Entries <abbr title="Really Simple Syndication">RSS</abbr></a></li>
			<li><a href="http://172.17.0.3/banana/index.php/comments/feed/">Comments <abbr title="Really Simple Syndication">RSS</abbr></a></li>
			<li><a href="https://wordpress.org/" title="Powered by WordPress, state-of-the-art semantic personal publishing platform.">WordPress.org</a></li>			</ul>
			</section></aside><!-- #secondary -->
</div><!-- .wrap -->


		</div><!-- #content -->

		<footer id="colophon" class="site-footer" role="contentinfo">
			<div class="wrap">
				
<div class="site-info">
		<a href="https://wordpress.org/" class="imprint">
		Proudly powered by WordPress	</a>
</div><!-- .site-info -->
			</div><!-- .wrap -->
		</footer><!-- #colophon -->
	</div><!-- .site-content-contain -->
</div><!-- #page -->
<script type='text/javascript'>
/* <![CDATA[ */
var twentyseventeenScreenReaderText = {"quote":"<svg class=\"icon icon-quote-right\" aria-hidden=\"true\" role=\"img\"> <use href=\"#icon-quote-right\" xlink:href=\"#icon-quote-right\"><\/use> <\/svg>"};
/* ]]> */
</script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/js/skip-link-focus-fix.js?ver=1.0'></script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/js/global.js?ver=1.0'></script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-content/themes/twentyseventeen/assets/js/jquery.scrollTo.js?ver=2.1.2'></script>
<script type='text/javascript' src='http://172.17.0.3/banana/wp-includes/js/wp-embed.min.js?ver=4.9.6'></script>

</body>
</html>
EOHTML;
		function home_url() {
			return 'http://google.com';
		}

		function get_template_directory_uri() {
			return '/var/www/html/blah';
		}

		function get_theme_root() {
			return '/var/www/html/blah';
		}

		function get_site_url() {
			return 'http://google.com';
		}

		function wp_upload_dir() {
			return array(
				'basedir' => '/var/www/html/blahblah'
			);
		}
		
		define('ABSPATH', '/var/www/html/blah2');
		define('WP_PLUGIN_DIR', '/var/www/html/blah2');
		define('WPINC', '/var/www/html/blah2');

		// mock getResponseBody with testable HTML content
		$mockUrlResponse->method('getResponseBody')
             ->willReturn($twenty_seventeen_home);


		$mockUrlResponse->expects($this->once())
			 ->method('isHtml') ;

		$mockUrlResponse->expects($this->once())
			 ->method('isCSS') ;

		$mockUrlResponse->expects($this->once())
			 ->method('getResponseBody') ;

		// assert that setResponseBody() is called with the correctly rewritten HTML
		$mockUrlResponse->expects($this->once())
			->method('setResponseBody')
			->with($twenty_seventeen_home) ;

		$mockUrlResponse->cleanup();
    }
}
