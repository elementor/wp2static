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

        $query = "
            SELECT ID,post_type
            FROM %s
            WHERE post_status = '%s'
            AND post_type NOT IN ('%s','%s')";

        $posts = $wpdb->get_results(
            sprintf(
                $query,
                $wpdb->posts,
                'publish',
                'revision',
                'nav_menu_item'
            )
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
