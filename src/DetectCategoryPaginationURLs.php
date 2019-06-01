<?php

namespace WP2Static;

class DetectCategoryPaginationURLs {

    /**
     * Detect Category Pagination URLs
     *
     * @return string[] list of URLs
     */
    public static function detect( string $wp_site_url ) : array {
        global $wp_rewrite, $wpdb;

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

            switch ( $post->post_type ) {
                case 'page':
                    $permalink = get_page_link( $post->ID );
                    break;
                case 'post':
                    $permalink_structure = get_option( 'permalink_structure' );
                    $permalink = WPOverrides::get_permalink(
                        $post->ID,
                        $permalink_structure
                    );
                    break;
                case 'attachment':
                    $permalink = get_attachment_link( $post->ID );
                    break;
                default:
                    $permalink = get_post_permalink( $post->ID );
                    break;
            }

            if ( ! is_string( $permalink ) ) {
                continue;
            }

            if ( strpos( $permalink, '?post_type' ) !== false ) {
                continue;
            }

            $post_urls[] = $permalink;

            /*
                Get the post's URL and each sub-chunk of the path as a URL

                  ie http://domain.com/2018/01/01/my-post/ to yield:

                    http://domain.com/2018/01/01/my-post/
                    http://domain.com/2018/01/01/
                    http://domain.com/2018/01/
                    http://domain.com/2018/
            */

            $parsed_link = parse_url( $permalink );
            // rely on WP's site URL vs reconstructing from parsed
            // subdomain, ie http://domain.com/mywpinstall/
            $link_host = $wp_site_url;

            if (
                ! $parsed_link || ! array_key_exists( 'path', $parsed_link )
            ) {
                continue;
            }

            $link_path = $parsed_link['path'];

            if ( ! is_string( $link_path ) ) {
                continue;
            }

            // NOTE: Windows filepath support
            $path_segments = explode( '/', $link_path );

            // remove first and last empty elements
            array_shift( $path_segments );
            array_pop( $path_segments );

            $number_of_segments = count( $path_segments );

            // build each URL
            for ( $i = 0; $i < $number_of_segments; $i++ ) {
                $full_url = $link_host;

                for ( $x = 0; $x <= $i; $x++ ) {
                    $full_url .= $path_segments[ $x ];
                }
                $post_urls[] = $full_url;
            }
        }

        // gets all category page links
        $args = array(
            'public'   => true,
        );

        $taxonomies = get_taxonomies( $args, 'objects' );

        $category_links = array();

        foreach ( $taxonomies as $taxonomy ) {
            if ( ! property_exists( $taxonomy, 'name' ) ) {
                continue;
            }

            $terms = get_terms(
                $taxonomy->name,
                array(
                    'hide_empty' => true,
                )
            );

            if ( ! is_iterable( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                if ( is_string( $term ) ) {
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

        $urls_to_include = array();
        $pagination_base = $wp_rewrite->pagination_base;
        $default_posts_per_page = get_option( 'posts_per_page' );

        foreach ( $category_links as $term => $total_posts ) {
            $total_pages = ceil( $total_posts / $default_posts_per_page );

            for ( $page = 1; $page <= $total_pages; $page++ ) {
                $urls_to_include[] =
                    "{$term}/{$pagination_base}/{$page}";
            }
        }

        return $urls_to_include;
    }
}
