<?php

namespace WP2Static;

class BaseHrefProcessor {

    /*
        When we use relative links, we'll need to set the base HREF tag
        if we are exporting for offline usage or have not specific a base HREF
        we will remove any that we find

        HEAD and BASE elements have been looked for during the main DOM
        iteration and recorded if found.
    */
    public function dealWithBaseHREFElement(
        DOMDocument $xml_doc,
        DOMNode $base_element,
        DOMNode $head_element
    ) : void {
        if ( $base_element ) {
            if ( $this->shouldCreateBaseHREF() ) {
                $base_element->setAttribute(
                    'href',
                    $this->settings['baseHREF']
                );
            } else {
                $base_element->parentNode->removeChild(
                    $base_element
                );
            }
        } elseif ( $this->shouldCreateBaseHREF() ) {
            $base_element = $xml_doc->createElement( 'base' );
            $base_element->setAttribute(
                'href',
                $this->settings['baseHREF']
            );

            if ( $head_element ) {
                $first_head_child = $head_element->firstChild;
                $head_element->insertBefore(
                    $base_element,
                    $first_head_child
                );
            } else {
                WsLog::l(
                    'No head element to attach base to: ' . $this->page_url
                );
            }
        }
    }
}
