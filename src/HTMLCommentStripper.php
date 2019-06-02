<?php

namespace WP2Static;

class HTMLCommentStripper {

    public function stripHTMLComments( $xml_doc ) : void {
        $xpath = new DOMXPath( $xml_doc );

        foreach ( $xpath->query( '//comment()' ) as $comment ) {
            $comment->parentNode->removeChild( $comment );
        }
    }
}
