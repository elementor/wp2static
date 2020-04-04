<?php

namespace WP2Static;

class DetectPageURLs {

    /**
     * Detect Page URLs
     *
     * @return string[] list of URLs
     */
    public static function detect() : array {
        global $wpdb;

        $page_urls = [];

        $query = "
            SELECT ID
            FROM %s
            WHERE post_status = '%s'
            AND post_type = 'page'";

        $pages = $wpdb->get_results(
            sprintf(
                $query,
                $wpdb->posts,
                'publish'
            )
        );

        foreach ( $pages as $page ) {
            $permalink = get_page_link( $page->ID );

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $page_urls[] = $permalink;
        }

        return $page_urls;
    }
}
