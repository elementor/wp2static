<?php

namespace WP2Static;

class SiteURLPatterns {

    /*
     * WordPress site URLs used in rewriting links to placeholder URLs
     *
     */
    public static function getWPSiteURLSearchPatterns( $wp_site_url ) {
        $wp_site_url = rtrim( $wp_site_url, '/' );

        $wp_site_url_with_cslashes = addcslashes( $wp_site_url, '/' );

        $protocol_relative_wp_site_url =
            URLHelper::getProtocolRelativeURL( $wp_site_url );

        $protocol_relative_wp_site_url_with_extra_2_slashes =
            URLHelper::getProtocolRelativeURL( $wp_site_url . '//' );

        $protocol_relative_wp_site_url_with_cslashes =
            URLHelper::getProtocolRelativeURL(
                addcslashes( $wp_site_url, '/' )
            );

        $search_patterns = array(
            $wp_site_url,
            $wp_site_url_with_cslashes,
            $protocol_relative_wp_site_url,
            $protocol_relative_wp_site_url_with_extra_2_slashes,
            $protocol_relative_wp_site_url_with_cslashes,
        );

        return $search_patterns;
    }

    /*
     * Placeholders to replace WP site URLs for easier processing
     *
     */
    public static function getPlaceholderURLReplacementPatterns(
        $placeholder_url
    ) {
        $placeholder_url = rtrim( $placeholder_url, '/' );
        $placeholder_url_with_cslashes = addcslashes( $placeholder_url, '/' );

        $protocol_relative_placeholder =
            URLHelper::getProtocolRelativeURL( $placeholder_url );

        $protocol_relative_placeholder_with_extra_slash =
            URLHelper::getProtocolRelativeURL( $placeholder_url . '/' );

        $protocol_relative_placeholder_with_cslashes =
            URLHelper::getProtocolRelativeURL(
                addcslashes( $placeholder_url, '/' )
            );

        $replace_patterns = array(
            $placeholder_url,
            $placeholder_url_with_cslashes,
            $protocol_relative_placeholder,
            $protocol_relative_placeholder_with_extra_slash,
            $protocol_relative_placeholder_with_cslashes,
        );

        return $replace_patterns;
    }
}
