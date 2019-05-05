<?php

namespace WP2Static;

/*
    In order to better rewrite all URLs to the Destination URL
    we first normalize them to the same full URL format, converting
    to an easily detectable domain name for further rewriting
*/
class RewriteSiteURLsToPlaceholder {
    public static function rewrite(
        $html_document,
        $search_patterns,
        $replace_patterns
    ) {

        $rewritten_source = str_replace(
            $search_patterns,
            $replace_patterns,
            $html_document
        );

        return $rewritten_source;
    }
}
