<?php

namespace WP2Static;

class DetectCategoryPaginationURLs {
    public static function detect( $categories ) {
        global $wp_rewrite;

        $urls_to_include = array();
        $pagination_base = $wp_rewrite->pagination_base;
        $default_posts_per_page = get_option( 'posts_per_page' );

        foreach ( $categories as $term => $total_posts ) {
            $total_pages = ceil( $total_posts / $default_posts_per_page );

            for ( $page = 1; $page <= $total_pages; $page++ ) {
                $urls_to_include[] =
                    "{$term}/{$pagination_base}/{$page}";
            }
        }

        return $urls_to_include;
    }
}
