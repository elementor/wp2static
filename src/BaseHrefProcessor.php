<?php

namespace WP2Static;

use DOMDocument;
use DOMNode;
use DOMElement;

class BaseHrefProcessor {

    private $settings;

    public function __construct() {
        $plugin = Controller::getInstance();
        $this->settings = $plugin->options->getSettings( true );
    }

    /*
        When we use relative links, we'll need to set the base HREF tag
        if we are exporting for offline usage or have not specific a base HREF
        we will remove any that we find

        HEAD and BASE elements have been looked for during the main DOM
        iteration and recorded if found.
    */
    public function dealWithBaseHREFElement(
        DOMDocument $xml_doc,
        DOMElement $base_element = null,
        DOMNode $head_element = null
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
                    'No head element to attach base to'
                );
            }
        }
    }

    public function shouldCreateBaseHREF() : bool {
        if ( empty( $this->settings['baseHREF'] ) ) {
            return false;
        }

        // NOTE: base HREF should not be set when creating an offline ZIP
        if ( isset( $this->settings['allowOfflineUsage'] ) ) {
            return false;
        }

        return true;
    }
}
