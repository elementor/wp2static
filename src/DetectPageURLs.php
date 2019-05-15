<?php

namespace WP2Static;

class DetectPageURLs {

    public static function detect( $wp_site_url ) {
        global $wpdb;

        $page_urls = array();

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
