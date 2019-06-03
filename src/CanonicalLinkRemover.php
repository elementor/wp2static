<?php

namespace WP2Static;

use DOMElement;

class CanonicalLinkRemover {

    public function removeCanonicalLink( DOMElement $element ) : void {
        $link_rel = $element->getAttribute( 'rel' );

        if ( strtolower( $link_rel ) == 'canonical' ) {
            $element->parentNode->removeChild( $element );
        }
    }

}
