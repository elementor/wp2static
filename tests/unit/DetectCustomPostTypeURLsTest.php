<?php

namespace WP2Static;

use Mockery;
use PHPUnit\Framework\TestCase;
use WP_Mock;

final class DetectCustomPostTypeURLsTest extends TestCase {


    public function testDetect() {
        global $wpdb;
        $site_url = 'https://foo.com/';

        // Create 3 attachments
        // @phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $wpdb = Mockery::mock( '\WPDB' );
        $wpdb->shouldReceive( 'get_col' )
            ->once()
            ->andReturn( [ 1, 2, 3 ] );
        $wpdb->posts = 'wp_posts';

        // And URLs for them
        \WP_Mock::userFunction(
            'get_post_permalink',
            [
                'times' => 1,
                'args' => [ 1 ],
                'return' => "{$site_url}?post_type=attachment&p=1/",
            ]
        );
        \WP_Mock::userFunction(
            'get_post_permalink',
            [
                'times' => 1,
                'args' => [ 2 ],
                'return' => "{$site_url}custom-post-type/foo/",
            ]
        );
        \WP_Mock::userFunction(
            'get_post_permalink',
            [
                'times' => 1,
                'args' => [ 3 ],
                'return' => "{$site_url}2020/10/08/bar/",
            ]
        );

        // the attachment should not skipped
        $expected = [
            "{$site_url}custom-post-type/foo/",
            "{$site_url}2020/10/08/bar/",
        ];
        $actual = DetectCustomPostTypeURLs::detect();
        $this->assertEquals( $expected, $actual );
    }
}
