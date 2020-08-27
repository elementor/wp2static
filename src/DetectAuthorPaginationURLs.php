<?php

namespace WP2Static;

class DetectAuthorPaginationURLs {

    /**
     * Detect Author Pagination URLs
     *
     * @return string[] list of URLs
     */
    public static function detect( string $wp_site_url ) : array {
        global $wp_rewrite, $wpdb;

        $public = true;
        $authors_urls = [];
        $urls_to_include = [];
        $users = get_users();
        $pagination_base = $wp_rewrite->pagination_base;
        $default_posts_per_page = get_option( 'posts_per_page' );

        foreach ( $users as $author ) {
            $author_link = get_author_posts_url( $author->ID );

            if ( ! is_string( $author_link ) ) {
                continue;
            }

            $permalink = trim( $author_link );

            $total_posts = count_user_posts( $author->ID, 'post', $public );

            $author_url = str_replace(
                $wp_site_url,
                '',
                $permalink
            );

            $authors_urls [ $author_url ] = $total_posts;
        }

        foreach ( $authors_urls as $author => $total_posts ) {
            $total_pages = ceil( $total_posts / $default_posts_per_page );

            for ( $page = 1; $page <= $total_pages; $page++ ) {
                $urls_to_include[] =
                    "/{$author}{$pagination_base}/{$page}/";
            }
        }

        return $urls_to_include;
    }
}
