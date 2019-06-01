<?php

namespace WP2Static;

class ConvertToSiteRootRelativeURL {
    /**
     * Convert absolute URL to site root-relative.
     * Our input URL has already been written to an absolute Destination URL
     * to allow for this kind of rewriting
     */
    public static function convert(
        string $url,
        string $destination_url
    ) : string {
        if ( ! is_string( $url ) ) {
            return $url;
        }

        // TODO: do we ever receive escaped URLs to this method?
        // if isInternalLink blocks it, then this is wasted rewrite
        $from = [
            $destination_url,
            addcslashes( $destination_url, '/' ),
        ];

        $to = [
            '/',
            '\/',
        ];

        $site_root_relative_url = str_replace(
            $from,
            $to,
            $url
        );

        return $site_root_relative_url;
    }
}
