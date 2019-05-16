<?php

namespace WP2Static;

class DetectPostURLs {

    public static function detect() {
        global $wpdb;

        $post_urls = array();

        $query = "
            SELECT ID
            FROM %s
            WHERE post_status = '%s'
            AND post_type = 'post'";

        $posts = $wpdb->get_results(
            sprintf(
                $query,
                $wpdb->posts,
                'publish'
            )
        );

        foreach ( $posts as $post ) {
            $permalink = get_permalink( $post->ID );

            if ( ! $permalink ) {
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
