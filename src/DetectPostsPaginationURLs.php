<?php

namespace WP2Static;

class DetectPostsPaginationURLs {

    /**
     * Detect Post pagination URLs
     *
     * @return string[] list of URLs
     */
    public static function detect( string $wp_site_url ) : array {
        global $wpdb, $wp_rewrite;

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
        }

        // get all pagination links for each post_type
        $post_types = array_unique( $unique_post_types );
        $pagination_base = $wp_rewrite->pagination_base;
        $default_posts_per_page = get_option( 'posts_per_page' );

        $urls_to_include = [];

        foreach ( $post_types as $post_type ) {
            $query = "SELECT COUNT(*) FROM %s WHERE post_status = '%s'" .
                " AND post_type = '%s'";

            $post_type_total = $wpdb->get_var(
                sprintf(
                    $query,
                    $wpdb->posts,
                    'publish',
                    $post_type
                )
            );

            if ( ! $post_type_total ) {
                continue;
            }

            $post_type_obj = get_post_type_object( $post_type );

            if ( ! $post_type_obj ) {
                continue;
            }

            // cast WP's object back to array
            $post_type_labels = (array) $post_type_obj->labels;

            $plural_form = strtolower( $post_type_labels['name'] );

            // skip post type names containing spaces
            if ( strpos( $plural_form, ' ' ) !== false ) {
                continue;
            }

            $total_pages = ceil( $post_type_total / $default_posts_per_page );

            for ( $page = 1; $page <= $total_pages; $page++ ) {
                // TODO: skipping page pagination here, but is it covered elsewhere?
                if ( $post_type === 'page' ) {
                    continue;
                }

                if ( $post_type === 'post' ) {
                    $post_archive_slug = '';

                    // check if a Posts page has been set in Settings > Reading
                    if ( get_option( 'page_for_posts' ) !== '0' ) {

                        // get FQURL to Posts Page
                        $post_archive_link = get_post_type_archive_link( 'post' );

                        if ( $post_archive_link ) {
                            $post_archive_slug = str_replace(
                                $wp_site_url,
                                '',
                                trailingslashit( $post_archive_link )
                            );
                        }
                    }

                    $urls_to_include[] = "/{$post_archive_slug}{$pagination_base}/{$page}/";
                } else {
                    $urls_to_include[] =
                        "/{$plural_form}/{$pagination_base}/{$page}/";
                }
            }
        }

        return $urls_to_include;
    }
}
