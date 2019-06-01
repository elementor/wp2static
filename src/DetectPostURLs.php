<?php

namespace WP2Static;

class DetectPostURLs {

    /**
     * Detect Post URLs
     *
     * @return string[] list of URLs
     */
    public static function detect( string $permalink_structure ) : array {
        global $wpdb;

        $post_urls = [];

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
            $permalink = WPOverrides::get_permalink(
                $post->ID,
                $permalink_structure
            );

            $permalink = WPOverrides::get_permalink( $post->ID, $permalink );

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
