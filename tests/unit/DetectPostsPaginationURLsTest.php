<?php

namespace WP2Static;

use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Mock;

final class DetectPostsPaginationURLsTest extends TestCase {


    public function testDetect() {
        global $wpdb;
        // Set the WordPress pagination base
        global $wp_rewrite;
        $site_url = 'https://foo.com/';

        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wp_rewrite = (object) [ 'pagination_base' => '/page' ];

        // Create 3 post objects
        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wpdb = Mockery::mock( '\WPDB' );
        // set table name
        $wpdb->posts = 'wp_posts';
        $query_string = "
            SELECT ID,post_type
            FROM $wpdb->posts
            WHERE post_status = 'publish'
            AND post_type NOT IN ('revision','nav_menu_item')";

        $posts = [
            (object) [
                'ID' => '1',
                'post_type' => 'post',
            ],
            // (object) [ 'ID' => '2', 'post_type' => 'page' ],
            // (object) [ 'ID' => '3', 'post_type' => 'attachment' ],
            // (object) [ 'ID' => '4', 'post_type' => 'mycustomtype' ],
        ];

        $wpdb->shouldReceive( 'get_results' )
            ->with( $query_string )
            ->once()
            ->andReturn( $posts );

        // Set pagination to 3 posts per page
        \WP_Mock::userFunction(
            'get_option',
            [
                'times' => 1,
                'args' => [ 'posts_per_page' ],
                'return' => 3,
            ]
        );

        $posts_query = "
            SELECT COUNT(*)
            FROM $wpdb->posts
            WHERE post_status = 'publish'
            AND post_type = 'post''";

        $wpdb->shouldReceive( 'get_var' )
            // ->with($posts_query)
            ->once()
            ->andReturn( 15 );

        $post_type_object = (object) [ 'labels' => [ 'name' => 'Posts' ] ];

        \WP_Mock::userFunction(
            'get_post_type_object',
            [
                'times' => 1,
                'args' => 'post',
                'return' => $post_type_object,
            ]
        );

        $expected = [
            '/blog/page/1/',
            '/blog/page/2/',
            '/blog/page/3/',
            '/blog/page/4/',
            '/blog/page/5/',
        ];
        $actual = DetectPostsPaginationURLs::detect();
        $this->assertEquals( $expected, $actual );
    }
}
