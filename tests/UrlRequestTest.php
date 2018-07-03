<?php

declare(strict_types=1);

require_once 'library/StaticHtmlOutput/UrlRequest.php';

use PHPUnit\Framework\TestCase;

final class StaticHtmlOutput_UrlRequestTest extends TestCase
{
    public function testGetUrlIsPrettyUseless(): void
    {
		$url = 'http://google.com';
		$basicAuth = null;

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

		// create a new instance
        $urlResponse = new StaticHtmlOutput_UrlRequest($url, $basicAuth);

		// call the _getURL method

		// assert it returns the url

        //$this->assertInstanceOf(
        //    Email::class,
        //    Email::fromString('user@example.com')
        //);

        $this->assertEquals(
            'http://google.com',
            $urlResponse->getURL()
        );
    }
}
