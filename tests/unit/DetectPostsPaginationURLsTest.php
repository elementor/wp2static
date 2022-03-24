<?php

namespace WP2Static;

use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Mock;

final class DetectPostsPaginationURLsTest extends TestCase {


    public function testDetectWithoutPostsPage() {
        global $wpdb;
        // Set the WordPress pagination base
        global $wp_rewrite;
        $site_url = 'https://foo.com/';

        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wp_rewrite = (object) [ 'pagination_base' => 'page' ];

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
            (object) [
                'ID' => '2',
                'post_type' => 'page',
            ],
            (object) [
                'ID' => '3',
                'post_type' => 'attachment',
            ],
            (object) [
                'ID' => '4',
                'post_type' => 'mycustomtype',
            ],
            (object) [
                'ID' => '5',
                'post_type' => 'nonexistent',
            ],
            (object) [
                'ID' => '6',
                'post_type' => 'noobjecttype',
            ],
            (object) [
                'ID' => '7',
                'post_type' => 'spacednametype',
            ],
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

        $posts_query = "SELECT COUNT(*) FROM $wpdb->posts WHERE" .
            " post_status = 'publish' AND post_type = 'post'";

        $wpdb->shouldReceive( 'get_var' )
            ->with( $posts_query )
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

        \WP_Mock::userFunction(
            'get_option',
            [
                'times' => 1,
                'args' => 'page_for_posts',
                'return' => '0',
            ]
        );

        $pages_query = "SELECT COUNT(*) FROM $wpdb->posts WHERE" .
            " post_status = 'publish' AND post_type = 'page'";

        $wpdb->shouldReceive( 'get_var' )
            ->with( $pages_query )
            ->once()
            ->andReturn( 9 );

        $page_type_object = (object) [ 'labels' => [ 'name' => 'Pages' ] ];

        \WP_Mock::userFunction(
            'get_post_type_object',
            [
                'times' => 1,
                'args' => 'page',
                'return' => $page_type_object,
            ]
        );

        $attachments_query = "SELECT COUNT(*) FROM $wpdb->posts WHERE" .
            " post_status = 'publish' AND post_type = 'attachment'";

        $wpdb->shouldReceive( 'get_var' )
            ->with( $attachments_query )
            ->once()
            ->andReturn( 13 );

        $attachment_type_object = (object) [ 'labels' => [ 'name' => 'Attachments' ] ];

        \WP_Mock::userFunction(
            'get_post_type_object',
            [
                'times' => 1,
                'args' => 'attachment',
                'return' => $attachment_type_object,
            ]
        );

        $custom_type_query = "SELECT COUNT(*) FROM $wpdb->posts WHERE" .
            " post_status = 'publish' AND post_type = 'mycustomtype'";

        $wpdb->shouldReceive( 'get_var' )
            ->with( $custom_type_query )
            ->once()
            ->andReturn( 21 );

        $custom_type_object = (object) [ 'labels' => [ 'name' => 'MyCustomType' ] ];

        \WP_Mock::userFunction(
            'get_post_type_object',
            [
                'times' => 1,
                'args' => 'mycustomtype',
                'return' => $custom_type_object,
            ]
        );

        $type_without_posts_query = "SELECT COUNT(*) FROM $wpdb->posts WHERE" .
            " post_status = 'publish' AND post_type = 'nonexistent'";

        $wpdb->shouldReceive( 'get_var' )
            ->with( $type_without_posts_query )
            ->once()
            ->andReturn( null );

        $type_not_returning_object_query = "SELECT COUNT(*) FROM $wpdb->posts WHERE" .
            " post_status = 'publish' AND post_type = 'noobjecttype'";

        $wpdb->shouldReceive( 'get_var' )
            ->with( $type_not_returning_object_query )
            ->once()
            ->andReturn( 1 );

        \WP_Mock::userFunction(
            'get_post_type_object',
            [
                'times' => 1,
                'args' => 'noobjecttype',
                'return' => null,
            ]
        );

        $type_with_spaced_name = "SELECT COUNT(*) FROM $wpdb->posts WHERE" .
            " post_status = 'publish' AND post_type = 'spacednametype'";

        $wpdb->shouldReceive( 'get_var' )
            ->with( $type_with_spaced_name )
            ->once()
            ->andReturn( 1 );

        $spaced_name_type_object =
            (object) [ 'labels' => [ 'name' => 'Type With Spaces In Name' ] ];

        \WP_Mock::userFunction(
            'get_post_type_object',
            [
                'times' => 1,
                'args' => 'spacednametype',
                'return' => $spaced_name_type_object,
            ]
        );

        $expected = [
            '/page/1/',
            '/page/2/',
            '/page/3/',
            '/page/4/',
            '/page/5/',
            '/attachments/page/1/',
            '/attachments/page/2/',
            '/attachments/page/3/',
            '/attachments/page/4/',
            '/attachments/page/5/',
            '/mycustomtype/page/1/',
            '/mycustomtype/page/2/',
            '/mycustomtype/page/3/',
            '/mycustomtype/page/4/',
            '/mycustomtype/page/5/',
            '/mycustomtype/page/6/',
            '/mycustomtype/page/7/',
        ];
        $actual = DetectPostsPaginationURLs::detect( $site_url );
        $this->assertEquals( $expected, $actual );
    }

    public function testDetectWithPostsPage() {
        global $wpdb;
        // Set the WordPress pagination base
        global $wp_rewrite;
        $site_url = 'https://foo.com/';

        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wp_rewrite = (object) [ 'pagination_base' => 'page' ];

        // Create 1 post object
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

        $posts_query = "SELECT COUNT(*) FROM $wpdb->posts WHERE" .
            " post_status = 'publish' AND post_type = 'post'";

        $wpdb->shouldReceive( 'get_var' )
            ->with( $posts_query )
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

        \WP_Mock::userFunction(
            'get_option',
            [
                'times' => 5,
                'args' => 'page_for_posts',
                'return' => '10',
            ]
        );

        \WP_Mock::userFunction(
            'get_post_type_archive_link',
            [
                'times' => 5,
                'args' => 'post',
                'return' => $site_url . 'blog',
            ]
        );

        $expected = [
            '/blog/page/1/',
            '/blog/page/2/',
            '/blog/page/3/',
            '/blog/page/4/',
            '/blog/page/5/',
        ];
        // getting '/blog//page/1/'...

        $actual = DetectPostsPaginationURLs::detect( $site_url );
        $this->assertEquals( $expected, $actual );
    }
}
