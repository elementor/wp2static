<?php

namespace WP2Static;

class ConvertToSiteRootRelativeURL {

    /*
     * Convert absolute URL to site root-relative.
     * Our input URL has already been written to an absolute Destination URL
     * to allow for this kind of rewriting
     *
     * @param string $url absolute URL rewritten for Destination URL
     * @param string $destination_url Destination URL reference for rewriting
     * @return string Rewritten URL
     */
    public static function convert(
        $url, $destination_url
    ) {
        if ( ! is_string( $url ) ) {
            return $url;
        }

        // TODO: do we ever receive escaped URLs to this method?
        // if isInternalLink blocks it, then this is wasted rewrite 
        $from = [
            $destination_url,
            addcslashes( $destination_url, '/'),
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
