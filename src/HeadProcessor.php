<?php

namespace WP2Static;

use DOMElement;
use DOMComment;

class HeadProcessor {

    private $settings;

    /**
     *  Process <head> element and return <base> if found
     *
     *  @return mixed DOMNode | null
     */
    public function processHead( DOMElement $element ) {
        $base_element = null;

        $head_elements = iterator_to_array(
            $element->childNodes
        );

        foreach ( $head_elements as $node ) {
            if ( $node instanceof DOMComment ) {
                if (
                    isset( $this->settings['removeConditionalHeadComments'] )
                ) {
                    $node->parentNode->removeChild( $node );
                }
            } elseif ( isset( $node->tagName ) ) {
                if ( $node->tagName === 'base' ) {
                    // as smaller iteration to run conditional
                    // against here
                    $base_element = $node;
                }
            }
        }

        return $base_element;
    }
}
