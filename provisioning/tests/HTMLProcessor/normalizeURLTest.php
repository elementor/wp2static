<?php

declare(strict_types=1);

require_once 'library/StaticHtmlOutput/HTMLProcessor.php';
require_once 'library/URL2/URL2.php';

use PHPUnit\Framework\TestCase;

final class HTMLProcessorNormalizeURLTest extends TestCase {

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
        $links = $html_doc->getElementsByTagName( 'a' );
        $element = $links[0];
        $attribute = 'href';

        // mock out only the unrelated methods
        $mockUrlResponse = $this->getMockBuilder( 'HTMLProcessor' )
            ->setMethods(
                [
                    'isInternalLink',
                    'getResponseBody',
                    'setResponseBody',
                ]
            )
            ->getMock();

        $mockUrlResponse->page_url = new Net_URL2(
            'http://mywpsite.com/category/photos/my-gallery/'
        );

        $mockUrlResponse->method( 'isInternalLink' )->willReturn( true );
        $mockUrlResponse->expects( $this->once() )->method( 'isInternalLink' );
        $mockUrlResponse->normalizeURL( $element, $attribute );

        $this->assertEquals(
            $element->getAttribute( 'href' ),
            'http://mywpsite.com/first_lvl_dir/a_file.jpg'
        );
    }
}
