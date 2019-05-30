<?php

namespace WP2Static;

class DetectArchiveURLs {

    public static function detect( $wp_site_url ) {
        global $wpdb;

        $post_urls = array();
        $unique_post_types = array();

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
            switch ( $post->post_type ) {
                case 'page':
                    break;
                case 'post':
                    $permalink_structure = get_option( 'permalink_structure' );
                    $permalink = WPOverrides::get_permalink(
                        $post->ID,
                        $permalink_structure
                    );
                    break;
                case 'attachment':
                    break;
                default:
                    $permalink = get_post_permalink( $post->ID );
                    break;
            }

            if ( ! isset( $permalink ) || ! is_string( $permalink ) ) {
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
                    $full_url .= $path_segments[ $x ] . '/';
                }
                $post_urls[] = $full_url;
            }
        }

        return $post_urls;
    }
}
