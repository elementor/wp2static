<?php

namespace WP2Static;

use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Mock;

final class DetectPostURLsTest extends TestCase {


    public function testDetect() {
        global $wpdb;
        $site_url = 'https://foo.com/';

        // Create 3 attachments
        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wpdb = Mockery::mock( '\WPDB' );
        $wpdb->shouldReceive( 'get_col' )
            ->once()
            ->andReturn( [ 1, 2, 3, 4, 5 ] );
        $wpdb->posts = 'wp_posts';

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
