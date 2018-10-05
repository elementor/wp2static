<?php

declare(strict_types=1);

require_once 'library/StaticHtmlOutput/HTMLProcessor.php';
require_once 'library/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorTest extends TestCase {
    public function setUp() {

            // dummy up the wp_remote_get call
        function wp_remote_get( $url, $someArray ) {
            return true;
        }
    }

    public function testNormalizePartialURLInAnchor(): void {
        $html_doc = new DOMDocument();

        $html_string = <<<EOHTML
<!DOCTYPE html>
<html lang="en-US" class="no-js no-svg">
<body>

<a href="/first_lvl_dir/a_file.jpg">Link to some file</a>

</body>
</html>
EOHTML;

        $html_doc->loadHTML( $html_string );

        // mock out only the unrelated methods
        $mockUrlResponse = $this->getMockBuilder( 'HTMLProcessor' )
            ->setMethods(
                [
                    'isRewritable',
                    'getResponseBody',
                    'setResponseBody',
                ]
            )
            ->setConstructorArgs(
                [
                    $html_document,
                    $page_url,
                    $wp_site_env,
                    $new_paths,
                    $wp_site_url,
                    $baseUrl,
                    $allowOfflineUsage,
                    $useRelativeURLs,
                    $useBaseHref,
                ]
            )
            ->getMock();

        $mockUrlResponse->method( 'isRewritable' )
             ->willReturn( true );

        // mock getResponseBody with testable HTML content
        $mockUrlResponse->method( 'getResponseBody' )
             ->willReturn( '<html><head></head><body>Something with a <a href="http://example.com">link</a>.</body></html>' );

        $mockUrlResponse->expects( $this->once() )
             ->method( 'isRewritable' );

        $mockUrlResponse->expects( $this->once() )
             ->method( 'getResponseBody' );

        // assert that setResponseBody() is called with the correctly rewritten HTML
        $mockUrlResponse->expects( $this->once() )
            ->method( 'setResponseBody' )
            ->with( '<html><head></head><body>Something with a <a href="http://google.com">link</a>.</body></html>' );

        $mockUrlResponse->normalizeURL( $element, $attribute );
    }

}
