<?php

namespace WP2Static;

class DetectAttachmentURLs {

    /**
     * Detect Attachment URLs
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
            AND post_type = 'attachment'";

        $posts = $wpdb->get_results(
            sprintf(
                $query,
                $wpdb->posts,
                'publish'
            )
        );

        foreach ( $posts as $post ) {
            $permalink = get_attachment_link( $post->ID );

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $post_urls[] = $permalink;
        }

        return $post_urls;
    }
}
