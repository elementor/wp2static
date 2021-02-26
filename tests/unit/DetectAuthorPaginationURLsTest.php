<?php

namespace WP2Static;

use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Mock;

final class DetectAuthorPaginationURLsTest extends TestCase {


    public function testDetect() {
        $site_url = 'https://foo.com/';

        // Set the WordPress pagination base
        global $wp_rewrite;
        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wp_rewrite = (object) [ 'pagination_base' => '/page' ];

        // Set pagination to 3 posts per page
        \WP_Mock::userFunction(
            'get_option',
            [
                'times' => 1,
                'args' => [ 'posts_per_page' ],
                'return' => 3,
            ]
        );
        $users = [];

        // Create some virtual users
        for ( $i = 1; $i <= 3; $i++ ) {
            // Add the user
            $users[] = (object) [ 'ID' => $i ];

            // Create an author URL for this user
            \WP_Mock::userFunction(
                'get_author_posts_url',
                [
                    'times' => 1,
                    'args' => [ $i ],
                    'return' => "{$site_url}users/{$i}",
                ]
            );

            \WP_Mock::userFunction(
                'count_user_posts',
                [
                    'times' => 1,
                    'args' => [ $i, 'post', true ],
                    'return' => '10',
                ]
            );
        }

        // create user missing author URL
        $users[] = (object) [ 'ID' => 4 ];
        \WP_Mock::userFunction(
            'get_author_posts_url',
            [
                'times' => 1,
                'args' => [ 4 ],
                'return' => null,
            ]
        );
        \WP_Mock::userFunction(
            'count_user_posts',
            [
                'times' => 1,
                'args' => [ 4, 'post', true ],
                'return' => '10',
            ]
        );

        \WP_Mock::userFunction(
            'get_users',
            [
                'times' => 1,
                'return' => $users,
            ]
        );

        $expected = [
            '/users/1/page/1/',
            '/users/1/page/2/',
            '/users/1/page/3/',
            '/users/1/page/4/',
            '/users/2/page/1/',
            '/users/2/page/2/',
            '/users/2/page/3/',
            '/users/2/page/4/',
            '/users/3/page/1/',
            '/users/3/page/2/',
            '/users/3/page/3/',
            '/users/3/page/4/',
        ];

        $actual = DetectAuthorPaginationURLs::detect( $site_url );
        $this->assertEquals( $expected, $actual );
    }
}
