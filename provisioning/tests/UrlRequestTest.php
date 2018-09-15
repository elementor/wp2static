<?php

declare(strict_types=1);

require_once 'library/StaticHtmlOutput/UrlRequest.php';
require_once 'library/URL2/URL2.php';

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

			// dummy up the is_wp_error call 
			function get_option($argument) {
        if ($argument == 'siteurl') {
          return 'http://172.17.0.3';
        }

				return '';
			}

			function esc_attr($some_string) {
				// TODO: replicate esc_attr here
				return $some_string;
			}
		}
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
			->with('<html><head></head><body>Something with a <a href="http://google.com">link</a>.</body></html>') ;

		$mockUrlResponse->replaceBaseUrl($wpURL, $baseURL, false);
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
			->with('<html><head></head><body>Something with a <a href="http://subdomain.google.com">link</a>.</body></html>') ;

		$mockUrlResponse->replaceBaseUrl($wpURL, $baseURL, false);
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
			->with('<html><head></head><body>Something with a <a href="http://google.com">link</a>.</body></html>') ;

		$mockUrlResponse->replaceBaseUrl($wpURL, $baseURL, false);
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
			->with('<html><head></head><body>Something with a <a href="http://subdomain.google.com">link</a>.</body></html>') ;

		$mockUrlResponse->replaceBaseUrl($wpURL, $baseURL, false);
    }

    public function testRewritesWordpressSlugsAndStripsWordpressMetaFromHtml(): void {
      $this->markTestSkipped('must be revisited.');
      $url = 'http://someurl.com';	
      $basicAuth = null;

      // mock out only the unrelated methods
      $mockUrlResponse = $this->getMockBuilder('StaticHtmlOutput_UrlRequest')
        ->setMethods([
          'isRewritable',
          'isHtml',
          'isCSS',
          'getResponseBody',
          'setResponseBody'
        ])
        ->setConstructorArgs([$url, $basicAuth])
        ->getMock();

      $mockUrlResponse->method('isRewritable')
               ->willReturn(true);

      // simulate a HTML file being detected
      $mockUrlResponse->method('isHtml')
               ->willReturn(true);


      $mockUrlResponse->method('isCSS')
               ->willReturn(false);

  $twenty_seventeen_home = <<<EOHTML
  <!DOCTYPE html>
  <html>
  <head>
  <link rel='stylesheet' id='twentyseventeen-style-css'  href='http://172.17.0.3/wp-content/themes/twentyseventeen/style.css?ver=4.9.6' type='text/css' media='all' />
  <!--[if lt IE 9]>
  <link rel='stylesheet' id='twentyseventeen-ie8-css'  href='http://172.17.0.3/wp-content/themes/twentyseventeen/assets/css/ie8.css?ver=1.0' type='text/css' media='all' />
  <![endif]-->
  <!--[if lt IE 9]>
  <script type='text/javascript' src='http://172.17.0.3/wp-content/themes/twentyseventeen/assets/js/html5.js?ver=3.7.3'></script>
  <![endif]-->
  <script type='text/javascript' src='http://172.17.0.3/wp-includes/js/jquery/jquery.js?ver=1.12.4'></script>
  <script type='text/javascript' src='http://172.17.0.3/wp-includes/js/jquery/jquery-migrate.min.js?ver=1.4.1'></script>
  <link rel='https://api.w.org/' href='http://172.17.0.3/wp-json/' />
  <link rel="EditURI" type="application/rsd+xml" title="RSD" href="http://172.17.0.3/xmlrpc.php?rsd" />
  <meta name="generator" content="WordPress 4.9.6" />
      <style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
      </head>

  <body class="home blog logged-in hfeed has-header-image has-sidebar colors-light">
        <div id="wp-custom-header" class="wp-custom-header"><img src="http://172.17.0.3/wp-content/themes/twentyseventeen/assets/images/header.jpg" width="2000" height="1200" alt="wp plugindev" /></div>		
                <h1 class="site-title"><a href="http://172.17.0.3/" rel="home">wp plugindev</a></h1>
  </body>
  </html>
  EOHTML;

  $twenty_seventeen_home_expected_rewrite = <<<EOHTML
  <!DOCTYPE html>
  <html><head>
  <link rel='stylesheet' id='twentyseventeen-style-css'  href='http://172.17.0.3/contents/ui/theme/style.css' type='text/css' media='all' />
  <!--[if lt IE 9]>
  <link rel='stylesheet' id='twentyseventeen-ie8-css'  href='http://172.17.0.3/contents/ui/theme/assets/css/ie8.css' type='text/css' media='all' />
  <![endif]-->
  <!--[if lt IE 9]>
  <script type='text/javascript' src='http://172.17.0.3/contents/ui/theme/assets/js/html5.js'></script>
  <![endif]-->
  <script type='text/javascript' src='http://172.17.0.3/inc/js/jquery/jquery.js'></script>
  <script type='text/javascript' src='http://172.17.0.3/inc/js/jquery/jquery-migrate.min.js'></script>



      <style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
      </head>

  <body class="home blog logged-in hfeed has-header-image has-sidebar colors-light">
        <div id="wp-custom-header" class="wp-custom-header"><img src="http://172.17.0.3/contents/ui/theme/assets/images/header.jpg" width="2000" height="1200" alt="wp plugindev" /></div>

                <h1 class="site-title"><a href="http://172.17.0.3/" rel="home">wp plugindev</a></h1>
  </body></html>
EOHTML;

      // mock getResponseBody with testable HTML content
      $mockUrlResponse->method('getResponseBody')
               ->willReturn($twenty_seventeen_home);


      $mockUrlResponse->expects($this->once())
         ->method('isHtml') ;

      $mockUrlResponse->expects($this->once())
         ->method('isCSS') ;

      $mockUrlResponse->expects($this->exactly(3))
         ->method('getResponseBody') ;

      // assert that setResponseBody() is called with the correctly rewritten HTML
      $mockUrlResponse->expects($this->once())
        ->method('setResponseBody')
        ->with($twenty_seventeen_home_expected_rewrite) ;

      $wp_site_environment = array(
        'wp_inc' =>  '/wp-includes',	
        'wp_plugin' =>  '',	
        'wp_content' => '/wp-content', // TODO: check if this has been modified/use constant
        'wp_uploads' =>  '/wp-content/uploads',	
        'wp_plugins' =>  '/wp-content/plugins',	
        'wp_themes' =>  '/wp-content/themes',	
        'wp_active_theme' =>  '/wp-content/themes/twentyseventeen',	
        'site_url' =>  'http://172.17.0.3'
      );

      $overwrite_slug_targets = array(
        'new_wp_content_path' => '/contents',
        'new_themes_path' => '/contents/ui',
        'new_active_theme_path' => '/contents/ui/theme',
        'new_uploads_path' => '/contents/data',
        'new_plugins_path' => '/contents/lib',
        'new_wpinc_path' => '/inc'
      );

      $mockUrlResponse->cleanup($wp_site_environment, $overwrite_slug_targets);
    }


    public function testRewritesEscapedURLs(): void {
      $this->markTestSkipped('must be revisited.');
      $url = 'http://someurl.com';	
      $basicAuth = null;

      // mock out only the unrelated methods
      $mockUrlResponse = $this->getMockBuilder('StaticHtmlOutput_UrlRequest')
        ->setMethods([
          'isRewritable',
          'isHtml',
          'isCSS',
          'getResponseBody',
          'setResponseBody'
        ])
        ->setConstructorArgs([$url, $basicAuth])
        ->getMock();

$escaped_url_block = <<<EOHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg">
<body>
<section  id="hero"  data-images="[&quot;https:\/\/mysite.example.com\/wp-content\/themes\/onepress\/assets\/images\/hero5.jpg&quot;]"             class="hero-slideshow-wrapper hero-slideshow-normal">
</section>
</body>
</html>
EOHTML;


$escaped_url_block_expected_rewrite = <<<EOHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg"></body>
<section id="hero" data-images='["https:\/\/mysite.example.com\/contents\/ui\/theme\/assets\/images\/hero5.jpg"]' class="hero-slideshow-wrapper hero-slideshow-normal"></section></body></html>
EOHTML;

		// mock getResponseBody with testable HTML content
		$mockUrlResponse->method('getResponseBody')
             ->willReturn($escaped_url_block);

    $mockUrlResponse->method('isRewritable')
             ->willReturn(true);

		$mockUrlResponse->expects($this->once())
			 ->method('isHtml') ;

		$mockUrlResponse->expects($this->once())
			 ->method('isCSS') ;

		$mockUrlResponse->expects($this->once())
			 ->method('getResponseBody') ;

		// assert that setResponseBody() is called with the correctly rewritten HTML
		$mockUrlResponse->expects($this->once())
			->method('setResponseBody')
			->with($escaped_url_block_expected_rewrite) ;

		$wp_site_environment = array(
			'wp_inc' =>  '/wp-includes',	
			'wp_plugin' =>  '',	
			'wp_content' => '/wp-content', // TODO: check if this has been modified/use constant
			'wp_uploads' =>  '/wp-content/uploads',	
			'wp_plugins' =>  '/wp-content/plugins',	
			'wp_themes' =>  '/wp-content/themes',	
			'wp_active_theme' =>  '/wp-content/themes/onepress',	
			'site_url' =>  'http://172.17.0.3'
		);

		$overwrite_slug_targets = array(
			'new_wp_content_path' => '/contents',
			'new_themes_path' => '/contents/ui',
			'new_active_theme_path' => '/contents/ui/theme',
			'new_uploads_path' => '/contents/data',
			'new_plugins_path' => '/contents/lib',
			'new_wpinc_path' => '/inc'
		);

		$mockUrlResponse->rewriteWPPaths($wp_site_environment, $overwrite_slug_targets);
    }

    public function testRewritingSubdomains(): void {
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


$escaped_url_block = <<<EOHTML
<head>
<link href='https://mydomain.com/wp-content/themes/onepress/css/font.css' rel='stylesheet' type='text/css'>

https:\/\/mydomain.com\/
mydomain.com
//mydomain.com
http://mydomain.com
EOHTML;

$escaped_url_block_expected_rewrite = <<<EOHTML
<head>
<link href='https://subdomain.mydomain.com/wp-content/themes/onepress/css/font.css' rel='stylesheet' type='text/css'>

https:\/\/subdomain.mydomain.com\/
subdomain.mydomain.com
//subdomain.mydomain.com
https://subdomain.mydomain.com
EOHTML;

		// mock getResponseBody with testable HTML content
		$mockUrlResponse->method('getResponseBody')
             ->willReturn($escaped_url_block);

		$mockUrlResponse->method('isRewritable')
             ->willReturn(true);

		$mockUrlResponse->expects($this->once())
			 ->method('getResponseBody') ;

		// assert that setResponseBody() is called with the correctly rewritten HTML
		$mockUrlResponse->expects($this->once())
			->method('setResponseBody')
			->with($escaped_url_block_expected_rewrite) ;

		$siteURL = 'https://mydomain.com';
		$newDomain = 'https://subdomain.mydomain.com';

		$mockUrlResponse->replaceBaseUrl($siteURL, $newDomain, false);
    }

    public function testAbosluteURLs(): void {
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


$escaped_url_block = <<<EOHTML
<head>
<link href='https://mydomain.com/wp-content/themes/onepress/css/font.css' rel='stylesheet' type='text/css'>

http://mydomain.com
EOHTML;

$escaped_url_block_expected_rewrite = <<<EOHTML
<head>
<base href="https://subdomain.mydomain.com/" />

<link href='wp-content/themes/onepress/css/font.css' rel='stylesheet' type='text/css'>


EOHTML;

		// mock getResponseBody with testable HTML content
		$mockUrlResponse->method('getResponseBody')
             ->willReturn($escaped_url_block);

		$mockUrlResponse->method('isRewritable')
             ->willReturn(true);

		$mockUrlResponse->expects($this->once())
			 ->method('getResponseBody') ;

		// assert that setResponseBody() is called with the correctly rewritten HTML
		$mockUrlResponse->expects($this->once())
			->method('setResponseBody')
			->with($escaped_url_block_expected_rewrite) ;

		$siteURL = 'https://mydomain.com';
		$newDomain = 'https://subdomain.mydomain.com';

		$mockUrlResponse->replaceBaseUrl($siteURL, $newDomain, false, 'absolute');
    }

    public function testDontRewriteExternalDomains(): void {
      $url = 'http://someurl.com';	
      $basicAuth = null;


      // mock out only the unrelated methods
      $mockUrlResponse = $this->getMockBuilder('StaticHtmlOutput_UrlRequest')
        ->setMethods([
          'isRewritable',
          'isHtml',
          'isCSS',
          'getResponseBody',
        ])
        ->setConstructorArgs([$url, $basicAuth])
        ->getMock();


$escaped_url_block = <<<EOHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg">
<body>
<a href="http://172.17.0.3/wp-content/themes/twentyseventeen/afile.css">Some CSS link</a>

<a href="http://someexternaldomain.com/wp-content/themes/twentyseventeen/afile.css">Some CSS link</a>
</body>
</html>
EOHTML;

$escaped_url_block_expected_rewrite = <<<EOHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg"><body>
<a href="http://172.17.0.3/contents/ui/theme/afile.css">Some CSS link</a>

<a href="http://someexternaldomain.com/wp-content/themes/twentyseventeen/afile.css">Some CSS link</a>
</body></html>

EOHTML;

      $mockUrlResponse->method('getResponseBody')
               ->willReturn($escaped_url_block);

      $mockUrlResponse->method('isRewritable')
               ->willReturn(true);

      $mockUrlResponse->expects($this->once())
         ->method('isHtml') ;

      $mockUrlResponse->expects($this->once())
         ->method('isCSS') ;

      $mockUrlResponse->expects($this->once())
         ->method('getResponseBody') ;

      $wp_site_environment = array(
        'wp_inc' =>  '/wp-includes',	
        'wp_plugin' =>  '',	
        'wp_content' => '/wp-content', // TODO: check if this has been modified/use constant
        'wp_uploads' =>  '/wp-content/uploads',	
        'wp_plugins' =>  '/wp-content/plugins',	
        'wp_themes' =>  '/wp-content/themes',	
        'wp_active_theme' =>  '/wp-content/themes/twentyseventeen',	
        'site_url' =>  'http://172.17.0.3'
      );

      $overwrite_slug_targets = array(
        'new_wp_content_path' => '/contents',
        'new_themes_path' => '/contents/ui',
        'new_active_theme_path' => '/contents/ui/theme',
        'new_uploads_path' => '/contents/data',
        'new_plugins_path' => '/contents/lib',
        'new_wpinc_path' => '/inc'
      );

      $mockUrlResponse->cleanup($wp_site_environment, $overwrite_slug_targets);
    }

    public function testURLNormalizationAtSiteRoot(): void {
      // requires php-xml installed where running tests, ie apt install php-xml

      $url = 'http://someurl.com';	
      $basicAuth = null;

      $mockUrlResponse = $this->getMockBuilder('StaticHtmlOutput_UrlRequest')
        ->setMethods([
          'isRewritable',
          'getResponseBody',
          'setResponseBody'
        ])
        ->setConstructorArgs([$url, $basicAuth])
        ->getMock();

$input_html = <<<EOHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg">
<body>
<a href="http://someurl.com/wp-content/themes/twentyseventeen/afile.css">Some CSS link</a>

<a href="/wp-content/themes/twentyseventeen/anotherfile.css">Some CSS link</a>

<a href="http://someexternaldomain.com/wp-content/themes/twentyseventeen/afile.css">Some CSS link</a>
</body>
</html>
EOHTML;

// NOTE: linebreaks not being preserved for html/body
$expected_output_html = <<<EOHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg"><body>
<a href="http://someurl.com/wp-content/themes/twentyseventeen/afile.css">Some CSS link</a>

<a href="http://someurl.com/wp-content/themes/twentyseventeen/anotherfile.css">Some CSS link</a>

<a href="http://someexternaldomain.com/wp-content/themes/twentyseventeen/afile.css">Some CSS link</a>
</body></html>

EOHTML;


      $mockUrlResponse->method('getResponseBody')
               ->willReturn($input_html);

      $mockUrlResponse->method('isRewritable')
               ->willReturn(true);

      $mockUrlResponse->expects($this->once())
         ->method('isRewritable') ;

      $mockUrlResponse->expects($this->once())
         ->method('getResponseBody') ;

      $mockUrlResponse->expects($this->once())
        ->method('setResponseBody')
        ->with($expected_output_html) ;

      $mockUrlResponse->normalizeURLs();
    }
}
