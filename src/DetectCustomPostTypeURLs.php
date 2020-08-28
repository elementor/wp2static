<?php

namespace WP2Static;

class DetectCustomPostTypeURLs {

    /**
     * Detect Custom Post Type URLs
     *
     * @return string[] list of URLs
     */
    public static function detect() : array {
        global $wpdb;

        $post_urls = [];
        $unique_post_types = [];

        $posts = $wpdb->get_results(
            "SELECT ID,post_type
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type NOT IN ('revision','nav_menu_item')"
        );

        foreach ( $posts as $post ) {
            // capture all post types
            $unique_post_types[] = $post->post_type;

            $permalink = get_post_permalink( $post->ID );

            if ( ! is_string( $permalink ) ) {
                continue;
            }

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $post_urls[] = $permalink;
        }

        return $post_urls;
    }
}
