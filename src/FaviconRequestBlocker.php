<?php

namespace WP2Static;

class FaviconRequestBlocker {

    public function createEmptyFaviconLink( DOMDocument $xml_doc ) : void {
        $link_element = $xml_doc->createElement( 'link' );
        $link_element->setAttribute(
            'rel',
            'icon'
        );

        $link_element->setAttribute(
            'href',
            'data:,'
        );

        if ( $this->head_element ) {
            $first_head_child = $this->head_element->firstChild;
            $this->head_element->insertBefore(
                $link_element,
                $first_head_child
            );
        } else {
            WsLog::l(
                'No head element to attach favicon link to to: ' .
                $this->page_url
            );
        }
    }
}
