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

        $post_ids = $wpdb->get_col(
            "SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = 'attachment'"
        );

        foreach ( $post_ids as $post_id ) {
            $permalink = get_attachment_link( $post_id );

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $post_urls[] = $permalink;
        }

        return $post_urls;
    }
}
