<?php

namespace WP2Static;

use DOMDocument;
use DOMXPath;

class HTMLCommentStripper {

    public function stripHTMLComments( DOMDocument $xml_doc ) : void {
        $xpath = new DOMXPath( $xml_doc );

        foreach ( $xpath->query( '//comment()' ) as $comment ) {
            $comment->parentNode->removeChild( $comment );
        }
    }
}
