<?php

namespace WP2Static;

/*
    In order to better rewrite all URLs to the Destination URL
    we first normalize them to the same full URL format, converting
    to an easily detectable domain name for further rewriting
*/
class RewriteSiteURLsToPlaceholder {

    /**
     *
     * Rewrite site urls to placeholder using patterns
     *
     * @param string $html_document HTML document source
     * @param array $search_patterns Patterns to search
     * @param array $replace_patterns Patterns to replace
     * @return string HTML document with placeholder URLs
     */
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
