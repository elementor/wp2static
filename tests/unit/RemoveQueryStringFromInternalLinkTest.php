<?php

namespace WP2Static;

use PHPUnit\Framework\TestCase;
use DOMDocument;

final class RemoveQueryStringFromInternalLinkTest extends TestCase{

    public function testremoveQueryStringFromInternalLink() {
        $url = 'https://somedomain.com/alink?height=100&width=200';

        $query_string_remover = new RemoveQueryStringFromInternalLink();
        $url = $query_string_remover->removeQueryStringFromInternalLink( $url );

        $this->assertEquals(
             'https://somedomain.com/alink',
            $url
        );
    }

    public function testReturnsSameURLWhenNoQueryString() {
        $url = 'https://somedomain.com/alink';

        $query_string_remover = new RemoveQueryStringFromInternalLink();
        $url = $query_string_remover->removeQueryStringFromInternalLink( $url );

        $this->assertEquals(
             'https://somedomain.com/alink',
            $url
        );
    }

    public function testReturnsEmptyStringIfURLEmpty() {
        $url = '';

        $query_string_remover = new RemoveQueryStringFromInternalLink();
        $url = $query_string_remover->removeQueryStringFromInternalLink( $url );

        $this->assertEquals(
             '',
            $url
        );
    }
}
