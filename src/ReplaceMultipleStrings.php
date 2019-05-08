<?php

namespace WP2Static;

/*
    Note: Once we've properly done the job of normalizing URLs and setting to
    our placeholder, we should be able to perform this multi string replacement
    to rewrite all target URLs to our Destination site's URLs

    Note: This class can be generic enough to replace
        ReriteSiteURLsToPlaceholder, where we're doing exactly the same thing!
*/
class ReplaceMultipleStrings {

    /*
     * Bulk replace URLS in string content
     *
     * @param string $html_document HTML document source
     * @param array $search_patterns Patterns to search
     * @param array $replace_patterns Patterns to replace
     * @return string HTML document containing Destination URLs
     */ 
    public function replace(
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
