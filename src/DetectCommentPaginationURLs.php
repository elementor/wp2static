<?php

namespace WP2Static;

class DetectCommentPaginationURLs {

    /**
     * Detect Comment pagination URLs
     *
     * @return string[] list of URLs
     */
    public static function detect() : array {
        global $wp_rewrite;

        $urls_to_include = [];
        // $comments_pagination_base = $wp_rewrite->comments_pagination_base;
        $comments = get_comments();

        if ( ! is_iterable( $comments ) ) {
            return [];
        }

        foreach ( $comments as $comment ) {
            $comment_url = get_comment_link( $comment->comment_ID );
            $comment_url = strtok( $comment_url, '#' );

            if ( ! is_string( $comment_url ) ) {
                continue;
            }

            $urls_to_include[] = $comment_url;
        }

        return array_unique( $urls_to_include );
    }
}
