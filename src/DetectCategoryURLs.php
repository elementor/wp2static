<?php

namespace WP2Static;

class DetectCategoryURLs {

    /**
     * Detect Category URLs
     *
     * @return string[] list of URLs
     */
    public static function detect( string $wp_site_url ) : array {
        global $wp_rewrite, $wpdb;

        $post_urls = [];

        // gets all category page links
        $args = [
            'public'   => true,
        ];

        $taxonomies = get_taxonomies( $args, 'objects' );

        $category_links = [];

        foreach ( $taxonomies as $taxonomy ) {
            if ( ! property_exists( $taxonomy, 'name' ) ) {
                continue;
            }

            if ( gettype( $taxonomy ) !== 'WP_Taxonomy' ) {
                continue;
            }

            $terms = get_terms(
                $taxonomy->name,
                [
                    'hide_empty' => true,
                ]
            );

            if ( ! is_iterable( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                if ( gettype( $term ) !== 'WP_Term' ) {
                    continue;
                }

                $term_link = get_term_link( $term );

                if ( ! is_string( $term_link ) ) {
                    continue;
                }

                $permalink = trim( $term_link );
                $total_posts = $term->count;

                $term_url = str_replace(
                    $wp_site_url,
                    '',
                    $permalink
                );

                $category_links[ $term_url ] = $total_posts;

                $post_urls[] = $permalink;
            }
        }

        return $post_urls;
    }
}
