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
}
