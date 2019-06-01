<?php

namespace WP2Static;

class DetectCommentPaginationURLs {

    /**
     * Detect Comment pagination URLs
     *
     * @return string[] list of URLs
     */
    public static function detect( string $wp_site_url ) : array {
        global $wp_rewrite;

        $urls_to_include = [];
        $comments_pagination_base = $wp_rewrite->comments_pagination_base;
        $comments = get_comments();

        if ( ! is_iterable( $comments ) ) {
            return array();
        }

        foreach ( $comments as $comment ) {
            $comment_url = get_comment_link( $comment->comment_ID );
            $comment_url = strtok( $comment_url, '#' );

            if ( ! is_string( $comment_url ) ) {
                continue;
            }

            $urls_to_include[] = str_replace(
                $wp_site_url,
                '',
                $comment_url
            );
        }

        return array_unique( $urls_to_include );
    }
}
