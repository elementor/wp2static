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
        // set table name
        $wpdb->posts = 'wp_posts';

        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wp_rewrite = (object) [ 'pagination_base' => '/page' ];

        // Create 3 post objects
        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wpdb = Mockery::mock( '\WPDB' );
        $query_string = "
            SELECT ID,post_type
            FROM $wpdb->posts
            WHERE post_status = 'publish'
            AND post_type NOT IN ('revision','nav_menu_item')";

        
        $posts = [
            (object) [ 'ID' => '1', 'post_type' => 'post' ],
            (object) [ 'ID' => '2', 'post_type' => 'page' ],
            (object) [ 'ID' => '3', 'post_type' => 'attachment' ],
            (object) [ 'ID' => '4', 'post_type' => 'mycustomtype' ],
        ];

        $wpdb->shouldReceive( 'get_results' )
            ->with($query_string)
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
            AND post_type = 'post'";

        $wpdb->shouldReceive( 'get_var' )
            ->with($posts_query)
            ->once()
            ->andReturn( 15 );

        // Mock the methods and functions used by DetectPostURLs
        \WP_Mock::userFunction(
            'get_permalink',
            [
                'times' => 1,
                'args' => [ 1 ],
                'return' => 'https://foo.com/2020/08/1',
            ]
        );
        \WP_Mock::userFunction(
            'get_permalink',
            [
                'times' => 1,
                'args' => [ 2 ],
                'return' => 'https://foo.com/2020/08/2',
            ]
        );
        \WP_Mock::userFunction(
            'get_permalink',
            [
                'times' => 1,
                'args' => [ 3 ],
                'return' => 'https://foo.com/2020/08/3',
            ]
        );
        \WP_Mock::userFunction(
            'get_permalink',
            [
                'times' => 1,
                'args' => [ 4 ],
                'return' => false,
            ]
        );
        \WP_Mock::userFunction(
            'get_permalink',
            [
                'times' => 1,
                'args' => [ 5 ],
                'return' => '?post_type',
            ]
        );

        $expected = [
            'https://foo.com/2020/08/1',
            'https://foo.com/2020/08/2',
            'https://foo.com/2020/08/3',
        ];
        $actual = DetectPostURLs::detect( '%year%/%month%/%day%' );
        $this->assertEquals( $expected, $actual );
    }

    public function get_permalink( int $post_id, string $permalink ) : string {
        return "https://foo.com/2020/08/{$post_id}";
    }
}
