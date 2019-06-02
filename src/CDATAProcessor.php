<?php

namespace WP2Static;

class CDATAProcessor {

    public function processCDATA( DOMNode $node ) : void {
        $node_text = $node->textContent;

        $node_text = str_replace(
            $this->rewrite_rules['site_url_patterns'],
            $this->rewrite_rules['destination_url_patterns'],
            $node_text
        );

        $new_node =
            $this->xml_doc->createTextNode( $node_text );

        // replace old node with new
        $node->parentNode->replaceChild( $new_node, $node );
    }
}

