<?php

namespace WP2Static;

use DOMDocument;
use DOMElement;

class FaviconRequestBlocker {

    public function createEmptyFaviconLink(
         DOMDocument $xml_doc,
         DOMElement $head_element = null
    ) : void {
        $link_element = $xml_doc->createElement( 'link' );
        $link_element->setAttribute(
            'rel',
            'icon'
        );

        $link_element->setAttribute(
            'href',
            'data:,'
        );

        if ( $head_element ) {
            $first_head_child = $head_element->firstChild;
            $head_element->insertBefore(
                $link_element,
                $first_head_child
            );
        } else {
            WsLog::l(
                'No head element to attach favicon link to to'
            );
        }
    }
}
