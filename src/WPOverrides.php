<?php

namespace WP2Static;

class WPOverrides {
    /*
     *
     * We call this a lot in detecting URLs for the crawl list generation
     * reducing the get_home_url and get_option calls will be helpful
     *
     * Taken from original WP get_permalink() function
     */
    public static function get_permalink( $post, $permalink ) {
        $rewritecode = array(
            '%year%',
            '%monthnum%',
            '%day%',
            '%hour%',
            '%minute%',
            '%second%',
            '%postname%',
            '%post_id%',
            '%category%',
            '%author%',
            '%pagename%',
        );

        $post   = get_post( $post );
        $sample = false;

        if ( empty( $post->ID ) ) {
            return false;
        }

        if ( $post->post_type == 'page' ) {
            return get_page_link( $post, false, $sample );
        } elseif ( $post->post_type == 'attachment' ) {
            return get_attachment_link( $post, false );
        } elseif (
            in_array(
                $post->post_type,
                get_post_types( array( '_builtin' => false ) )
            )
        ) {
            return get_post_permalink( $post, false, $sample );
        }

        $permalink = apply_filters( 'pre_post_link', $permalink, $post, false );

        $unixtime = strtotime( $post->post_date );

        $category = '';
        if ( strpos( $permalink, '%category%' ) !== false ) {
            $cats = get_the_category( $post->ID );
            if ( $cats ) {
                $cats = wp_list_sort(
                    $cats,
                    array(
                        'term_id' => 'ASC',
                    )
                );

                $category_object =
                    apply_filters(
                        'post_link_category',
                        $cats[0],
                        $cats,
                        $post
                    );

                $category_object = get_term( $category_object, 'category' );
                $category = $category_object->slug;

                if ( $category_object->parent ) {
                    $category =
                        get_category_parents(
                            $category_object->parent,
                            false,
                            '/',
                            true
                        ) . $category;
                }
            }

            // show default category in permalinks, without
            // having to assign it explicitly
            if ( empty( $category ) ) {
                $default_category =
                    get_term( get_option( 'default_category' ), 'category' );
                if ( $default_category && ! is_wp_error( $default_category ) ) {
                    $category = $default_category->slug;
                }
            }
        }

        $author = '';
        if ( strpos( $permalink, '%author%' ) !== false ) {
            $authordata = get_userdata( $post->post_author );
            $author     = $authordata->user_nicename;
        }

        $date           = explode( ' ', date( 'Y m d H i s', $unixtime ) );
        $rewritereplace =
        array(
            $date[0],
            $date[1],
            $date[2],
            $date[3],
            $date[4],
            $date[5],
            $post->post_name,
            $post->ID,
            $category,
            $author,
            $post->post_name,
        );
        $permalink =
            home_url(
                str_replace( $rewritecode, $rewritereplace, $permalink )
            );
        $permalink = user_trailingslashit( $permalink, 'single' );

        return apply_filters( 'post_link', $permalink, $post, false );
    }
}
