<?php

namespace WP2Static;

use DOMElement;

class FormProcessor {

    public static function process( DOMElement $element ) : void {
        // check if any form detection options are enabled

        // detect form types user wants converted

        // perform transformations

        // example gravity
        $element->setAttribute( 'target', '' );
        $element->setAttribute(
            'action',
            'https://usebasin.com/f/f58d90a5a674'
        );

        // inject any JS required
    }
}
