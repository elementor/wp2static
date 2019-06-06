<?php

namespace WP2Static;

use DOMElement;
use DOMComment;

class HeadProcessor {

    private $rm_conditional_head_comments;

    public function __construct( bool $rm_conditional_head_comments ) {
        $this->rm_conditional_head_comments = $rm_conditional_head_comments;
    }

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
                if ( $this->rm_conditional_head_comments ) {
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
