<?php

namespace WP2Static;

class ConvertToSiteRootRelativeURL {

    /*
     * Convert absolute URL to site root-relative.
     *
     * @param string $url URL to change
     * @param string $site_url Site URL reference for rewriting
     * @return string Rewritten URL
     */
    public static function convert(
        $url_to_change, $site_url
    ) {
        if ( ! is_string( $url_to_change ) ) {
            return $url_to_change;
        }

        $site_root_relative_url = str_replace(
            $site_url,
            '/',
            $url_to_change
        );

        return $site_root_relative_url;
    }
}
