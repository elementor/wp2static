<?php

namespace WP2Static;

use DOMElement;
/*
    For <link> elements, choose whether to remove
    based on the `rel` attribute

    this removes some extra cruft added by WP

    less cruft == faster loading!
*/
class RemoveLinkElementsBasedOnRelAttr {
    public static function remove( DOMElement $element ) : void {
        $relative_links_to_rm = array(
            'shortlink',
            'pingback',
            'alternate',
            'EditURI',
            'wlwmanifest',
            'index',
            'profile',
            'prev',
            'next',
            'wlwmanifest',
        );

        $link_rel = $element->getAttribute( 'rel' );

        if ( in_array( $link_rel, $relative_links_to_rm ) ) {
            $element->parentNode->removeChild( $element );
        } elseif ( strpos( $link_rel, '.w.org' ) !== false ) {
            $element->parentNode->removeChild( $element );
        }
    }
}
